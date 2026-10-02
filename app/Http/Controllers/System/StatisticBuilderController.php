<?php

namespace App\Http\Controllers\System;

use crocodicstudio\crudbooster\controllers\CBController;

use CRUDBooster;
use App\Helpers\QlikHelper as HelpersQlikHelper;
use App\Helpers\LicenseHelper;
use App\Dashboards\DashboardDatasetRegistry;
use App\Dashboards\DatasetAccessScope;
use App\Dashboards\LegacyDashboardGridConverter;

use App\Http\Controllers\System\QlikAppController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Excel;
use Illuminate\Support\Facades\PDF;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;

use Illuminate\Support\Facades\Log;

class StatisticBuilderController extends CBController
{
    public function cbInit()
    {
        $this->table = "cms_statistics";
        $this->primary_key = "id";
        $this->title_field = "name";
        $this->limit = 20;
        $this->orderby = ["id" => "desc"];
        $this->global_privilege = false;

        $this->button_table_action = true;
        $this->button_action_style = "button_icon_text";
        $this->button_add = true;
        $this->button_delete = true;
        $this->button_edit = true;
        $this->button_detail = false;
        $this->button_show = false;
        $this->button_filter = false;
        $this->button_export = false;
        $this->button_import = false;

        $this->col = [];
        $this->col[] = ["label" => "Name", "name" => "name"];
        //$this->col[] = ["label" => "Layout", "name" => "layout"];
        $this->col[] = array("label" => "Layout", "name" => "layout", "join" => "dashboard_layouts,layoutname");

        $this->form = [];
        $this->form[] = [
            "label" => "Name",
            "name" => "name",
            "type" => "text",
            "required" => true,
            "validation" => "required|min:3|max:255",
            "placeholder" => "",
        ];

        $this->form[] = [
            "label" => "Layout",
            "name" => "layout",
            "type" => "select",
            "required" => false,
            "datatable" => "dashboard_layouts,layoutname",
            "validation" => "nullable",
            "placeholder" => "",
        ];

        // Le nuove dashboard nascono sempre 'grid' (vedi hook_before_add()) e
        // il layout ad aree fisse viene ignorato: il campo non si mostra ne'
        // si scrive, ne' in creazione ne' in modifica (hide_form: il valore
        // gia' salvato di una dashboard 'legacy_areas' resta intatto, NON
        // unset da $this->form: vedi input_assignment()).
        $this->hide_form = ['layout'];

        $this->addaction = [];
        $this->addaction[] = ['label' => 'Builder', 'url' => CRUDBooster::mainpath('builder') . '/[id]', 'icon' => 'bi bi-wrench'];
        // Piano "dashboard a griglia libera" (docs/piano-dashboard-griglia-libera.md,
        // docs/refactoring/114-*): unico punto da cui una dashboard
        // 'legacy_areas' esistente passa alla nuova griglia - senza
        // questo link l'endpoint di conversione (getConvertToGrid()) non
        // era raggiungibile da nessuna parte dell'interfaccia.
        // Niente 'showIf': la sintassi esatta supportata da CRUDBooster per
        // condizionare un addaction su un campo diverso da quelli standard
        // non e' risultata affidabile in prova (errore "Undefined constant"
        // nell'eval interno) - il bottone resta visibile anche su una
        // dashboard gia' 'grid', dove l'endpoint si limita a riportare
        // alla stessa griglia senza riconvertire nulla (vedi getConvertToGrid()).
        $this->addaction[] = ['label' => 'Griglia libera', 'url' => CRUDBooster::mainpath('convert-to-grid') . '/[id]', 'icon' => 'bi bi-grid-fill'];


    }



    public function getShowDashboard()
    {

        $this->cbLoader();

        $m = CRUDBooster::sidebarDashboard();
        $m->path = str_replace("statistic_builder/show/", "", $m->path);

        if ($m->type != 'Statistic') {
            redirect('/');
        }
        $row = CRUDBooster::first($this->table, ['slug' => $m->path]);

        if (!$row) {
            return CRUDBooster::redirect(CRUDBooster::adminPath(), 'Dashboard non trovata: il link non è più valido (probabilmente la dashboard è stata rinominata o eliminata).', 'warning');
        }

        $id_cms_statistics = $row->id;
        $page_title = $row->name;



        return view('crudbooster::statistic_builder.show', compact('page_title', 'id_cms_statistics'));
    }

    /**
     * Risolve la riga dashboard_layouts assegnata (se esiste) o la griglia
     * di default a 9 aree - usata sia da getDashboard() che da getShow(),
     * che prima duplicavano questa logica in modo incompleto (getShow() non
     * la calcolava affatto, ricadendo sempre e comunque sulla griglia di
     * default definita separatamente in index.blade.php, indipendentemente
     * dal layout realmente assegnato alla dashboard).
     *
     * @return array{0: object|null, 1: string} [$layout, $code_layout]
     */
    private function resolveDashboardCodeLayout($layoutId)
    {
        $layout = DB::table('dashboard_layouts')->where('id', $layoutId)->first();

        if ($layout) {
            return [$layout, html_entity_decode($layout->code_layout)];
        }

        $code_layout = "
                <div class='statistic-row row'>
        <div id='area1' class='col-sm-3 connectedSortable'>

        </div>
        <div id='area2' class='col-sm-3 connectedSortable'>

        </div>
        <div id='area3' class='col-sm-3 connectedSortable'>

        </div>
        <div id='area4' class='col-sm-3 connectedSortable'>

        </div>
    </div>

<div class='statistic-row row'>
        <div id='area5' class='col-sm-3 connectedSortable'>

        </div>
        <div id='area6' class='col-sm-3 connectedSortable'>

        </div>
        <div id='area7' class='col-sm-3 connectedSortable'>

        </div>
        <div id='area8' class='col-sm-3 connectedSortable'>

        </div>
    </div>

    <div class='statistic-row row'>
        <div id='area9' class='col-sm-12 connectedSortable'>

        </div>
</div>
        ";

        return [null, $code_layout];
    }

    public function getDashboard()
    {
        $this->cbLoader();

        $menus = DB::table('cms_menus')->whereRaw("cms_menus.id IN (select id_cms_menus from cms_menus_privileges where id_cms_privileges = '" . CRUDBooster::myPrivilegeId() . "')")->where('is_dashboard', 1)->where('is_active', 1)->first();

        $slug = str_replace("statistic_builder/show/", "", $menus->path);
        $row = CRUDBooster::first($this->table, ['slug' => $slug]);

        if (!$row) {
            return CRUDBooster::redirect(CRUDBooster::adminPath(), 'Dashboard non trovata: il link del menu non è più valido (probabilmente la dashboard è stata rinominata o eliminata).', 'warning');
        }

        $id_cms_statistics = isset($row->id) ? $row->id : 0;
        $page_title = isset($row->name) ? $row->name : 'Dashboard';

        return $this->renderDashboardShow($row, $page_title, $id_cms_statistics);
    }

