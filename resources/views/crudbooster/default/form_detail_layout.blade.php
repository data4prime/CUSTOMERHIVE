<?php
// Dettaglio a blocchi e schede (docs/refactoring/203): stessa griglia del form
// (form_layout.blade.php), ma ogni blocco e' una tabella etichetta/valore di
// sola lettura. Le larghezze dei singoli campi non si applicano: nel dettaglio
// ogni campo e' una riga.
$byName = [];
foreach ($forms as $f) {
    // Stesso nome due volte (select disabilitata + hidden di backup): nel
    // dettaglio vale quella visibile.
    if (isset($f['name']) && (!isset($byName[$f['name']]) || ($f['type'] ?? 'text') !== 'hidden')) {
        $byName[$f['name']] = $f;
    }
}
$layoutTabs = \App\Helpers\ModuleGeneratorLayout::tabs($form_layout);
$placedNames = \App\Helpers\ModuleGeneratorLayout::placedNames($form_layout);
$restForms = array_values(array_filter($forms, function ($f) use ($placedNames) {
    return isset($f['name']) && !in_array($f['name'], $placedNames, true) && ($f['type'] ?? 'text') !== 'hidden';
}));
// Tenant/gruppo non posizionati: scheda "Sistema" (intervento 238), la stessa del
// form; se il layout ne ha gia' una vanno li', altrimenti se ne aggiunge una in coda.
$sysRest = array_values(array_filter($restForms, function ($f) {
    return in_array($f['name'], \App\Helpers\ModuleGeneratorLayout::ALWAYS_NAMES, true);
}));
$restForms = array_values(array_filter($restForms, function ($f) {
    return !in_array($f['name'], \App\Helpers\ModuleGeneratorLayout::ALWAYS_NAMES, true);
}));
$sysTabIdx = null;
if ($sysRest) {
    $sysTabIdx = \App\Helpers\ModuleGeneratorLayout::systemTabIndex($layoutTabs);
    if ($sysTabIdx === null) {
        $layoutTabs[] = ['id' => 'sysauto', 'title' => trans('crudbooster.form_tab_system'), 'blocks' => []];
        $sysTabIdx = count($layoutTabs) - 1;
    }
}
?>
@push('head')
<style>
    .cbd-layout { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: 12px; align-items: stretch; margin-bottom: 12px; }
    .cbd-card { height: 100%; margin-bottom: 0; }
    .cbd-fields { display: flex; flex-wrap: wrap; gap: 0 12px; }
    .cbd-field { flex: 0 0 calc((100% - 11 * 12px) / 12 * var(--cbw) + (var(--cbw) - 1) * 12px); max-width: 100%; min-width: 0; }
    /* come nel form l'etichetta sta sopra il valore; la mini tabella di ogni campo diventa a blocchi */
    .cbd-table { margin-bottom: .5rem; }
    .cbd-table, .cbd-table tbody, .cbd-table tr, .cbd-table td { display: block; width: 100%; }
    .cbd-table td { border: 0; padding: .1rem 0; }
    .cbd-table tr td:first-child { font-weight: bold; font-size: .85em; color: var(--ch-text-secondary); width: 100%; }
    /* Stessi margini del form di modifica (form_layout.blade.php: .cb-tabs e .cb-layout) */
    .flat-form .nav-tabs { margin: 0 15px 12px; }
    .flat-form .cbd-layout { padding: 0 15px 15px; margin-bottom: 0; }
    /* Pagine con intestazione propria (utenti): il valore sta in un riquadro come il campo del form di modifica */
    .flat-form .cbd-table tr td:last-child:not(:has(.ch-image)):not(:has(.ch-grid-card)):not(:has(.ch-qlik-detail)) { min-height: var(--ch-field-h); padding: 7px var(--ch-field-px); border: 1px solid var(--ch-field-border); border-radius: var(--ch-radius-sm); background: var(--ch-field-bg); overflow-wrap: anywhere; }
    /* stessa altezza di etichetta e stessa distanza tra i campi del form di modifica (etichetta 32px, riga 102px) */
    .flat-form .cbd-table tr td:first-child { color: var(--ch-text); font-size: var(--ch-font-size-sm); min-height: 32px; padding: 6px 0 4px; }
    .flat-form .cbd-table { margin-bottom: 32px; }
    /* la griglia child (ch-grid) dentro la mini tabella del campo torna una tabella vera */
    .cbd-table .ch-grid { display: table; width: 100%; }
    .cbd-table .ch-grid thead { display: table-header-group; }
    .cbd-table .ch-grid tbody { display: table-row-group; }
    .cbd-table .ch-grid tr { display: table-row; }
    .cbd-table .ch-grid th, .cbd-table .ch-grid td { display: table-cell; width: auto; }
    .cbd-table .ch-grid td { padding: 10px 12px; border: 0; border-bottom: 1px solid var(--ch-border); border-radius: 0; background: transparent; min-height: 0; font-weight: normal; font-size: inherit; color: inherit; }
    .cbd-table .ch-grid tr:last-child td { border-bottom: 0; }
    .cbd-table .ch-grid td.ch-grid-empty { padding: 22px; text-align: center; color: var(--ch-text-muted); }
    .cbd-card table:not(.cbd-table) { margin-bottom: 0; }
    .cbd-card table:not(.cbd-table) tr td:first-child { font-weight: bold; width: 35%; }
    @media (max-width: 767.98px) {
        .cbd-layout { display: block; }
        .cbd-block { margin-bottom: 12px; }
        .cbd-field { flex: 0 0 100%; }
    }
