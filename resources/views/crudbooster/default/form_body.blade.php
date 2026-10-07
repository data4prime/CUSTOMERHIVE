<?php

//Loading Assets
//add group and tenant columns for admins
$forms = ModuleHelper::add_default_form_fields($table, $forms);

//dd($forms);

$asset_already = [];

foreach($forms as $key => $form) {

  $type = isset($form['type']) ? $form['type'] : 'text';
  $name = isset($form['name']) ? $form['name'] : '';

  if (in_array($type, $asset_already)) continue;

  ?>
@if(file_exists(resource_path('views/crudbooster/default/type_components/'.$type.'/asset.blade.php')))
@include('crudbooster::default.type_components.'.$type.'.asset')
@elseif(file_exists(resource_path('views/vendor/crudbooster/type_components/'.$type.'/asset.blade.php')))
@include('vendor.crudbooster.type_components.'.$type.'.asset')
@endif
<?php

  $asset_already[] = $type;
}


// Campi due per riga (opzione 'detail_half' del campo, usata anche dal dettaglio):
// con almeno un campo segnato, i campi si dispongono su una griglia a due colonne
// con l'etichetta sopra (solo con il nuovo look). Gli altri restano a tutta larghezza.
$halfNames = [];
foreach ($forms as $hf) {
  if (!empty($hf['detail_half'])) { $halfNames[] = $hf['name']; }
}
if ($halfNames) {
  $halfSel = implode(',', array_map(function ($n) { return '#form-group-' . preg_replace('/[^A-Za-z0-9_-]/', '', $n); }, $halfNames));
  ?>
<style>
    body.ch-ui2 #parent-form-area { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); column-gap: 32px; }
    body.ch-ui2 #parent-form-area > .row { grid-column: 1 / -1; margin: 0 0 14px; }
    body.ch-ui2 #parent-form-area > .row .help-block:empty { display: none; }
    body.ch-ui2 #parent-form-area > .row > [class*="col-"] { flex: 0 0 100%; max-width: 100%; padding-left: 0; padding-right: 0; }
    body.ch-ui2 #parent-form-area > .row > .col-form-label { padding-bottom: 5px; font-size: 12.5px; font-weight: 700; color: var(--ch-text-secondary); }
    body.ch-ui2 {{ $halfSel }} { grid-column: auto !important; }
    @media (max-width: 767px) { body.ch-ui2 {{ $halfSel }} { grid-column: 1 / -1 !important; } }
</style>
<?php
}
//Loading input components
// Il disegno di ogni campo e' in form_field.blade.php (docs/refactoring/195).
// Qui resta solo lo stato cumulativo $header_group_class, che dipende dal
// campo precedente: lo calcola questo loop prima di ogni include.
// Con un layout a blocchi/schede (blocco FORM LAYOUT del controller, docs/
// refactoring/197) il disegno e' in form_layout.blade.php; senza, il form
// piatto di sempre.
$use_layout = \App\Helpers\ModuleGeneratorLayout::isActive(isset($form_layout) ? $form_layout : null);
if ($use_layout) {
  ?>
@include('crudbooster::default.form_layout')
<?php
} else {
// Form piatto. Tenant/gruppo (intervento 238) non stanno piu' in un box
// collassabile in fondo ma in una scheda "Sistema": se il modulo ha almeno uno
// di questi campi visibili il form si divide in "Generale" + "Sistema", altrimenti
// resta com'era (nessuna scheda).
$sys_forms = [];
foreach ($forms as $index => $form) {
  $sn = isset($form['name']) ? $form['name'] : '';
  $st = isset($form['type']) ? $form['type'] : 'text';
  if (in_array($sn, \App\Helpers\ModuleGeneratorLayout::ALWAYS_NAMES, true) && $st !== 'hidden' && $parent_field != $sn) {
    $sys_forms[$index] = true;
  }
}
$header_group_class = "";
$flat_general = [];
$flat_system = [];
foreach($forms as $index => $form) {
  $hg_type = isset($form['type']) ? $form['type'] : 'text';
  if ($parent_field == $form['name']) {
    $hg_type = 'hidden';
  }

  if ($hg_type == 'header') {
    $header_group_class = "header-group-$index";
  } else {
    $header_group_class = ($header_group_class) ?: "header-group-$index";
  }
  $entry = ['form' => $form, 'index' => $index, 'header_group_class' => $header_group_class];
  if (isset($sys_forms[$index])) {
    $flat_system[] = $entry;
  } else {
    $flat_general[] = $entry;
  }
}
?>
@if($flat_system)
@push('head')
<style>
    .ch-flat-tabs { margin: 0 15px 12px; }
</style>
@endpush
<ul class="nav nav-tabs mb-3 ch-flat-tabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#cft-general" role="tab">{{ trans('crudbooster.form_tab_general') }}</button>
    </li>
    <li class="nav-item" role="presentation">
        <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#cft-system" role="tab">{{ trans('crudbooster.form_tab_system') }}</button>
    </li>
</ul>
<div class="tab-content">
    <div class="tab-pane fade show active" id="cft-general" role="tabpanel">
        @foreach($flat_general as $fe)
        @include('crudbooster::default.form_field', ['form' => $fe['form'], 'index' => $fe['index'], 'header_group_class' => $fe['header_group_class'], 'no_system_box' => true])
        @endforeach
    </div>
    <div class="tab-pane fade" id="cft-system" role="tabpanel">
        @foreach($flat_system as $fe)
        @include('crudbooster::default.form_field', ['form' => $fe['form'], 'index' => $fe['index'], 'header_group_class' => $fe['header_group_class'], 'no_system_box' => true])
        @endforeach
    </div>
</div>
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
@else
@foreach($flat_general as $fe)
@include('crudbooster::default.form_field', ['form' => $fe['form'], 'index' => $fe['index'], 'header_group_class' => $fe['header_group_class']])
@endforeach
@endif
<?php
}
?>
