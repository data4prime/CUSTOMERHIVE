{{--
    Modalita' "Elenco record" del widget Tabella (vedi
    docs/piano-widget-tabella-elenco-record.md, Fase 3): tabella (solo
    dataset mg_* con modulo), colonne da mostrare, ordinamento, record per
    pagina, filtri. Incluso da _source_config.blade.php solo se il widget
    passa $recordsSupported=true (oggi solo table.blade.php).

    Salva in config: mode='records', dataset, columns[], order_by,
    order_dir, page_length, filters[] (stesso formato di _query_builder_fields,
    letto da DashboardDatasetRegistry::normalizeFilters()). Il server non si
    fida di nulla di questo: executeRows() riverifica tutto a ogni render.

    Il codice delle righe filtro ricalca quello di _query_builder_fields
    (volutamente non condiviso: quel partial gira dentro una closure legata
    al suo pannello Query guidata e non e' incluso nel widget Tabella).

    Gestisce da se' il toggle di modalita' (input hidden + pulsante attivo +
    pannelli), perche' quello in _query_builder_fields non e' presente qui.
--}}
<?php
    $currentMode = $config->mode ?? 'sql';
    $fieldPrefix = $fieldPrefix ?? 'config';
    $scopeId = $scopeId ?? $componentID;
    $existingFilters = \App\Dashboards\DashboardDatasetRegistry::normalizeFilters($config->filters ?? []);
    $existingColumns = array_values((array) ($config->columns ?? []));
    $currentPageLength = (int) ($config->page_length ?? 10);
    if (!in_array($currentPageLength, \App\Dashboards\DashboardDatasetRegistry::ROWS_PAGE_LENGTHS, true)) {
        $currentPageLength = 10;
    }
?>
<div class="ch-mode-panel" data-mode-panel="records" id="ch-panel-records-{{ $scopeId }}" style="{{ $currentMode === 'records' ? '' : 'display:none' }}">
    <div class="mb-3 row">
        <label>{{ trans('crudbooster.statistic_builder_records_table') }}</label>
        <select class="form-control ch-records-dataset" name="{{ $fieldPrefix }}[dataset]" data-current="{{ @$config->dataset }}" style="width:100%"></select>
    </div>
    <div class="mb-3 row">
        <label>{{ trans('crudbooster.statistic_builder_records_columns') }}</label>
        <div class="ch-records-columns" id="ch-records-columns-{{ $scopeId }}"></div>
        <div class="help-block">{{ trans('crudbooster.statistic_builder_records_columns_help') }}</div>
    </div>
    <div class="mb-3 row">
        <label>{{ trans('crudbooster.statistic_builder_records_order_by') }}</label>
        <div class="ch-records-order-row">
            <select class="form-control ch-records-order-by" name="{{ $fieldPrefix }}[order_by]" data-current="{{ @$config->order_by }}"></select>
            <select class="form-control ch-records-order-dir" name="{{ $fieldPrefix }}[order_dir]">
                <option value="asc" {{ ($config->order_dir ?? 'asc') === 'asc' ? 'selected' : '' }}>{{ trans('crudbooster.statistic_builder_records_order_asc') }}</option>
                <option value="desc" {{ ($config->order_dir ?? 'asc') === 'desc' ? 'selected' : '' }}>{{ trans('crudbooster.statistic_builder_records_order_desc') }}</option>
            </select>
        </div>
    </div>
    <div class="mb-3 row">
        <label>{{ trans('crudbooster.statistic_builder_records_page_length') }}</label>
        <select class="form-control ch-records-page-length" name="{{ $fieldPrefix }}[page_length]">
            @foreach(\App\Dashboards\DashboardDatasetRegistry::ROWS_PAGE_LENGTHS as $length)
            <option value="{{ $length }}" {{ $currentPageLength === $length ? 'selected' : '' }}>{{ $length }}</option>
            @endforeach
        </select>
        <div class="help-block">{{ trans('crudbooster.statistic_builder_records_limit_help') }}</div>
    </div>
    <div class="mb-3 row">
        <label>{{ trans('crudbooster.statistic_builder_records_filters') }}</label>
        <div class="ch-records-filters" id="ch-records-filters-{{ $scopeId }}"></div>
        <button type="button" class="ch-btn ch-records-add-filter" style="margin-top:2px;">{{ trans('crudbooster.statistic_builder_records_add_filter') }}</button>
    </div>
    <button type="button" class="ch-btn ch-records-preview-btn">{{ trans('crudbooster.statistic_builder_records_preview') }}</button>
    <div class="help-block">{{ trans('crudbooster.statistic_builder_records_preview_note') }}</div>
    <div class="ch-records-preview-result" style="margin-top:10px; font-size:13px; overflow-x:auto;"></div>
