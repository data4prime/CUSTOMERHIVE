@php
    // Loading Assets
    $forms = ModuleHelper::add_default_form_fields($table, $forms);

    foreach($forms as $key => $form) {
        if (isset($forms[$key]['validation'])) {
            $forms[$key]['validation'] = str_replace('required', '', $form['validation']);
        }

        if (isset($forms[$key]['width'])) {
            $forms[$key]['width'] = 'col-sm-8';
        }
    }

$fields_to_eliminate = ['multitext', 'password'];

    foreach($forms as $key => $form) {
        if (isset($form['type'])) {
            if(in_array($form['type'], $fields_to_eliminate)) {
                unset($forms[$key]);
            }
        }

    }


    $asset_already = [];



//dd($forms);
@endphp



@foreach($forms as $key => $form)
    @php
        $type = isset($form['type']) ? $form['type'] : 'text';
        $name = isset($form['name']) ? $form['name'] : '';
    @endphp

    @if(!in_array($type, $asset_already))
      
        @if(file_exists(base_path('/packages/crocodicstudio/crudbooster/src/views/default/type_components/'.$type.'/asset.blade.php')))
            @include('crudbooster::default.type_components.'.$type.'.asset')
        @elseif(file_exists(resource_path('views/vendor/crudbooster/type_components/'.$type.'/asset.blade.php')))
            @include('vendor.crudbooster.type_components.'.$type.'.asset')
        @endif      


        @php
            $asset_already[] = $type;
        @endphp
    @endif
@endforeach

@php
    // Loading input components
    $header_group_class = "";
    foreach($forms as $index => $form) {
        unset($value);
        
        // #RAMA add default value for group on mg_ for edit form
        if($form['name'] == 'group' && isset($row) && isset($row->group)) {
            $form['default'] = \App\Group::find($row->group)->name;
        }

        $name = $form['name'];
        @$join = $form['join'];

        if(isset($row->{$name})) {
            @$value = $row->{$name};
        } elseif(isset($form['value'])) {
            @$value = $form['value'];
        } else {
            @$value = '';
        }

        $old = old($name);
        $value = (!empty($old)) ? $old : $value;

        $validation = [];
        $validation_raw = isset($form['validation']) ? explode('|', $form['validation']) : [];
        if ($validation_raw) {
            foreach ($validation_raw as $vr) {
                $vr_a = explode(':', $vr);
                if (isset($vr_a[1]) && $vr_a[1]) {
                    $key = $vr_a[0];
                    $validation[$key] = $vr_a[1];
                } else {
                    $validation[$vr] = true;
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
        
        $form['type'] = $form['type'] ?? 'text';
        $type = $form['type'];
        $required = (isset($form['required']) && $form['required'] == true) ? "required" : "";
        $required = (@strpos($form['validation'], 'required') !== false) ? "required" : $required;
        $readonly = (@$form['readonly']) ? "readonly" : "";
        $disabled = (@$form['disabled']) ? "disabled" : "";
        $placeholder = (@$form['placeholder']) ? "placeholder='".$form['placeholder']."'" : "";
        $col_width = @$form['width'] ?: "col-sm-8";

        if (isset($parent_field) && $parent_field == $name) {
            $type = 'hidden';
            $value = $parent_id;
        }

        if ($type == 'header') {
            $header_group_class = "header-group-$index";
        } else {
            $header_group_class = ($header_group_class) ?: "header-group-$index";
        }
@endphp

    @if($name == 'tenant')
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="box box-info collapsed-box">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <strong>
                                <i class='{{ CRUDBooster::getCurrentModule()->icon }}'></i> Informazioni di sistema
                            </strong>
                        </h3>
                        <div class="box-tools pull-right">
                            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="box-body no-padding">
    @endif

<div class="row">
    @if(file_exists(base_path('packages/crocodicstudio/crudbooster/src/views/default/type_components/'.$type.'/component.blade.php')))
        @include('crudbooster::default.type_components.'.$type.'.component')
    @elseif(file_exists(resource_path('views/vendor/crudbooster/type_components/'.$type.'/component.blade.php')))
        @include('vendor.crudbooster.type_components.'.$type.'.component')
    @else
        <p class='text-danger'>{{ $type }} is not found in type component system</p><br />
    @endif


    <input type="checkbox" class="col-sm-2" name="mass_edit_{{ $name }}">
</div>
    @if($name == 'group')
                    </div>
                </div>
            </div>
        </div>
    @endif

@php
    }
@endphp
