<?php

namespace App\Services\QlikSync;

use App\Jobs\SyncQlikRunJob;
use App\QlikSyncRun;
use App\QlikSyncRunRecord;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sincronizzazione di app e item Qlik da una configurazione
 * (docs/piano-qlik-sync-app-items.md).
 *
 * Regole:
 * - i record creati ereditano tenant e gruppo primario di chi importa;
 * - se il record esiste gia' (chiave conf + appid, o conf + app + id foglio)
 *   si riusa e si aggiungono tenant/gruppo dell'importatore;
 * - mai sovrascrivere campi modificati a mano (titoli, nomi);
 * - i record spariti da Qlik si segnano is_missing, solo se il run arriva a
 *   fine, e non si cancellano mai in automatico;
 * - ogni azione e' registrata in qlik_sync_run_records (report e rollback).
 */
class QlikSyncService
{
    const STAGE_APPS = 'apps';

    const STAGE_ITEMS_INIT = 'items_init';

    const STAGE_ITEMS_APP = 'items_app';

    /** Ogni quanti record si controlla la richiesta di annullamento. */
    const CANCEL_CHECK_EVERY = 25;

    // ------------------------------------------------------------------
    // Avvio
    // ------------------------------------------------------------------

    /**
     * Valida e mette in coda una sincronizzazione.
     *
     * @throws QlikSyncException con messaggio gia' tradotto
     */
    public static function start(string $type, int $confId, ?int $appId, int $userId): QlikSyncRun
    {
        if (! in_array($type, [QlikSyncRun::TYPE_APPS, QlikSyncRun::TYPE_ITEMS], true)) {
            throw new QlikSyncException(trans('crudbooster.qlik_sync_err_bad_type'));
        }

        $user = User::find($userId);
        if (! $user || ! $user->isSuperAdmin()) {
            throw new QlikSyncException(trans('crudbooster.denied_access'));
        }

        if (! DB::table('qlik_confs')->where('id', $confId)->exists()) {
            throw new QlikSyncException(trans('crudbooster.qlik_sync_err_conf_not_found'));
        }

        if (! QlikDriverFactory::userHasQlikUser($userId, $confId)) {
            throw new QlikSyncException(trans('crudbooster.qlik_sync_err_no_qlik_user'));
        }

        $tenantId = (int) $user->tenant;
        $groupId = (int) $user->primary_group;
        if ($tenantId <= 0 || ! DB::table('tenants')->where('id', $tenantId)->exists()
            || $groupId <= 0 || ! DB::table('groups')->where('id', $groupId)->exists()) {
            throw new QlikSyncException(trans('crudbooster.qlik_sync_err_no_tenant_group'));
        }

        if ($type === QlikSyncRun::TYPE_ITEMS && $appId !== null) {
            $app = DB::table('qlik_apps')->where('id', $appId)->first();
            if (! $app || (string) $app->conf !== (string) $confId) {
                throw new QlikSyncException(trans('crudbooster.qlik_sync_err_app_not_found'));
            }
        } else {
            $appId = null;
        }

        $active = QlikSyncRun::where('type', $type)
            ->where('qlik_conf_id', $confId)
            ->whereIn('status', QlikSyncRun::activeStatuses())
            ->exists();
        if ($active) {
            throw new QlikSyncException(trans('crudbooster.qlik_sync_err_already_running'));
        }

        $run = QlikSyncRun::create([
            'type' => $type,
            'qlik_conf_id' => $confId,
            'qlik_app_id' => $appId,
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'group_id' => $groupId,
            'status' => QlikSyncRun::STATUS_QUEUED,
        ]);

        self::dispatchStep($run->id, $type === QlikSyncRun::TYPE_APPS ? self::STAGE_APPS : self::STAGE_ITEMS_INIT, 0);

        return $run;
    }

    public static function dispatchStep(int $runId, string $stage, int $cursor): void
    {
        SyncQlikRunJob::dispatch($runId, $stage, $cursor)
            ->onConnection('qlik_sync')
            ->onQueue('qlik_sync');
    }

    /**
     * Ci sono run in coda da troppo tempo? Segnale che manca un worker.
     */
    public static function hasStuckQueuedRuns(): bool
    {
        return QlikSyncRun::where('status', QlikSyncRun::STATUS_QUEUED)
            ->where('created_at', '<', now()->subMinutes(2))
            ->exists();
    }

