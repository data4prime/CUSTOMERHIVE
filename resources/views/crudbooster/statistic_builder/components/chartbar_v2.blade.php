{{--
    Fase 4 del piano "dashboard a griglia libera" (vedi
    docs/piano-dashboard-griglia-libera.md, docs/refactoring/112-*).

    Grafico a barre su ApexCharts (CDN), raggiungibile solo dalla palette
    della nuova griglia - il widget legacy "Chart Bar" (Morris/Raphael,
    chartbar.blade.php) resta invariato e usato solo dalle dashboard
    ancora 'legacy_areas'. Stessa forma dati e stessa configurazione di
    chartline_v2 (docs/refactoring/156-*): Query guidata o SQL libera
    (colonne 'label'/'value'), colore, fino a 4 serie extra (barre
    affiancate, oppure impilate con config[stacked]).
--}}
@if($command=='layout')
<div id='{{$componentID}}' class='border-box'>
    @if(empty($config->name))
    @include('crudbooster::statistic_builder.components._empty_widget_state', [
        'icon' => '|||',
        'title' => 'Grafico non configurato',
        'subtitle' => 'Seleziona per collegare i dati',
        'link' => $editUrl ?? null,
    ])
    @else
    <div class="card card-default">
        <div class="card-header">
            [name]
        </div>
        <div class="card-body">
            <div id="apexchart-{{ $componentID }}" style="min-height:240px;">[sql]</div>
        </div>
    </div>
    @endif

    <div class='action pull-right'>
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' data-name='Grafico a barre'
            class='btn-edit-component'><i class='fa fa-pencil'></i></a>
        &nbsp;
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' class='btn-delete-component'><i
                class='fa fa-trash'></i></a>
    </div>
</div>
@elseif($command=='configuration')
<?php
    // Stessa configurazione del Grafico a linee (docs/refactoring/156-*):
    // Query guidata + SQL libera nel popup condiviso _source_config, colore
    // della prima serie, serie extra nel partial condiviso
    // _multi_series_config. Un widget salvato con la sola SQL (nessun
    // config[mode]) si riapre in modalita' SQL con la sua query intatta.
    $groupBySupported = true;
    $sqlLabel = trans('crudbooster.statistic_builder_chart_sql_label');
    $sqlPlaceholder = 'select regione as label, totale as value from ...';
    $sqlHelp = trans('crudbooster.statistic_builder_chart_sql_help');
    $seriesNoun = trans('crudbooster.statistic_builder_noun_series');
    $seriesNounPlural = trans('crudbooster.statistic_builder_noun_series_plural');
?>
<form method='post'>
    <input type='hidden' name='_token' value='{{csrf_token()}}' />
    <input type='hidden' name='componentid' value='{{$componentID}}' />
    <div class="mb-3 row">
        <label>Name</label>
        <input class="form-control" required name='config[name]' type='text' value='{{@$config->name}}' />
    </div>

    <div class="mb-3 row">
        <label>{{ trans('crudbooster.statistic_builder_extra_series_color', ['noun' => $seriesNoun]) }}</label>
        <input type='color' class='form-control' name='config[color]' value='{{ @$config->color ?: "#3B5BDB" }}' />
        <div class="help-block">{{ trans('crudbooster.statistic_builder_chart_color_help') }}</div>
    </div>

    <div class="mb-3 row">
        <label>
            <input type="checkbox" name="config[stacked]" value="1" {{ !empty($config->stacked) ? 'checked' : '' }} />
            {{ trans('crudbooster.statistic_builder_chart_stacked') }}
        </label>
        <div class="help-block">{{ trans('crudbooster.statistic_builder_chart_stacked_help') }}</div>
    </div>

    @include('crudbooster::statistic_builder.components._source_config', compact('componentID', 'config', 'groupBySupported', 'sqlLabel', 'sqlPlaceholder', 'sqlHelp'))

    @include('crudbooster::statistic_builder.components._multi_series_config', compact('componentID', 'config', 'sqlLabel', 'sqlPlaceholder', 'sqlHelp', 'seriesNoun', 'seriesNounPlural'))
</form>
@elseif($command=='showFunction')
<?php
    if ($key == 'sql') {
        // Piu' serie (docs/refactoring/156-*): stessa normalizzazione del
        // Grafico a linee, condivisa in ChartSeriesBuilder.
        $chartData = \App\Dashboards\ChartSeriesBuilder::build($value, $config);
        $rowsError = $chartData['error'];
        $series = $chartData['series'];
        $categories = $chartData['categories'];
?>
@if($rowsError)
<span class="small-box-sql-error">{{ $rowsError }}</span>
@else
<script>
(function () {
    var el = document.getElementById('apexchart-{{ $componentID }}');
    if (!el) { return; }
    el.innerHTML = '';

    function render() {
        new ApexCharts(el, {
            chart: { type: 'bar', height: 240, toolbar: { show: false }, stacked: {{ !empty($config->stacked) ? 'true' : 'false' }} },
            plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
            series: {!! json_encode(array_map(fn ($s) => ['name' => $s['name'], 'data' => array_map(fn ($v) => is_numeric($v) ? $v + 0 : $v, $s['data'])], $series)) !!},
            xaxis: { categories: {!! json_encode($categories) !!} },
            colors: {!! json_encode(array_column($series, 'color')) !!},
            dataLabels: { enabled: false }
        }).render();
    }

    if (window.ApexCharts) { render(); return; }
    var existing = document.querySelector('script[data-ch-apexcharts-cdn]');
    if (existing) { existing.addEventListener('load', render); return; }
    var script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
    script.setAttribute('data-ch-apexcharts-cdn', '1');
    script.onload = render;
    document.body.appendChild(script);
})();
</script>
@endif
<?php
    } else {
        echo $value;
    }
?>
@endif

<script defer>
if (!window.location.href.includes('statistic_builder/builder')) {
    var action = $('#{{$componentID}}').find('.action');
    action.hide();
}
</script>
