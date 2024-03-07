
@if(isset($form['datatable']))
@if(isset($form['relationship_table']))
@push('bottom')
<script type="text/javascript">
    $(function () {
        $("#{{$name}}").select2();
    })
</script>
@endpush

@else
@if(isset($form['datatable_ajax']) && $form['datatable_ajax'] == true)

<?php
            $datatable = @$form['datatable'];
            $where = @$form['datatable_where'];
            $format = @$form['datatable_format'];

            $raw = explode(',', $datatable);
            $url = CRUDBooster::mainpath("find-data");

            $table1 = $raw[0];
            $column1 = $raw[1];

            @$table2 = $raw[2];
            @$column2 = $raw[3];

            @$table3 = $raw[4];
            @$column3 = $raw[5];
            ?>

@push('bottom')
<script>
    $(function () {
        console.log("URL {{$url}}");
        $('#{{$name}}').select2({
            placeholder: {
                id: '-1',
                text: '{{trans('crudbooster.text_prefix_option')}} {{$form['label']}}'
                            },
            allowClear: true,
            ajax: {
                url: '{{$url }}',
                delay: 250,
                data: function (params) {
                    var query = {
                        q: params.term,
                        format: "{{$format}}",
                        table1: "{{$table1}}",
                        column1: "{{$column1}}",
                        table2: "{{$table2}}",
                        column2: "{{$column2}}",
                        table3: "{{$table3}}",
                        column3: "{{$column3}}",
                        where: "{{addslashes($where) }}"
                    }
                    return query;
                },
                processResults: function (data) {
                    return {
                        results: data.items
                    };
                }
            },
            escapeMarkup: function (markup) {
                return markup;
            },
            minimumInputLength: 1,
                            @if ($value)
            initSelection: function (element, callback) {
                var id = $(element).val() ? $(element).val() : "{{$value}}";
                if (id !== '') {
                    $.ajax('{{$url}}', {
                        data: {
                            id: id,
                            format: "{{$format}}",
                            table1: "{{$table1}}",
                            column1: "{{$column1}}",
                            table2: "{{$table2}}",
                            column2: "{{$column2}}",
                            table3: "{{$table3}}",
                            column3: "{{$column3}}"
                        },
                        dataType: "json"
                    }).done(function (data) {
                        callback(data.items[0]);
                        $('#{{$name}}').html("<option value='" + data.items[0].id + "' selected >" + data.items[0].text + "</option>");
                    });
                }
            }

        @endif
    });

                    })
</script>
@endpush

@else
@push('bottom')
<script type="text/javascript">
    $(function () {
        $('#{{$name}}').select2();
    })
</script>
@endpush
@endif
@endif
@else

@push('bottom')
<script type="text/javascript">
    $(function () {
        $('#{{$name}}').select2();
    })
</script>
@endpush

@endif
<div class='form-group {{$header_group_class}} {{ ($errors->first($name))?"has-error":"" }}' id='form-group-{{$name}}'
    style="{{@$form['style']}}">
    <label class='control-label col-sm-2'>{{$form['label']}}
        @if($required)
        <span class='text-danger' title='{!! trans(' crudbooster.this_field_is_required') !!}'>*</span>
        @endif
    </label>

    <div class="{{$col_width?:'col-sm-10'}}">
        <select style='width:100%' class='form-control' id="{{$name}}" {{$required}} {{$readonly}} {!!$placeholder!!}
            {{$disabled}} name="{{$name}}{{isset($form['relationship_table'])?'[]':''}}" {{
            isset($form['relationship_table'])?'multiple="multiple"':'' }} >
            @if(isset($form['dataenum'])) <option value=''>{{trans('crudbooster.text_prefix_option')}}
            {{$form['label']}}
            </option>
            <?php
                $dataenum = $form['dataenum'];
                $dataenum = (is_array($dataenum)) ? $dataenum : explode(";", $dataenum);
                ?>
            @foreach($dataenum as $enum)
            <?php