    // ------------------------------------------------------------------
    // Annullamento
    // ------------------------------------------------------------------

    /**
     * Chiede l'annullamento di un run attivo. Se ancora in coda si annulla
     * subito; se in corso si ferma alla prossima verifica del job.
     */
    public static function requestCancel(QlikSyncRun $run, bool $deleteCreated): void
    {
        if (! $run->isActive()) {
            return;
        }

        if ($run->status === QlikSyncRun::STATUS_QUEUED) {
            $run->update([
                'cancel_requested' => true,
                'cancel_delete' => $deleteCreated,
            ]);
            self::finishCancelled($run->fresh());

            return;
        }

        $run->update([
            'status' => QlikSyncRun::STATUS_CANCELLING,
            'cancel_requested' => true,
            'cancel_delete' => $deleteCreated,
        ]);
    }

    private static function cancelRequested(int $runId): bool
    {
        return (bool) DB::table('qlik_sync_runs')->where('id', $runId)->value('cancel_requested');
    }

    private static function finishCancelled(QlikSyncRun $run): void
    {
        $run->update([
            'status' => QlikSyncRun::STATUS_CANCELLED,
            'finished_at' => now(),
        ]);

        if ($run->cancel_delete) {
            QlikSyncRollback::rollback($run->fresh());
        }
    }

    // ------------------------------------------------------------------
    // Esecuzione di un passo del run (chiamato dal job)
    // ------------------------------------------------------------------

