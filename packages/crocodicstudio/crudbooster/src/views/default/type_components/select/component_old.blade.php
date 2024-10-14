@php
use Illuminate\Support\Facades\Schema;
@endphp
<?php

//dd($form);

  $default = isset($form['default']) ? $form['default'] : trans('crudbooster.text_prefix_option')." ".$form['label'];

?>

@if(array_key_exists('parent_select', $form))
<?php

    $parent_select = (count(explode(",", $form['parent_select'])) > 1) ? explode(",", $form['parent_select']) : $form['parent_select'];

    $parent = is_array($parent_select) ? $parent_select[0] : $parent_select;
    $add_field = is_array($parent_select) ? $parent_select[1] : '';
    ?>
@push('bottom')
<script type="text/javascript">
    $(function () {

        var default_tenant;

        $('#{{$parent}}, input:radio[name={{$parent}}]').change(function () {
            var $current = $("#{{$form['name']}}");
            var parent_id = $(this).val();
            var fk_name = "{{$parent}}";
            var fk_value = $(this).val();
            var datatable = "{{$form['datatable']}}".split(',');
            @php  if (!empty($add_field)) {
                @endphp
                var add_field = ($("#{{$add_field}}").val()) ? $("#{{$add_field}}").val() : "";
                @php
            } @endphp
            var datatableWhere = "{{$form['datatable_where']}}";
            console.log(datatableWhere);
            @if (!empty($add_field))
                if (datatableWhere) {
                    if (add_field) {
                        datatableWhere = datatableWhere + " and {{$add_field}} = " + add_field;
                    }
                } else {
                    if (add_field) {
                        datatableWhere = "{{$add_field}} = " + add_field;
                    }
                }
            @endif
            var table = datatable[0].trim('');
            var label = datatable[1].trim('');
            var value = "{{$value}}";
            var is_default_present = false;
            var belongs_to_tenant = true;
            //salvo solo all'atterraggio default_tenant e non ogni volta che la tendina tenant cambia
            if (typeof default_tenant == 'undefined') {
                default_tenant = fk_value;
            }

            if (fk_value != '') {
                $current.html("<option value=''>{{trans('crudbooster.text_prefix_option')}} {{$form['label']}}");
                console.log(datatableWhere);
                $.get("{{CRUDBooster::mainpath('data-table')}}?table=" + table + "&label=" + label + "&fk_name=" + fk_name + "&fk_value=" + fk_value + "&datatable_where=" + encodeURI(datatableWhere), function (response) {
                    if (response) {
                        //check if default is already between the options
                        $.each(response, function (i, obj) {
                            if (obj.select_label == '{{$default}}') {
                                is_default_present = true;
                            }
                        })
                        if (fk_value !== default_tenant) {
                            belongs_to_tenant = false;
                        }
                        //if it's not a duplicate..
                        //and group's tenant is selected
                        if (!is_default_present && belongs_to_tenant) {
                            //..add the default option
                            $current.html("<option value=''>{{$default}}");
                        }
                        //add the other options
                        $.each(response, function (i, obj) {
                            var selected = (value && value == obj.select_value) ? "selected" : "";
                            $("<option " + selected + " value='" + obj.select_value + "'>" + obj.select_label + "</option>").appendTo("#{{$form['name']}}");
                        })
                        $current.trigger('change');
                    }
                });
            } else {
                $current.html("<option value=''>{{$default}}");
            }
        })

        $('#{{$parent}}').trigger('change');
        $("input[name='{{$parent}}']:checked").trigger("change");
        $("#{{$form['name']}}").trigger('change');
    })
</script>
@endpush

@endif


