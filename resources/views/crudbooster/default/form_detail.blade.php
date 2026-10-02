<?php
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
    #table-detail tr td:first-child {
        font-weight: bold;
        width: 25%;
    }
</style>
@endpush

<div class='table-responsive'>
    <table id='table-detail' class='table table-striped'>
        @foreach($forms as $form)
        @include('crudbooster::default.form_detail_row', ['form' => $form])
        @endforeach
    </table>
</div>
@endif
