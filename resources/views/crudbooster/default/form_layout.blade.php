<?php
// Form a blocchi e schede (docs/refactoring/197): usato da form_body quando il
// controller del modulo ha un blocco FORM LAYOUT. Ogni campo e' disegnato da
// form_field, come nel form piatto.
$byName = [];
foreach ($forms as $i => $f) {
    if (isset($f['name'])) {
        // Stesso nome due volte (es. select disabilitata + hidden di backup per
        // il tenant admin): il layout disegna quella visibile, l'hidden viene
        // disegnato a parte piu' sotto.
        $isHidden = ($f['type'] ?? 'text') === 'hidden';
        if (!isset($byName[$f['name']]) || !$isHidden) {
            $byName[$f['name']] = ['form' => $f, 'index' => $i];
        }
    }
}
$layoutTabs = \App\Helpers\ModuleGeneratorLayout::tabs($form_layout);
$placedNames = \App\Helpers\ModuleGeneratorLayout::placedNames($form_layout);
// tenant/group/primary_group: scheda "Sistema" (intervento 238; prima un box
// collassabile "System Information" in fondo), a meno che il layout non li
// posizioni esplicitamente (es. scheda "Sistema" degli utenti). Se il layout ha
// gia' una scheda "Sistema" i campi vanno li', altrimenti se ne aggiunge una in coda.
$systemNames = array_values(array_filter(\App\Helpers\ModuleGeneratorLayout::ALWAYS_NAMES, function ($n) use ($byName, $placedNames) {
    return isset($byName[$n]) && !in_array($n, $placedNames, true);
}));
$sysTabIdx = null;
if ($systemNames) {
    $sysTabIdx = \App\Helpers\ModuleGeneratorLayout::systemTabIndex($layoutTabs);
    if ($sysTabIdx === null) {
        $layoutTabs[] = ['id' => 'sysauto', 'title' => trans('crudbooster.form_tab_system'), 'blocks' => []];
        $sysTabIdx = count($layoutTabs) - 1;
    }
}
?>
@push('head')
<style>
    .cb-tabs { margin: 0 15px 12px; }
    .cb-layout { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: 12px; align-items: stretch; padding: 0 15px 15px; }
    .cb-card { height: 100%; }
    .cb-fields { display: flex; flex-wrap: wrap; gap: 0 12px; }
    .cb-field { flex: 0 0 calc((100% - 11 * 12px) / 12 * var(--cbw) + (var(--cbw) - 1) * 12px); max-width: 100%; min-width: 0; }
    /* i componenti sono pensati per un form orizzontale (etichetta a sinistra): nei blocchi l'etichetta sta sopra il campo */
    .cb-field .row { margin-left: 0; margin-right: 0; }
    .cb-field .row > [class*="col-"] { flex: 0 0 100%; width: 100%; max-width: 100%; }
    .cb-field .col-form-label { text-align: left; padding-bottom: .25rem; }
    @media (max-width: 767.98px) {
        .cb-layout { display: block; }
        .cb-block { margin-bottom: 12px; }
        .cb-field { flex: 0 0 100%; }
    }
</style>
@endpush

@if(count($layoutTabs) > 1)
<ul class="nav nav-tabs mb-3 cb-tabs" role="tablist">
    @foreach($layoutTabs as $ti => $tab)
    <li class="nav-item" role="presentation">
        <button type="button" class="nav-link {{ $ti === 0 ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#cbt-{{ $tab['id'] }}" role="tab">{{ ($tab['title'] ?? '') !== '' ? $tab['title'] : trans('crudbooster.mg_lay_tab_default', ['n' => $ti + 1]) }}</button>
    </li>
    @endforeach
</ul>
@endif
<div class="tab-content">
    @foreach($layoutTabs as $ti => $tab)
    <div class="tab-pane fade {{ $ti === 0 ? 'show active' : '' }}" id="cbt-{{ $tab['id'] }}" role="tabpanel">
        <div class="cb-layout">
            @foreach(\App\Helpers\ModuleGeneratorLayout::sortedBlocks((array) ($tab['blocks'] ?? [])) as $blk)
            <div class="cb-block" style="grid-column: {{ $blk['x'] + 1 }} / span {{ $blk['w'] }}; grid-row: {{ $blk['row_start'] }} / span {{ $blk['row_span'] }};">
                <div class="card cb-card">
                    @if(!empty($blk['title']))
                    <div class="card-header"><strong>{{ $blk['title'] }}</strong></div>
                    @endif
                    <div class="card-body">
                        <div class="cb-fields">
                            @include('crudbooster::default.form_layout_fields', ['items' => (array) ($blk['fields'] ?? [])])
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @if($systemNames && $ti === $sysTabIdx)
        {{-- Tenant e gruppo (intervento 238): stessa logica per ruolo di sempre
             (add_default_form_fields), ora in una scheda invece che nel box in fondo.
             Griglia a parte: i blocchi sopra hanno righe esplicite. --}}
        <div class="cb-layout">
            <div class="cb-block" style="grid-column: 1 / span 12;">
                <div class="card cb-card">
                    <div class="card-body">
                        <div class="cb-fields">
                            @include('crudbooster::default.form_layout_fields', ['items' => array_map(function ($n) { return ['name' => $n, 'w' => 12]; }, $systemNames)])
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
        {{-- Contenuto speciale della scheda (chiave 'view', solo da layout scritti nel codice, es. utenti). --}}
        @if(!empty($tab['view']))
        @include($tab['view'])
        @endif
    </div>
    @endforeach
</div>

@foreach($forms as $lf_i => $lf_f)
<?php
    $lf_n = (string) ($lf_f['name'] ?? '');
    $lf_hidden = (($lf_f['type'] ?? 'text') === 'hidden') || ($lf_n !== '' && isset($parent_field) && $lf_n == $parent_field);
?>
@if($lf_n !== '' && $lf_hidden && !(($byName[$lf_n]['index'] ?? null) === $lf_i && (in_array($lf_n, $placedNames) || in_array($lf_n, $systemNames))))
@include('crudbooster::default.form_field', ['form' => $lf_f, 'index' => $lf_i, 'header_group_class' => 'header-group-' . $lf_i, 'no_system_box' => true])
@endif
@endforeach

@push('bottom')
<script>
    // Un campo obbligatorio in una scheda non visibile bloccherebbe l'invio
    // senza che l'utente capisca perche': si apre la scheda del primo campo non valido.
    document.addEventListener('invalid', function (e) {
        var pane = e.target.closest ? e.target.closest('.tab-pane') : null;
        if (!pane || pane.classList.contains('active') || !window.bootstrap) { return; }
        var btn = document.querySelector('[data-bs-target="#' + pane.id + '"]');
        if (!btn) { return; }
        bootstrap.Tab.getOrCreateInstance(btn).show();
        setTimeout(function () { try { e.target.reportValidity(); } catch (x) {} }, 250);
    }, true);
</script>
@endpush
