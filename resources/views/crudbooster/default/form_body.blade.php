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
$header_group_class = "";
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
  ?>
@include('crudbooster::default.form_field', ['form' => $form, 'index' => $index, 'header_group_class' => $header_group_class])
<?php
}
}
?>