<div class='mb-3 row {{$header_group_class}} {{ ($errors->first($name))?"has-error":"" }}' id='mb-3 row-{{$name}}'
    style="{{ isset($form['style']) ? $form['style'] : '' }}">
    <label class='col-form-labell col-sm-2'>{{$form['label']}}
        @if($required)
        <span class='text-danger' title="{!! trans('crudbooster.this_field_is_required') !!}">*</span>
        @endif
    </label>

    <div class="{{$col_width?:'col-sm-10'}}">
        <select class='form-control' id="{{$name}}" data-value='{{$value}}' {{$required}} {!! $placeholder !!}
            {{$readonly}} {{$disabled}} name="{{$name}}">
            <option value='{{$default}}'>{{$default}}</option>
            <?php
            
            if (! isset($form['parent_select']))
            {
              if (@$form['dataquery'])
              {
                $query = DB::select(DB::raw($form['dataquery']));
                if (isset($query))
                {
                  foreach ($query as $q)
                  {
                    $selected = ($value == $q->value) ? "selected" : "";
                    echo "<option ".$selected." value='".$q->value."'>".$q->label."</option>";
                  }
                }

              }
            }

                if (isset($form['dataenum'])){
                    $dataenum = $form['dataenum'];
                    $dataenum = (is_array($dataenum)) ? $dataenum : explode(";", $dataenum);

                    foreach ($dataenum as $d)
                    {

                        $val = $lab = '';
                        if (strpos($d, '|') !== FALSE)
                        {
                            $draw = explode("|", $d);
                            $val = $draw[0];
                            $lab = $draw[1];
                        }
                        else
                        {
                            $val = $lab = $d;
                        }

                        $select = ($value == $val) ? "selected" : "";

                        echo "<option ".$select." value='".$val."'>".$lab."</option>";
                    }
                }

               
                $datatable_order = [];
                $format = '';
                if (isset($form['datatable'])){
                    
                    $raw = explode(",", $form['datatable']);
                    
                    $format = isset($form['datatable_format']) ? $form['datatable_format'] : '';
                    $datatable_order = isset($form['datatable_order']) ? explode(',', $form['datatable_order']) : ['id'];
                    $table1 = $raw[0];
                    $column1 = $raw[1];

                    @$table2 = $raw[2];
                    @$column2 = $raw[3];

                    @$table3 = $raw[4];
                    @$column3 = $raw[5];

                    $selects_data = DB::table($table1)->select($table1.".id");
                    




                    if (!empty($table1)) {
                        if (Schema::hasColumn($table1, 'deleted_at')) {
                        $selects_data->where($table1.'.deleted_at', NULL);
                    }
                    }
                    
                    
                    

                    if (@$form['datatable_where']) {
                        $selects_data->whereraw($form['datatable_where']);
                    }

                    if ($table1 && $column1) {
                        $orderby_table = $table1;
                        $orderby_column = $column1;
                    }

                    if ($table2 && $column2) {
                        $selects_data->join($table2, $table2.'.id', '=', $table1.'.'.$column1);
                        $orderby_table = $table2;
                        $orderby_column = $column2;
                    }

                    if ($table3 && $column3) {
                        $selects_data->join($table3, $table3.'.id', '=', $table2.'.'.$column2);
                        $orderby_table = $table3;
                        $orderby_column = $column3;
                    }

                    
                    if (isset($format)) {
                        
                        $format = str_replace('&#039;', "'", $format);

                        $format = !empty($format) ? "CONCAT(".$format.") AS label" : "'' AS label";

                        if (!empty($format)) {
                            $selects_data->addselect(DB::raw($format));
                        }
                        
                        
                        $selects_data = $selects_data->orderby(
                            empty($datatable_order[0]) ? DB::raw("CONCAT($format)") : $datatable_order[0],
                            isset($datatable_order[1]) ? "desc" : "asc"
                        )->get();
                       
                    } else {
                        $selects_data->addselect($orderby_table.'.'.$orderby_column.' as label');
                        $selects_data = $selects_data->orderby($orderby_table.'.'.$orderby_column, "asc")->get();
                    }
                    

                    foreach ($selects_data as $d) {

                        $val = $d->id;
                        $select = ($value == $val) ? "selected" : "";

                        echo "<option $select value='".$val."'>".$d->label."</option>";
                    }
                }
             //end if not parent select
             
            ?>
        </select>
        <div class="text-danger">{!! $errors->first($name)?"<i class='fa fa-info-circle'></i> ".$errors->first($name):""
            !!}</div>
        <p class='help-block'>{{ @$form['help'] }}</p>
    </div>
</div>