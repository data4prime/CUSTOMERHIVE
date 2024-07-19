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
@if(file_exists(base_path('/packages/crocodicstudio/crudbooster/src/views/default/type_components/'.$type.'/asset.blade.php')))
@include('crudbooster::default.type_components.'.$type.'.asset')
@elseif(file_exists(resource_path('views/vendor/crudbooster/type_components/'.$type.'/asset.blade.php')))
@include('vendor.crudbooster.type_components.'.$type.'.asset')
@endif
<?php

  $asset_already[] = $type;
}


//Loading input components
$header_group_class = "";
foreach($forms as $index => $form) {
  unset($value);
  /*
  * #RAMA add default value for group on mg_ for edit form
  */
  if($form['name']=='group' AND isset($row) AND isset($row->group)){
    $form['default']=\App\Group::find($row->group)->name;
  }

  $name = $form['name'];
  @$join = $form['join'];



  if(isset($row->{$name})){
    @$value = $row->{$name};
  }
  elseif(isset($form['value'])){
    @$value = $form['value'];
  }
  else{
    @$value = '';
  }

  $old = old($name);
  $value = (! empty($old)) ? $old : $value;

  $validation = array();
  $validation_raw = isset($form['validation']) ? explode('|', $form['validation']) : array();
  if ($validation_raw) {
    foreach ($validation_raw as $vr) {
      $vr_a = explode(':', $vr);
      if (isset($vr_a[1]) && $vr_a[1]) {
        $key = $vr_a[0];
        $validation[$key] = $vr_a[1];
      } else {
        $validation[$vr] = TRUE;
      }
    }
  }

  if (isset($form['callback_php'])) {
    @eval("\$value = ".$form['callback_php'].";");
  }


  if (isset($form['callback'])) {
    $value = call_user_func($form['callback'], $row);
  }

  if ($join && @$row) {
    $join_arr = explode(',', $join);
    array_walk($join_arr, 'trim');
    $join_table = $join_arr[0];
    $join_title = $join_arr[1];
    ${"join_query_".$join_table} = DB::table($join_table)->select($join_title)->where("id", $row->{'id_'.$join_table})->first();
    //${"join_query_".$join_table} = DB::table($join_table)->select($join_title)->where("id", $row->{'id_'.$join_table})->first();
    $value = @${"join_query_".$join_table}->{$join_title};
  }
  $form['type'] = (isset($form['type'])) ? $form['type']: 'text';
  $type = isset($form['type']) ? $form['type'] : 'text';
  $required = (isset($form['required']) && $form['required'] == true ) ? "required" : "";
  $required = (@strpos($form['validation'], 'required') !== FALSE) ? "required" : $required;
  $readonly = (@$form['readonly']) ? "readonly" : "";
  $disabled = (@$form['disabled']) ? "disabled" : "";
  $placeholder = (@$form['placeholder']) ? "placeholder='".$form['placeholder']."'" : "";
  $col_width = @$form['width'] ?: "col-sm-9";

  if ($parent_field == $name) {
    $type = 'hidden';
    $value = $parent_id;
  }

  if ($type == 'header') {
    $header_group_class = "header-group-$index";
  } else {
    $header_group_class = ($header_group_class) ?: "header-group-$index";
  }
  ?>
@if($name == 'tenant')
<div class="row">
  <div class="col-sm-12 col-md-12">
    <div class="box box-info collapsed-box">
      <div class="box-header with-border">
        <h3 class="box-title">
          <strong>
            <i class='{{CRUDBooster::getCurrentModule()->icon}}'></i> Informazioni di sistema
          </strong>
        </h3>
        <div class="box-tools pull-right">
          <!-- <span class="label label-info">0 righe</span> -->
          <button type="button" class="btn btn-box-tool" data-widget="collapse">
            <i class="fa fa-plus"></i>
          </button>
        </div>
      </div>
      <div class="box-body no-padding">
        @endif

        @if(file_exists(base_path('packages/crocodicstudio/crudbooster/src/views/default/type_components/'.$type.'/component.blade.php')))

        @include('crudbooster::default.type_components.'.$type.'.component')
        @elseif(file_exists(resource_path('views/vendor/crudbooster/type_components/'.$type.'/component.blade.php')))
        @include('vendor.crudbooster.type_components.'.$type.'.component')
        @else
        <p class='text-danger'>{{$type}} is not found in type component system</p><br />
        @endif
        @if($name == 'group')
      </div>
    </div>
  </div>
</div>
@endif
<?php
}
?>