echo $enum;
                    $val = $lab = '';
                    if (strpos($enum, '|') !== FALSE) {
                        $draw = explode("|", $enum);
                        $val = $draw[0];
                        $lab = $draw[1];
                    } else {
                        $val = $lab = $enum;
                    }
                    $select = ($value == $val) ? "selected" : "";
                    ?>
            "<option {{$select}} value='{{$val}}'>{{$lab}}</option>";
            @endforeach
            @endif
            @if(isset($form['datatable']))
            @if(isset($form['relationship_table']))
            <?php
                    $table_name = explode(',', $form['datatable'])[0];
                    $select_title = explode(',', $form['datatable'])[1];
                    $select_where = isset($form['datatable_where']) ? $form['datatable_where'] : '';
                    $table_pk_name = CRUDBooster::findPrimaryKey($table_name);

                    $result = DB::table($table_name)
                              ->select($table_pk_name, $select_title);
                    if ($select_where) {
                      $result->whereraw($select_where);
                    }
                    $result = $result->orderby($select_title, 'asc')->get();

                    if(isset($form['datatable_orig']) && $form['datatable_orig'] != ''){
                      $params = explode("|", $form['datatable_orig']);
                      if(!isset($params[2])) $params[2] = "id";
                      $value = DB::table($params[0])->where($params[2], $id)->first()->{$params[1]};
                      $value = explode(",", $value);
                    } else {
                      $foreignKey = CRUDBooster::getForeignKey($table, $form['relationship_table']);
                      $foreignKey2 = CRUDBooster::getForeignKey($table_name, $form['relationship_table']);
                      $value = DB::table($form['relationship_table'])->where($foreignKey, (isset($id) ? $id : 0))->get();
                      $value = $value->pluck($foreignKey2)->toArray();
                    }

                    foreach ($result as $r) {
                      $option_label = $r->{$select_title};
                      $option_value = $r->id;
                      $selected = (is_array($value) && in_array($r->$table_pk_name, $value)) ? "selected" : "";
                      echo "<option {$selected} value='{$option_value}'>{$option_label}</option>";
                    }
                    ?>
            @else

            @if(!isset($form['datatable_ajax']) || $form['datatable_ajax'] == false)
            <option value=''>{{trans('crudbooster.text_prefix_option')}} {{$form['label']}}</option>
            <?php
                          $table_name = explode(',', $form['datatable'])[0];
                          $select_title = explode(',', $form['datatable'])[1];
                          $select_where = isset($form['datatable_where']) ? $form['datatable_where'] : '';
                          $datatable_format = isset($form['datatable_format']) ? $form['datatable_format'] : '';
                          $table_pk_name = CRUDBooster::findPrimaryKey($table_name);
                          $result = DB::table($table_name)->select($table_pk_name, $select_title);
                          if ($datatable_format) {
                              $result->addSelect(DB::raw("CONCAT(".$datatable_format.") as $select_title"));
                          }
                          if ($select_where) {
                              $result->whereraw($select_where);
                          }
                          if (CRUDBooster::isColumnExists($table_name, 'deleted_at')) {
                              $result->whereNull('deleted_at');
                          }
                          $result = $result->orderby($select_title, 'asc')->get();

                          foreach ($result as $r) {
                            $option_label = $r->{$select_title};
                            $option_value = $r->$table_pk_name;
                            //TODO define $value
                            $selected = ($option_value == $value) ? "selected" : "";
                            echo "<option $selected value='$option_value'>$option_label</option>";
                          }
                          ?>
            <!--end-datatable-ajax-->
            @endif

            <!--end-relationship-table-->
            @endif

            <!--end-datatable-->
            @endif
        </select>
        <div class="text-danger">
            {!! $errors->first($name)?"<i class='fa fa-info-circle'></i> ".$errors->first($name):"" !!}
        </div><!--end-text-danger-->
        <p class='help-block'>{{ @$form['help'] }}</p>

    </div>
</div>

@push('bottom')
@if(isset($form['datatable']) AND isset($form['relationship_table']) AND isset($form['parent_select']))
<?php
    $exploded_parent = explode(",", $form['parent_select']);
    $parent_select = count($exploded_parent) > 1 ? $exploded_parent : $form['parent_select'];
    $parent = is_array($parent_select) ? $parent_select[0] : $parent_select;
    $add_field = is_array($parent_select) ? $parent_select[1] : '';
    //se viene passata una variabile parent_crosstable usala come nome della tabella di relazione tra child e parent
    $parent_crosstable = empty($form['parent_crosstable']) ? '' : $form['parent_crosstable'];
    //se viene passata una variabile child_crosstable_fk_name usala come nome della colonna chiave verso il child nella tabella di relazione tra child e parent
    $child_crosstable_fk_name = empty($form['child_crosstable_fk_name']) ? '' : $form['child_crosstable_fk_name'];

    //se viene passata una variabile fk_name usala come nome della colonna in cui cercare, altrimenti usa il name della parent select
    $fk_name = empty($form['fk_name']) ? $parent : $form['fk_name'];
    ?>
