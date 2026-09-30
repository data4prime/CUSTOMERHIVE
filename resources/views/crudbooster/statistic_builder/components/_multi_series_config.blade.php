{{--
    Blocco "Altre linee/serie" del pannello di configurazione dei grafici
    (docs/refactoring/152-*, estratto in docs/refactoring/156-* da
    chartline_v2.blade.php per riusarlo identico in chartbar_v2): fino a
    $maxExtraLines serie EXTRA opzionali, ciascuna con la propria
    sorgente dati indipendente, salvate come config[lines][N]. La prima
    serie resta sempre quella "principale" del widget (Nome/Colore/Sorgente
    dati a livello di config), questo partial aggiunge solo le altre.

    Il chiamante deve avere in scope (li passa l'@include):
    - $componentID, $config
    - $sqlLabel, $sqlPlaceholder, $sqlHelp: come per _source_config
    - $seriesNoun / $seriesNounPlural (string): sostantivo da mostrare
      ("linea"/"linee" per il Grafico a linee, "serie" per le barre) -
      il resto dei testi lo compone questo partial da chiavi trans().
    Fino a $maxExtraLines blocchi sono gia' pre-renderizzati (nessun
    round-trip AJAX per aggiungerne uno): solo quelli davvero compilati
    sono visibili all'apertura, gli altri restano nascosti finche' non si
    preme "+ Aggiungi".
--}}
<?php
    $maxExtraLines = 4;
    // Solo le serie davvero compilate: i blocchi nascosti vengono comunque
    // inviati dal form, quindi al salvataggio 'lines' contiene anche gli
    // slot vuoti - contarli faceva riapparire tutti i blocchi (vedi
    // docs/refactoring/155-*). Stesso criterio di "serie vuota" di
    // StatisticBuilderController::renderComponentPayload() (nome/dataset/
    // sql tutti vuoti).
    $existingLines = array_values(array_filter(
        (array) ($config->lines ?? []),
        fn ($line) => !empty($line->name) || !empty($line->dataset) || !empty($line->sql)
    ));
?>
<div class="mb-3 row">
    <label>{{ trans('crudbooster.statistic_builder_extra_series_title', ['plural' => $seriesNounPlural]) }}</label>
    <div class="help-block">{{ trans('crudbooster.statistic_builder_extra_series_help', ['plural' => $seriesNounPlural, 'noun' => $seriesNoun]) }}</div>
</div>

@for ($i = 0; $i < $maxExtraLines; $i++)
<?php
    $lineConfig = $existingLines[$i] ?? new \stdClass();
    $lineVisible = $i < count($existingLines);
    $lineFieldPrefix = "config[lines][{$i}]";
    $lineScopeId = "{$componentID}-line-{$i}";
?>
<div class="ch-chart-line-block" data-line-index="{{ $i }}" style="{{ $lineVisible ? '' : 'display:none;' }} border:1px solid #E4E7EC; border-radius:10px; padding:12px; margin-bottom:10px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
        <strong style="font-size:12.5px; color:#101828;">{{ trans('crudbooster.statistic_builder_extra_series_block_title', ['noun' => ucfirst($seriesNoun), 'number' => $i + 2]) }}</strong>
        <button type="button" class="ch-chart-line-remove" style="border:none; background:none; color:#98A2B3; cursor:pointer; font-size:12.5px; text-decoration:underline;">{{ trans('crudbooster.statistic_builder_extra_series_remove') }}</button>
    </div>
    <div class="mb-3 row">
        <label>{{ trans('crudbooster.statistic_builder_extra_series_name', ['noun' => $seriesNoun]) }}</label>
        <input class="form-control" name="{{ $lineFieldPrefix }}[name]" type="text" value="{{ @$lineConfig->name }}" />
    </div>
    <div class="mb-3 row">
        <label>{{ trans('crudbooster.statistic_builder_extra_series_color', ['noun' => $seriesNoun]) }}</label>
        <input type="color" class="form-control" name="{{ $lineFieldPrefix }}[color]" value="{{ @$lineConfig->color ?: '#0EA5E9' }}" />
    </div>
    @include('crudbooster::statistic_builder.components._source_config', [
        'componentID' => $componentID,
        'config' => $lineConfig,
        'groupBySupported' => true,
        'sqlLabel' => $sqlLabel,
        'sqlPlaceholder' => $sqlPlaceholder,
        'sqlHelp' => $sqlHelp,
        'fieldPrefix' => $lineFieldPrefix,
        'scopeId' => $lineScopeId,
    ])
</div>
@endfor

<button type="button" class="ch-btn" id="ch-chart-add-line-{{ $componentID }}">{{ trans('crudbooster.statistic_builder_extra_series_add', ['noun' => $seriesNoun]) }}</button>

<script>
(function () {
    var componentID = {!! json_encode($componentID) !!};
    var $blocks = $('.ch-chart-line-block[data-line-index]');
    var $addBtn = $('#ch-chart-add-line-' + componentID);

    function updateAddButtonVisibility() {
        var anyHidden = $blocks.filter(function () { return $(this).css('display') === 'none'; }).length > 0;
        $addBtn.toggle(anyHidden);
    }

    $addBtn.on('click', function () {
        $blocks.filter(function () { return $(this).css('display') === 'none'; }).first().show();
        updateAddButtonVisibility();
    });

    // "Rimuovi": pulisce solo i campi che il backend usa per decidere se
    // una serie e' vuota (renderComponentPayload(): nome/dataset/sql) e
    // nasconde di nuovo il blocco - non serve ripulire ogni singolo
    // filtro/select della Query guidata, una serie senza nome ne'
    // sorgente viene gia' ignorata a prescindere da cos'altro contiene.
    $blocks.each(function () {
        var $block = $(this);
        $block.find('.ch-chart-line-remove').on('click', function () {
            $block.find('input[name$="[name]"]').val('');
            $block.find('.ch-builder-dataset').val('').trigger('change');
            $block.find('textarea[name$="[sql]"]').val('');
            $block.hide();
            updateAddButtonVisibility();
        });
    });

    updateAddButtonVisibility();
})();
</script>