</style>
@endpush

@if(count($layoutTabs) > 1)
<ul class="nav nav-tabs mb-3" role="tablist">
    @foreach($layoutTabs as $ti => $tab)
    <li class="nav-item" role="presentation">
        <button type="button" class="nav-link {{ $ti === 0 ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#cbdt-{{ $tab['id'] }}" role="tab">{{ ($tab['title'] ?? '') !== '' ? $tab['title'] : trans('crudbooster.mg_lay_tab_default', ['n' => $ti + 1]) }}</button>
    </li>
    @endforeach
</ul>
@endif
<div class="tab-content">
    @foreach($layoutTabs as $ti => $tab)
    <div class="tab-pane fade {{ $ti === 0 ? 'show active' : '' }}" id="cbdt-{{ $tab['id'] }}" role="tabpanel">
        <div class="cbd-layout">
            @foreach(\App\Helpers\ModuleGeneratorLayout::sortedBlocks((array) ($tab['blocks'] ?? [])) as $blk)
            <div class="cbd-block" style="grid-column: {{ $blk['x'] + 1 }} / span {{ $blk['w'] }}; grid-row: {{ $blk['row_start'] }} / span {{ $blk['row_span'] }};">
                <div class="card cbd-card">
                    @if(!empty($blk['title']))
                    <div class="card-header"><strong>{{ $blk['title'] }}</strong></div>
                    @endif
                    <div class="card-body">
                        <div class="cbd-fields">
                            @include('crudbooster::default.form_detail_layout_rows', ['items' => (array) ($blk['fields'] ?? [])])
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @if($sysRest && $ti === $sysTabIdx)
        {{-- Stessa struttura della scheda "Sistema" del form (form_layout): griglia a parte, una card, etichetta sopra il valore. --}}
        <div class="cbd-layout">
            <div class="cbd-block" style="grid-column: 1 / span 12;">
                <div class="card cbd-card">
                    <div class="card-body">
                        <div class="cbd-fields">
                            @include('crudbooster::default.form_detail_layout_rows', ['items' => array_map(function ($rf) { return ['name' => $rf['name'], 'w' => 12]; }, $sysRest)])
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endforeach
</div>

@if($restForms)
{{-- Campi non posizionati rimasti (tenant/group, hidden): in coda come prima. --}}
<div class="card cbd-card mb-3">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                @foreach($restForms as $rf)
                @include('crudbooster::default.form_detail_row', ['form' => $rf])
                @endforeach
            </table>
        </div>
    </div>
</div>
@endif
