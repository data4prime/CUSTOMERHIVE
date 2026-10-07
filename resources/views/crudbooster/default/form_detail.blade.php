<?php
// Tenant/gruppo dei moduli mg_* come nel form di modifica (form_body.blade.php):
// senza questa chiamata il dettaglio non li mostrava nella scheda "Sistema".
$forms = ModuleHelper::add_default_form_fields($table, $forms);

//Loading Assets
$asset_already = [];
foreach($forms as $form) {
$type = @$form['type'] ?: 'text';

if (in_array($type, $asset_already)) continue;

?>
@if(file_exists(resource_path('views/crudbooster/default/type_components/'.$type.'/asset.blade.php')))
@include('crudbooster::default.type_components.'.$type.'.asset')
@elseif(file_exists(resource_path('views/vendor/crudbooster/type_components/'.$type.'/asset.blade.php')))
@include('vendor.crudbooster.type_components.'.$type.'.asset')
@endif
<?php
$asset_already[] = $type;
} //end forms
?>

<?php $detail_use_layout = \App\Helpers\ModuleGeneratorLayout::isActive(isset($form_layout) ? $form_layout : null); ?>
@if($detail_use_layout)
{{-- Blocchi e schede come nel form (docs/refactoring/203) --}}
@include('crudbooster::default.form_detail_layout')
@else
@push('head')
<style type="text/css">
    #table-detail tr td:first-child, .table-detail-sys tr td:first-child {
        font-weight: bold;
        width: 25%;
    }
</style>
@endpush

@php
    // Tenant/gruppo in una scheda "Sistema" come nel form (intervento 238);
    // senza questi campi la tabella resta unica, come prima.
    $dt_system = array_values(array_filter($forms, function ($f) {
        return in_array($f['name'] ?? '', \App\Helpers\ModuleGeneratorLayout::ALWAYS_NAMES, true) && ($f['type'] ?? 'text') !== 'hidden';
    }));
    $dt_general = $dt_system ? array_values(array_filter($forms, function ($f) {
        return !(in_array($f['name'] ?? '', \App\Helpers\ModuleGeneratorLayout::ALWAYS_NAMES, true) && ($f['type'] ?? 'text') !== 'hidden');
    })) : $forms;
@endphp
@if($dt_system)
<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item" role="presentation">
        <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#cdt-general" role="tab">{{ trans('crudbooster.form_tab_general') }}</button>
    </li>
    <li class="nav-item" role="presentation">
        <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#cdt-system" role="tab">{{ trans('crudbooster.form_tab_system') }}</button>
    </li>
</ul>
<div class="tab-content">
    <div class="tab-pane fade show active" id="cdt-general" role="tabpanel">
        <div class='table-responsive'>
            <table id='table-detail' class='table table-striped'>
                @foreach($dt_general as $form)
                @include('crudbooster::default.form_detail_row', ['form' => $form])
                @endforeach
            </table>
        </div>
    </div>
    <div class="tab-pane fade" id="cdt-system" role="tabpanel">
        <div class='table-responsive'>
            <table class='table table-striped table-detail-sys'>
                @foreach($dt_system as $form)
                @include('crudbooster::default.form_detail_row', ['form' => $form])
                @endforeach
            </table>
        </div>
    </div>
</div>
@else
<div class='table-responsive'>
    <table id='table-detail' class='table table-striped'>
        @foreach($forms as $form)
        @include('crudbooster::default.form_detail_row', ['form' => $form])
        @endforeach
    </table>
</div>
@endif
@endif