</div>

<style>
    .ch-records-columns { max-height: 180px; overflow-y: auto; border: 1px solid var(--ch-border-strong); border-radius: 8px; padding: 6px 10px; }
    .ch-records-columns label { display: flex; align-items: center; gap: 8px; font-weight: 500; margin: 3px 0; cursor: pointer; }
    .ch-records-columns input[type=checkbox] { margin: 0; }
    .ch-records-order-row { display: flex; gap: 6px; }
    .ch-records-order-row .ch-records-order-by { flex: 1; min-width: 0; }
    .ch-records-order-row .ch-records-order-dir { flex: 0 0 140px; }
    .ch-records-filter-row { display: flex; gap: 6px; align-items: center; margin-bottom: 6px; }
    .ch-records-filter-row .ch-records-filter-key { flex: 1; min-width: 0; }
    .ch-records-filter-row .ch-records-filter-operator { flex: 0 0 160px; }
    .ch-records-filter-row .ch-records-filter-value { flex: 1; min-width: 0; }
    .ch-records-filter-remove { border: none; background: none; color: var(--ch-text-muted); cursor: pointer; font-size: 18px; line-height: 1; padding: 0 4px; flex-shrink: 0; }
    .ch-records-filter-remove:hover { color: var(--ch-danger); }
</style>