    /**
     * @return array{0:string,1:int}|null prossimo passo [stage, cursor], null se il run e' finito
     */
    public static function step(int $runId, string $stage, int $cursor): ?array
    {
        $run = QlikSyncRun::find($runId);
        if (! $run || ! $run->isActive()) {
            return null;
        }

        if ($run->status === QlikSyncRun::STATUS_QUEUED) {
            $run->update(['status' => QlikSyncRun::STATUS_RUNNING, 'started_at' => now()]);
        }

        if (self::cancelRequested($runId)) {
            self::finishCancelled($run->fresh());

            return null;
        }

        try {
            $driver = QlikDriverFactory::make((int) $run->qlik_conf_id, (int) $run->user_id);

            switch ($stage) {
                case self::STAGE_APPS:
                    return self::stageApps($run, $driver);
                case self::STAGE_ITEMS_INIT:
                    return self::stageItemsInit($run, $driver);
                case self::STAGE_ITEMS_APP:
                    return self::stageItemsApp($run, $driver, $cursor);
            }

            throw new QlikSyncException(trans('crudbooster.qlik_sync_err_bad_type'));
        } catch (QlikSyncException $e) {
            self::fail($run, $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Qlik sync run '.$runId.' interrotto: '.get_class($e).': '.$e->getMessage());
            self::fail($run, trans('crudbooster.qlik_sync_err_internal'));
        }

        return null;
    }

    /**
     * Chiamato dal job quando fallisce fuori da step() (timeout, worker ucciso).
     */
    public static function markFailed(int $runId, string $message): void
    {
        $run = QlikSyncRun::find($runId);
        if ($run && $run->isActive()) {
            self::fail($run, $message);
        }
    }

    private static function fail(QlikSyncRun $run, string $message): void
    {
        $run->update([
            'status' => QlikSyncRun::STATUS_FAILED,
            'error' => mb_substr($message, 0, 1000),
            'finished_at' => now(),
        ]);
    }

    private static function complete(QlikSyncRun $run): void
    {
        $run->update([
            'status' => QlikSyncRun::STATUS_COMPLETED,
            'finished_at' => now(),
        ]);
    }

    // ------------------------------------------------------------------
    // Sincronizzazione app
    // ------------------------------------------------------------------

    private static function stageApps(QlikSyncRun $run, QlikDriver $driver): ?array
    {
        $apps = $driver->listApps();
        DB::table('qlik_sync_runs')->where('id', $run->id)->update(['total' => count($apps)]);

        foreach ($apps as $i => $app) {
            if ($i > 0 && $i % self::CANCEL_CHECK_EVERY === 0 && self::cancelRequested($run->id)) {
                self::finishCancelled($run->fresh());

                return null;
            }
            self::upsertApp($run, $app);
            DB::table('qlik_sync_runs')->where('id', $run->id)->increment('processed');
        }

        self::markMissingApps($run);
        self::complete($run->fresh());

        return null;
    }

    /**
     * @param  array{id:string,name:string}  $app
     * @param  bool  $count  se false il record non incrementa i contatori del run
     */
    private static function upsertApp(QlikSyncRun $run, array $app, bool $count = true): void
    {
        $now = now();
        $confKey = (string) $run->qlik_conf_id;

        $existing = DB::table('qlik_apps')
            ->where('conf', $confKey)
            ->where('appid', $app['id'])
            ->orderBy('id')
            ->first();

        if (! $existing) {
            $id = DB::table('qlik_apps')->insertGetId([
                'appname' => mb_substr($app['name'] !== '' ? $app['name'] : $app['id'], 0, 255),
                'conf' => $confKey,
                'appid' => $app['id'],
                'is_missing' => 0,
                'last_synced_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('qlikapps_tenants')->insert(['qlik_apps_id' => $id, 'tenant_id' => $run->tenant_id]);
            DB::table('qlikapps_groups')->insert(['qlik_apps_id' => $id, 'group_id' => $run->group_id]);

            self::addRecord($run, 'app', $id, $app['id'], QlikSyncRunRecord::ACTION_CREATED, $run->tenant_id, $run->group_id, null, $count);

            return;
        }

        $addedTenant = DB::table('qlikapps_tenants')->insertOrIgnore([
            'qlik_apps_id' => $existing->id,
            'tenant_id' => $run->tenant_id,
        ]) > 0;
        $addedGroup = DB::table('qlikapps_groups')->insertOrIgnore([
            'qlik_apps_id' => $existing->id,
            'group_id' => $run->group_id,
        ]) > 0;

        $wasMissing = (bool) $existing->is_missing;
        // updated_at non si tocca: serve al rollback per riconoscere le modifiche manuali.
        DB::table('qlik_apps')->where('id', $existing->id)->update(['is_missing' => 0, 'last_synced_at' => $now]);

        if ($addedTenant || $addedGroup) {
            $action = QlikSyncRunRecord::ACTION_LINKED;
        } elseif ($wasMissing) {
            $action = QlikSyncRunRecord::ACTION_UPDATED;
        } else {
            $action = QlikSyncRunRecord::ACTION_SKIPPED;
        }

        self::addRecord(
            $run, 'app', $existing->id, $app['id'], $action,
            $addedTenant ? $run->tenant_id : null,
            $addedGroup ? $run->group_id : null,
            null, $count
        );
    }

    /**
     * App presenti in DB per questa conf, gia' sincronizzate in passato ma
     * non piu' elencate da Qlik. Solo a fine run completo.
     */
    private static function markMissingApps(QlikSyncRun $run): void
    {
        $seen = QlikSyncRunRecord::where('run_id', $run->id)->where('record_type', 'app')->pluck('record_id')->filter()->all();

        $query = DB::table('qlik_apps')
            ->where('conf', (string) $run->qlik_conf_id)
            ->whereNotNull('last_synced_at')
            ->where('is_missing', 0);
        if ($seen) {
            $query->whereNotIn('id', $seen);
        }

        $count = $query->update(['is_missing' => 1]);
        if ($count > 0) {
            DB::table('qlik_sync_runs')->where('id', $run->id)->update(['missing' => $count]);
        }
    }

    // ------------------------------------------------------------------
    // Sincronizzazione item
    // ------------------------------------------------------------------

    private static function stageItemsInit(QlikSyncRun $run, QlikDriver $driver): ?array
    {
        if ($run->qlik_app_id) {
            $app = DB::table('qlik_apps')->where('id', $run->qlik_app_id)->first();
            if (! $app) {
                throw new QlikSyncException(trans('crudbooster.qlik_sync_err_app_not_found'));
            }
            // Puntatore all'app da elaborare: non conta nei contatori.
            self::addRecord($run, 'app', $app->id, $app->appid, QlikSyncRunRecord::ACTION_SKIPPED, null, null, null, false);
            $total = 1;
        } else {
            // "Tutte le app": prima si allineano le app (create al volo se mancano).
            $apps = $driver->listApps();
            foreach ($apps as $i => $app) {
                if ($i > 0 && $i % self::CANCEL_CHECK_EVERY === 0 && self::cancelRequested($run->id)) {
                    self::finishCancelled($run->fresh());

                    return null;
                }
                self::upsertApp($run, $app);
            }
            $total = count($apps);
        }

        DB::table('qlik_sync_runs')->where('id', $run->id)->update(['total' => $total]);

        return [self::STAGE_ITEMS_APP, 0];
    }

    private static function stageItemsApp(QlikSyncRun $run, QlikDriver $driver, int $cursor): ?array
    {
        $pointer = QlikSyncRunRecord::where('run_id', $run->id)
            ->where('record_type', 'app')
            ->where('action', '!=', QlikSyncRunRecord::ACTION_FAILED)
            ->where('id', '>', $cursor)
            ->orderBy('id')
            ->first();

        if (! $pointer) {
            self::markMissingItems($run);
            self::complete($run->fresh());

            return null;
        }

        $app = DB::table('qlik_apps')->where('id', $pointer->record_id)->first();

        if ($app) {
            try {
                $sheets = $driver->listSheets($app->appid);
                foreach ($sheets as $i => $sheet) {
                    if ($i > 0 && $i % self::CANCEL_CHECK_EVERY === 0 && self::cancelRequested($run->id)) {
                        self::finishCancelled($run->fresh());

                        return null;
                    }
                    self::upsertItem($run, $driver, $app, $sheet);
                }
            } catch (QlikSyncException $e) {
                // L'errore su una app non ferma il run: si registra e si prosegue.
                self::addRecord($run, 'app', $app->id, $app->appid, QlikSyncRunRecord::ACTION_FAILED, null, null, $e->getMessage());
            }
        }

        DB::table('qlik_sync_runs')->where('id', $run->id)->increment('processed');

        return [self::STAGE_ITEMS_APP, $pointer->id];
    }

    /**
     * @param  object  $app  riga di qlik_apps
     * @param  array{id:string,title:string,description:string}  $sheet
     */
    private static function upsertItem(QlikSyncRun $run, QlikDriver $driver, $app, array $sheet): void
    {
        $now = now();
        $confKey = (string) $run->qlik_conf_id;

        $existing = DB::table('qlik_items')
            ->whereNull('deleted_at')
            ->where('qlik_conf', $confKey)
            ->where('qlik_app_id', $app->id)
            ->where('external_id', $sheet['id'])
            ->orderBy('id')
            ->first();

        $legacy = false;
        if (! $existing) {
            // Item creato a mano prima della sincronizzazione: si aggancia (per URL) invece di duplicarlo.
            $needle = $driver->sheetUrlNeedle($app->appid, $sheet['id']);
            $existing = DB::table('qlik_items')
                ->whereNull('deleted_at')
                ->where('qlik_conf', $confKey)
                ->whereNull('external_id')
                ->where('url', 'like', '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $needle).'%')
                ->orderBy('id')
                ->first();
            $legacy = (bool) $existing;
        }

        if (! $existing) {
            $id = DB::table('qlik_items')->insertGetId([
                'title' => mb_substr($sheet['title'] !== '' ? $sheet['title'] : $sheet['id'], 0, 255),
                'subtitle' => $sheet['description'] !== '' ? mb_substr($sheet['description'], 0, 255) : null,
                'url' => $driver->sheetUrl($app->appid, $sheet['id']),
                'qlik_conf' => $confKey,
                'qlik_app_id' => $app->id,
                'item_type' => 'sheet',
                'external_id' => $sheet['id'],
                'is_missing' => 0,
                'last_synced_at' => $now,
                'created_by' => $run->user_id,
                'created_at' => $now,
            ]);
            DB::table('tenants_allowed')->insert(['item_id' => $id, 'tenant_id' => $run->tenant_id, 'created_by' => $run->user_id, 'created_at' => $now]);
            DB::table('items_allowed')->insert(['item_id' => $id, 'group_id' => $run->group_id, 'created_by' => $run->user_id, 'created_at' => $now]);

            self::addRecord($run, 'item', $id, $sheet['id'], QlikSyncRunRecord::ACTION_CREATED, $run->tenant_id, $run->group_id);

            return;
        }

        $addedTenant = false;
        if (! DB::table('tenants_allowed')->where('item_id', $existing->id)->where('tenant_id', $run->tenant_id)->exists()) {
            DB::table('tenants_allowed')->insert(['item_id' => $existing->id, 'tenant_id' => $run->tenant_id, 'created_by' => $run->user_id, 'created_at' => $now]);
            $addedTenant = true;
        }
        $addedGroup = false;
        if (! DB::table('items_allowed')->where('item_id', $existing->id)->where('group_id', $run->group_id)->exists()) {
            DB::table('items_allowed')->insert(['item_id' => $existing->id, 'group_id' => $run->group_id, 'created_by' => $run->user_id, 'created_at' => $now]);
            $addedGroup = true;
        }

        $wasMissing = (bool) $existing->is_missing;
        $update = ['is_missing' => 0, 'last_synced_at' => $now];
        if ($legacy) {
            $update += ['qlik_app_id' => $app->id, 'item_type' => 'sheet', 'external_id' => $sheet['id']];
        }
        // modified_at non si tocca: serve al rollback per riconoscere le modifiche manuali.
        DB::table('qlik_items')->where('id', $existing->id)->update($update);

        if ($legacy || $addedTenant || $addedGroup) {
            $action = QlikSyncRunRecord::ACTION_LINKED;
        } elseif ($wasMissing) {
            $action = QlikSyncRunRecord::ACTION_UPDATED;
        } else {
            $action = QlikSyncRunRecord::ACTION_SKIPPED;
        }

        self::addRecord(
            $run, 'item', $existing->id, $sheet['id'], $action,
            $addedTenant ? $run->tenant_id : null,
            $addedGroup ? $run->group_id : null
        );
    }

    /**
     * Item gia' sincronizzati di un'app elaborata con successo, non piu'
     * elencati da Qlik. Solo a fine run completo.
     */
    private static function markMissingItems(QlikSyncRun $run): void
    {
        $failedApps = QlikSyncRunRecord::where('run_id', $run->id)
            ->where('record_type', 'app')
            ->where('action', QlikSyncRunRecord::ACTION_FAILED)
            ->pluck('record_id')->all();

        $appIds = QlikSyncRunRecord::where('run_id', $run->id)
            ->where('record_type', 'app')
            ->where('action', '!=', QlikSyncRunRecord::ACTION_FAILED)
            ->pluck('record_id')->filter()->unique()->values()->all();
        $appIds = array_values(array_diff($appIds, $failedApps));
        if (! $appIds) {
            return;
        }

        $seen = QlikSyncRunRecord::where('run_id', $run->id)->where('record_type', 'item')->pluck('record_id')->filter()->all();

        $query = DB::table('qlik_items')
            ->whereNull('deleted_at')
            ->whereIn('qlik_app_id', $appIds)
            ->whereNotNull('external_id')
            ->where('is_missing', 0);
        if ($seen) {
            $query->whereNotIn('id', $seen);
        }

        $count = $query->update(['is_missing' => 1]);
        if ($count > 0) {
            DB::table('qlik_sync_runs')->where('id', $run->id)->update(['missing' => $count]);
        }
    }

    // ------------------------------------------------------------------
    // Registro delle azioni
    // ------------------------------------------------------------------

    private static function addRecord(
        QlikSyncRun $run,
        string $type,
        ?int $recordId,
        ?string $externalId,
        string $action,
        ?int $addedTenantId = null,
        ?int $addedGroupId = null,
        ?string $message = null,
        bool $count = true
    ): void {
        QlikSyncRunRecord::create([
            'run_id' => $run->id,
            'record_type' => $type,
            'record_id' => $recordId,
            'external_id' => $externalId,
            'action' => $action,
            'added_tenant_id' => $addedTenantId,
            'added_group_id' => $addedGroupId,
            'message' => $message !== null ? mb_substr($message, 0, 500) : null,
        ]);

        if ($count) {
            DB::table('qlik_sync_runs')->where('id', $run->id)->increment($action);
        }
    }
}