    /**
     * Fase 5 del piano "dashboard a griglia libera" (vedi
     * docs/piano-dashboard-griglia-libera.md, docs/refactoring/113-*):
     * usata sia da getDashboard() sia da getShow() per non duplicare il
     * branch legacy/griglia. Per 'legacy_areas' (il default per tutte le
     * dashboard esistenti) il comportamento resta byte-per-byte identico
     * a prima (stessa view 'show', stesse variabili).
     *
     * Per 'grid' il rendering della GRIGLIA e' fatto server-side in puro
     * CSS (grid-column/grid-row da pos_x/pos_y/width/height) - a
     * differenza del builder qui non serve gridstack.js: e' una vista di
     * sola lettura, niente drag/resize. I widget vengono renderizzati con
     * la stessa renderComponentPayload() del builder e della bulk API, in
     * un'unica query invece degli N round-trip di oggi anche per le
     * dashboard legacy.
     */
    private function renderDashboardShow($row, $page_title, $id_cms_statistics)
    {
        if (($row->layout_mode ?? 'legacy_areas') === 'grid') {
            // Il link al builder ha senso solo per chi puo' davvero
            // aprirlo (getBuilder() e' gia' riservato al superadmin):
            // per gli altri resta il solo testo, niente link che porta
            // comunque a un "accesso negato".
            $builderUrl = CRUDBooster::isSuperadmin()
                ? CRUDBooster::mainpath('builder') . '/' . $id_cms_statistics
                : null;
            $components = DB::table('cms_statistic_components')
                ->where('id_cms_statistics', $id_cms_statistics)
                ->orderBy('pos_y')
                ->orderBy('pos_x')
                ->get()
                ->map(function ($component) use ($builderUrl) {
                    $payload = $this->renderComponentPayload($component, $builderUrl);
                    if ($payload === null) {
                        return null;
                    }

                    return $payload + [
                        'pos_x' => (int) ($component->pos_x ?? 0),
                        'pos_y' => (int) ($component->pos_y ?? 0),
                        'width' => (int) ($component->width ?: 3),
                        'height' => (int) ($component->height ?: 3),
                    ];
                })
                // widget nascosti all'utente (Tabella "Elenco record" senza
                // accesso alla tabella): nessun segnaposto, le posizioni
                // degli altri restano quelle salvate
                ->filter()
                ->values();

            return view('crudbooster::statistic_builder.show_grid', compact('page_title', 'id_cms_statistics', 'components'));
        }

        $layoutId = isset($row->layout) ? $row->layout : 0;
        [$layout, $code_layout] = $this->resolveDashboardCodeLayout($layoutId);

        return view('crudbooster::statistic_builder.show', compact('page_title', 'id_cms_statistics', 'layout', 'code_layout'));
    }

    /**
     * Un utente vede una dashboard (getShow/getListComponent/
     * getViewComponent) solo se e' superadmin o se il suo ruolo ha un menu
     * (cms_menus, type='Statistic') che punta proprio a questa dashboard -
     * stesso meccanismo di visibilita' gia' usato per il menu stesso
     * (cms_menus_privileges), non uno nuovo. Prima non c'era alcun
     * controllo: qualunque utente loggato che conoscesse/indovinasse lo
     * slug (o l'id numerico, per i due metodi sotto) vedeva i dati veri di
     * qualunque dashboard. Vedi "Rischi e note" in
     * docs/refactoring/079-statistic-builder-privilegi-componenti.md e
     * docs/refactoring/091.
     *
     * **Comportamento visibile che cambia**: un utente non superadmin il
     * cui ruolo non ha un menu verso una data dashboard non puo' piu'
     * aprirla (prima poteva, se conosceva il link). I "link condivisi"
     * restano validi SOLO se la dashboard e' effettivamente nel menu del
     * ruolo di chi apre il link.
     */
    private function isDashboardVisibleToCurrentUser($idCmsStatistics)
    {
        if (CRUDBooster::isSuperadmin()) {
            return true;
        }

        // Tabella letterale invece di $this->table: getListComponent()/
        // getViewComponent() (a differenza di getShow()) non chiamano
        // cbLoader() prima di arrivare qui, quindi $this->table (valorizzata
        // in cbInit()) non e' ancora popolata quando si chiama da li'.
        $slug = DB::table('cms_statistics')->where('id', $idCmsStatistics)->value('slug');
        if (!$slug) {
            return false;
        }

        return DB::table('cms_menus')
            ->join('cms_menus_privileges', 'cms_menus_privileges.id_cms_menus', '=', 'cms_menus.id')
            ->where('cms_menus_privileges.id_cms_privileges', CRUDBooster::myPrivilegeId())
            ->where('cms_menus.type', 'Statistic')
            ->where('cms_menus.path', 'statistic_builder/show/' . $slug)
            ->exists();
    }

