<?php
// Riga di dettaglio di un campo (label + valore). Estratta da form_detail
// perche' la usano sia l'elenco piatto sia i blocchi/schede del layout
// (docs/refactoring/203). Riceve $form; il resto (row, table) e' ereditato.
$name = $form['name'];
@$join = $form['join'];
@$value = (isset($form['value'])) ? $form['value'] : '';
@$value = (isset($row->{$name})) ? $row->{$name} : $value;
@$showInDetail = (isset($form['showInDetail'])) ? $form['showInDetail'] : true;
?>
@if($showInDetail != FALSE)
<?php
if (isset($form['callback_php'])) {
    @eval("\$value = ".$form['callback_php'].";");
}

if (isset($form['callback'])) {
    $value = call_user_func($form['callback'], $row);
}

if (isset($form['default_value'])) {
    @$value = $form['default_value'];
}

if ($join && @$row) {
    $join_arr = explode(',', $join);
    array_walk($join_arr, 'trim');
    $join_table = $join_arr[0];
    $join_title = $join_arr[1];
    $join_table_pk = CB::pk($join_table);
    $join_fk = CB::getForeignKey($table, $join_table);
    ${"join_query_".$join_table} = DB::table($join_table)->select($join_title)->where($join_table_pk, $row->{$join_fk})->first();
    $value = @${"join_query_".$join_table}->{$join_title};
}

$type = @$form['type'] ?: 'text';
$required = (@$form['required']) ? "required" : "";
$readonly = (@$form['readonly']) ? "readonly" : "";
$disabled = (@$form['disabled']) ? "disabled" : "";
$jquery = @$form['jquery'];
$placeholder = (@$form['placeholder']) ? "placeholder='".$form['placeholder']."'" : "";
$file_location = resource_path('views/crudbooster/default/type_components/'.$type.'/component_detail.blade.php');
$user_location = resource_path('views/vendor/crudbooster/type_components/'.$type.'/component_detail.blade.php');
?>

@if(file_exists($file_location))
<?php $containTR = (substr(trim(file_get_contents($file_location)), 0, 4) == '<tr>') ? TRUE : FALSE;?>
@if($containTR)
@include('crudbooster::default.type_components.'.$type.'.component_detail')
@else
<tr data-field="{{ $name }}"{!! !empty($form['detail_half']) ? ' class="dt-half"' : '' !!}>
    <td>{{$form['label']}}</td>
    <td>@include('crudbooster::default.type_components.'.$type.'.component_detail')</td>
</tr>
@endif
@elseif(file_exists($user_location))
<?php $containTR = (substr(trim(file_get_contents($user_location)), 0, 4) == '<tr>') ? TRUE : FALSE;?>
@if($containTR)
@include('vendor.crudbooster.type_components.'.$type.'.component_detail')
@else
<tr data-field="{{ $name }}"{!! !empty($form['detail_half']) ? ' class="dt-half"' : '' !!}>
    <td>{{$form['label']}}</td>
    <td>@include('vendor.crudbooster.type_components.'.$type.'.component_detail')</td>
</tr>
@endif
@else
<!-- <tr><td colspan='2'>NO COMPONENT {{$type}}</td></tr> -->
@endif
@endif
