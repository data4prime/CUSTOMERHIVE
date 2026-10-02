<?php
// Dettaglio a blocchi e schede (docs/refactoring/203): stessa griglia del form
// (form_layout.blade.php), ma ogni blocco e' una tabella etichetta/valore di
// sola lettura. Le larghezze dei singoli campi non si applicano: nel dettaglio
// ogni campo e' una riga.
$byName = [];
foreach ($forms as $f) {
    if (isset($f['name'])) {
        $byName[$f['name']] = $f;
    }
}
$layoutBlocks = \App\Helpers\ModuleGeneratorLayout::sortedBlocks($form_layout);
$placedNames = \App\Helpers\ModuleGeneratorLayout::placedNames($form_layout);
$restForms = array_values(array_filter($forms, function ($f) use ($placedNames) {
    return isset($f['name']) && !in_array($f['name'], $placedNames, true);
}));
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
    .cbd-card table:not(.cbd-table) { margin-bottom: 0; }
    .cbd-card table:not(.cbd-table) tr td:first-child { font-weight: bold; width: 35%; }
    @media (max-width: 767.98px) {
        .cbd-layout { display: block; }
        .cbd-block { margin-bottom: 12px; }
        .cbd-field { flex: 0 0 100%; }
    }
</style>
@endpush

<div class="cbd-layout">
    @foreach($layoutBlocks as $blk)
    <?php $isTabs = ($blk['kind'] ?? 'block') === 'tabs'; ?>
    <div class="cbd-block" style="grid-column: {{ $blk['x'] + 1 }} / span {{ $blk['w'] }}; grid-row: {{ $blk['row_start'] }} / span {{ $blk['row_span'] }};">
        <div class="card cbd-card">
            @if($isTabs)
            <div class="card-header pb-0">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    @foreach((array) ($blk['tabs'] ?? []) as $ti => $tab)
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link {{ $ti === 0 ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#cbdt-{{ $blk['id'] }}-{{ $tab['id'] }}" role="tab">{{ $tab['title'] !== '' ? $tab['title'] : '#' . ($ti + 1) }}</button>
                    </li>
                    @endforeach
                </ul>
            </div>
            <div class="card-body tab-content">
                @foreach((array) ($blk['tabs'] ?? []) as $ti => $tab)
                <div class="tab-pane fade {{ $ti === 0 ? 'show active' : '' }}" id="cbdt-{{ $blk['id'] }}-{{ $tab['id'] }}" role="tabpanel">
                    <div class="cbd-fields">
                        @include('crudbooster::default.form_detail_layout_rows', ['items' => (array) ($tab['fields'] ?? [])])
                    </div>
                </div>
                @endforeach
            </div>
            @else
            @if(!empty($blk['title']))
            <div class="card-header"><strong>{{ $blk['title'] }}</strong></div>
            @endif
            <div class="card-body">
                <div class="cbd-fields">
                    @include('crudbooster::default.form_detail_layout_rows', ['items' => (array) ($blk['fields'] ?? [])])
                </div>
            </div>
            @endif
        </div>
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