@push('bottom')
<script type="text/javascript">
    $(function () {

        var default_tenant;

        $('#{{$parent}}, input:radio[name={{$parent}}]').change(function () {
            var current = $("#{{$form['name']}}");
            var parent_id = $(this).val();
            var parent_crosstable = "{{$parent_crosstable}}";
            var child_crosstable_fk_name = "{{$child_crosstable_fk_name}}";
            var fk_name = "{{$fk_name}}";
            var fk_value = $(this).val();
            var datatable = "{{$form['datatable']}}".split(',');
            @if (!empty($add_field))
                var add_field = ($("#{{$add_field}}").val()) ? $("#{{$add_field}}").val() : "";
            @endif
            var datatableWhere = "{{isset($form['datatable_where']) ? $form['datatable_where'] : ''}}";
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
            var value = [{{ implode(',', $value)
    }}];
    var default_value = "{{ UserHelper::current_user_primary_group() }}";
    var is_default_present = false;
    var belongs_to_tenant = true;
    var keep_options = false;
    //salvo solo all'atterraggio default_tenant e non ogni volta che la tendina tenant cambia
    if (typeof default_tenant == 'undefined') {
        default_tenant = fk_value;
    }

    if (fk_value != '') {
        //at parent change,
        //if there were options already selected in the child select,
        //save child selected options before rewriting the select
        //in order to avoid forcing the user to reselect them
        var children = current.children();
        var li_selected = $('.select2-selection__choice', $(current.parent().children()[1]));
        var selected_label = [];
        //if there are selected options in the child select..
        if (li_selected.html() != undefined) {
            //..then keep child selected options rather then setting up the default value
            keep_options = true;
            $.each(li_selected, function (i, obj) {
                var label = $(obj).attr('title');
                selected_label.push(label);
            })
        }
        current.html("<option value=''>{{trans('crudbooster.text_prefix_option')}} {{$form['label']}}");
        var query_data_table = "{{CRUDBooster::mainpath('data-table')}}?";
        query_data_table += "table=" + table;
        query_data_table += "&label=" + label;
        query_data_table += "&fk_name=" + fk_name;
        query_data_table += "&fk_value=" + fk_value;
        query_data_table += "&parent_crosstable=" + parent_crosstable;
        query_data_table += "&child_crosstable_fk_name=" + child_crosstable_fk_name;
        query_data_table += "&datatable_where=" + encodeURI(datatableWhere);
        // console.log(query_data_table);

        $.get(query_data_table, function (response) {
            if (response) {
                //check if default is already between the options
                $.each(response, function (i, obj) {
                    if (obj.select_label == '{{ isset($default) ? $default :''}}') {
                    is_default_present = true;
                }
            })
        if (fk_value !== default_tenant) {
            belongs_to_tenant = false;
        }
        //if it's not a duplicate..
        //and group's tenant is selected..
        if (!is_default_present && belongs_to_tenant) {
            //..add the default option
            current.html("<option value=''>{{ isset($default) ? $default :''}}");
        }
        //add the other options
        $.each(response, function (i, obj) {
            if (keep_options) {
                var selected = ($.inArray(obj.select_label, selected_label) > -1) ? "selected" : "";
            }
            else if (value.length == 0) {
                //use default value in create
                var selected = (default_value && (obj.select_value == default_value)) ? "selected" : "";
            }
            else {
                var selected = (value && $.inArray(obj.select_value, value) > -1) ? "selected" : "";
            }
            $("<option " + selected + " value='" + obj.select_value + "'>" + obj.select_label + "</option>").appendTo("#{{$form['name']}}");
        });
        current.trigger('change');
    }
        });
    }
    else {
        current.html("<option value=''>{{ isset($default) ? $default :''}}");
    }
                })

    $('#{{$parent}}').trigger('change');
    $("input[name='{{$parent}}']:checked").trigger("change");
    $("#{{$form['name']}}").trigger('change');
            })
</script>
@endpush
@elseif(isset($form['datatable']) AND isset($form['parent_crosstable']) AND isset($form['parent_select']))
<?php
    $exploded_parent = explode(",", $form['parent_select']);
    $parent_select = count($exploded_parent) > 1 ? $exploded_parent : $form['parent_select'];
    $parent = is_array($parent_select) ? $parent_select[0] : $parent_select;
    $add_field = is_array($parent_select) ? $parent_select[1] : '';
    //se viene passata una variabile parent_crosstable usala come nome della tabella di relazione tra child e parent
    $parent_crosstable = empty($form['parent_crosstable']) ? '' : $form['parent_crosstable'];
    //se viene passata una variabile child_crosstable_fk_name usala come nome della colonna chiave verso il child nella tabella di relazione tra child e parent
    $child_crosstable_fk_name = empty($form['child_crosstable_fk_name']) ? '' : $form['child_crosstable_fk_name'];

    //se viene passata una variabile fk_name usala come nome della colonna in cui cercare, altrimenti usa il name della parent select
    $fk_name = empty($form['fk_name']) ? $parent : $form['fk_name'];
    ?>