    public function getShow($slug)
    {

        $this->cbLoader();
        $row = CRUDBooster::first($this->table, ['slug' => $slug]);

        if (!$row) {
            return CRUDBooster::redirect(CRUDBooster::adminPath(), 'Dashboard non trovata: il link non è più valido (probabilmente la dashboard è stata rinominata o eliminata).', 'warning');
        }

        if (!$this->isDashboardVisibleToCurrentUser($row->id)) {
            CRUDBooster::insertLog(trans("crudbooster.log_try_view", ['name' => $row->name, 'module' => 'Statistic']));
            return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        $id_cms_statistics = $row->id;
        $page_title = $row->name;

        return $this->renderDashboardShow($row, $page_title, $id_cms_statistics);
    }

    public function getBuilder($id_cms_statistics)
    {
        $this->cbLoader();

        if (!CRUDBooster::isSuperadmin()) {
            CRUDBooster::insertLog(trans("crudbooster.log_try_view", ['name' => 'Builder', 'module' => 'Statistic']));
            return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        $page_title = 'Statistic Builder';

        $statistic = CRUDBooster::first($this->table, ['id' => $id_cms_statistics]);

        // Fase 3 del piano "dashboard a griglia libera" (vedi
        // docs/piano-dashboard-griglia-libera.md, docs/refactoring/111-*):
        // una dashboard gia' convertita (layout_mode='grid', vedi
        // postConvertToGrid()) apre il nuovo editor a griglia libera
        // invece del drag&drop legacy ad aree fisse - branch additivo, il
        // ramo sotto (layout_mode='legacy_areas', il default per tutte le
        // dashboard esistenti) resta identico a prima.
        if (($statistic->layout_mode ?? 'legacy_areas') === 'grid') {
            return view('crudbooster::statistic_builder.builder_grid', compact('page_title', 'id_cms_statistics'));
        }

        $layout = DB::table('dashboard_layouts')->where('id', $statistic->layout)->first();

        if ($layout) {
            $code_layout = html_entity_decode($layout->code_layout);
        } else {
            $code_layout = '';
        }

        return view('crudbooster::statistic_builder.builder', compact('page_title', 'id_cms_statistics', 'layout', 'code_layout'));
    }

    /**
     * Fase 1 del piano "dashboard a griglia libera": converte una
     * dashboard 'legacy_areas' a 'grid' calcolando x/y/w/h di partenza
     * dal layout HTML attuale (vedi LegacyDashboardGridConverter).
     * Operazione non distruttiva (area_name/sorting/layout HTML restano
     * intatti) ma irreversibile lato UI: una volta 'grid' la dashboard
     * apre sempre il nuovo editor (vedi getBuilder() sopra).
     *
     * GET (non POST) e raggiunta da un link (vedi addaction 'Griglia
     * libera' in cbInit()), coerente con le altre azioni amministrative
     * di questo controller gia' esposte come link GET (es.
     * getDeleteComponent()) - CSRF e' comunque disabilitato globalmente
     * in questo progetto (vedi CLAUDE.md/docs/login-e-licensing.md), non
     * e' un indebolimento introdotto qui.
     */
    public function getConvertToGrid($id_cms_statistics)
    {
        $this->cbLoader();

        if (!CRUDBooster::isSuperadmin()) {
            CRUDBooster::insertLog(trans("crudbooster.log_try_view", ['name' => 'Convert To Grid', 'module' => 'Statistic']));
            return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        $statistic = DB::table($this->table)->where('id', $id_cms_statistics)->first();
        if (!$statistic) {
            return CRUDBooster::redirect(CRUDBooster::mainpath(), 'Dashboard non trovata.', 'warning');
        }

        if (($statistic->layout_mode ?? 'legacy_areas') !== 'grid') {
            [$layout, $code_layout] = $this->resolveDashboardCodeLayout($statistic->layout);
            (new LegacyDashboardGridConverter())->convert((int) $id_cms_statistics, $code_layout);
            CRUDBooster::insertLog('Dashboard statistica "' . $statistic->name . '" convertita alla nuova griglia libera');
        }

        return CRUDBooster::redirect(CRUDBooster::mainpath('builder') . '/' . $id_cms_statistics, 'Dashboard convertita alla nuova griglia libera.', 'success');
    }

    public function getListComponent($id_cms_statistics, $area_name)
    {
        // Volutamente NON limitata al superadmin (serve anche alla
        // visualizzazione normale delle dashboard, vedi postSaveComponent()).
        // Per i non superadmin pero' non si restituisce 'config': contiene
        // la query SQL dei widget (Small Box/Table/Chart...), e il JS della
        // pagina usa solo componentID per poi chiamare view-component. Vedi
        // docs/refactoring/079-statistic-builder-privilegi-componenti.md.
        //
        // Raggiungibile direttamente per URL con l'id numerico della
        // dashboard (non solo dopo aver aperto getShow()): senza questo
        // controllo, chi indovinava/conosceva un id vedeva comunque
        // l'elenco widget di una dashboard non sua - vedi
        // isDashboardVisibleToCurrentUser() e docs/refactoring/091.
        if (!$this->isDashboardVisibleToCurrentUser($id_cms_statistics)) {
            return response()->json(['components' => []], 403);
        }

        $query = DB::table('cms_statistic_components')->where('id_cms_statistics', $id_cms_statistics)->where('area_name', $area_name)
                ->orderby('sorting', 'asc');
        if (!CRUDBooster::isSuperadmin()) {
            $query->select('id', 'id_cms_statistics', 'componentID', 'component_name', 'area_name', 'sorting', 'name');
        }
        $rows = $query->get();

        return response()->json(['components' => $rows]);
    }

    public function getViewComponent($componentID)
    {
        $component = DB::table('cms_statistic_components')->where('componentID', $componentID)->first();

        // Raggiungibile direttamente per URL col componentID (non solo
        // dopo aver aperto getShow()/getListComponent()): esegue davvero
        // la query SQL del widget e ne restituisce il risultato, quindi e'
        // il punto piu' sensibile dei tre - vedi
        // isDashboardVisibleToCurrentUser() e docs/refactoring/091.
        if (!$component || !$this->isDashboardVisibleToCurrentUser($component->id_cms_statistics)) {
            return response()->json(['error' => trans('crudbooster.denied_access')], 403);
        }

        $payload = $this->renderComponentPayload($component);
        if ($payload === null) {
            // widget nascosto a questo utente (Tabella "Elenco record" senza
            // accesso alla tabella): layout vuoto, nessuna config
            return response()->json(['componentID' => $componentID, 'layout' => '', 'config' => null, 'conf' => null]);
        }

        return response()->json($payload);
    }

    /**
     * Fase 2 del piano "dashboard a griglia libera" (vedi
     * docs/piano-dashboard-griglia-libera.md, docs/refactoring/110-*):
     * corpo di getViewComponent() estratto cosi' com'era (nessun cambio
     * di comportamento per i widget in modalita' 'sql'/legacy) per poterlo
     * riusare da getListComponentsGrid() - una sola chiamata bulk con
     * l'HTML di tutti i widget gia' pronto, invece degli N round-trip
     * (list-component poi view-component per ciascuno) di oggi.
     *
     * In piu' (unica aggiunta reale): se config.mode === 'builder', il
     * placeholder [sql] viene risolto eseguendo il dataset scelto tramite
     * DashboardDatasetRegistry invece di un DB::select() letterale - i
     * widget esistenti non hanno mai questa chiave, quindi per loro il
     * comportamento resta identico a prima.
     */
    private function renderComponentPayload($component, $editUrl = null)
    {
        $componentID = $component->componentID;
        $component_name = $component->component_name;

        $command = 'layout';
        $config = json_decode($component->config);
        if ($config) {
            $mashup = isset($config->mashups) ? DB::table('qlik_apps')->where('id', $config->mashups)->first() : null;
            $conf = $mashup ? QlikAppController::getConf($mashup->conf) : null;
        } else {
            $conf = null;
            $config = new \stdClass();
            $config->mashups = 0;
            $config->object = 0;
        }

        // Widget Tabella "Elenco record": se l'utente non puo' leggere la
        // tabella il widget sparisce del tutto (payload nullo, nessuna config
        // esposta) - vedi docs/piano-widget-tabella-elenco-record.md, Fase 4.
        if ($this->isRecordsWidgetHidden($component_name, $config)) {
            return null;
        }

        $mashup = isset($config->mashups) ? QlikAppController::getMashupFromCompID($componentID) : null;

        $token = ($conf && isset($conf->id) && $conf->type == 'SAAS')
            ? HelpersQlikHelper::getJWTToken(CRUDBooster::myId(), $conf->id)
            : '';

        // $editUrl e' valorizzato solo quando si chiama da
        // renderDashboardShow() (vista pubblica di sola lettura): serve
        // al placeholder "widget non configurato" per proporre un link
        // diretto al builder invece dell'invito a "selezionare" il
        // widget, che li' non ha senso (non c'e' alcuna sidebar).
        $layout = view('crudbooster::statistic_builder.components.' . $component_name, compact('command', 'componentID', 'conf', 'config', 'mashup', 'token', 'editUrl'))->render();

        $mode = $config->mode ?? 'sql';

        if ($config) {
            foreach ($config as $key => $value) {
                // In modalita' 'builder', o quando ci sono linee extra
                // (docs/refactoring/152-*), il placeholder [sql] lo
                // valorizza il blocco dedicato piu' sotto (risultato del
                // dataset, o delle N linee risolte) - un valore scalare
                // 'sql' rimasto in $config (anche solo quello della prima
                // linea, salvato mentre mode==='sql') andrebbe altrimenti
                // sostituito qui per primo, e la sostituzione sotto non
                // troverebbe piu' nessun [sql] da rimpiazzare (mostrando
                // il grafico a una sola serie invece di quello multi-linea
                // appena configurato).
                // 'records' (Tabella "Elenco record"): idem, e in piu' una
                // 'sql' scalare rimasta da quando il widget era in SQL libera
                // NON deve mai essere eseguita in questa modalita'.
                if ($key === 'sql' && ($mode === 'builder' || $mode === 'records' || !empty($config->lines))) {
                    continue;
                }
                // Il resto di questo ciclo si aspetta un valore scalare da
                // interpolare al posto di '[chiave]' (name/icon/color/
                // description/link/...). 'filters' (dataset key => value
                // della query guidata, ora davvero salvabile - vedi
                // docs/refactoring/135) e' un oggetto/mappa strutturata,
                // mai un placeholder testuale: nessun layout usa
                // '[filters]', quindi scartarlo qui non cambia l'HTML
                // finale, ma senza questo controllo finiva comunque nel
                // ramo generico "echo $value" di smallbox.blade.php
                // ("Object of class stdClass could not be converted to
                // string") non appena un filtro veniva davvero salvato.
                if (!is_scalar($value)) {
                    continue;
                }
                if ($value) {
                    $command = 'showFunction';
                    $rendered = view('crudbooster::statistic_builder.components.' . $component_name, compact('command', 'value', 'key', 'config', 'conf', 'componentID', 'mashup', 'token'))->render();
                    $layout = str_replace('[' . $key . ']', $rendered, $layout);
                }
            }
        }

        // Piu' linee (docs/refactoring/152-*, Grafico a linee): la
        // sorgente del widget (dataset/metric/group_by/filters/sql a
        // livello di $config, esattamente come oggi) resta SEMPRE la
        // prima linea - config->lines aggiunge solo linee EXTRA,
        // opzionali. Senza config->lines (ogni widget gia' salvato, e
        // qualunque widget che non e' un grafico multi-linea) questo
        // ramo non scatta mai: il comportamento sotto (mode='builder'
        // singolo) resta l'unico percorso, invariato.
        if (!empty($config->lines)) {
            $command = 'showFunction';
            $key = 'sql';
            $resolvedLines = [];
            $resolvedLines[] = [
                'name' => $config->name ?? '',
                'color' => $config->color ?? null,
                'rows' => $this->resolveSourceRows($config),
            ];
            foreach ($config->lines as $line) {
                // Una linea "vuota" (slot pre-renderizzato ma mai
                // compilato, o svuotato con "Rimuovi linea" - vedi
                // chartline_v2.blade.php) non deve comparire come serie
                // fantasma nel grafico.
                if (empty($line->name) && empty($line->dataset) && empty($line->sql)) {
                    continue;
                }
                $resolvedLines[] = [
                    'name' => $line->name ?? '',
                    'color' => $line->color ?? null,
                    'rows' => $this->resolveSourceRows($line),
                ];
            }
            $value = $resolvedLines;
            $rendered = view('crudbooster::statistic_builder.components.' . $component_name, compact('command', 'value', 'key', 'config', 'conf', 'componentID', 'mashup', 'token'))->render();
            $layout = str_replace('[sql]', $rendered, $layout);
        } elseif ($mode === 'builder' && !empty($config->dataset)) {
            $command = 'showFunction';
            $key = 'sql';
            try {
                $value = DashboardDatasetRegistry::execute($config->dataset, [
                    'metric' => $config->metric ?? null,
                    'group_by' => $config->group_by ?? null,
                    'filters' => (array) ($config->filters ?? []),
                ]);
                $rendered = view('crudbooster::statistic_builder.components.' . $component_name, compact('command', 'value', 'key', 'config', 'conf', 'componentID', 'mashup', 'token'))->render();
            } catch (\Throwable $e) {
                $rendered = '<span class="small-box-sql-error">' . e($e->getMessage()) . '</span>';
            }
            $layout = str_replace('[sql]', $rendered, $layout);
        } elseif ($mode === 'records' && !empty($config->dataset)) {
            $command = 'showFunction';
            $key = 'sql';
            try {
                // $value = ['columns' => [nome => etichetta], 'rows' => [...]],
                // gia' scopato sull'utente corrente da executeRows(): il
                // template table.blade.php distingue questo formato dalle
                // righe {label,value} dalla chiave 'columns'.
                $value = DashboardDatasetRegistry::executeRows($config->dataset, $this->recordsParams($config));
                $rendered = view('crudbooster::statistic_builder.components.' . $component_name, compact('command', 'value', 'key', 'config', 'conf', 'componentID', 'mashup', 'token'))->render();
            } catch (\Throwable $e) {
                $rendered = '<div class="alert alert-danger table-widget-sql-error" style="margin:15px;">' . e($e->getMessage()) . '</div>';
            }
            $layout = str_replace('[sql]', $rendered, $layout);
        }

        return compact('componentID', 'layout', 'config', 'conf');
    }

    /**
     * Parametri di DashboardDatasetRegistry::executeRows() dalla config di un
     * widget Tabella in modalita' 'records' (o dalla richiesta di anteprima).
     * Nessuna validazione qui: executeRows() riverifica tutto contro la
     * whitelist del dataset.
     */
    private function recordsParams($config, ?int $limit = null): array
    {
        $params = [
            'columns' => array_values((array) ($config->columns ?? [])),
            'filters' => (array) ($config->filters ?? []),
            'order_by' => $config->order_by ?? null,
            'order_dir' => $config->order_dir ?? 'asc',
        ];
        if ($limit !== null) {
            $params['limit'] = $limit;
        }

        return $params;
    }

    /**
     * true se il widget e' una Tabella "Elenco record" su una tabella che
     * l'utente corrente non puo' leggere (Fase 4 del piano): il widget non
     * va proprio mostrato. I widget in qualunque altra modalita' non sono
     * mai toccati da questo controllo.
     */
    private function isRecordsWidgetHidden($componentName, $config): bool
    {
        if ($componentName !== 'table' || ($config->mode ?? 'sql') !== 'records' || empty($config->dataset)) {
            return false;
        }

        $dataset = DashboardDatasetRegistry::get((string) $config->dataset);

        return !$dataset || !DatasetAccessScope::canRead($dataset['table']);
    }

    /**
     * Risolve le righe {label,value} di UNA sorgente (SQL libera o
     * Query guidata) - stessa logica gia' usata sia per il blocco
     * builder-mode sopra sia per lo showFunction 'sql' dei widget con
     * SQL libera (es. chartline_v2), estratta qui perche' il Grafico a
     * linee multi-linea (docs/refactoring/152-*) la applica una volta
     * per ciascuna linea, non solo per l'unica sorgente del widget.
     * Non lancia mai: una query in errore per una singola linea produce
     * solo una linea vuota (0 punti), non fa fallire l'intero grafico -
     * per il debug di una query sbagliata resta il pulsante "Prova" nel
     * pannello di configurazione di quella linea, che mostra l'errore
     * vero.
     *
     * @param object $source oggetto con eventuali proprieta' mode/dataset/metric/group_by/filters/sql
     * @return array<int, array{label: string, value: mixed}>
     */
    private function resolveSourceRows($source): array
    {
        $mode = $source->mode ?? 'sql';

        try {
            if ($mode === 'builder' && !empty($source->dataset)) {
                return DashboardDatasetRegistry::execute($source->dataset, [
                    'metric' => $source->metric ?? null,
                    'group_by' => $source->group_by ?? null,
                    'filters' => (array) ($source->filters ?? []),
                ]);
            }

            if (empty($source->sql)) {
                return [];
            }

            $sql = $source->sql;
            foreach (Session::all() as $sessionKey => $sessionValue) {
                if (gettype($sessionValue) == gettype($sql)) {
                    $sql = str_replace('[' . $sessionKey . ']', $sessionValue, $sql);
                }
            }

            $rows = [];
            foreach (DB::select($sql) as $r) {
                $rows[] = ['label' => $r->label ?? '', 'value' => $r->value ?? 0];
            }

            return $rows;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Fase 2 del piano "dashboard a griglia libera": equivalente di
     * getListComponent() + N chiamate a getViewComponent() ma in
     * un'unica risposta bulk, con x/y/w/h per ciascun widget - usata solo
     * dal nuovo builder_grid.blade.php (dashboard con layout_mode='grid').
     */
    public function getListComponentsGrid($id_cms_statistics)
    {
        if (!$this->isDashboardVisibleToCurrentUser($id_cms_statistics)) {
            return response()->json(['components' => []], 403);
        }

        $rows = DB::table('cms_statistic_components')
            ->where('id_cms_statistics', $id_cms_statistics)
            ->orderBy('pos_y')
            ->orderBy('pos_x')
            ->get();

        $components = $rows->map(function ($row) {
            $payload = $this->renderComponentPayload($row);
            if ($payload === null) {
                return null;
            }
            $payload['component_name'] = $row->component_name;
            $payload['pos_x'] = (int) ($row->pos_x ?? 0);
            $payload['pos_y'] = (int) ($row->pos_y ?? 0);
            $payload['width'] = (int) ($row->width ?: 3);
            $payload['height'] = (int) ($row->height ?: 3);

            return $payload;
        })->filter()->values();

        return response()->json(['components' => $components]);
    }

    public function postAddComponent()
    {
        $this->cbLoader();

        // Chiamata solo dal drag&drop dell'editor, attivo solo in
        // getBuilder() (gia' riservato al superadmin): stesso controllo.
        if (!CRUDBooster::isSuperadmin()) {
            CRUDBooster::insertLog(trans("crudbooster.log_try_view", ['name' => 'Add Component', 'module' => 'Statistic']));
            return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        $component_name = Request::get('component_name');
        $id_cms_statistics = Request::get('id_cms_statistics');
        $sorting = Request::get('sorting');
        $area = Request::get('area');

        $componentID = md5(time());

        $command = 'layout';
        $layout = view('crudbooster::statistic_builder.components.' . $component_name, compact('command', 'componentID'))->render();


        $data = [
            'id_cms_statistics' => $id_cms_statistics,
            'componentID' => $componentID,
            'component_name' => $component_name,
            'area_name' => $area,
            'sorting' => $sorting,
            'name' => 'Untitled',
        ];

        // Fase 2/3 del piano "dashboard a griglia libera": il builder a
        // griglia manda x/y/w/h del punto di drop invece di area/sorting
        // - additivo, il flusso legacy sopra (area/sorting, sempre
        // presenti quando il drop arriva dal drag&drop ad aree fisse)
        // resta identico quando questi campi non ci sono.
        if (Request::has('pos_x')) {
            $data['pos_x'] = (int) Request::get('pos_x');
            $data['pos_y'] = (int) Request::get('pos_y');
            $data['width'] = (int) Request::get('width');
            $data['height'] = (int) Request::get('height');
        }

        CRUDBooster::insert('cms_statistic_components', $data);

        return response()->json(compact('layout', 'componentID'));
    }

    /**
     * Dimensioni di partenza (larghezza x altezza in celle) per tipo di
     * widget: le stesse della palette di builder_grid.blade.php
     * (data-w/data-h), usate per importare un widget che non ha
     * larghezza/altezza salvate (es. arriva da una dashboard legacy ad aree).
     */
    private const IMPORT_DEFAULT_SIZES = [
        'smallbox' => [3, 2],
        'chartline_v2' => [6, 5],
        'chartbar_v2' => [6, 5],
        'chartarea_v2' => [6, 5],
        'table' => [6, 5],
        'panelarea' => [6, 4],
        'panelcustom' => [6, 5],
        'qlikwidget' => [6, 5],
    ];

    /**
     * Etichetta leggibile di un widget per il selettore "Importa": nome
     * configurato (config->name, altrimenti la colonna name se non e' il
     * 'Untitled' di un widget appena creato) oppure "Senza nome".
     */
    private function importWidgetName($row): string
    {
        $config = json_decode((string) $row->config);
        $name = trim((string) ($config->name ?? ''));

        if ($name === '' && $row->name !== null && $row->name !== 'Untitled') {
            $name = trim((string) $row->name);
        }

        return $name !== '' ? $name : trans('crudbooster.statistic_builder_import_unnamed');
    }

    private function importWidgetTypeLabel(string $componentName): string
    {
        $key = 'crudbooster.statistic_builder_widget_type_' . $componentName;
        $label = trans($key);

        return $label === $key ? $componentName : $label;
    }

    /**
     * Importa widget (1/3): dashboard da cui si puo' copiare, in ordine
     * alfabetico. Solo quelle con almeno un widget (nessuna scelta vuota);
     * la dashboard corrente e' inclusa (serve anche a duplicare un widget
     * nella stessa dashboard). Solo superadmin, come tutto il builder.
     */
    public function getImportSources()
    {
        if (!CRUDBooster::isSuperadmin()) {
            return response()->json(['dashboards' => []], 403);
        }

        $dashboards = DB::table('cms_statistics')
            ->whereIn('id', DB::table('cms_statistic_components')->select('id_cms_statistics'))
            ->get(['id', 'name'])
            ->map(function ($d) {
                return ['id' => (int) $d->id, 'name' => (string) $d->name];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return response()->json(['dashboards' => $dashboards]);
    }

    /**
     * Importa widget (2/3): widget di una dashboard, in ordine alfabetico,
     * con larghezza/altezza che avra' la copia.
     */
    public function getImportSourceWidgets($id_cms_statistics)
    {
        if (!CRUDBooster::isSuperadmin()) {
            return response()->json(['widgets' => []], 403);
        }

        $widgets = DB::table('cms_statistic_components')
            ->where('id_cms_statistics', $id_cms_statistics)
            ->get()
            ->map(function ($row) {
                $default = self::IMPORT_DEFAULT_SIZES[$row->component_name] ?? [6, 5];

                return [
                    'componentID' => $row->componentID,
                    'label' => $this->importWidgetName($row) . ' · ' . $this->importWidgetTypeLabel((string) $row->component_name),
                    'width' => (int) ($row->width ?: $default[0]),
                    'height' => (int) ($row->height ?: $default[1]),
                ];
            })
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return response()->json(['widgets' => $widgets]);
    }

    /**
     * Importa widget (3/3): COPIA INDIPENDENTE di un widget (stesso tipo,
     * nome e config; nuovo componentID) nella dashboard a griglia corrente,
     * alla posizione scelta dal builder. Dopo la copia i due widget non
     * hanno piu' nulla in comune: modificare l'uno non cambia l'altro.
     * Il config e' autosufficiente (anche il collegamento di un widget Qlik
     * al suo mashup sta in config->mashups), quindi basta copiarlo.
     * Solo superadmin: come postSaveComponent(), copia anche eventuale SQL
     * libera contenuta nel config.
     */
    public function postImportComponent()
    {
        if (!CRUDBooster::isSuperadmin()) {
            CRUDBooster::insertLog(trans("crudbooster.log_try_view", ['name' => 'Import Component', 'module' => 'Statistic']));
            return response()->json(['status' => false], 403);
        }

        $source = DB::table('cms_statistic_components')->where('componentID', (string) Request::get('source_componentid'))->first();
        $targetId = (int) Request::get('id_cms_statistics');
        $target = DB::table('cms_statistics')->where('id', $targetId)->first();

        if (!$source || !$target) {
            return response()->json(['status' => false], 404);
        }

        $name = $source->name;
        $config = $source->config;

        // Copia nella stessa dashboard: suffisso "(copia)" per distinguerla
        // dall'originale (sia nella colonna name sia nel config->name, che e'
        // quello mostrato dal widget).
        if ((int) $source->id_cms_statistics === $targetId) {
            $suffix = ' ' . trans('crudbooster.statistic_builder_import_copy_suffix');
            $decoded = json_decode((string) $source->config);
            if ($decoded && !empty($decoded->name)) {
                $decoded->name .= $suffix;
                $config = json_encode($decoded);
            }
            if (!empty($name) && $name !== 'Untitled') {
                $name .= $suffix;
            }
        }

        $default = self::IMPORT_DEFAULT_SIZES[$source->component_name] ?? [6, 5];
        // uniqid() oltre a md5(time()) del flusso di creazione: due widget
        // creati nello stesso secondo non devono avere lo stesso componentID.
        $componentID = md5(uniqid((string) $source->componentID, true));

        CRUDBooster::insert('cms_statistic_components', [
            'id_cms_statistics' => $targetId,
            'componentID' => $componentID,
            'component_name' => $source->component_name,
            'area_name' => null,
            'sorting' => null,
            'name' => $name,
            'config' => $config,
            'pos_x' => (int) Request::get('pos_x'),
            'pos_y' => (int) Request::get('pos_y'),
            'width' => (int) (Request::get('width') ?: ($source->width ?: $default[0])),
            'height' => (int) (Request::get('height') ?: ($source->height ?: $default[1])),
        ]);

        $sourceDashboard = DB::table('cms_statistics')->where('id', $source->id_cms_statistics)->value('name');
        CRUDBooster::insertLog(trans('crudbooster.log_statistic_widget_imported', [
            'widget' => $this->importWidgetName($source),
            'from' => $sourceDashboard,
            'to' => $target->name,
            'ip' => Request::server('REMOTE_ADDR'),
        ]));

        $row = DB::table('cms_statistic_components')->where('componentID', $componentID)->first();
        $payload = $this->renderComponentPayload($row);

        return response()->json([
            'componentID' => $componentID,
            'component_name' => $source->component_name,
            'layout' => $payload['layout'] ?? '',
        ]);
    }

    public function postUpdateAreaComponent()
    {
        // Come postAddComponent(): usata solo dal drag&drop dell'editor.
        if (!CRUDBooster::isSuperadmin()) {
            CRUDBooster::insertLog(trans("crudbooster.log_try_view", ['name' => 'Move Component', 'module' => 'Statistic']));
            return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        DB::table('cms_statistic_components')->where('componentID', Request::get('componentid'))->update([
            'sorting' => Request::get('sorting'),
            'area_name' => Request::get('areaname'),
        ]);

        return response()->json(['status' => true]);
    }

    /**
     * Fase 2/3 del piano "dashboard a griglia libera": equivalente di
     * postUpdateAreaComponent() ma per x/y/w/h - chiamata in autosave a
     * ogni drag/resize dal builder a griglia (stesso comportamento di
     * salvataggio immediato di oggi, solo su colonne diverse).
     */
    public function postUpdateComponentPosition()
    {
        if (!CRUDBooster::isSuperadmin()) {
            CRUDBooster::insertLog(trans("crudbooster.log_try_view", ['name' => 'Move Component (grid)', 'module' => 'Statistic']));
            return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        DB::table('cms_statistic_components')->where('componentID', Request::get('componentid'))->update([
            'pos_x' => (int) Request::get('x'),
            'pos_y' => (int) Request::get('y'),
            'width' => (int) Request::get('w'),
            'height' => (int) Request::get('h'),
        ]);

        return response()->json(['status' => true]);
    }

    public function getEditComponent($componentID)
    {
        $errors = [];
        $this->cbLoader();

        if (!CRUDBooster::isSuperadmin()) {
            CRUDBooster::insertLog(trans("crudbooster.log_try_view", ['name' => 'Edit Component', 'module' => 'Statistic']));
            return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        $component_row = CRUDBooster::first('cms_statistic_components', ['componentID' => $componentID]);

        $config = json_decode($component_row->config);


        if (!$config) {
            $errors[] = 'Widget configuration is empty. Please, add configuration for this widget.';

        }

        if (isset($config->mashups)) {
            $conf = QlikAppController::getConf($config->mashups);
        } else {
            $errors[] = 'Mashup is not selected. Please, select mashup for this widget.';
        }

        $command = 'configuration';

        //dd(QlikAppController::getMashups());
        $mashups = QlikAppController::getMashups();
        if (isset($config->mashups)) {
            
            $mashup = QlikAppController::getMashupFromCompID($componentID);
        } else {
            $mashup = null;
        }



        if (!$mashup && isset($config->mashups)) {
            $errors[] = 'Mashup is not selected. Please, select mashup for this widget.';
        }

        if (isset($conf) && isset($conf->id) && $conf->type == 'SAAS') {
            $token = HelpersQlikHelper::getJWTToken(CRUDBooster::myId(), $conf->id);
        } else {
            //$errors[] = 'Qlik configuration is empty or not selected.';
            $conf = null;
            $token = null;
        }

        //dd($mashups);

        return view('crudbooster::statistic_builder.components.' . $component_row->component_name, compact('command', 'componentID', 'config', 'mashups', 'conf', 'mashup', 'token', 'errors'));
    }

    /**
     * Fase 2/3 del piano "dashboard a griglia libera": popola le select
     * del pannello "Query guidata" in sidebar (dataset/metriche/
     * dimensioni/filtri disponibili) - vedi
     * _query_builder_fields.blade.php e DashboardDatasetRegistry.
     */
    public function getDatasetOptions()
    {
        if (!CRUDBooster::isSuperadmin()) {
            return response()->json(['error' => trans('crudbooster.denied_access')], 403);
        }

        return response()->json(['datasets' => DashboardDatasetRegistry::optionsForFrontend()]);
    }

    /**
     * Fase 2/3: anteprima live del "Query builder guidato" mentre si
     * configura un widget, senza salvare - stesse regole/whitelist di
     * DashboardDatasetRegistry::execute() usate poi al render vero del
     * widget (renderComponentPayload()).
     */
    public function postDatasetPreview()
    {
        if (!CRUDBooster::isSuperadmin()) {
            return response()->json(['error' => trans('crudbooster.denied_access')], 403);
        }

        try {
            $rows = DashboardDatasetRegistry::execute((string) Request::get('dataset'), [
                'metric' => Request::get('metric'),
                'group_by' => Request::get('group_by'),
                'filters' => (array) Request::get('filters', []),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['rows' => $rows]);
    }

    /**
     * Anteprima dell'"Elenco record" (widget Tabella) mentre lo si
     * configura, senza salvare: stesse regole di executeRows() usate al
     * render vero. Gira come superadmin, quindi mostra TUTTE le righe della
     * tabella senza lo scoping per tenant/gruppo che il widget applichera'
     * a ogni altro utente - il testo accanto a "Prova" lo dice.
     */
    public function postRecordsPreview()
    {
        if (!CRUDBooster::isSuperadmin()) {
            return response()->json(['error' => trans('crudbooster.denied_access')], 403);
        }

        try {
            $config = (object) [
                'columns' => (array) Request::get('columns', []),
                'filters' => (array) Request::get('filters', []),
                'order_by' => Request::get('order_by'),
                'order_dir' => Request::get('order_dir', 'asc'),
            ];
            $result = DashboardDatasetRegistry::executeRows((string) Request::get('dataset'), $this->recordsParams($config, 10));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    /**
     * Anteprima della SQL libera (scritta a mano, non "Query guidata")
     * mentre si configura un widget, senza salvare - stessa sostituzione
     * delle sessioni e stessa lettura del primo valore della prima riga
     * gia' usate al render vero (comando 'showFunction', chiave 'sql', in
     * ogni widget che la supporta) - vedi docs/refactoring/133-*.
     */
    public function postSqlPreview()
    {
        if (!CRUDBooster::isSuperadmin()) {
            return response()->json(['error' => trans('crudbooster.denied_access')], 403);
        }

        $sql = (string) Request::get('sql');

        try {
            foreach (Session::all() as $sessionKey => $sessionValue) {
                if (gettype($sessionValue) == gettype($sql)) {
                    $sql = str_replace('[' . $sessionKey . ']', $sessionValue, $sql);
                }
            }

            $rows = DB::select($sql);
            $value = $rows ? reset($rows[0]) : null;
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['rows' => [['label' => 'Risultato', 'value' => $value ?? 0]]]);
    }

    public function postSaveComponent()
    {
        // Unico punto di scrittura di 'config' (compresa la chiave 'sql' di
        // alcuni widget - Small Box/Table/Chart Area/Bar/Line/Qlik - che
        // getViewComponent() esegue letteralmente via DB::select() quando
        // il widget viene renderizzato). Senza questo controllo, QUALUNQUE
        // utente autenticato - non solo superadmin, a differenza di
        // getBuilder()/getEditComponent()/getDeleteComponent() in questo
        // stesso controller - poteva scrivere SQL arbitrario in un widget
        // di una dashboard esistente ed eseguirlo contro il DB reale con la
        // semplice visualizzazione di quella dashboard da parte di
        // chiunque (getViewComponent()/getListComponent() restano
        // volutamente SENZA questo controllo: sono usati anche dalla
        // pagina di visualizzazione normale delle dashboard - show.blade.php
        // - non solo dall'editor, quindi non vanno limitati al superadmin).
        if (!CRUDBooster::isSuperadmin()) {
            CRUDBooster::insertLog(trans("crudbooster.log_try_view", ['name' => 'Save Component', 'module' => 'Statistic']));
            return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        DB::table('cms_statistic_components')->where('componentID', Request::get('componentid'))->update([
            'name' => Request::get('name'),
            'config' => json_encode(Request::get('config')),
        ]);

        return response()->json(['status' => true]);
    }

    public function getDeleteComponent($id)
    {
        if (!CRUDBooster::isSuperadmin()) {
            CRUDBooster::insertLog(trans("crudbooster.log_try_view", ['name' => 'Delete Component', 'module' => 'Statistic']));
            return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        DB::table('cms_statistic_components')->where('componentID', $id)->delete();

        return response()->json(['status' => true]);
    }

    public function hook_before_add(&$arr)
    {
        $arr['slug'] = str_slug($arr['name']);

        // Decisione del piano "dashboard a griglia libera": la nuova
        // griglia e' l'opzione per le dashboard NUOVE (quelle esistenti
        // restano 'legacy_areas' finche' non convertite esplicitamente,
        // vedi getConvertToGrid()) - senza questa riga ogni dashboard
        // creata da qui in poi finiva comunque in 'legacy_areas' (il
        // default della colonna), rendendo la nuova griglia
        // raggiungibile solo convertendo dashboard gia' esistenti. Il
        // campo "Layout" del form resta (serve solo se poi si torna a
        // 'legacy_areas'), ma viene ignorato finche' la dashboard e' 'grid'.
        $arr['layout_mode'] = 'grid';
    }

    // Lo slug NON va rigenerato qui: viene usato come permalink pubblico
    // (menu, /statistic_builder/show/{slug}, link condivisi). Se lo
    // rigenerassimo dal nuovo nome ad ogni modifica, rinominare una
    // dashboard già pubblicata romperebbe tutti i link esistenti verso il
    // vecchio slug (vedi getShow()/getDashboard()).
    public function hook_before_edit(&$postdata, $id)
    {
    }

    public function mashup($componentID) {

            //LICENSE CHECK
            if (!LicenseHelper::isActiveQlik()) {
                return view('mashup_noqlik');
            }   



            $mashups = DB::table('cms_statistic_components')->where('componentID', $componentID)->first();

            $qlik_conf = null;
            $mashup = null;

            if ($mashups) {
                $mashups = json_decode($mashups->config);

                if (isset($mashups->mashups)) {
                    $mashup = DB::table('qlik_apps')->where('id', $mashups->mashups)->first();
                    if ($mashup) {
                        $qlik_conf_record = DB::table('qlik_confs')->where('id', $mashup->conf)->first();
                        if ($qlik_conf_record) {
                            $qlik_conf = $qlik_conf_record->id;
                        }
                    }
                }
            }

            if ($mashup && $qlik_conf) {
                return view('mashup', compact('componentID', 'mashup', 'qlik_conf', 'mashups'));
            }

    }

    /**
     * Widget Qlik in modalita' "foglio" (config qlik_mode = 'sheet'): pagina
     * minimale, aperta nell'iframe del widget, che fa il login a Qlik con il
     * JWT dell'utente e incorpora l'URL dell'item scelto (lo stesso che usano
     * le pagine degli item). Vedi docs/piano-qlik-sync-app-items.md.
     * Route: GET /mashup-sheet/{componentID} (login obbligatorio).
     */
    public function mashupSheet($componentID) {
        if (!LicenseHelper::isActiveQlik()) {
            return view('mashup_noqlik');
        }

        $comp = DB::table('cms_statistic_components')->where('componentID', $componentID)->first();
        $config = $comp ? json_decode($comp->config) : null;
        $item = ($config && !empty($config->item)) ? \App\QlikItem::find((int) $config->item) : null;
        $conf = $item ? HelpersQlikHelper::getConfFromItem($item->id) : null;

        if (!$item || !$conf || $conf->auth !== 'JWT') {
            return view('mashup_sheet', ['error' => trans('crudbooster.qlik_widget_sheet_missing')]);
        }

        $isSaas = $conf->type == 'SAAS';

        // Per On-Premise il token richiede un utente Qlik associato (senza, l'helper
        // farebbe un redirect con exit dentro l'iframe): lo si controlla prima.
        if (!$isSaas && !\App\Services\QlikSync\QlikDriverFactory::userHasQlikUser((int) CRUDBooster::myId(), (int) $conf->id)) {
            return view('mashup_sheet', ['error' => trans('crudbooster.qlik_widget_no_qlik_user')]);
        }

        $token = $isSaas
            ? HelpersQlikHelper::getJWTToken(CRUDBooster::myId(), $conf->id)
            : HelpersQlikHelper::getJWTTokenOP(CRUDBooster::myId(), $conf->id);

        if (empty($token)) {
            return view('mashup_sheet', ['error' => trans('crudbooster.qlik_jwt_generation_failed')]);
        }

        return view('mashup_sheet', [
            'error' => null,
            'title' => $item->title,
            'item_url' => htmlspecialchars_decode((string) $item->url),
            'tenant' => $conf->url,
            'prefix' => $conf->endpoint,
            'web_int_id' => $conf->web_int_id,
            'token' => $token,
            'js_login' => $isSaas ? 'js/qliksaas_login.js' : 'js/qlik_op_jwt_login.js',
        ]);
    }

    public function mashup_objects($mashup, $componentID, $objectid) {
            //LICENSE CHECK
            if (!LicenseHelper::isActiveQlik()) {
                return view('mashup_noqlik');
            }   
        $comp = DB::table('cms_statistic_components')->where('componentID', $componentID)->first();

        $config = new \stdClass();
        $config->mashups = 0;
        $config->object = 0;

        if ($comp) {
            $decodedConfig = json_decode($comp->config);
            if ($decodedConfig) {
                $config = $decodedConfig;
            }
        }

        if (!isset($config->mashups)) {
            $config->mashups = 0;
        }
        if (!isset($config->object)) {
            $config->object = 0;
        }

        $mashup = DB::table('qlik_apps')->where('id', $mashup)->first();
        $qlik_conf = null;

        if ($mashup) {
            $qlik_conf_record = DB::table('qlik_confs')->where('id', $mashup->conf)->first();
            if ($qlik_conf_record) {
                $qlik_conf = $qlik_conf_record->id;
            }
        }

        if ($mashup && $qlik_conf) {
            return view('mashup_objects', compact('componentID', 'mashup', 'qlik_conf', 'config'));
        }

    }


}
