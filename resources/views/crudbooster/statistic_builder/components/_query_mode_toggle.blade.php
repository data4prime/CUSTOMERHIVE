{{--
    Fase 3 del piano "dashboard a griglia libera" (vedi
    docs/piano-dashboard-griglia-libera.md, docs/refactoring/111-*).

    Toggle "SQL libera / Query guidata", condiviso dai widget che
    interrogano dati (smallbox, table, chartline_v2, chartbar_v2).
    Il blade del widget lo include prima del proprio campo SQL, che va
    avvolto manualmente in <div id="ch-panel-sql-{{ $scopeId ?? $componentID }}"
    data-mode-panel="sql">...</div> (vedi smallbox/table/chartline_v2/
    chartbar_v2 .blade.php) - il JS che pilota mostra/nascondi e il
    pannello "Query guidata" vero e proprio sono nel partial gemello
    _query_builder_fields.blade.php, incluso subito dopo.

    $fieldPrefix (default 'config') e $scopeId (default $componentID):
    docs/refactoring/152-*, per poter includere questo partial piu' volte
    nello stesso widget (una per "linea" extra del Grafico a linee) senza
    id DOM ne' name= di campo in collisione tra un'istanza e l'altra -
    con i default invariati il comportamento per gli usi esistenti
    (smallbox, prima sorgente di chartline_v2) resta identico.
--}}
<?php
    $currentMode = $config->mode ?? 'sql';
    $fieldPrefix = $fieldPrefix ?? 'config';
    $scopeId = $scopeId ?? $componentID;
    // $builderSupported (default true) e $recordsSupported (default false):
    // widget Tabella "Elenco record" (docs/piano-widget-tabella-elenco-
    // record.md) - mostra il pulsante della modalita' 'records' e puo'
    // nascondere 'Query guidata'. Senza questi parametri il toggle e'
    // identico a prima.
    $builderSupported = $builderSupported ?? true;
    $recordsSupported = $recordsSupported ?? false;
?>
<div class="mb-3 row">
    <label>Sorgente dati</label>
    <div class="ch-query-mode-toggle" id="ch-mode-toggle-{{ $scopeId }}">
        <button type="button" class="ch-btn ch-mode-btn {{ $currentMode === 'sql' ? 'ch-btn-active active' : '' }}" data-mode="sql">SQL libera</button>
        @if($builderSupported)
        <button type="button" class="ch-btn ch-mode-btn {{ $currentMode === 'builder' ? 'ch-btn-active active' : '' }}" data-mode="builder">Query guidata</button>
        @endif
        @if($recordsSupported)
        <button type="button" class="ch-btn ch-mode-btn {{ $currentMode === 'records' ? 'ch-btn-active active' : '' }}" data-mode="records">{{ trans('crudbooster.statistic_builder_records_mode') }}</button>
        @endif
    </div>
    <input type="hidden" name="{{ $fieldPrefix }}[mode]" id="ch-mode-input-{{ $scopeId }}" value="{{ $currentMode }}" />
</div>
<style>
    .ch-query-mode-toggle { display: flex; gap: 6px; margin-top: 4px; }
</style>
