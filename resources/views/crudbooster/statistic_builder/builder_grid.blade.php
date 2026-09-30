<!--
    Fase 3 del piano "dashboard a griglia libera" (vedi
    docs/piano-dashboard-griglia-libera.md, docs/refactoring/111-*).

    Vista standalone (NON estende crudbooster::statistic_builder.layout,
    che resta quella del builder legacy ad aree fisse - vedi builder.blade.php/
    index.blade.php, invariati): editor a griglia libera con palette
    widget a sinistra, griglia centrale (gridstack.js da CDN, stesso
    pattern di jQuery UI/Morris.js oggi - nessuna build step introdotta),
    sidebar destra con la configurazione del widget selezionato SEMPRE
    inline (mai in una modale, a differenza del builder legacy).

    Autosave immediato su ogni drag/resize/aggiunta (stesso comportamento
    del builder legacy), niente stato di bozza/bottone "Salva".
-->
<!doctype html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>{{ ($page_title) ? Session::get('appname').': '.strip_tags($page_title) : 'Admin Area' }}</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex,nofollow">
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@9/dist/gridstack.min.css">
<link rel="stylesheet" href="{{ asset('vendor/crudbooster/assets/select2/dist/css/select2.min.css') }}">
<script src="{{ asset('vendor/crudbooster/assets/adminlte/plugins/jQuery/jquery-2.2.3.min.js') }}"></script>
{{-- DataTables (core, senza l'integrazione Bootstrap: qui non c'e' Bootstrap):
     il widget Tabella inizializza la sua tabella con $.fn.DataTable, senza
     il quale in questa pagina mostrava tutte le righe in un colpo solo,
     ignorando "Record per pagina". Stessa libreria/versione usata da
     admin_template_plugins nella vista di sola lettura. --}}
<link rel="stylesheet" href="{{ asset('vendor/crudbooster/assets/adminlte/plugins/datatables/jquery.dataTables.min.css') }}">
<script src="{{ asset('vendor/crudbooster/assets/adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/gridstack@9/dist/gridstack-all.js"></script>
{{-- Morris.js + Raphael per i widget Chart Area/Line/Bar "classici" (non
     *_v2): dopo la conversione di una dashboard legacy compaiono qui, e
     senza queste librerie `new Morris.*` lancia ReferenceError (lo stesso
     caricamento di statistic_builder/index.blade.php, il builder legacy). --}}
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.css">
<script src="//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.min.js"></script>

<style>
    * { box-sizing: border-box; }
    body { margin: 0; font-family: 'Segoe UI', system-ui, sans-serif; background: #F4F5F7; color: #101828; }
    a { text-decoration: none; color: inherit; }

    .ch-topbar { height: 60px; flex-shrink: 0; background: #FFFFFF; border-bottom: 1px solid #E4E7EC; box-sizing: border-box; padding: 0 24px; display: flex; align-items: center; justify-content: space-between; }
    .ch-topbar-title { font-size: 16px; font-weight: 700; }
    .ch-topbar-sub { font-size: 11px; color: #667085; }
    .ch-autosave { font-size: 12px; font-weight: 600; color: #667085; display: flex; align-items: center; gap: 6px; }
    .ch-autosave .dot { width: 6px; height: 6px; border-radius: 999px; background: #12897F; }
    .ch-autosave.is-saving .dot { background: #DC6803; }
    .ch-btn { border: 1px solid #D0D5DD; background: #FFFFFF; color: #344054; border-radius: 8px; padding: 8px 14px; font-size: 13px; font-weight: 600; cursor: pointer; }

    .ch-body { display: flex; height: calc(100vh - 60px); }

    .ch-palette { width: 220px; flex-shrink: 0; background: #FFFFFF; border-right: 1px solid #E4E7EC; padding: 18px 14px; overflow-y: auto; }
    .ch-palette h4 { font-size: 11px; text-transform: uppercase; letter-spacing: .3px; color: #101828; margin: 0 0 12px; }
    .ch-palette-item { display: flex; align-items: center; gap: 10px; background: #FFFFFF; border: 1px solid #E4E7EC; border-radius: 10px; padding: 10px; margin-bottom: 10px; cursor: pointer; }
    .ch-palette-item:hover { border-color: #3B5BDB; background: #F8F9FF; }
    .ch-palette-item .icon { width: 32px; height: 32px; border-radius: 8px; background: #EEF2FF; color: #3B5BDB; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 15px; }
    .ch-palette-item .label { font-size: 12.5px; font-weight: 600; }

    .ch-canvas { flex-grow: 1; overflow: auto; padding: 20px; }
    /* Senza un'altezza minima esplicita, .grid-stack (il vero elemento
       "droppable" di gridstack) collassa a 0px quando la griglia e'
       vuota: la zona grigia visibile sotto/intorno ai widget e' solo il
       padding di .ch-canvas, non fa parte dell'area di drop - un drop
       li' non raggiunge mai il grid (bug reale, trovato trascinando un
       widget su una dashboard vuota: elementFromPoint() nella zona
       vuota restituiva .ch-canvas, mai #ch-grid-canvas). */
    .grid-stack { background: transparent; min-height: calc(100vh - 140px); }
    {{-- padding: 0 (docs/refactoring/142, stessa richiesta gia' fatta per
         la vista pubblica in 130): un padding qui creava un margine/
         doppio bordo visibile tra il bordo di ogni widget (es.
         .kpi-indicator-card, che ha gia' il proprio bordo/sfondo/ombra) e
         quello di questa card generica che lo contiene. show_grid.blade.php
         mantiene lo stesso valore (0) per restare allineato alla
         geometria di 137 (altezza contenuto = righe*cellHeight -
         2*margin, invariata: qui si toglie solo il padding interno, non
         il margin esterno tra un widget e l'altro). --}}
    .grid-stack-item-content { background: #FFFFFF; border: 1px solid #E4E7EC; border-radius: 12px; box-shadow: 0 1px 2px rgba(16,24,40,0.04); overflow: auto; padding: 0; scrollbar-width: thin; }
    /* Scrollbar sottile (Windows/Chrome disegna di default quella
       classica con le freccette su/giu', che da lontano si confondeva
       con un secondo controllo di ridimensionamento accanto a quello
       vero in basso a destra). */
    .grid-stack-item-content::-webkit-scrollbar { width: 6px; height: 6px; }
    .grid-stack-item-content::-webkit-scrollbar-thumb { background: #D0D5DD; border-radius: 999px; }
    .grid-stack-item-content::-webkit-scrollbar-button { display: none; }
    .grid-stack-item.ch-selected > .grid-stack-item-content { outline: 2px solid #3B5BDB; outline-offset: 2px; }
    /* Pulsanti "modifica"/"elimina" legacy (.action, dentro ogni
       .border-box): puntano a #btn-edit-component/#btn-delete-component,
       handler mai definiti in questa pagina (esistono solo in
       index.blade.php, il builder legacy) - qui sono ridondanti e non
       funzionanti, la modifica/eliminazione passa dalla sidebar/dal
       pulsante di .ch-widget-toolbar. */
    .border-box .action { display: none !important; }
    /* Widget "Modulo incorporato": l'intera card e' l'handle di drag e
       l'area di selezione (vedi handle: '.grid-stack-item-content' sotto) -
       un iframe (o il link "Apri a pagina intera") che catturasse il mouse
       renderebbe il widget impossibile da spostare/selezionare. Qui il
       modulo e' solo un'anteprima; e' interattivo nella dashboard. */
    .ch-module-frame, .ch-module-open { pointer-events: none; }
    /* Senza altezza esplicita, .border-box (wrapper comune a ogni widget)
       resta alto quanto il suo contenuto invece di riempire la card
       (.grid-stack-item-content): un widget piu' basso del contenitore
       lasciava spazio bianco sotto, invece di adattarsi alla dimensione
       scelta trascinando la card. */
    .border-box { height: 100%; box-sizing: border-box; }
    /* Dentro al bordo della card (non piu' a cavallo del bordo con top
       negativo): quel posizionamento veniva tagliato dallo scroll di
       .ch-canvas per i widget vicini al margine superiore, ed era troppo
       vicino alla maniglia di resize in basso a destra della card sopra
       nella stessa colonna. */
    .ch-widget-toolbar { position: absolute; top: 6px; right: 6px; background: #FFFFFF; border: 1px solid #E4E7EC; border-radius: 999px; box-shadow: 0 1px 3px rgba(16,24,40,0.1); display: none; gap: 2px; padding: 3px; z-index: 5; }
    .grid-stack-item:hover .ch-widget-toolbar, .grid-stack-item.ch-selected .ch-widget-toolbar { display: flex; }
    .ch-widget-toolbar button { border: none; background: none; width: 24px; height: 24px; border-radius: 999px; cursor: pointer; color: #667085; font-size: 14px; line-height: 1; }
    .ch-widget-toolbar button:hover { background: #FEF3F2; color: #B42318; }
    /* Maniglia di resize di gridstack (angolo in basso a destra): un po'
       piu' grande e con un indizio visivo permanente, invece della sola
       piccola freccetta di default - piu' facile da individuare e da
       afferrare con precisione. */
    .grid-stack-item > .ui-resizable-se { width: 18px !important; height: 18px !important; right: 2px !important; bottom: 2px !important; }
    .ch-widget-toolbar button:hover { background: #F2F4F7; }
    .ch-drop-placeholder { display: flex; align-items: center; justify-content: center; height: 100%; color: #98A2B3; font-size: 20px; }

    /* Stile minimo per i componenti dei widget (.small-box, .card) che
       nel builder legacy arrivavano gratis da AdminLTE (mai caricato
       qui, pagina standalone): senza, il widget KPI in particolare
       risultava illeggibile (nessun contenimento del colore di sfondo,
       niente spaziatura). Non e' un porting di AdminLTE, solo l'essenziale
       per rendere leggibile l'anteprima reale di un widget configurato. */
    .small-box { position: relative; border-radius: 10px; overflow: hidden; color: #FFFFFF; height: 100%; box-sizing: border-box; display: flex; flex-direction: column; justify-content: center; }
    .small-box .inner-box { padding: 12px 14px; }
    .small-box .inner-box h3 { font-size: 24px; font-weight: 700; margin: 0 0 2px; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .small-box .inner-box p { font-size: 12px; margin: 0; opacity: 0.9; }
    .small-box .icon { position: absolute; top: 4px; right: 8px; font-size: 32px; opacity: 0.35; line-height: 1; }
    .small-box .small-box-footer { display: block; padding: 5px; text-align: center; background: rgba(0,0,0,0.12); color: rgba(255,255,255,0.95); font-size: 11px; }
    .small-box .small-box-footer:hover { background: rgba(0,0,0,0.2); }
    .card { border: none; margin: 0; height: 100%; box-sizing: border-box; }
    .card-header { font-size: 12.5px; font-weight: 600; color: #101828; padding: 8px 4px; border-bottom: 1px solid #F2F4F7; margin-bottom: 6px; }
    .card-body { padding: 2px 4px; }

    .ch-sidebar { width: 340px; flex-shrink: 0; background: #FFFFFF; border-left: 1px solid #E4E7EC; padding: 20px; overflow-y: auto; }
    .ch-sidebar-empty { font-size: 13px; color: #98A2B3; text-align: center; margin-top: 40px; }
    .ch-sidebar form label { display: block; font-size: 12px; font-weight: 600; color: #344054; margin-bottom: 4px; }
    .ch-sidebar form .form-control { width: 100%; border: 1px solid #D0D5DD; border-radius: 8px; padding: 8px 10px; font-size: 13px; margin-bottom: 2px; }
    .ch-sidebar form .mb-3 { margin-bottom: 14px; }
    .ch-sidebar form .help-block { font-size: 11px; color: #98A2B3; margin-top: 4px; }
    .ch-sidebar-save { width: 100%; margin-top: 10px; background: #3B5BDB; color: #FFFFFF; border: none; border-radius: 8px; padding: 10px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; }
    .ch-sidebar-save:disabled { opacity: 0.7; cursor: default; }
    /* Uniforma i controlli che il browser/select2 renderizzano con un
       loro stile proprio (niente classe "form-control" applicabile) a
       quelli generati da noi (input di testo, textarea): stesso bordo,
       stessi angoli, stessa altezza. */
    .ch-sidebar form select.form-control { -webkit-appearance: none; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23667085' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; background-size: 14px; padding-right: 30px; }
    .ch-sidebar form input[type="color"].form-control { height: 36px; padding: 4px; cursor: pointer; }
    .ch-sidebar .select2-container { width: 100% !important; }
    .ch-sidebar .select2-container--default .select2-selection--single {
        height: 36px;
        border: 1px solid #D0D5DD;
        border-radius: 8px;
        display: flex;
        align-items: center;
        padding: 0 10px;
    }
    .ch-sidebar .select2-container--default .select2-selection--single .select2-selection__rendered { padding: 0; font-size: 13px; color: #101828; line-height: normal; }
    .ch-sidebar .select2-container--default .select2-selection--single .select2-selection__arrow { height: 34px; }

    /* Spinner generico (bottone "Salva", toolbar in eliminazione) - un
       cerchio che ruota, niente immagine/icona esterna da caricare. */
    .ch-spinner { display: inline-block; width: 13px; height: 13px; border: 2px solid rgba(255,255,255,0.4); border-top-color: #FFFFFF; border-radius: 50%; animation: ch-spin 0.6s linear infinite; flex-shrink: 0; }
    .ch-spinner.ch-spinner-dark { border-color: #E4E7EC; border-top-color: #3B5BDB; }
    @keyframes ch-spin { to { transform: rotate(360deg); } }

    /* Popup di conferma (elimina widget) al posto del confirm() nativo
       del browser. */
    .ch-modal-overlay { position: fixed; inset: 0; background: rgba(16,24,40,0.45); display: none; align-items: center; justify-content: center; z-index: 1000; }
    .ch-modal-overlay.is-open { display: flex; }
    .ch-modal { background: #FFFFFF; border-radius: 12px; box-shadow: 0 8px 24px rgba(16,24,40,0.2); width: 320px; padding: 20px; }
    .ch-modal-title { font-size: 15px; font-weight: 700; color: #101828; margin-bottom: 8px; }
    .ch-modal-body { font-size: 13px; color: #667085; margin-bottom: 18px; line-height: 1.4; }
    .ch-modal-actions { display: flex; justify-content: flex-end; gap: 8px; }
    .ch-modal-actions .ch-btn-danger { background: #D92D20; color: #FFFFFF; border-color: #D92D20; }
    .ch-btn-primary { background: #3B5BDB; color: #FFFFFF; border-color: #3B5BDB; }
    .ch-btn-primary:hover { background: #324ec0; border-color: #324ec0; }
    .ch-btn:disabled { opacity: .5; cursor: not-allowed; }
    /* Modale "Copia da" (Importa widget) */
    .ch-palette-import { margin-top: 18px; border-style: dashed; }
    .ch-import-modal { width: 420px; max-width: calc(100vw - 32px); }
    .ch-import-field { margin-bottom: 14px; }
    .ch-import-field label { display: block; font-size: 12px; font-weight: 600; color: #344054; margin-bottom: 5px; }
    .ch-import-message { font-size: 12.5px; color: #B42318; margin-bottom: 14px; }
    .ch-import-modal .select2-container { width: 100% !important; }
    .ch-import-modal .select2-container--default .select2-selection--single { height: 38px; border: 1px solid #D0D5DD; border-radius: 8px; }
    .ch-import-modal .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px; padding-left: 12px; font-size: 13px; color: #101828; }
    .ch-import-modal .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
    .ch-import-modal .select2-container--default.select2-container--disabled .select2-selection--single { background: #F2F4F7; }
</style>
@include('crudbooster::statistic_builder.components._widget_card_style')
</head>
<body>

<header class="ch-topbar">
    <div>
        <div class="ch-topbar-title">{{ $page_title }}</div>
        <div class="ch-topbar-sub">Modalità builder · griglia libera · visibile solo agli amministratori</div>
    </div>
    <div style="display:flex; align-items:center; gap:16px;">
        <span class="ch-autosave" id="ch-autosave-indicator"><span class="dot"></span> Salvato</span>
        <a class="ch-btn" href="{{ CRUDBooster::mainpath() }}">Esci</a>
    </div>
</header>

<div class="ch-body">
    <aside class="ch-palette">
        <h4>Widget disponibili</h4>
        <div class="ch-palette-item" data-component="smallbox" data-w="3" data-h="2">
            <span class="icon">#</span>
            <span class="label">Indicatore KPI</span>
        </div>
        <div class="ch-palette-item" data-component="chartline_v2" data-w="6" data-h="5">
            <span class="icon">~</span>
            <span class="label">Grafico a linee</span>
        </div>
        <div class="ch-palette-item" data-component="chartbar_v2" data-w="6" data-h="5">
            <span class="icon">|||</span>
            <span class="label">Grafico a barre</span>
        </div>
        <div class="ch-palette-item" data-component="chartarea_v2" data-w="6" data-h="5">
            <span class="icon">◮</span>
            <span class="label">Grafico ad area</span>
        </div>
        <div class="ch-palette-item" data-component="table" data-w="6" data-h="5">
            <span class="icon">▤</span>
            <span class="label">Tabella</span>
        </div>
        <div class="ch-palette-item" data-component="panelarea" data-w="6" data-h="4">
            <span class="icon">¶</span>
            <span class="label">Pannello di testo</span>
        </div>
        <div class="ch-palette-item" data-component="panelcustom" data-w="6" data-h="5">
            <span class="icon">▦</span>
            <span class="label">Modulo incorporato</span>
        </div>
        @if(App\Helpers\LicenseHelper::isActiveQlik())
        <div class="ch-palette-item" data-component="qlikwidget" data-w="6" data-h="5">
            <span class="icon">Q</span>
            <span class="label">{{ trans('crudbooster.qlik_widget') }}</span>
        </div>
        @endif
        {{-- Importa: copia indipendente di un widget di un'altra dashboard.
             Stessa grafica di un tipo di widget ma SENZA data-component: il
             click sui tipi (sotto) agisce solo su .ch-palette-item[data-component]. --}}
        <div class="ch-palette-item ch-palette-import" id="ch-import-open">
            <span class="icon">↓</span>
            <span class="label">{{ trans('crudbooster.statistic_builder_import_tile') }}</span>
        </div>
    </aside>

    <main class="ch-canvas">
        <div class="grid-stack" id="ch-grid-canvas"></div>
    </main>

    <aside class="ch-sidebar" id="ch-widget-config-panel">
        <div class="ch-sidebar-empty">Seleziona un widget nella griglia per configurarlo, oppure clicca un tipo di widget nella libreria a sinistra per aggiungerlo.</div>
    </aside>
</div>

<div class="ch-modal-overlay" id="ch-confirm-modal">
    <div class="ch-modal">
        <div class="ch-modal-title" id="ch-confirm-title">Confermi?</div>
        <div class="ch-modal-body" id="ch-confirm-body"></div>
        <div class="ch-modal-actions">
            <button type="button" class="ch-btn" id="ch-confirm-cancel">Annulla</button>
            <button type="button" class="ch-btn ch-btn-danger" id="ch-confirm-ok">Elimina</button>
        </div>
    </div>
</div>

{{-- Modale "Copia da": due select ricercabili (select2, caricato a richiesta
     come per la configurazione dei widget) - prima la dashboard, poi il
     widget da copiare. --}}
<div class="ch-modal-overlay" id="ch-import-modal">
    <div class="ch-modal ch-import-modal">
        <div class="ch-modal-title">{{ trans('crudbooster.statistic_builder_import_title') }}</div>
        <div class="ch-import-field">
            <label for="ch-import-dashboard">{{ trans('crudbooster.statistic_builder_import_dashboard_label') }}</label>
            <select id="ch-import-dashboard"><option value=""></option></select>
        </div>
        <div class="ch-import-field">
            <label for="ch-import-widget">{{ trans('crudbooster.statistic_builder_import_widget_label') }}</label>
            <select id="ch-import-widget" disabled><option value=""></option></select>
        </div>
        <div class="ch-import-message" id="ch-import-message" hidden></div>
        <div class="ch-modal-actions">
            <button type="button" class="ch-btn" id="ch-import-cancel">{{ trans('crudbooster.statistic_builder_import_cancel') }}</button>
            <button type="button" class="ch-btn ch-btn-primary" id="ch-import-ok" disabled>{{ trans('crudbooster.statistic_builder_import_button') }}</button>
        </div>
    </div>
</div>

<script>
(function () {
    var idCmsStatistics = {{ (int) $id_cms_statistics }};
    var basePath = "{{ CRUDBooster::mainpath() }}";

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    var $autosave = $('#ch-autosave-indicator');
    function markSaving() { $autosave.addClass('is-saving').html('<span class="dot"></span> Salvataggio...'); }
    function markSaved() { $autosave.removeClass('is-saving').html('<span class="dot"></span> Salvato'); }

    // Popup di conferma al posto di confirm() nativo del browser.
    var $confirmModal = $('#ch-confirm-modal');
    function askConfirm(title, body, onConfirm) {
        $('#ch-confirm-title').text(title);
        $('#ch-confirm-body').text(body);
        $confirmModal.addClass('is-open');
        $('#ch-confirm-ok').off('click').on('click', function () {
            $confirmModal.removeClass('is-open');
            onConfirm();
        });
    }
    $('#ch-confirm-cancel').on('click', function () { $confirmModal.removeClass('is-open'); });
    $confirmModal.on('click', function (e) { if (e.target === this) { $confirmModal.removeClass('is-open'); } });

    var grid = GridStack.init({
        cellHeight: 40,
        margin: 8,
        column: 12,
        float: true,
        acceptWidgets: true,
        handle: '.grid-stack-item-content',
        // Solo l'angolo in basso a destra (default di gridstack: piu'
        // maniglie contemporaneamente - est/sud-est/sud - che finivano
        // ammassate proprio dove sta anche la toolbar in alto a destra,
        // scomode da afferrare con precisione).
        resizable: { handles: 'se' },
    }, '#ch-grid-canvas');

    // Autosave: un solo evento 'change' copre sia il drag sia il resize
    // (gridstack lo emette a fine gesto, non ad ogni frame) - stesso
    // comportamento "salva subito" del builder legacy, solo su x/y/w/h
    // invece di area/sorting (vedi postUpdateComponentPosition()).
    grid.on('change', function (event, items) {
        (items || []).forEach(function (item) {
            var componentID = $(item.el).attr('data-component-id');
            if (!componentID) { return; }
            markSaving();
            $.post(basePath + '/update-component-position', {
                componentid: componentID,
                x: item.x, y: item.y, w: item.w, h: item.h
            }, markSaved).fail(markSaved);
        });
    });

    function addExistingWidget(component) {
        // Il contenuto NON va passato come opzione "content" di
        // grid.addWidget(): gridstack lo inserisce con innerHTML diretto,
        // che non esegue gli <script> incorporati - i grafici ApexCharts
        // (il loro <script> chiama new ApexCharts(...)) restavano quindi
        // vuoti a ogni ricaricamento della pagina. jQuery .html(), usato
        // qui sotto, li esegue correttamente (stesso motivo per cui
        // funziona gia' nella sidebar - vedi selectWidget()).
        var el = grid.addWidget({ x: component.pos_x, y: component.pos_y, w: component.width, h: component.height });
        $(el).attr('data-component-id', component.componentID);
        $(el).attr('data-component-name', component.component_name);

        var content = '<div class="ch-widget-toolbar">' +
            '<button type="button" class="ch-btn-delete" title="Elimina">×</button>' +
            '</div>' + component.layout;
        $(el).find('.grid-stack-item-content').html(content);
    }

    function loadComponents() {
        $.get(basePath + '/list-components-grid/' + idCmsStatistics, function (response) {
            // Chiamata anche dopo ogni salvataggio (per rileggere il
            // valore aggiornato del widget appena configurato): svuota
            // prima la griglia, altrimenti i widget gia' presenti si
            // duplicherebbero ad ogni salvataggio invece di limitarsi ad
            // aggiornarsi.
            grid.removeAll();
            // try/catch per widget: lo script incorporato di un widget
            // (es. un errore JS del suo grafico) lancia durante .html() e
            // senza questo interrompeva il forEach, lasciando senza
            // riquadro tutti i widget successivi.
            (response.components || []).forEach(function (component) {
                try {
                    addExistingWidget(component);
                } catch (e) {
                    if (window.console) { console.error('Widget ' + component.componentID + ' non renderizzato:', e); }
                }
            });
        });
    }

    function selectWidget($item) {
        $('.grid-stack-item').removeClass('ch-selected');
        $item.addClass('ch-selected');

        var componentID = $item.attr('data-component-id');
        var $panel = $('#ch-widget-config-panel');
        $panel.html('<i class="fa fa-spin fa-spinner"></i> Caricamento...');

        $.get(basePath + '/edit-component/' + componentID, function (response) {
            // Iniettato cosi' com'e' (stesso HTML che il builder legacy
            // mette in #modal-statistic): gli si assegna solo un id e un
            // bottone di submit, che li' arrivavano dal markup esterno
            // della modale - qui in sidebar non c'e' modale, quindi vanno
            // aggiunti dopo l'injection invece di manipolare la stringa.
            $panel.html(response);
            $panel.find('form').attr('id', 'ch-widget-config-form')
                .append('<button type="submit" class="ch-sidebar-save">Salva</button>');
        });
    }

    // NON un semplice 'click': '.grid-stack-item-content' e' anche
    // l'handle di drag di gridstack (vedi handle: '.grid-stack-item-content'
    // sopra), che intercetta mousedown/mouseup e impedisce al browser di
    // sintetizzare il click successivo - verificato in manuale, il
    // click non arriva mai in quel caso. Distinguo io stesso un vero
    // click da un drag: mousedown+mouseup nello stesso punto (pochi px
    // di tolleranza) e in tempi brevi.
    var pressStart = null;
    $(document).on('mousedown', '.grid-stack-item-content', function (e) {
        if ($(e.target).closest('.ch-widget-toolbar').length) { return; }
        pressStart = { x: e.pageX, y: e.pageY, time: Date.now(), el: this };
    });
    $(document).on('mouseup', '.grid-stack-item-content', function (e) {
        if (!pressStart || pressStart.el !== this) { return; }
        var moved = Math.abs(e.pageX - pressStart.x) + Math.abs(e.pageY - pressStart.y);
        var elapsed = Date.now() - pressStart.time;
        pressStart = null;
        if (moved < 6 && elapsed < 500) {
            selectWidget($(this).closest('.grid-stack-item'));
        }
    });

    $(document).on('click', '.ch-btn-delete', function (e) {
        e.stopPropagation();
        var $item = $(this).closest('.grid-stack-item');
        var componentID = $item.attr('data-component-id');
        askConfirm('Eliminare questo widget?', 'L\'operazione non può essere annullata.', function () {
            // Riscontro visivo che l'eliminazione e' in corso: c'e' un
            // breve intervallo tra la conferma e la scomparsa del widget
            // (round-trip col server), altrimenti sembra che il click
            // sia stato ignorato.
            $item.css('opacity', '0.5').find('.ch-widget-toolbar').html('<span class="ch-spinner ch-spinner-dark"></span>').css('display', 'flex');
            $.get(basePath + '/delete-component/' + componentID)
                .done(function () {
                    grid.removeWidget($item[0]);
                    $('#ch-widget-config-panel').html('<div class="ch-sidebar-empty">Seleziona un widget nella griglia per configurarlo.</div>');
                })
                .fail(function () {
                    $item.css('opacity', '1');
                });
        });
    });

    // Salvataggio della configurazione - stesso endpoint 'save-component'
    // del builder legacy, solo iniettato nella sidebar invece che in
    // #modal-statistic.
    $(document).on('submit', '#ch-widget-config-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $saveBtn = $form.find('.ch-sidebar-save');
        var originalHtml = $saveBtn.html();
        $saveBtn.prop('disabled', true).html('<span class="ch-spinner"></span> Salvataggio...');
        markSaving();
        $.post(basePath + '/save-component', $form.serialize())
            .done(function () {
                markSaved();
                loadComponents();
            })
            .fail(markSaved)
            .always(function () {
                $saveBtn.prop('disabled', false).html(originalHtml);
            });
    });

    // Aggiunta widget: un CLICK sulla libreria, non un trascinamento.
    // GridStack.setupDragIn() (drag da un elemento esterno alla griglia)
    // e' risultato inaffidabile in prova - a differenza dello
    // spostamento/ridimensionamento di un widget gia' in griglia (che
    // usa il drag interno di gridstack, verificato funzionante), il
    // trascinamento da fuori non completava mai il drop (vedi
    // docs/refactoring/116-*). Click sul tipo di widget -> lo si
    // aggiunge subito in griglia (gridstack sceglie la prima posizione
    // libera, "float": true), poi si sposta/ridimensiona come qualunque
    // altro widget.
    $(document).on('click', '.ch-palette-item[data-component]', function () {
        var component = $(this).attr('data-component');
        var w = parseInt($(this).attr('data-w'), 10) || 3;
        var h = parseInt($(this).attr('data-h'), 10) || 2;

        markSaving();

        var el = grid.addWidget({
            w: w, h: h,
            content: '<div class="ch-drop-placeholder"><i class="fa fa-spin fa-spinner"></i></div>',
        });
        var node = el.gridstackNode;

        $.post(basePath + '/add-component', {
            component_name: component,
            id_cms_statistics: idCmsStatistics,
            pos_x: node.x, pos_y: node.y,
            width: node.w, height: node.h,
        }, function (response) {
            markSaved();
            $(el).find('.grid-stack-item-content').html(
                '<div class="ch-widget-toolbar"><button type="button" class="ch-btn-delete" title="Elimina">×</button></div>' + response.layout
            );
            $(el).attr('data-component-id', response.componentID);
            $(el).attr('data-component-name', component);
            // Selezione automatica: chi aggiunge un widget si ritrova
            // subito la sidebar aperta pronta per configurarlo, invece
            // di doverci ricliccare sopra.
            selectWidget($(el));
        }).fail(function () {
            markSaved();
            grid.removeWidget(el);
        });
    });

    // ---- Importa: copia INDIPENDENTE di un widget di un'altra dashboard ----
    // Modale "Copia da": prima si sceglie la dashboard, poi il widget (due
    // select2 ricercabili). Il widget copiato compare in griglia come uno
    // appena aggiunto dalla palette (stesso flusso di sopra), gia' selezionato.
    var IMPORT_TEXT = {!! json_encode([
        'dashboardPlaceholder' => trans('crudbooster.statistic_builder_import_dashboard_placeholder'),
        'widgetPlaceholder' => trans('crudbooster.statistic_builder_import_widget_placeholder'),
        'noResults' => trans('crudbooster.statistic_builder_import_no_results'),
        'noWidgets' => trans('crudbooster.statistic_builder_import_no_widgets'),
        'error' => trans('crudbooster.statistic_builder_import_error'),
    ]) !!};

    // Stessa definizione di _query_builder_fields.blade.php (chi la chiama
    // per primo carica select2 una volta sola): qui serve prima che un
    // pannello di configurazione qualunque sia stato aperto.
    if (!window.__chEnsureSelect2) {
        window.__chEnsureSelect2 = function (callback) {
            if (window.jQuery && $.fn.select2) {
                callback();
                return;
            }
            if (window.__chSelect2Callbacks) {
                window.__chSelect2Callbacks.push(callback);
                return;
            }
            window.__chSelect2Callbacks = [callback];
            var script = document.createElement('script');
            script.src = '{{ asset("vendor/crudbooster/assets/select2/dist/js/select2.full.js") }}';
            script.onload = function () {
                window.__chSelect2Callbacks.forEach(function (cb) { cb(); });
                window.__chSelect2Callbacks = null;
            };
            document.body.appendChild(script);
        };
    }

    var $importModal = $('#ch-import-modal');
    var $importDashboard = $('#ch-import-dashboard');
    var $importWidget = $('#ch-import-widget');
    var $importOk = $('#ch-import-ok');
    var $importMessage = $('#ch-import-message');
    var importWidgets = {};

    function importMessage(text) {
        if (text) { $importMessage.text(text).show(); } else { $importMessage.hide().text(''); }
    }

    // Svuota la select lasciando solo l'opzione vuota (necessaria al
    // placeholder di select2) e la riempie con {value, label}.
    function fillImportSelect($select, items, valueKey, labelKey) {
        $select.empty().append('<option value=""></option>');
        (items || []).forEach(function (item) {
            $select.append($('<option></option>').val(item[valueKey]).text(item[labelKey]));
        });
        $select.val('').trigger('change.select2');
    }

    function setupImportSelect($select, placeholder) {
        if ($select.hasClass('select2-hidden-accessible')) { $select.select2('destroy'); }
        $select.select2({
            placeholder: placeholder,
            width: '100%',
            dropdownParent: $importModal.find('.ch-modal'),
            language: { noResults: function () { return IMPORT_TEXT.noResults; } }
        });
    }

    function closeImportModal() { $importModal.removeClass('is-open'); }

    $('#ch-import-open').on('click', function () {
        importMessage('');
        importWidgets = {};
        $importOk.prop('disabled', true);
        window.__chEnsureSelect2(function () {
            setupImportSelect($importDashboard, IMPORT_TEXT.dashboardPlaceholder);
            setupImportSelect($importWidget, IMPORT_TEXT.widgetPlaceholder);
            fillImportSelect($importDashboard, [], 'id', 'name');
            fillImportSelect($importWidget, [], 'componentID', 'label');
            $importWidget.prop('disabled', true);
            $importModal.addClass('is-open');
            $.get(basePath + '/import-sources', function (response) {
                fillImportSelect($importDashboard, response.dashboards, 'id', 'name');
            }).fail(function () { importMessage(IMPORT_TEXT.error); });
        });
    });

    $importDashboard.on('change', function () {
        var dashboardId = $(this).val();
        importWidgets = {};
        importMessage('');
        $importOk.prop('disabled', true);
        fillImportSelect($importWidget, [], 'componentID', 'label');
        $importWidget.prop('disabled', true);
        if (!dashboardId) { return; }

        $.get(basePath + '/import-source-widgets/' + dashboardId, function (response) {
            var widgets = response.widgets || [];
            if (!widgets.length) { importMessage(IMPORT_TEXT.noWidgets); return; }
            widgets.forEach(function (w) { importWidgets[w.componentID] = w; });
            fillImportSelect($importWidget, widgets, 'componentID', 'label');
            $importWidget.prop('disabled', false);
        }).fail(function () { importMessage(IMPORT_TEXT.error); });
    });

    $importWidget.on('change', function () {
        $importOk.prop('disabled', !$(this).val());
    });

    $('#ch-import-cancel').on('click', closeImportModal);
    $importModal.on('mousedown', function (e) { if (e.target === this) { closeImportModal(); } });

    $importOk.on('click', function () {
        var sourceId = $importWidget.val();
        var meta = importWidgets[sourceId];
        if (!sourceId || !meta) { return; }

        closeImportModal();
        markSaving();

        var el = grid.addWidget({
            w: meta.width, h: meta.height,
            content: '<div class="ch-drop-placeholder"><i class="fa fa-spin fa-spinner"></i></div>',
        });
        var node = el.gridstackNode;

        $.post(basePath + '/import-component', {
            source_componentid: sourceId,
            id_cms_statistics: idCmsStatistics,
            pos_x: node.x, pos_y: node.y,
            width: node.w, height: node.h,
        }, function (response) {
            markSaved();
            $(el).find('.grid-stack-item-content').html(
                '<div class="ch-widget-toolbar"><button type="button" class="ch-btn-delete" title="Elimina">×</button></div>' + response.layout
            );
            $(el).attr('data-component-id', response.componentID);
            $(el).attr('data-component-name', response.component_name);
            selectWidget($(el));
        }).fail(function () {
            markSaved();
            grid.removeWidget(el);
            importMessage(IMPORT_TEXT.error);
            $importModal.addClass('is-open');
        });
    });

    loadComponents();
})();
</script>
</body>
</html>
