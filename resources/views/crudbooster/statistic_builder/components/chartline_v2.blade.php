{{--
    Fase 4 del piano "dashboard a griglia libera" (vedi
    docs/piano-dashboard-griglia-libera.md, docs/refactoring/112-*).

    Grafico a linee su ApexCharts (CDN), raggiungibile solo dalla palette
    della nuova griglia - il widget legacy "Chart Line" (Morris/Raphael,
    chartline.blade.php) resta invariato e usato solo dalle dashboard
    ancora 'legacy_areas'. La sorgente dati principale del widget (Nome/
    Colore/Sorgente dati a livello di 'config') e' sempre la prima linea:
    la SQL libera deve restituire le colonne 'label' e 'value' (stessa
    forma delle righe prodotte dalla modalita' 'builder').

    Piu' linee (docs/refactoring/152-*): fino a 4 linee extra opzionali,
    ciascuna con la propria sorgente dati indipendente, salvate come
    config[lines][N] - non e' il formato multi-serie con 'area_name' in
    un'unica query del widget legacy, ogni linea qui ha una sua query o
    dataset separati. Il controller (renderComponentPayload()) le risolve
    e passa a showFunction un array di {name,color,rows} invece del
    vecchio array piatto di righe quando config->lines non e' vuoto.
--}}
@if($command=='layout')
<div id='{{$componentID}}' class='border-box'>
    @if(empty($config->name))
    @include('crudbooster::statistic_builder.components._empty_widget_state', [
        'icon' => '~',
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
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' data-name='Grafico a linee'
            class='btn-edit-component'><i class='fa fa-pencil'></i></a>
        &nbsp;
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' class='btn-delete-component'><i
                class='fa fa-trash'></i></a>
    </div>
</div>
@elseif($command=='configuration')
<?php
    // Query guidata dentro un popup, come l'Indicatore KPI
    // (docs/refactoring/148-*, richiesta esplicita dell'utente): stesso
    // partial condiviso _source_config.blade.php, con in piu' il
    // raggruppamento (groupBySupported=true) - un grafico ha bisogno di
    // piu' punti, non di un solo valore aggregato.
    $groupBySupported = true;
    $sqlLabel = 'SQL Query (colonne label e value)';
    $sqlPlaceholder = 'select mese as label, totale as value from ...';
    $sqlHelp = 'Una sola serie: la query deve restituire le colonne label e value. Usa [SESSION_NAME] per la sessione.';
    // Piu' linee (docs/refactoring/152-*): Nome/Colore/Sorgente dati qui
    // sopra restano SEMPRE la prima linea (invariati, stesso config[...]
    // di sempre) - le linee EXTRA sono nel partial condiviso
    // _multi_series_config.blade.php (docs/refactoring/156-*).
    $seriesNoun = trans('crudbooster.statistic_builder_noun_line');
    $seriesNounPlural = trans('crudbooster.statistic_builder_noun_line_plural');
?>
<form method='post'>
    <input type='hidden' name='_token' value='{{csrf_token()}}' />
    <input type='hidden' name='componentid' value='{{$componentID}}' />
    <div class="mb-3 row">
        <label>Name</label>
        <input class="form-control" required name='config[name]' type='text' value='{{@$config->name}}' />
    </div>

    <div class="mb-3 row">
        <label>Colore linea (opzionale)</label>
        <input type='color' class='form-control' name='config[color]' value='{{ @$config->color ?: "#3B5BDB" }}' />
        <div class="help-block">Se non lo cambi resta il colore di default.</div>
    </div>

    @include('crudbooster::statistic_builder.components._source_config', compact('componentID', 'config', 'groupBySupported', 'sqlLabel', 'sqlPlaceholder', 'sqlHelp'))

    @include('crudbooster::statistic_builder.components._multi_series_config', compact('componentID', 'config', 'sqlLabel', 'sqlPlaceholder', 'sqlHelp', 'seriesNoun', 'seriesNounPlural'))
</form>
@elseif($command=='showFunction')
<?php
    if ($key == 'sql') {
        // Tutta la logica di normalizzazione del valore (SQL grezza / righe
        // piatte / array di {name,color,rows} per piu' linee, unione delle
        // label, palette di default) vive in ChartSeriesBuilder
        // (docs/refactoring/156-*), condiviso con chartbar_v2.
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
            chart: { type: 'line', height: 240, toolbar: { show: false } },
            series: {!! json_encode($series) !!},
            xaxis: { categories: {!! json_encode($categories) !!} },
            stroke: { width: 3, curve: 'smooth' },
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
