<?php

namespace App\Services\QlikSync;

use App\Helpers\CRUDBooster;
use App\Helpers\LicenseHelper;
use Illuminate\Support\Facades\DB;

/**
 * Aggancia ai controller delle liste app/item i pulsanti "Sincronizza da
 * Qlik" (modale) e "Sincronizzazioni" (monitoraggio). Solo superadmin, solo
 * con il modulo Qlik in licenza, solo nella lista (non in add/edit/detail).
 */
class QlikSyncUi
{
    /** URL della pagina di monitoraggio (rotta esplicita, modulo qlik_apps). */
    public static function runsUrl(): string
    {
        return url('admin/qlik_apps/sync-runs');
    }

    /**
     * Item (fogli) collegati a un'app, per la modalita' "foglio" del widget
     * Qlik: [qlik_app_id => [{id, title}, ...]], titoli in ordine alfabetico.
     * Gli item senza app (relazione facoltativa) non compaiono.
     */
    public static function sheetItemsByApp(): array
    {
        return DB::table('qlik_items')
            ->whereNull('deleted_at')
            ->whereNotNull('qlik_app_id')
            ->get(['id', 'title', 'qlik_app_id'])
            ->map(function ($i) {
                return [
                    'app' => (int) $i->qlik_app_id,
                    'id' => (int) $i->id,
                    'title' => ($i->title !== null && $i->title !== '') ? $i->title : '#'.$i->id,
                ];
            })
            ->sortBy(function ($i) {
                return mb_strtolower($i['title']);
            })
            ->groupBy('app')
            ->map(function ($group) {
                return $group->map(function ($i) {
                    return ['id' => $i['id'], 'title' => $i['title']];
                })->values()->all();
            })
            ->all();
    }

    /**
     * @param  \crocodicstudio\crudbooster\controllers\CBController  $controller
     * @param  string  $mode  'apps' | 'items'
     */
    public static function boot($controller, string $mode): void
    {
        if (! CRUDBooster::isSuperadmin() || ! LicenseHelper::isActiveQlik()) {
            return;
        }
        if (CRUDBooster::getCurrentMethod() !== 'getIndex') {
            return;
        }

        $controller->index_button[] = [
            'label' => trans('crudbooster.qlik_sync_button'),
            'url' => 'javascript:void(0)',
            'icon' => 'bi bi-arrow-repeat',
            'color' => 'info',
            'onClick' => 'if (window.qlikSyncOpen) { qlikSyncOpen(); } return false;',
        ];
        $controller->index_button[] = [
            'label' => trans('crudbooster.qlik_sync_runs_button'),
            'url' => self::runsUrl(),
            'icon' => 'bi bi-list-check',
            'color' => 'default',
        ];

        $userId = (int) CRUDBooster::myId();
        $confs = DB::table('qlik_confs')->get(['id', 'confname'])->map(function ($c) use ($userId) {
            return [
                'id' => (int) $c->id,
                'name' => ($c->confname !== null && $c->confname !== '') ? $c->confname : '#'.$c->id,
                'usable' => QlikDriverFactory::userHasQlikUser($userId, (int) $c->id),
            ];
        })->sortBy(function ($c) {
            return mb_strtolower($c['name']);
        })->values()->all();

        $config = [
            'mode' => $mode,
            'confs' => $confs,
            'urls' => [
                'start' => CRUDBooster::mainpath('sync-start'),
                'apps' => CRUDBooster::mainpath('sync-apps'),
                'preview' => CRUDBooster::mainpath('sync-preview'),
                'runs' => self::runsUrl(),
            ],
            'i18n' => [
                'title_apps' => trans('crudbooster.qlik_sync_modal_title_apps'),
                'title_items' => trans('crudbooster.qlik_sync_modal_title_items'),
                'label_conf' => trans('crudbooster.qlik_sync_label_conf'),
                'label_app' => trans('crudbooster.qlik_sync_label_app'),
                'all_apps' => trans('crudbooster.qlik_sync_all_apps'),
                'apps_hint' => trans('crudbooster.qlik_sync_apps_hint'),
                'items_hint' => trans('crudbooster.qlik_sync_items_hint'),
                'search' => trans('crudbooster.qlik_sync_search'),
                'no_qlik_user' => trans('crudbooster.qlik_sync_conf_no_qlik_user'),
                'choose_conf' => trans('crudbooster.qlik_sync_choose_conf'),
                'start' => trans('crudbooster.qlik_sync_start'),
                'import_all' => trans('crudbooster.qlik_sync_import_all'),
                'import_selected' => trans('crudbooster.qlik_sync_import_selected'),
                'select_visible' => trans('crudbooster.qlik_sync_select_visible'),
                'deselect_visible' => trans('crudbooster.qlik_sync_deselect_visible'),
                'selected_count' => trans('crudbooster.qlik_sync_selected_count'),
                'already_imported' => trans('crudbooster.qlik_sync_already_imported'),
                'loading_apps' => trans('crudbooster.qlik_sync_loading_apps'),
                'no_apps_found' => trans('crudbooster.qlik_sync_no_apps_found'),
                'pick_apps_hint' => trans('crudbooster.qlik_sync_pick_apps_hint'),
                'already_imported_item' => trans('crudbooster.qlik_sync_already_imported_item'),
                'pick_sheets_hint' => trans('crudbooster.qlik_sync_pick_sheets_hint'),
                'close' => trans('crudbooster.qlik_test_close'),
                'started' => trans('crudbooster.qlik_sync_started'),
                'go_to_runs' => trans('crudbooster.qlik_sync_go_to_runs'),
                'bad_response' => trans('crudbooster.qlik_test_bad_response'),
                'network_error' => trans('crudbooster.qlik_test_network_error'),
            ],
        ];

        // ?v=mtime: senza, il browser tiene la versione vecchia del JS dopo un aggiornamento.
        $jsFile = public_path('js/qlik_sync_modal.js');
        $controller->load_js[] = asset('js/qlik_sync_modal.js').'?v='.(is_file($jsFile) ? filemtime($jsFile) : 1);
        $controller->script_js = ($controller->script_js ?? '')
            .'window.QLIK_SYNC = '.json_encode($config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT).';';
    }
}
