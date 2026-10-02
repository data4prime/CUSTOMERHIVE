<?php
// Form a blocchi e schede (docs/refactoring/197): usato da form_body quando il
// controller del modulo ha un blocco FORM LAYOUT. Ogni campo e' disegnato da
// form_field, come nel form piatto.
$byName = [];
foreach ($forms as $i => $f) {
    if (isset($f['name'])) {
        $byName[$f['name']] = ['form' => $f, 'index' => $i];
    }
}
$layoutBlocks = \App\Helpers\ModuleGeneratorLayout::sortedBlocks($form_layout);
$placedNames = \App\Helpers\ModuleGeneratorLayout::placedNames($form_layout);
$systemNames = array_values(array_filter(\App\Helpers\ModuleGeneratorLayout::ALWAYS_NAMES, function ($n) use ($byName) {
    return isset($byName[$n]);
}));
?>
@push('head')
<style>
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

<div class="cb-layout">
    @foreach($layoutBlocks as $blk)
    <div class="cb-block" style="grid-column: {{ $blk['x'] + 1 }} / span {{ $blk['w'] }}; grid-row: {{ $blk['row_start'] }} / span {{ $blk['row_span'] }};">
        @if(($blk['kind'] ?? 'block') === 'tabs')
        <div class="card cb-card">
            <div class="card-header pb-0">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    @foreach((array) ($blk['tabs'] ?? []) as $ti => $tab)
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link {{ $ti === 0 ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#cbt-{{ $blk['id'] }}-{{ $tab['id'] }}" role="tab">{{ $tab['title'] !== '' ? $tab['title'] : '#' . ($ti + 1) }}</button>
                    </li>
                    @endforeach
                </ul>
            </div>
            <div class="card-body tab-content">
                @foreach((array) ($blk['tabs'] ?? []) as $ti => $tab)
                <div class="tab-pane fade {{ $ti === 0 ? 'show active' : '' }}" id="cbt-{{ $blk['id'] }}-{{ $tab['id'] }}" role="tabpanel">
                    <div class="cb-fields">
                        @include('crudbooster::default.form_layout_fields', ['items' => (array) ($tab['fields'] ?? [])])
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @else
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
        @endif
    </div>
    @endforeach
</div>

@if($systemNames)
{{-- Blocco di sistema: tenant e gruppo, con la stessa logica per ruolo di
     sempre (add_default_form_fields), nello stesso box collassabile "System
     Information". Chiuso qui, senza dipendere dall'ordine dei campi. --}}
<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="box box-info collapsed-box">
            <div class="box-header mb-3 with-border">
                <h3 class="box-title">
                    <strong>
                        <i class='bi bi-gear-wide-connected'></i> {{ trans('crudbooster.system_information') }}
                    </strong>
                </h3>
                <div class="box-tools float-end">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
            </div>
            <div class="box-body no-padding">
                @foreach($systemNames as $sn)
                <?php
                    $lf_form = $byName[$sn]['form'];
                    $lf_index = $byName[$sn]['index'];
                ?>
                @include('crudbooster::default.form_field', ['form' => $lf_form, 'index' => $lf_index, 'header_group_class' => 'header-group-' . $lf_index, 'no_system_box' => true])
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

@foreach($forms as $lf_i => $lf_f)
<?php
    $lf_n = (string) ($lf_f['name'] ?? '');
    $lf_hidden = (($lf_f['type'] ?? 'text') === 'hidden') || ($lf_n !== '' && isset($parent_field) && $lf_n == $parent_field);
?>
@if($lf_n !== '' && $lf_hidden && !in_array($lf_n, $placedNames) && !in_array($lf_n, $systemNames))
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
