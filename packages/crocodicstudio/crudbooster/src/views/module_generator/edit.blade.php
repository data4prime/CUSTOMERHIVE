@extends('crudbooster::admin_template')

@section('content')

<div style="/*width:750px;margin:0 auto*/">

  @if(CRUDBooster::getCurrentMethod() != 'getProfile')
  <p>
    <a href='{{CRUDBooster::mainpath()}}'>{{trans("crudbooster.form_back_to_list",['module'=>CRUDBooster::getCurrentModule()->name])}}
    </a>
  </p>
  @endif

  <!-- Box -->
  <div class="card card-primary">
    <div class="card-header mb-3 with-border">
      <h5 class="card-title">{{ $page_title }}</h5>
      <div class="card-tools">

      </div>
    </div>
    <form method='post'
      action='{{ (@$row->id)?route("ModulsControllerPostEditSave")."/$row->id":route("ModulsControllerPostAddSave") }}'>
      <input type="hidden" name="_token" value="{{ csrf_token() }}">
      <div class="box-body">
        <?php

                //Loading Assets

                $asset_already = [];
                foreach($forms as $form) {
                  $type = @$form['type'] ?: 'text';
                  $name = $form['name'];

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
                foreach($forms as $index=>$form) {

                  $name = $form['name'];
                  @$join = $form['join'];
                  @$value = (isset($form['value'])) ? $form['value'] : '';
                  @$value = (isset($row->{$name})) ? $row->{$name} : $value;

                  $old = old($name);
                  $value = (! empty($old)) ? $old : $value;

                  $validation = array();
                  $validation_raw = isset($form['validation']) ? explode('|', $form['validation']) : array();
                  if ($validation_raw) {
                    foreach ($validation_raw as $vr) {
                      $vr_a = explode(':', $vr);
                      if (isset($vr_a[1])) {
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
                    $value = @${"join_query_".$join_table}->{$join_title};
                  }
                  $form['type'] = isset($form['type']) ? $form['type']: 'text';
                  $type = @$form['type'];
                  $required = (@$form['required']) ? "required" : "";
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

        @if(file_exists(base_path('/packages/crocodicstudio/crudbooster/src/views/default/type_components/'.$type.'/component.blade.php')))
        @include('crudbooster::default.type_components.'.$type.'.component')
        @elseif(file_exists(resource_path('views/vendor/crudbooster/type_components/'.$type.'/component.blade.php')))
        @include('vendor.crudbooster.type_components.'.$type.'.component')
        @else
        <p class='text-danger'>{{$type}} is not found in type component system</p><br />
        @endif
        <?php
                }
                ?>
        <div id='privileges_configuration' class=''>
          <label>Enable/Disable Module on Tenants</label>
          <table class='table table-hover table-bordered'>
            <thead>
              <tr class='active'>
                <?php foreach ($tenants as $tenant): ?>
                <th style="text-align: center">{{ $tenant->name }}</th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php $no = 1;?>
              <tr>
                <?php
                                      $colors = ['active', 'warning', 'success', 'danger'];
                                      foreach ($tenants as $key => $tenant){
                                        if(ModuleHelper::is_enabled($module->id, $tenant->id))
                                        {
                                          $checked = 'checked';
                                        }
                                        else{
                                          $checked = '';
                                        }

                                     ?>
                <td class='<?php echo isset($colors[$key]) ? $colors[$key] : "" ?>' align="center">
                  <input {{$checked}} type='checkbox' class="module_tenant_enabler" data-tenant-id='{{$tenant->id}}'
                    data-module-id='{{$module->id}}' name='module_tenant_enabler[<?php echo $module->id?>][<?php echo $tenant->id?>]'
                    value='1' />
                </td>
                <?php } ?>
              </tr>
            </tbody>
          </table>

        </div>

      </div><!-- /.box-body -->
      <div class="card-footer" align="right">
        <button type='button' onclick="location.href='{{CRUDBooster::mainpath()}}'"
          class='btn btn-default'>{{trans("crudbooster.button_cancel")}}</button>
        <button type='submit' class='btn btn-primary'><i class='fa fa-save'></i>
          {{trans("crudbooster.button_save")}}</button>
      </div><!-- /.box-footer-->
  </div><!-- /.box -->

</div><!-- /.row -->
@endsection