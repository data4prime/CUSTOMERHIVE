<?php

namespace App\Http\Controllers\System\Concerns;

use App\Helpers\CRUDBooster;
use App\QlikSyncRun;
use App\Services\QlikSync\QlikSyncException;
use App\Services\QlikSync\QlikSyncService;
use App\Services\QlikSync\QlikSyncUi;
use Illuminate\Support\Facades\DB;
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

        try {
            $run = QlikSyncService::start($type, $confId, $appId, (int) CRUDBooster::myId());
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