<script>
(function () {
    var scopeId = {!! json_encode($scopeId) !!};
    var fieldPrefix = {!! json_encode($fieldPrefix) !!};
    var existingFilters = {!! json_encode($existingFilters) !!};
    var existingColumns = {!! json_encode($existingColumns) !!};
    var T = {
        select: {!! json_encode(trans('crudbooster.statistic_builder_records_select')) !!},
        orderDefault: {!! json_encode(trans('crudbooster.statistic_builder_records_order_default')) !!},
        filterColumn: {!! json_encode(trans('crudbooster.statistic_builder_records_filter_column')) !!},
        filterValue: {!! json_encode(trans('crudbooster.statistic_builder_records_filter_value')) !!},
        filterDays: {!! json_encode(trans('crudbooster.statistic_builder_records_filter_days')) !!},
        removeFilter: {!! json_encode(trans('crudbooster.statistic_builder_records_remove_filter')) !!},
        loading: {!! json_encode(trans('crudbooster.statistic_builder_records_loading')) !!},
        noResults: {!! json_encode(trans('crudbooster.statistic_builder_records_no_results')) !!},
        error: {!! json_encode(trans('crudbooster.statistic_builder_records_error')) !!}
    };
    var DEFAULT_PRESELECTED_COLUMNS = 5;

    var $panel = $('#ch-panel-records-' + scopeId);
    var $sqlPanel = $('#ch-panel-sql-' + scopeId);

    // Toggle di modalita' (vedi nota in testa al file)
    $('#ch-mode-toggle-' + scopeId + ' .ch-mode-btn').on('click', function () {
        var mode = $(this).data('mode');
        $('#ch-mode-input-' + scopeId).val(mode);
        $('#ch-mode-toggle-' + scopeId + ' .ch-mode-btn').removeClass('ch-btn-active active');
        $(this).addClass('ch-btn-active active');
        $sqlPanel.toggle(mode === 'sql');
        $panel.toggle(mode === 'records');
    });

    var $datasetSelect = $panel.find('.ch-records-dataset');
    var $columnsBox = $panel.find('.ch-records-columns');
    var $orderBy = $panel.find('.ch-records-order-by');
    var $filtersContainer = $panel.find('.ch-records-filters');
    var $previewResult = $panel.find('.ch-records-preview-result');

    // Stessa whitelist di DashboardDatasetRegistry::OPERATORS_BY_TYPE: serve
    // solo a scegliere cosa mostrare, il server la riverifica sempre.
    var OPERATORS_BY_TYPE = {
        numeric: [
            { v: '=', l: '=' }, { v: '!=', l: '≠' },
            { v: '>', l: '>' }, { v: '<', l: '<' },
            { v: '>=', l: '>=' }, { v: '<=', l: '<=' }
        ],
        text: [
            { v: '=', l: '=' }, { v: '!=', l: '≠' },
            { v: 'contains', l: {!! json_encode(trans('crudbooster.statistic_builder_records_op_contains')) !!} }
        ],
        date: [
            { v: '=', l: '=' }, { v: '!=', l: '≠' },
            { v: '>', l: {!! json_encode(trans('crudbooster.statistic_builder_records_op_after')) !!} },
            { v: '<', l: {!! json_encode(trans('crudbooster.statistic_builder_records_op_before')) !!} },
            { v: '>=', l: {!! json_encode(trans('crudbooster.statistic_builder_records_op_from')) !!} },
            { v: '<=', l: {!! json_encode(trans('crudbooster.statistic_builder_records_op_until')) !!} },
            { v: 'last_days', l: {!! json_encode(trans('crudbooster.statistic_builder_records_op_last_days')) !!} }
        ]
    };

    function currentDataset() {
        var key = $datasetSelect.val();
        return (window.__chDashboardDatasets || []).find(function (d) { return d.key === key; });
    }

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

    function populateColumns(dataset, checked) {
        $columnsBox.empty();
        (dataset ? dataset.filters : []).forEach(function (col, index) {
            var isChecked = checked ? checked.indexOf(col.key) !== -1 : index < DEFAULT_PRESELECTED_COLUMNS;
            var $label = $('<label>');
            $label.append($('<input type="checkbox">').attr('name', fieldPrefix + '[columns][]').val(col.key).prop('checked', isChecked));
            $label.append($('<span>').text(col.label));
            $columnsBox.append($label);
        });
    }

    function selectedColumns() {
        return $columnsBox.find('input:checked').map(function () { return $(this).val(); }).get();
    }

    // --- filtri (stesso formato di _query_builder_fields) ---
    function updateFilterValueMode($row) {
        var operator = $row.find('.ch-records-filter-operator').val();
        var columnType = $row.find('.ch-records-filter-key option:selected').data('type');
        var $value = $row.find('.ch-records-filter-value');
        if (operator === 'last_days') {
            $value.attr('type', 'number').attr('min', '1').attr('placeholder', T.filterDays);
        } else if (columnType === 'numeric') {
            $value.attr('type', 'number').attr('placeholder', T.filterValue);
        } else if (columnType === 'date') {
            $value.attr('type', 'date').removeAttr('placeholder');
        } else {
            $value.attr('type', 'text').attr('placeholder', T.filterValue);
        }
    }

    function populateFilterOperatorSelect($row, currentOperator) {
        var columnType = $row.find('.ch-records-filter-key option:selected').data('type') || 'text';
        var $operator = $row.find('.ch-records-filter-operator');
        $operator.empty();
        (OPERATORS_BY_TYPE[columnType] || OPERATORS_BY_TYPE.text).forEach(function (opt) {
            $operator.append($('<option>').val(opt.v).text(opt.l));
        });
        if (currentOperator) {
            $operator.val(currentOperator);
        }
        updateFilterValueMode($row);
    }

    function reindexFilterRows() {
        $filtersContainer.find('.ch-records-filter-row').each(function (index) {
            $(this).find('.ch-records-filter-key').attr('name', fieldPrefix + '[filters][' + index + '][column]');
            $(this).find('.ch-records-filter-operator').attr('name', fieldPrefix + '[filters][' + index + '][operator]');
            $(this).find('.ch-records-filter-value').attr('name', fieldPrefix + '[filters][' + index + '][value]');
        });
    }

    function addFilterRow(existing) {
        var $row = $(
            '<div class="ch-records-filter-row">' +
                '<select class="form-control ch-records-filter-key"></select>' +
                '<select class="form-control ch-records-filter-operator"></select>' +
                '<input class="form-control ch-records-filter-value" type="text" />' +
                '<button type="button" class="ch-records-filter-remove">&times;</button>' +
            '</div>'
        );
        $row.find('.ch-records-filter-remove').attr('title', T.removeFilter);
        $filtersContainer.append($row);

        var dataset = currentDataset();
        populateSelect($row.find('.ch-records-filter-key'), dataset ? dataset.filters : [], existing ? existing.column : null, T.filterColumn);
        populateFilterOperatorSelect($row, existing ? existing.operator : null);
        if (existing) {
            $row.find('.ch-records-filter-value').val(existing.value);
        }

        $row.find('.ch-records-filter-key').on('change', function () { populateFilterOperatorSelect($row, null); });
        $row.find('.ch-records-filter-operator').on('change', function () { updateFilterValueMode($row); });
        $row.find('.ch-records-filter-remove').on('click', function () {
            $row.remove();
            reindexFilterRows();
        });
        reindexFilterRows();
    }

    $panel.find('.ch-records-add-filter').on('click', function () { addFilterRow(null); });

    function onDatasetChange(keepCurrent) {
        var dataset = currentDataset();
        populateColumns(dataset, keepCurrent ? existingColumns : null);
        populateSelect($orderBy, dataset ? dataset.filters : [], keepCurrent ? $orderBy.data('current') : null, T.orderDefault);
        $filtersContainer.empty();
        (keepCurrent ? existingFilters : []).forEach(function (f) { addFilterRow(f); });
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

    loadDatasets(function () {
        var recordsDatasets = window.__chDashboardDatasets.filter(function (d) { return d.records; })
            .map(function (d) { return { key: d.key, label: d.label }; });
        populateSelect($datasetSelect, recordsDatasets, $datasetSelect.data('current'), T.select);
        onDatasetChange(true);
    });
    $datasetSelect.on('change', function () { onDatasetChange(false); });

    // --- anteprima ---
    $panel.find('.ch-records-preview-btn').on('click', function () {
        var filters = [];
        $filtersContainer.find('.ch-records-filter-row').each(function () {
            var column = $(this).find('.ch-records-filter-key').val();
            var value = $(this).find('.ch-records-filter-value').val();
            if (!column || value === '') {
                return;
            }
            filters.push({ column: column, operator: $(this).find('.ch-records-filter-operator').val(), value: value });
        });

        $previewResult.text(T.loading);
        $.post("{{ CRUDBooster::mainpath('records-preview') }}", {
            _token: "{{ csrf_token() }}",
            dataset: $datasetSelect.val(),
            columns: selectedColumns(),
            order_by: $orderBy.val(),
            order_dir: $panel.find('.ch-records-order-dir').val(),
            filters: filters
        }, function (response) {
            if (!response.rows || !response.rows.length) {
                $previewResult.empty().append($('<em>').text(T.noResults));
                return;
            }
            var keys = Object.keys(response.columns);
            var $table = $('<table class="table table-sm" style="margin:0;">');
            var $head = $('<tr>');
            keys.forEach(function (k) { $head.append($('<th>').text(response.columns[k])); });
            $table.append($('<thead>').append($head));
            var $body = $('<tbody>');
            response.rows.forEach(function (row) {
                var $tr = $('<tr>');
                keys.forEach(function (k) { $tr.append($('<td>').text(row[k] === null ? '' : row[k])); });
                $body.append($tr);
            });
            $previewResult.empty().append($table.append($body));
        }).fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : T.error;
            $previewResult.empty().append($('<span class="text-danger">').text(message));
        });
    });
})();
</script>
