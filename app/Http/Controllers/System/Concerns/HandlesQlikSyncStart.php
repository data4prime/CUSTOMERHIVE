<?php

namespace App\Http\Controllers\System\Concerns;

use App\Helpers\CRUDBooster;
use App\QlikSyncRun;
use App\Services\QlikSync\QlikDriverFactory;
use App\Services\QlikSync\QlikSyncException;
use App\Services\QlikSync\QlikSyncService;
use App\Services\QlikSync\QlikSyncUi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Endpoint condivisi dalle liste app e item per avviare una sincronizzazione
 * Qlik (docs/piano-qlik-sync-app-items.md). Solo superadmin.
 */
trait HandlesQlikSyncStart
{
    /**
     * Avvia un run e risponde in JSON: {ok, run_id, run_url} oppure {ok:false, message}.
     */
    protected function qlikSyncStart(string $type)
    {
        if (! CRUDBooster::isSuperadmin()) {
            return response()->json(['ok' => false, 'message' => trans('crudbooster.denied_access')], 403);
        }

        $confId = (int) Request::input('conf_id');
        $appInput = Request::input('app_id');
        $appId = ($type === QlikSyncRun::TYPE_ITEMS && $appInput !== null && $appInput !== '') ? (int) $appInput : null;

        // Import selettivo (solo app): assente = tutte le app della conf.
        $selected = null;
        if ($type === QlikSyncRun::TYPE_APPS && Request::has('app_ids')) {
            $raw = Request::input('app_ids');
            $selected = is_array($raw) ? array_filter($raw, 'is_scalar') : [];
        }

        // Import selettivo dei fogli (solo item di una singola app).
        $selectedSheets = null;
        if ($type === QlikSyncRun::TYPE_ITEMS && $appId !== null && Request::has('sheet_ids')) {
            $raw = Request::input('sheet_ids');
            $selectedSheets = is_array($raw) ? array_filter($raw, 'is_scalar') : [];
        }

        try {
            $run = QlikSyncService::start($type, $confId, $appId, (int) CRUDBooster::myId(), $selected, $selectedSheets);
        } catch (QlikSyncException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'run_id' => $run->id,
            'run_url' => QlikSyncUi::runsUrl().'/'.$run->id,
        ]);
    }

    /**
     * Anteprima per l'import selettivo: app lette da Qlik con l'utente Qlik di
     * chi chiede, con il flag "gia' importata". Non scrive nulla.
     * Risponde {ok, apps:[{id,name,imported}]} oppure {ok:false, message}.
     */
    protected function qlikSyncPreviewApps()
    {
        if (! CRUDBooster::isSuperadmin()) {
            return response()->json(['ok' => false, 'message' => trans('crudbooster.denied_access')], 403);
        }

        $confId = (int) Request::input('conf_id');
        $userId = (int) CRUDBooster::myId();

        if (! DB::table('qlik_confs')->where('id', $confId)->exists()) {
            return response()->json(['ok' => false, 'message' => trans('crudbooster.qlik_sync_err_conf_not_found')], 422);
        }
        if (! QlikDriverFactory::userHasQlikUser($userId, $confId)) {
            return response()->json(['ok' => false, 'message' => trans('crudbooster.qlik_sync_err_no_qlik_user')], 422);
        }

        try {
            $listed = QlikDriverFactory::make($confId, $userId)->listApps();
        } catch (QlikSyncException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('Qlik sync anteprima app (conf '.$confId.'): '.get_class($e).': '.$e->getMessage());

            return response()->json(['ok' => false, 'message' => trans('crudbooster.qlik_sync_err_internal')], 500);
        }

        $imported = array_flip(
            DB::table('qlik_apps')->where('conf', (string) $confId)->pluck('appid')->map(function ($id) {
                return (string) $id;
            })->all()
        );

        $apps = collect($listed)->map(function ($a) use ($imported) {
            $id = (string) $a['id'];

            return [
                'id' => $id,
                'name' => ($a['name'] ?? '') !== '' ? $a['name'] : $id,
                'imported' => isset($imported[$id]),
            ];
        })->sortBy(function ($a) {
            return mb_strtolower($a['name']);
        })->values()->all();

        return response()->json(['ok' => true, 'apps' => $apps]);
    }

    /**
     * Anteprima per l'import selettivo degli item: fogli di una app letti da
     * Qlik con il flag "gia' importato". Non scrive nulla.
     * Risponde {ok, apps:[{id,name,imported}]} (stessa forma dell'anteprima app,
     * cosi' la modale usa lo stesso elenco) oppure {ok:false, message}.
     */
    protected function qlikSyncPreviewSheets()
    {
        if (! CRUDBooster::isSuperadmin()) {
            return response()->json(['ok' => false, 'message' => trans('crudbooster.denied_access')], 403);
        }

        $confId = (int) Request::input('conf_id');
        $appLocalId = (int) Request::input('app_id');
        $userId = (int) CRUDBooster::myId();

        $app = DB::table('qlik_apps')->where('id', $appLocalId)->first();
        if (! $app || (string) $app->conf !== (string) $confId) {
            return response()->json(['ok' => false, 'message' => trans('crudbooster.qlik_sync_err_app_not_found')], 422);
        }
        if (! QlikDriverFactory::userHasQlikUser($userId, $confId)) {
            return response()->json(['ok' => false, 'message' => trans('crudbooster.qlik_sync_err_no_qlik_user')], 422);
        }

        try {
            $driver = QlikDriverFactory::make($confId, $userId);
            $sheets = $driver->listSheets($app->appid);
        } catch (QlikSyncException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('Qlik sync anteprima fogli (app '.$appLocalId.'): '.get_class($e).': '.$e->getMessage());

            return response()->json(['ok' => false, 'message' => trans('crudbooster.qlik_sync_err_internal')], 500);
        }

        $confKey = (string) $confId;
        $known = array_flip(
            DB::table('qlik_items')->whereNull('deleted_at')->where('qlik_conf', $confKey)->where('qlik_app_id', $app->id)
                ->whereNotNull('external_id')->pluck('external_id')->map(function ($id) {
                    return (string) $id;
                })->all()
        );
        // Item creati a mano prima della sync: la sync li aggancia per URL, quindi contano come gia' importati.
        $legacyUrls = DB::table('qlik_items')->whereNull('deleted_at')->where('qlik_conf', $confKey)
            ->whereNull('external_id')->pluck('url')->all();

        $items = collect($sheets)->map(function ($s) use ($known, $legacyUrls, $driver, $app) {
            $id = (string) $s['id'];
            $imported = isset($known[$id]);
            if (! $imported && $legacyUrls) {
                $needle = $driver->sheetUrlNeedle($app->appid, $id);
                foreach ($legacyUrls as $url) {
                    if ($url !== null && strpos($url, $needle) !== false) {
                        $imported = true;
                        break;
                    }
                }
            }

            return [
                'id' => $id,
                'name' => ($s['title'] ?? '') !== '' ? $s['title'] : $id,
                'description' => (string) ($s['description'] ?? ''),
                'imported' => $imported,
            ];
        })->sortBy(function ($s) {
            return mb_strtolower($s['name']);
        })->values()->all();

        return response()->json(['ok' => true, 'apps' => $items]);
    }

    /**
     * App di una configurazione, in ordine alfabetico (per la modale item).
     */
    protected function qlikSyncAppsForConf()
    {
        if (! CRUDBooster::isSuperadmin()) {
            return response()->json(['apps' => []], 403);
        }

        $confId = (int) Request::input('conf_id');
        $apps = DB::table('qlik_apps')
            ->where('conf', (string) $confId)
            ->get(['id', 'appname', 'appid'])
            ->map(function ($a) {
                return ['id' => (int) $a->id, 'name' => $a->appname !== '' && $a->appname !== null ? $a->appname : $a->appid];
            })
            ->sortBy(function ($a) {
                return mb_strtolower($a['name']);
            })
            ->values()
            ->all();

        return response()->json(['apps' => $apps]);
    }
}
