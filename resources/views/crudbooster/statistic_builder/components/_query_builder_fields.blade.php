{{--
    Fase 3 del piano "dashboard a griglia libera" (vedi
    docs/piano-dashboard-griglia-libera.md, docs/refactoring/111-*).
    Filtri estesi in docs/refactoring/138-* (piu' filtri in AND, ciascuno
    con un operatore adatto al tipo della colonna, oltre alla sola
    uguaglianza) e metriche estese nello stesso intervento (Somma/Media/
    Minimo/Massimo per colonna numerica, non solo "Conteggio righe").
    Raggruppamento aggiunto in docs/refactoring/147-* per i widget grafico
    (chartline_v2/chartbar_v2): era gia' supportato da
    DashboardDatasetRegistry::execute() (parametro group_by, righe multiple
    invece del solo valore aggregato) ma senza alcuna UI - tolta apposta in
    133 perche' il solo Indicatore KPI (un valore, non una serie) non ne
    aveva bisogno.

    Pannello "Query guidata" (dataset/metrica/raggruppamento/filtri +
    anteprima live) e JS condiviso che pilota anche il toggle di
    _query_mode_toggle.blade.php - richiede che il blade del widget abbia
    gia' incluso quel partial e avvolto il proprio campo SQL in
    <div id="ch-panel-sql-{{ $componentID }}">.

    Variabile opzionale $groupBySupported (bool, default false): un
    widget che produce piu' punti (un grafico) la passa a true per
    mostrare la select "Raggruppa per"; l'Indicatore KPI (un solo valore)
    non la passa, la select resta assente dal markup - non solo nascosta,
    cosi' non finisce mai nel form da salvare per quel widget.

    $fieldPrefix (default 'config') e $scopeId (default $componentID):
    docs/refactoring/152-*, permettono di includere questo partial piu'
    volte nello stesso widget (una per "linea" extra del Grafico a
    linee) senza id DOM ne' name= di campo in collisione - con i default
    invariati gli usi esistenti (smallbox, prima sorgente di
    chartline_v2) restano identici.
--}}
<?php
    $currentMode = $config->mode ?? 'sql';
    $groupBySupported = $groupBySupported ?? false;
    $fieldPrefix = $fieldPrefix ?? 'config';
    $scopeId = $scopeId ?? $componentID;
    // Stessa normalizzazione usata da DashboardDatasetRegistry::execute()
    // (formato nuovo: lista di {column,operator,value} - formato vecchio,
    // ancora presente sui widget salvati prima di 138: mappa
    // {colonna: valore}, sempre uguaglianza) cosi' il pannello precompila
    // le righe filtro allo stesso modo in cui la query viene poi
    // eseguita davvero - un solo punto che decide come interpretare
    // config->filters.
    $existingFilters = \App\Dashboards\DashboardDatasetRegistry::normalizeFilters($config->filters ?? []);
