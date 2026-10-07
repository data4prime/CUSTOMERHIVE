@extends('crudbooster::admin_template')

@section('content')

<div style="/*width:750px;margin:0 auto*/">

  @if(CRUDBooster::getCurrentMethod() != 'getProfile')
  <p>
    <a title='Main Module' href='{{CRUDBooster::mainpath()}}'>
      <i class='bi bi-chevron-left'></i>&nbsp;
      {{trans("crudbooster.form_back_to_list",['module'=>CRUDBooster::getCurrentModule()->name])}}
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
        @if(file_exists(resource_path('views/crudbooster/default/type_components/'.$type.'/asset.blade.php')))
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

        @if(file_exists(resource_path('views/crudbooster/default/type_components/'.$type.'/component.blade.php')))
        @include('crudbooster::default.type_components.'.$type.'.component')
        @elseif(file_exists(resource_path('views/vendor/crudbooster/type_components/'.$type.'/component.blade.php')))
        @include('vendor.crudbooster.type_components.'.$type.'.component')
        @else
        <p class='text-danger'>{{$type}} is not found in type component system</p><br />
        @endif
        <?php
                }
                ?>
        {{-- Matrice dei tenant come nel mockup: una colonna per tenant + "tutti" a inizio riga.
             Stessi campi di prima (module_tenant_enabler[modulo][tenant] = 1). --}}
        <div id='privileges_configuration' class='mt-4'>
          <label class="fw-semibold mb-2">{{ trans('crudbooster.mg_enable_on_tenants') }}</label>
          <div class="table-responsive rel-table">
            <table class='table align-middle mb-0'>
              <thead>
                <tr>
                  <th class="text-center" style="width:90px">{{ trans('crudbooster.mg_all_tenants') }}</th>
                  @foreach ($tenants as $tenant)
                  <th class="text-center">{{ $tenant->name }}</th>
                  @endforeach
                </tr>
              </thead>
              <tbody>
                <tr>
                  @php
                    $mgEnabled = [];
                    foreach ($tenants as $tenant) { $mgEnabled[$tenant->id] = ModuleHelper::is_enabled($module->id, $tenant->id); }
                  @endphp
                  <td class="text-center">
                    <div class="d-flex justify-content-center">
                    @include('crudbooster::partials.ch_check', [
                      'name' => '', 'value' => '1', 'label' => '', 'switch' => false,
                      'checked' => count($mgEnabled) > 0 && !in_array(false, $mgEnabled, true),
                      'input_id' => 'mg-tenant-all', 'aria' => trans('crudbooster.mg_all_tenants'),
                    ])
                    </div>
                  </td>
                  @foreach ($tenants as $tenant)
                  <td class="text-center">
                    <div class="d-flex justify-content-center">
                    @include('crudbooster::partials.ch_check', [
                      'name' => 'module_tenant_enabler[' . $module->id . '][' . $tenant->id . ']',
                      'value' => '1', 'label' => '', 'switch' => false,
                      'checked' => $mgEnabled[$tenant->id],
                      'input_class' => 'module_tenant_enabler',
                      'input_attrs' => ['data-tenant-id' => $tenant->id, 'data-module-id' => $module->id],
                      'aria' => $tenant->name,
                    ])
                    </div>
                  </td>
                  @endforeach
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        @push('bottom')
        <script>
          (function () {
            var all = document.getElementById('mg-tenant-all');
            var boxes = document.querySelectorAll('.module_tenant_enabler');
            if (!all) { return; }
            // "Tutti": spunta o toglie la spunta a ogni tenant; si riallinea se i singoli cambiano.
            all.addEventListener('change', function () { boxes.forEach(function (b) { b.checked = all.checked; }); });
            boxes.forEach(function (b) {
              b.addEventListener('change', function () {
                all.checked = Array.prototype.every.call(boxes, function (x) { return x.checked; });
              });
            });
          })();
        </script>
        @endpush

      </div><!-- /.box-body -->
      <div class="card-footer" align="right">
        <button type='button' onclick="location.href='{{CRUDBooster::mainpath()}}'"
          class='btn btn-secondary'>{{trans("crudbooster.button_cancel")}}</button>
        <button type='submit' class='btn btn-primary'><i class='bi bi-floppy-fill'></i>
          {{trans("crudbooster.button_save")}}</button>
      </div><!-- /.box-footer-->
  </div><!-- /.box -->

</div><!-- /.row -->
@endsection