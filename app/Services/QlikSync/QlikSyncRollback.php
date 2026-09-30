<?php

namespace App\Services\QlikSync;

use App\Menu;
use App\QlikSyncRun;
use App\QlikSyncRunRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Annulla gli effetti di una sincronizzazione (docs/piano-qlik-sync-app-items.md):
 *
 * - record creati dal run: eliminati, ma solo se non sono stati modificati,
 *   assegnati ad altri o usati (widget, voce di menu, item collegati);
 *   altrimenti si saltano e si segnalano nel dettaglio del run;
 * - record riusati (linked): si toglie solo il tenant/gruppo aggiunto dal
 *   run, mai il record;
 * - gli aggiornamenti (updated) non vengono ripristinati.
 */
class QlikSyncRollback
{
    /**
     * @return array{deleted:int, unlinked:int, kept:int}
     */
    public static function rollback(QlikSyncRun $run): array
    {
        $result = ['deleted' => 0, 'unlinked' => 0, 'kept' => 0];

        if ($run->rolled_back_at !== null || $run->isActive()) {
            return $result;
        }

        // Ordine inverso: gli item (creati dopo le app) vanno via prima delle app.
        $records = QlikSyncRunRecord::where('run_id', $run->id)
            ->whereIn('action', [QlikSyncRunRecord::ACTION_CREATED, QlikSyncRunRecord::ACTION_LINKED])
            ->orderByDesc('id')
            ->get();

        foreach ($records as $record) {
            if (! $record->record_id) {
                continue;
            }

            if ($record->action === QlikSyncRunRecord::ACTION_LINKED) {
                self::unlink($record);
                $result['unlinked']++;

                continue;
            }

            $reason = $record->record_type === 'item'
                ? self::deleteItemIfSafe($run, (int) $record->record_id)
                : self::deleteAppIfSafe($run, (int) $record->record_id);

            if ($reason === null) {
                $result['deleted']++;
            } else {
                $result['kept']++;
                $record->update(['message' => mb_substr($reason, 0, 500)]);
            }
        }

        $run->update(['rolled_back_at' => now()]);
        Log::info('Qlik sync run '.$run->id.' annullato: '.json_encode($result));

        return $result;
    }

    private static function unlink(QlikSyncRunRecord $record): void
    {
        if ($record->record_type === 'item') {
            if ($record->added_tenant_id) {
                DB::table('tenants_allowed')->where('item_id', $record->record_id)->where('tenant_id', $record->added_tenant_id)->delete();
            }
            if ($record->added_group_id) {
                DB::table('items_allowed')->where('item_id', $record->record_id)->where('group_id', $record->added_group_id)->delete();
            }

            return;
        }

        if ($record->added_tenant_id) {
            DB::table('qlikapps_tenants')->where('qlik_apps_id', $record->record_id)->where('tenant_id', $record->added_tenant_id)->delete();
        }
        if ($record->added_group_id) {
            DB::table('qlikapps_groups')->where('qlik_apps_id', $record->record_id)->where('group_id', $record->added_group_id)->delete();
        }
    }

    /**
     * @return string|null null se eliminato, altrimenti il motivo per cui e' stato mantenuto
     */
    private static function deleteItemIfSafe(QlikSyncRun $run, int $itemId): ?string
    {
        $item = DB::table('qlik_items')->where('id', $itemId)->first();
        if (! $item || $item->deleted_at !== null) {
            return null; // gia' eliminato: niente da fare
        }

        if ($item->modified_at !== null) {
            return trans('crudbooster.qlik_sync_rb_modified');
        }

        $otherTenants = DB::table('tenants_allowed')->where('item_id', $itemId)->where('tenant_id', '!=', $run->tenant_id)->exists();
        $otherGroups = DB::table('items_allowed')->where('item_id', $itemId)->where('group_id', '!=', $run->group_id)->exists();
        if ($otherTenants || $otherGroups) {
            return trans('crudbooster.qlik_sync_rb_assigned');
        }

        if (Menu::where('path', 'qlik_items/content/'.$itemId)->exists()) {
            return trans('crudbooster.qlik_sync_rb_in_menu');
        }

        DB::table('tenants_allowed')->where('item_id', $itemId)->delete();
        DB::table('items_allowed')->where('item_id', $itemId)->delete();
        DB::table('qlik_items')->where('id', $itemId)->delete();

        return null;
    }

    /**
     * @return string|null null se eliminata, altrimenti il motivo per cui e' stata mantenuta
     */
    private static function deleteAppIfSafe(QlikSyncRun $run, int $appId): ?string
    {
        $app = DB::table('qlik_apps')->where('id', $appId)->first();
        if (! $app) {
            return null;
        }

        if ($app->created_at !== null && $app->updated_at !== null
            && abs(strtotime((string) $app->updated_at) - strtotime((string) $app->created_at)) > 2) {
            return trans('crudbooster.qlik_sync_rb_modified');
        }

        $otherTenants = DB::table('qlikapps_tenants')->where('qlik_apps_id', $appId)->where('tenant_id', '!=', $run->tenant_id)->exists();
        $otherGroups = DB::table('qlikapps_groups')->where('qlik_apps_id', $appId)->where('group_id', '!=', $run->group_id)->exists();
        if ($otherTenants || $otherGroups) {
            return trans('crudbooster.qlik_sync_rb_assigned');
        }

        if (DB::table('qlik_items')->whereNull('deleted_at')->where('qlik_app_id', $appId)->exists()) {
            return trans('crudbooster.qlik_sync_rb_has_items');
        }

        $usedByWidget = DB::table('cms_statistic_components')
            ->whereRaw('config REGEXP ?', ['"mashups"[[:space:]]*:[[:space:]]*"?'.$appId.'"?[,}[:space:]]'])
            ->exists();
        if ($usedByWidget) {
            return trans('crudbooster.qlik_sync_rb_in_widget');
        }

        DB::table('qlikapps_tenants')->where('qlik_apps_id', $appId)->delete();
        DB::table('qlikapps_groups')->where('qlik_apps_id', $appId)->delete();
        DB::table('qlik_apps')->where('id', $appId)->delete();

        return null;
    }
}