?>
{{-- CSS di select2, usato qui sotto per il Dataset (docs/refactoring/149):
     prima viveva solo dentro smallbox.blade.php (per il picker delle
     icone, non specifico a questo file), quindi un widget senza icone
     come il Grafico a linee (147) inizializzava select2 via JS senza mai
     caricarne il CSS - risultato un campo Dataset non stilizzato/rotto
     (segnalato dall'utente come "si vede due volte e non e' ricercabile").
     Spostato qui, dove il select2 del Dataset viene davvero creato,
     cosi' ogni widget che include questo partial lo carica una volta
     sola a prescindere da cos'altro include. --}}
<link rel='stylesheet' href='{{ asset("vendor/crudbooster/assets/select2/dist/css/select2.min.css") }}' />
<div class="ch-mode-panel" data-mode-panel="builder" id="ch-panel-builder-{{ $scopeId }}" style="{{ $currentMode === 'builder' ? '' : 'display:none' }}">
    <div class="mb-3 row">
        <label>Dataset</label>
        <select class="form-control ch-builder-dataset" name="{{ $fieldPrefix }}[dataset]" data-current="{{ @$config->dataset }}" style="width:100%"></select>
    </div>
    <div class="mb-3 row">
        <label>Funzione</label>
        <select class="form-control ch-builder-metric-function" style="width:100%"></select>
    </div>
    <div class="mb-3 row ch-builder-metric-column-row" style="display:none;">
        <label>Colonna</label>
        <select class="form-control ch-builder-metric-column" style="width:100%"></select>
    </div>
    {{-- Funzione+Colonna sono solo UI: il valore che viene davvero
         salvato resta questo campo nascosto, ricomposto da entrambe
         (composeMetricValue()) nello stesso formato di sempre ("count" o
         "aggregazione:colonna", docs/refactoring/140) - zero cambi al
         formato che DashboardDatasetRegistry::execute() si aspetta. --}}
    <input type="hidden" class="ch-builder-metric" name="{{ $fieldPrefix }}[metric]" data-current="{{ @$config->metric }}" />
    @if($groupBySupported)
    <div class="mb-3 row">
        <label>Raggruppa per (opzionale)</label>
        <select class="form-control ch-builder-group-by-column" style="width:100%"></select>
        <div class="help-block">Senza raggruppamento il widget mostra un unico valore aggregato invece di una serie di punti.</div>
    </div>
    <div class="mb-3 row ch-builder-group-by-format-row" style="display:none;">
        <label>Formato</label>
        <select class="form-control ch-builder-group-by-format" style="width:100%"></select>
    </div>
    {{-- Colonna+Formato sono solo UI (docs/refactoring/151, stessa idea
         di Funzione+Colonna in 140): il valore che viene davvero salvato
         resta questo campo nascosto, ricomposto da entrambe
         (composeGroupByValue()) nello stesso formato di sempre ("colonna"
         o "colonna:formato") - zero cambi a DashboardDatasetRegistry::execute(). --}}
    <input type="hidden" class="ch-builder-group-by" name="{{ $fieldPrefix }}[group_by]" data-current="{{ @$config->group_by }}" />
    @endif
    <div class="mb-3 row">
        <label>Filtri (opzionale)</label>
        {{-- Righe costruite via JS (addFilterRow()): ciascuna e' Colonna
             + Operatore (adatto al tipo della colonna scelta) + Valore,
             tutte in AND. name= assegnato dinamicamente da
             reindexFilterRows() come "config[filters][N][column|operator|value]"
             - la colonna/il tipo non si conoscono finche' l'utente non
             sceglie, e il numero di righe e' variabile (nessun limite),
             quindi non si puo' scrivere un name= statico qui. --}}
        <div class="ch-builder-filters" id="ch-builder-filters-{{ $scopeId }}"></div>
        <button type="button" class="ch-btn ch-builder-add-filter" style="margin-top:2px;">+ Aggiungi filtro</button>
    </div>
    <button type="button" class="ch-btn ch-builder-preview-btn">
        Prova
    </button>
    <div class="ch-builder-preview-result" style="margin-top:10px; font-size:13px;"></div>
</div>

<style>
    {{-- L'operatore ha una larghezza fissa (docs/refactoring/145) invece
         di dividersi lo spazio con `flex: 1`: opzioni come "negli ultimi
         N giorni" restavano tagliate/illeggibili in una colonna troppo
         stretta, non bastava allargare il popup da solo perche' Colonna
         e Valore (flex: 2 ciascuno) si prendevano comunque la parte
         maggiore dello spazio in piu'. --}}
    .ch-builder-filter-row { display: flex; gap: 6px; align-items: center; margin-bottom: 6px; }
    .ch-builder-filter-row .ch-builder-filter-key { flex: 1; min-width: 0; }
    .ch-builder-filter-row .ch-builder-filter-operator { flex: 0 0 160px; }
    .ch-builder-filter-row .ch-builder-filter-value { flex: 1; min-width: 0; }
    .ch-builder-filter-remove {
        border: none; background: none; color: #98A2B3; cursor: pointer;
        font-size: 18px; line-height: 1; padding: 0 4px; flex-shrink: 0;
    }
    .ch-builder-filter-remove:hover { color: #D92D20; }
</style>

<script>
// Caricamento pigro condiviso di select2 (usato qui per il Dataset e in
// smallbox.blade.php per l'icona): una sola definizione, chiunque la
// chiami per primo lo carica una volta sola, l'altro aspetta lo stesso
// caricamento invece di iniettare un secondo <script src> in corsa con
// il primo (due select2.js caricati in corsa avrebbero potuto finire
// entrambi ad inizializzare lo stesso plugin due volte).
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

(function () {
    // scopeId (non componentID) costruisce i selettori DOM: con piu'
    // istanze di questo partial nello stesso widget (una per "linea"
    // extra, docs/refactoring/152) componentID sarebbe lo stesso per
    // tutte - scopeId e' invece unico per istanza (default componentID
    // per gli usi esistenti, dove le due cose coincidono).
    var scopeId = {!! json_encode($scopeId) !!};
    var fieldPrefix = {!! json_encode($fieldPrefix) !!};
    var existingFilters = {!! json_encode($existingFilters) !!};
    var $sqlPanel = $('#ch-panel-sql-' + scopeId);
    var $builderPanel = $('#ch-panel-builder-' + scopeId);
    var $form = $builderPanel.closest('form');

    $('#ch-mode-toggle-' + scopeId + ' .ch-mode-btn').on('click', function () {
        var mode = $(this).data('mode');
        $('#ch-mode-input-' + scopeId).val(mode);
        $('#ch-mode-toggle-' + scopeId + ' .ch-mode-btn').removeClass('ch-btn-active active');
        $(this).addClass('ch-btn-active active');
        $sqlPanel.toggle(mode === 'sql');
        $builderPanel.toggle(mode === 'builder');
    });

    var $datasetSelect = $builderPanel.find('.ch-builder-dataset');
    var $metricFunctionSelect = $builderPanel.find('.ch-builder-metric-function');
    var $metricColumnSelect = $builderPanel.find('.ch-builder-metric-column');
    var $metricColumnRow = $builderPanel.find('.ch-builder-metric-column-row');
    var $metricHidden = $builderPanel.find('.ch-builder-metric');
    // Presenti nel DOM solo per i widget che passano groupBySupported=true
    // (es. chartline_v2) - per l'Indicatore KPI questi find() restituiscono
    // un set vuoto, e .val()/.toggle()/.on() su un set vuoto sono no-op
    // sicuri in jQuery, nessun controllo if() in piu' necessario altrove.
    var $groupByColumnSelect = $builderPanel.find('.ch-builder-group-by-column');
    var $groupByFormatSelect = $builderPanel.find('.ch-builder-group-by-format');
    var $groupByFormatRow = $builderPanel.find('.ch-builder-group-by-format-row');
    var $groupByHidden = $builderPanel.find('.ch-builder-group-by');
    var $filtersContainer = $builderPanel.find('.ch-builder-filters');
    var $addFilterBtn = $builderPanel.find('.ch-builder-add-filter');
    var $previewBtn = $builderPanel.find('.ch-builder-preview-btn');
    var $previewResult = $builderPanel.find('.ch-builder-preview-result');

    // Stessa whitelist di DashboardDatasetRegistry::OPERATORS_BY_TYPE -
    // qui serve solo a scegliere quali opzioni mostrare nella select
    // "Operatore" in base al tipo della colonna scelta, il server la
    // riverifica comunque (mai fidarsi di cosa mostra la UI).
    var OPERATORS_BY_TYPE = {
        numeric: [
            { v: '=', l: '=' }, { v: '!=', l: '≠' },
            { v: '>', l: '>' }, { v: '<', l: '<' },
            { v: '>=', l: '>=' }, { v: '<=', l: '<=' }
        ],
        text: [
            { v: '=', l: '=' }, { v: '!=', l: '≠' },
            { v: 'contains', l: 'contiene' }
        ],
        date: [
            { v: '=', l: '=' }, { v: '!=', l: '≠' },
            { v: '>', l: 'dopo il' }, { v: '<', l: 'prima del' },
            { v: '>=', l: 'da (incluso)' }, { v: '<=', l: 'fino a (incluso)' },
            { v: 'last_days', l: 'negli ultimi N giorni' }
        ]
    };

    // opt.type e' opzionale (dataset/funzione/formato non lo passano): su
    // un'opzione senza type, .attr('data-type', undefined) e' un no-op
    // sicuro in jQuery (ne' imposta ne' rimuove l'attributo), quindi va
    // bene usare questa stessa funzione anche per le select che non ne
    // hanno bisogno.
    function populateSelect($select, options, currentValue, placeholder) {
        $select.empty();
        if (placeholder) {
            $select.append($('<option>').val('').text(placeholder));
        }
        (options || []).forEach(function (opt) {
            $select.append($('<option>').val(opt.key).text(opt.label).attr('data-type', opt.type));
        });
        if (currentValue) {
            $select.val(currentValue);
        }
    }

    function currentDataset() {
        var datasetKey = $datasetSelect.val();
        return (window.__chDashboardDatasets || []).find(function (d) { return d.key === datasetKey; });
    }

    // Funzione+Colonna sono solo UI (docs/refactoring/140): il valore
    // vero resta un'unica stringa "count" o "aggregazione:colonna" nel
    // campo nascosto .ch-builder-metric, stesso formato di sempre.
    function composeMetricValue() {
        var func = $metricFunctionSelect.val();
        if (!func || func === 'count') {
            return 'count';
        }
        var column = $metricColumnSelect.val();

        return column ? (func + ':' + column) : '';
    }

    function updateMetricColumnVisibility() {
        $metricColumnRow.toggle($metricFunctionSelect.val() !== 'count');
    }

    function syncMetricHidden() {
        $metricHidden.val(composeMetricValue());
    }

    // Scompone il valore gia' salvato ("sum:importo") in funzione+colonna
    // per precompilare le due select - stessa idea di normalizeFilters()
    // lato server per i filtri, qui solo lato JS perche' il formato non
    // e' mai cambiato (nessuna retrocompatibilita' da gestire).
    function populateMetricSelects(dataset, currentMetricKey) {
        var currentFunction = 'count';
        var currentColumn = null;
        if (currentMetricKey && currentMetricKey !== 'count') {
            var parts = currentMetricKey.split(':');
            currentFunction = parts[0];
            currentColumn = parts[1];
        }

        populateSelect($metricFunctionSelect, dataset ? dataset.metric_functions : [], currentFunction);
        populateSelect($metricColumnSelect, dataset ? dataset.metric_columns : [], currentColumn);
        updateMetricColumnVisibility();
        syncMetricHidden();
    }

    $metricFunctionSelect.on('change', function () {
        updateMetricColumnVisibility();
        syncMetricHidden();
    });
    $metricColumnSelect.on('change', syncMetricHidden);

    // Colonna+Formato sono solo UI (docs/refactoring/151, stessa idea di
    // Funzione+Colonna sopra): il valore vero resta un'unica stringa
    // "colonna" o "colonna:formato" nel campo nascosto .ch-builder-group-by.
    // Il Formato compare solo se la colonna scelta e' di tipo data
    // (data-type="date" sull'<option>, popolato da populateGroupBySelects()).
    function composeGroupByValue() {
        var column = $groupByColumnSelect.val();
        if (!column) {
            return '';
        }
        var columnType = $groupByColumnSelect.find('option:selected').data('type');
        if (columnType !== 'date') {
            return column;
        }
        var format = $groupByFormatSelect.val();

        return format ? (column + ':' + format) : '';
    }

    function updateGroupByFormatVisibility() {
        var columnType = $groupByColumnSelect.find('option:selected').data('type');
        $groupByFormatRow.toggle(columnType === 'date');
    }

    function syncGroupByHidden() {
        $groupByHidden.val(composeGroupByValue());
    }

    // Scompone il valore gia' salvato ("created_at:month") in colonna+
    // formato per precompilare le due select - stessa idea di
    // populateMetricSelects() sopra. Senza un formato gia' salvato, una
    // colonna data appena scelta parte da "Mese" (il piu' utile come
    // default per un grafico), non dalla prima voce della lista.
    function populateGroupBySelects(dataset, currentGroupByKey) {
        var currentColumn = null;
        var currentFormat = null;
        if (currentGroupByKey) {
            var parts = currentGroupByKey.split(':');
            currentColumn = parts[0];
            currentFormat = parts[1] || null;
        }

        populateSelect($groupByColumnSelect, dataset ? dataset.dimension_columns : [], currentColumn, '-- nessun raggruppamento --');
        populateSelect($groupByFormatSelect, dataset ? dataset.dimension_formats : [], currentFormat || 'month');
        updateGroupByFormatVisibility();
        syncGroupByHidden();
    }

    $groupByColumnSelect.on('change', function () {
        updateGroupByFormatVisibility();
        syncGroupByHidden();
    });
    $groupByFormatSelect.on('change', syncGroupByHidden);

    // L'input valore diventa un campo numerico per le colonne numeriche,
    // un date picker nativo per le colonne data con un confronto diretto
    // (docs/refactoring/153-*: prima era un campo testo libero, causa di
    // errori di battitura tipo "2026-01-01" scritto senza accorgersi di
    // aver scelto una colonna numerica invece che data) e un campo
    // numerico per l'operatore "negli ultimi N giorni" (il valore li' e'
    // un numero di giorni, non una data) - solo un aiuto visivo, la
    // validazione vera resta lato server (DashboardDatasetRegistry).
    function updateFilterValueMode($row) {
        var operator = $row.find('.ch-builder-filter-operator').val();
        var columnType = $row.find('.ch-builder-filter-key option:selected').data('type');
        var $value = $row.find('.ch-builder-filter-value');

        if (operator === 'last_days') {
            $value.attr('type', 'number').attr('min', '1').attr('placeholder', 'Numero di giorni (es. 30)');
        } else if (columnType === 'numeric') {
            $value.attr('type', 'number').attr('placeholder', 'Valore');
        } else if (columnType === 'date') {
            $value.attr('type', 'date').removeAttr('placeholder');
        } else {
            $value.attr('type', 'text').attr('placeholder', 'Valore');
        }
    }

    function populateFilterOperatorSelect($row, currentOperator) {
        var columnType = $row.find('.ch-builder-filter-key option:selected').data('type') || 'text';
        var $operator = $row.find('.ch-builder-filter-operator');
        var options = OPERATORS_BY_TYPE[columnType] || OPERATORS_BY_TYPE.text;

        $operator.empty();
        options.forEach(function (opt) {
            $operator.append($('<option>').val(opt.v).text(opt.l));
        });
        if (currentOperator) {
            $operator.val(currentOperator);
        }
        updateFilterValueMode($row);
    }

    function populateFilterColumnSelect($row, currentColumn) {
        var dataset = currentDataset();
        populateSelect($row.find('.ch-builder-filter-key'), dataset ? dataset.filters : [], currentColumn, '-- seleziona colonna --');
    }

    function reindexFilterRows() {
        $filtersContainer.find('.ch-builder-filter-row').each(function (index) {
            $(this).find('.ch-builder-filter-key').attr('name', fieldPrefix + '[filters][' + index + '][column]');
            $(this).find('.ch-builder-filter-operator').attr('name', fieldPrefix + '[filters][' + index + '][operator]');
            $(this).find('.ch-builder-filter-value').attr('name', fieldPrefix + '[filters][' + index + '][value]');
        });
    }

    function addFilterRow(existing) {
        var $row = $(
            '<div class="ch-builder-filter-row">' +
                '<select class="form-control ch-builder-filter-key"></select>' +
                '<select class="form-control ch-builder-filter-operator"></select>' +
                '<input class="form-control ch-builder-filter-value" type="text" placeholder="Valore" />' +
                '<button type="button" class="ch-builder-filter-remove" title="Rimuovi filtro">&times;</button>' +
            '</div>'
        );
        $filtersContainer.append($row);

        populateFilterColumnSelect($row, existing ? existing.column : null);
        populateFilterOperatorSelect($row, existing ? existing.operator : null);
        if (existing) {
            $row.find('.ch-builder-filter-value').val(existing.value);
        }

        $row.find('.ch-builder-filter-key').on('change', function () {
            populateFilterOperatorSelect($row, null);
        });
        $row.find('.ch-builder-filter-operator').on('change', function () {
            updateFilterValueMode($row);
        });
        $row.find('.ch-builder-filter-remove').on('click', function () {
            $row.remove();
            reindexFilterRows();
        });

        reindexFilterRows();

        return $row;
    }

    function resetFilterRows(list) {
        $filtersContainer.empty();
        (list || []).forEach(function (f) { addFilterRow(f); });
    }

    $addFilterBtn.on('click', function () { addFilterRow(null); });

    function onDatasetChange(keepCurrent) {
        var datasetKey = $datasetSelect.val();
        var dataset = (window.__chDashboardDatasets || []).find(function (d) { return d.key === datasetKey; });

        populateMetricSelects(dataset, keepCurrent ? $metricHidden.data('current') : null);
        populateGroupBySelects(dataset, keepCurrent ? $groupByHidden.data('current') : null);
        // I filtri sono legati alle colonne del dataset scelto: al primo
        // caricamento si ricostruiscono dai filtri gia' salvati
        // (existingFilters), a un cambio dataset fatto dall'utente si
        // riparte vuoti (le colonne del dataset precedente potrebbero non
        // esistere nel nuovo, niente riga con una colonna non piu' valida).
        resetFilterRows(keepCurrent ? existingFilters : []);
    }

    function loadDatasets(callback) {
        if (window.__chDashboardDatasets) {
            callback();
            return;
        }
        $.get("{{ CRUDBooster::mainpath('dataset-options') }}", function (response) {
            window.__chDashboardDatasets = response.datasets || [];
            callback();
        });
    }

    // Select2 sul Dataset: gia' in ordine alfabetico dal server
    // (DashboardDatasetRegistry::optionsForFrontend()), qui serve solo
    // renderlo ricercabile - con decine di tabelle una <select> nativa
    // andrebbe scorsa a occhio. window.__chEnsureSelect2 e' condivisa con
    // lo script dell'icona in smallbox.blade.php (che potrebbe partire
    // prima o dopo questo, mai due volte lo stesso <script src> in corsa).
    function initDatasetSelect2() {
        $datasetSelect.select2({ width: '100%' });
    }

    loadDatasets(function () {
        populateSelect($datasetSelect, window.__chDashboardDatasets.map(function (d) { return { key: d.key, label: d.label }; }), $datasetSelect.data('current'), '-- seleziona --');
        onDatasetChange(true);
        window.__chEnsureSelect2(initDatasetSelect2);
    });

    $datasetSelect.on('change', function () { onDatasetChange(false); });

    function runPreview($btn, $result, request) {
        $result.text('Caricamento...');
        $.post(request.url, request.data, function (response) {
            if (!response.rows || !response.rows.length) {
                $result.html('<em>Nessun risultato.</em>');
                return;
            }
            var html = '<table class="table table-condensed" style="margin:0;"><tbody>';
            response.rows.forEach(function (row) {
                html += '<tr><td>' + row.label + '</td><td style="text-align:right;font-weight:600;">' + row.value + '</td></tr>';
            });
            html += '</tbody></table>';
            $result.html(html);
        }).fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Errore imprevisto.';
            $result.html('<span class="text-danger">' + message + '</span>');
        });
    }

    $previewBtn.on('click', function () {
        var filters = [];
        $filtersContainer.find('.ch-builder-filter-row').each(function () {
            var column = $(this).find('.ch-builder-filter-key').val();
            var value = $(this).find('.ch-builder-filter-value').val();
            if (!column || value === '') {
                return;
            }
            filters.push({
                column: column,
                operator: $(this).find('.ch-builder-filter-operator').val(),
                value: value
            });
        });

        runPreview($previewBtn, $previewResult, {
            url: "{{ CRUDBooster::mainpath('dataset-preview') }}",
            data: {
                _token: "{{ csrf_token() }}",
                dataset: $datasetSelect.val(),
                metric: composeMetricValue(),
                group_by: composeGroupByValue(),
                filters: filters
            }
        });
    });

    // "Prova" sul pannello SQL libera (vedi smallbox.blade.php) - stessa
    // funzione di anteprima, endpoint diverso (esegue la SQL cosi' com'e',
    // niente dataset/whitelist: stessa fiducia gia' accordata a chi puo'
    // salvare quella query, che gira comunque ad ogni caricamento del
    // widget - vedi StatisticBuilderController::postSqlPreview()).
    var $sqlPreviewBtn = $sqlPanel.find('.ch-sql-preview-btn');
    var $sqlPreviewResult = $sqlPanel.find('.ch-sql-preview-result');
    $sqlPreviewBtn.on('click', function () {
        runPreview($sqlPreviewBtn, $sqlPreviewResult, {
            url: "{{ CRUDBooster::mainpath('sql-preview') }}",
            data: {
                _token: "{{ csrf_token() }}",
                sql: $sqlPanel.find('textarea[name="' + fieldPrefix + '[sql]"]').val()
            }
        });
    });
})();
</script>