@push('bottom')
<script type="text/javascript">
    $(function () {

        var default_tenant;

        $('#{{$parent}}, input:radio[name={{$parent}}]').change(function () {
            var current = $("#{{$form['name']}}");
            var parent_id = $(this).val();
            var parent_crosstable = "{{$parent_crosstable}}";
            var child_crosstable_fk_name = "{{$child_crosstable_fk_name}}";
            var fk_name = "{{$fk_name}}";
            var fk_value = $(this).val();
            var datatable = "{{$form['datatable']}}".split(',');
            @if (!empty($add_field))
                var add_field = ($("#{{$add_field}}").val()) ? $("#{{$add_field}}").val() : "";
            @endif
            var datatableWhere = "{{isset($form['datatable_where']) ? $form['datatable_where'] : ''}}";
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
            var value = "{{empty($value) ? "0" : $value }}";
            var default_value = "{{ UserHelper::current_user_primary_group() }}";
            var is_default_present = false;
            var belongs_to_tenant = true;
            var keep_options = false;
            //salvo solo all'atterraggio default_tenant e non ogni volta che la tendina tenant cambia
            if (typeof default_tenant == 'undefined') {
                default_tenant = fk_value;
            }

            if (fk_value != '') {
                //at parent change,
                //if there were options already selected in the child select,
                //save child selected options before rewriting the select
                //in order to avoid forcing the user to reselect them
                var children = current.children();
                var li_selected = $('.select2-selection__choice', $(current.parent().children()[1]));
                var selected_label = [];
                //if there are selected options in the child select..
                if (li_selected.html() != undefined) {
                    //..then keep child selected options rather then setting up the default value
                    keep_options = true;
                    $.each(li_selected, function (i, obj) {
                        var label = $(obj).attr('title');
                        selected_label.push(label);
                    })
                }
                current.html("<option value=''>{{trans('crudbooster.text_prefix_option')}} {{$form['label']}}");
                var query_data_table = "{{CRUDBooster::mainpath('data-table')}}?";
                query_data_table += "table=" + table;
                query_data_table += "&label=" + label;
                query_data_table += "&fk_name=" + fk_name;
                query_data_table += "&fk_value=" + fk_value;
                query_data_table += "&parent_crosstable=" + parent_crosstable;
                query_data_table += "&child_crosstable_fk_name=" + child_crosstable_fk_name;
                query_data_table += "&datatable_where=" + encodeURI(datatableWhere);
                console.log(query_data_table);

                $.get(query_data_table, function (response) {
                    if (response) {
                        //check if default is already between the options
                        $.each(response, function (i, obj) {
                            if (obj.select_label == '{{isset($default) ? $default : ''}}') {
                            is_default_present = true;
                        }
                    })
                if (fk_value !== default_tenant) {
                    belongs_to_tenant = false;
                }
                //if it's not a duplicate..
                //and group's tenant is selected..
                if (!is_default_present && belongs_to_tenant) {
                    //..add the default option
                    current.html("<option value=''>{{isset($default) ? $default : ''}}");
                }
                //add the other options
                $.each(response, function (i, obj) {
                    if (keep_options) {
                        var selected = ($.inArray(obj.select_label, selected_label) > -1) ? "selected" : "";
                    }
                    else if (value == 0) {
                        //use default value in create
                        var selected = (default_value && (obj.select_value == default_value)) ? "selected" : "";
                    }
                    else {
                        var selected = obj.select_value == value ? "selected" : "";
                    }
                    $("<option " + selected + " value='" + obj.select_value + "'>" + obj.select_label + "</option>").appendTo("#{{$form['name']}}");
                });
                current.trigger('change');
            }
        });
    }
            else {
            current.html("<option value=''>{{isset($default) ? $default : ''}}");
        }
        })

    $('#{{$parent}}').trigger('change');
    $("input[name='{{$parent}}']:checked").trigger("change");
    $("#{{$form['name']}}").trigger('change');
    })
</script>
@endpush
@endif
@endpush