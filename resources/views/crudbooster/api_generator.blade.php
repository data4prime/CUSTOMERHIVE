@extends('crudbooster::admin_template')

@section('content')

@push('head')
<link href="{{ asset('vendor/crudbooster/assets/summernote/summernote.css') }}" rel="stylesheet">
@endpush
@push('bottom')
<script src="{{ asset('vendor/crudbooster/assets/summernote/summernote.min.js') }}"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $('.wysiwyg').summernote();
    })
</script>
@endpush




@include('crudbooster::partials.api_nav', ['active' => 'editor'])

<div>
        @push('bottom')
        <script>
            $(function () {
                jQuery.fn.selectText = function () {
                    var doc = document;
                    var element = this[0];
                    console.log(this, element);
                    if (doc.body.createTextRange) {
                        var range = document.body.createTextRange();
                        range.moveToElementText(element);
                        range.select();
                    } else if (window.getSelection) {
                        var selection = window.getSelection();
                        var range = document.createRange();
                        range.selectNodeContents(element);
                        selection.removeAllRanges();
                        selection.addRange(range);
                    }
                };

                $(document).on("click", ".selected_text", function () {
                    console.log("clicked");
                    $(this).selectText();
                });


                $('#input-nama').on('input', function () {
                    var v = $(this).val();
                    if (v) {
                        v = v.replace(/[^0-9a-z]/gi, '_').toLowerCase();
                        $('#input-permalink').val(v);
                    } else {
                        $('#input-permalink').val('');
                    }
                })

                $('#input-permalink').on('input', function () {
                    var v = $(this).val();
                    v = v.replace(/[^0-9a-z]/gi, '_').toLowerCase();
                    $('#input-permalink').val(v);
                })

                $('#tipe_action').change(function () {
                    var v = $(this).val();
                    $('.method_type').prop('checked', false);
                    switch (v) {
                        case 'list':
                        case 'detail':
                        case 'delete':
                            $('.method_type[value=get]').prop('checked', true);
                            break;
                        case 'save_add':
                        case 'save_edit':
                            $('.method_type[value=post]').prop('checked', true);
                            break;
                    }
                })

                $(document).on('click', '.tr-response', function () {
                    //console.log('tr response clicked');
/*
                    var is_check = $(this).find('select').val();
                    if (is_check == '1') {
                        $(this).find('select').val(0);
                        $(this).removeClass('success');
                    } else {
                        $(this).find('select').val(1);
                        $(this).addClass('success');
                    }
*/
                })
            })

            function load_response() {
                var t = $('#combo_tabel').val();
                var type = 'list';
                var tipe_action = $('#tipe_action').val();
                var no_params = 0;
                $('#table-response tbody').empty();

                if (t == '') return false;
                if (tipe_action == '') return false;

                no_params += 1;
                $('#table-response tbody').append("<tr><td>" + no_params + "</td><td>api_status</td><td>boolean</td><td>-</td><td><select class='form-select form-select-sm' disabled><option>YES</option></select></td><td>-</td></tr>");
                no_params += 1;
                $('#table-response tbody').append("<tr><td>" + no_params + "</td><td>api_message</td><td>string</td><td>-</td><td><select class='form-select form-select-sm' disabled><option>YES</option></select></td><td>-</td></tr>");

                if (tipe_action == 'list') {
                    $('#table-response tbody').append("<tr class='info' style='font-weight'><td>#</td><td>data</td><td>&nbsp;</td><td>-</td><td>-</td><td>-</td></tr>");
                }

                if (tipe_action == 'detail') {
                    $('#table-response tbody').append("<tr class='info' style='font-weight'><td>#</td><td>data</td><td>&nbsp;</td><td>-</td><td>-</td><td>-</td></tr>");
                }

                no_params = 0;
                $.get('{{url(config("crudbooster.ADMIN_PATH"))."/api_generator/column-table"}}/' + t + '/' + type, function (resp) {
                    $.each(resp, function (i, obj) {

                        switch (obj.type) {
                            default:
                                var obj_type = obj.type;
                                break;
                            case 'varchar':
                            case 'nvarchar':
                            case 'char':
                            case 'text':
                                var obj_type = 'string';
                                break;
                            case 'integer':
                                var obj_type = 'integer';
                                break;
                            case 'double':
                            case 'float':
                            case 'decimal':
                                var obj_type = 'numeric';
                                break;
                            case 'date':
                                var obj_type = 'date';
                                break;
                            case 'datetime':
                            case 'timestamp':
                                var obj_type = 'date_format:Y-m-d H:i:s';
                                break;
                            case 'email':
                                var obj_type = 'email';
                                break;
                            case 'image':
                                var obj_type = 'image';
                                break;
                            case 'password':
                                var obj_type = 'password';
                                break;
                        }

                        no_params += 1;
                        $('#table-response tbody').append("<tr class='success tr-response'><td>" + no_params + "</td><td>&nbsp;&nbsp;- " + obj.name + "<input type='hidden' name='responses_name[]' value='" + obj.name + "'/></td><td>" + obj_type + "<input type='hidden' name='responses_type[]' value='" + obj_type + "'/></td><td>-<input type='hidden' name='responses_subquery[]' value=''/></td><td><select class='form-select form-select-sm responses_used' name='responses_used[]'><option value='1'>YES</option><option value='0'>NO</option></select></td><td><a class='btn btn-danger' href='javascript:void(0)' onclick='deleteResponse(this)'><i class='bi bi-slash-circle'></i></a></td></tr>");
                    })
                })

                $('#table-response tfoot').show();
            }

            function load_parameters() {
                //console.log('load_parameters');
                var t = $('#combo_tabel').val();
                var type = 'save_add';
                var tipe_action = $('#tipe_action').val();

                if (t == '') return false;
                if (tipe_action == '') return false;

                if (tipe_action == 'list' || tipe_action == 'detail') {
                    $('textarea[name=sub_query_1]').prop('readonly', false);
                } else {
                    $('textarea[name=sub_query_1]').prop('readonly', true);
                }

                $.get('{{url(config("crudbooster.ADMIN_PATH"))."/api_generator/column-table"}}/' + t + '/' + type, function (resp) {
                    //console.log(resp);
                    if (tipe_action == 'detail' || tipe_action == 'delete') {

                        //remove all items from resp except the item with name id

                        var new_resp = [];
                        $.each(resp, function (i, obj) {
                            if (obj.name == 'id') {
                                new_resp.push(obj);
                            }
                        })

                        resp = new_resp;


                    }
                    var no_params = 0;
                    $('#table-parameters tbody').empty();
                    $.each(resp, function (i, obj) {

                        var param_html = $('#table-parameters tfoot tr').clone();

                        $('#table-parameters tbody').append(param_html);



                    })

                    var i = 0;
                    $('#table-parameters tbody tr').each(function () {

                        var field_type = resp[i].type;
                        var field_name = resp[i].name;

                        if (tipe_action == 'save_add' && field_name == 'id') {
                            $(this).remove();
                        } else {
                            no_params += 1;
                        }


                        switch (field_type) {
                            default:
                            case 'varchar':
                            case 'nvarchar':
                            case 'char':
                            case 'text':
                                var type = 'string';
                                break;
                            case 'integer':
                                var type = 'integer';
                                break;
                            case 'double':
                            case 'float':
                            case 'decimal':
                                var type = 'numeric';
                                break;
                            case 'date':
                                var type = 'date';
                                break;
                            case 'datetime':
                            case 'timestamp':
                                var type = 'date_format:Y-m-d H:i:s';
                                break;
                            case 'email':
                                var type = 'email';
                                break;
                            case 'image':
                                var type = 'image';
                                break;
                            case 'password':
                                var type = 'password';
                                break;
                        }


                        $(this).find('td:nth-child(1)').text(no_params);
                        $(this).find('td:nth-child(2) input').val(field_name);
                        $(this).find('td:nth-child(3) select').val(type);

                        if (tipe_action == 'list' || tipe_action == 'detail' || tipe_action == 'delete') {
                            $(this).find('td:nth-child(5) select').val('0');
                            $(this).find('td:nth-child(6) select').val('0');
                        }
                        if (tipe_action == 'detail' && field_name == 'id') {
                            $(this).find('td:nth-child(5) select').val('1');
                            $(this).find('td:nth-child(6) select').val('1');
                        }

                        if (tipe_action == 'delete' && field_name == 'id') {
                            $(this).find('td:nth-child(5) select').val('1');
                            $(this).find('td:nth-child(6) select').val('1');
                        }


                        $(this).find('.col-delete').html("<a class='btn btn-danger' href='javascript:void(0)' onclick='deleteParam(this)'><i class='bi bi-slash-circle'></i></a>");
                        i += 1;
                    })
                })
                    //console.log(tipe_action);
                    if (tipe_action != 'detail' && tipe_action != 'delete') {
                        $('#table-parameters tfoot').show();
                    } else {
                        $('#table-parameters tfoot').hide();
                    }
                    //$('#table-parameters tfoot').show();

            }

            function init_data_parameters() {
                console.log('init_data_parameters');
                @if (isset($parameters))

                    var resp = {!!$parameters!!};
            var tipe_action = $('#tipe_action').val();

            var no_params = 0;
            $('#table-parameters tbody').empty();
            $.each(resp, function (i, obj) {
                var param_html = $('#table-parameters tfoot tr').clone();
                $('#table-parameters tbody').append(param_html);
            })

            var i = 0;
            $('#table-parameters tbody tr').each(function () {

                var field_type = resp[i].type;
                var field_name = resp[i].name;
                var required = resp[i].required;
                var used = resp[i].used;
                var config = resp[i].config;

                console.log(field_name + ' - ' + field_type);

                if (tipe_action == 'save_add' && field_name == 'id') {
                    $(this).remove();
                } else {
                    no_params += 1;
                }


                switch (field_type) {
                    default:
                        var type = field_type;
                        break;
                    case 'varchar':
                    case 'nvarchar':
                    case 'char':
                    case 'text':
                        var type = 'string';
                        break;
                    case 'integer':
                        var type = 'integer';
                        break;
                    case 'double':
                    case 'float':
                    case 'decimal':
                        var type = 'numeric';
                        break;
                    case 'date':
                        var type = 'date';
                        break;
                    case 'datetime':
                    case 'timestamp':
                        var type = 'date_format:Y-m-d H:i:s';
                        break;
                    case 'email':
                        var type = 'email';
                        break;
                    case 'image':
                        var type = 'image';
                        break;
                    case 'password':
                        var type = 'password';
                        break;
                }

                $(this).find('td:nth-child(1)').text(no_params);
                $(this).find('td:nth-child(2) input').val(field_name);
                $(this).find('td:nth-child(3) select').val(type);
                $(this).find('td:nth-child(4) input').val(config);
                $(this).find('td:nth-child(5) select').val(required);
                $(this).find('td:nth-child(6) select').val(used);

                $(this).find('.col-delete').html("<a class='btn btn-danger' href='javascript:void(0)' onclick='deleteParam(this)'><i class='bi bi-slash-circle'></i></a>");
                i += 1;
            })

            $('#table-parameters tfoot').show();

            @endif
                    } //end function init_data_parameter


            function init_data_responses() {
                @if (isset($responses))

                    var t = $('#combo_tabel').val();
                var type = 'list';
                var tipe_action = $('#tipe_action').val();
                var no_params = 0;
                $('#table-response tbody').empty();

                if (t == '') return false;
                if (tipe_action == '') return false;

                no_params += 1;
                $('#table-response tbody').append("<tr><td>" + no_params + "</td><td>api_status</td><td>boolean</td><td>-</td><td><select class='form-select form-select-sm' disabled><option>YES</option></select></td><td>-</td></tr>");
                no_params += 1;
                $('#table-response tbody').append("<tr><td>" + no_params + "</td><td>api_message</td><td>string</td><td>-</td><td><select class='form-select form-select-sm' disabled><option>YES</option></select></td><td>-</td></tr>");

                if (tipe_action == 'list') {
                    $('#table-response tbody').append("<tr class='info' style='font-weight'><td>#</td><td>data</td><td>&nbsp;</td><td>-</td><td>-</td><td>-</td></tr>");
                }

                no_params = 0;
                var responses_data = {!! $responses !!};

            $.each(responses_data, function (i, obj) {
                no_params += 1;
                var used_yes = (obj.used == '1') ? "selected" : "";
                var used_no = (obj.used == '0') ? "selected" : "";
                var tr_success = (obj.used == '1') ? "success" : "";


                switch (obj.type) {
                    default:
                        var obj_type = obj.type;
                        break;
                    case 'varchar':
                    case 'nvarchar':
                    case 'char':
                    case 'text':
                        var obj_type = 'string';
                        break;
                    case 'integer':
                        var obj_type = 'integer';
                        break;
                    case 'double':
                    case 'float':
                    case 'decimal':
                        var obj_type = 'numeric';
                        break;
                    case 'date':
                        var obj_type = 'date';
                        break;
                    case 'datetime':
                    case 'timestamp':
                        var obj_type = 'dateTime';
                        break;
                    case 'email':
                        var obj_type = 'email';
                        break;
                    case 'image':
                        var obj_type = 'image';
                        break;
                    case 'password':
                        var obj_type = 'password';
                        break;
                }

                var input_subquery = '';
                var delete_btn = '';

                if (obj.subquery == '') {
                    input_subquery = "-<input type='hidden' name='responses_subquery[]' value='" + obj.subquery + "'/>";
                    delete_btn = "<a class='btn btn-danger' href='javascript:void(0)' onclick='deleteResponse(this)'><i class='bi bi-slash-circle'></i></a>";
                } else {
                    if (obj.subquery) {
                        var subquery = obj.subquery;
                    } else {
                        var subquery = '';
                    }
                    input_subquery = subquery + "<input type='hidden' name='responses_subquery[]' value='" + subquery + "'/>";
                    delete_btn = "<a class='btn btn-danger' href='javascript:void(0)' onclick='deleteResponse(this)'><i class='bi bi-slash-circle'></i></a>";
                }

                $('#table-response tbody').append("<tr class='" + tr_success + " tr-response'><td>" + no_params + "</td><td>&nbsp;&nbsp;- " + obj.name + "<input type='hidden' name='responses_name[]' value='" + obj.name + "'/></td><td>" + obj_type + "<input type='hidden' name='responses_type[]' value='" + obj_type + "'/></td><td>" + input_subquery + "</td><td><select class='form-select form-select-sm responses_used' name='responses_used[]'><option " + used_yes + " value='1'>YES</option><option " + used_no + " value='0'>NO</option></select></td><td>" + delete_btn + "</td></tr>");
            })

            $('#table-response tfoot').show();

            @endif
                    } //end function init_data_responses

            $(function () {

                @if (isset($row))
                    init_data_parameters();
                init_data_responses();
                @endif

                $('#combo_tabel,#tipe_action').change(function () {

                    load_response();

                    load_parameters();
                })

                $('#table-parameters tfoot tr td:nth-child(2) input').on('input', function () {
                    var v = $(this).val();
                    v = v.replace(/[^0-9a-z]/gi, '_').toLowerCase();
                    $(this).val(v);
                })


            })

            function deleteParam(t) {
                $(t).parent().parent().remove();
                var no_params = 0;
                $('#table-parameters tbody tr').each(function () {
                    no_params += 1;
                    $(this).find('td:nth-child(1)').text(no_params);
                });
            }

            function addParam() {

                var htm = $('#table-parameters tfoot tr').clone();

                var val = $('#table-parameters tfoot tr td:nth-child(2) input').val();
                var validation = $('#table-parameters tfoot tr td:nth-child(3) select').val();
                var config = $('#table-parameters tfoot tr td:nth-child(4) select').val();
                var m = $('#table-parameters tfoot tr td:nth-child(5) select').val();
                var u = $('#table-parameters tfoot tr td:nth-child(6) select').val();

                htm.find('td:nth-child(3)').find('select').val(validation);
                htm.find('td:nth-child(4)').find('select').val(config);
                htm.find('td:nth-child(5)').find('select').val(m);
                htm.find('td:nth-child(6)').find('select').val(u);

                if (val == '') return false;

                $('#table-parameters .row-no-data').remove();
                $('#table-parameters tbody').append(htm);

                $('#table-parameters tfoot tr').find('input[type=text]').val('');
                $('#table-parameters tfoot tr').find('option').removeAttr('selected');

                var no_params = 0;
                $('#table-parameters tbody tr').each(function () {
                    no_params += 1;
                    $(this).find('td:nth-child(1)').text(no_params);
                    $(this).find('.col-delete').html("<a class='btn btn-danger' href='javascript:void(0)' onclick='deleteParam(this)'><i class='bi bi-slash-circle'></i></a>");
                });
            }

            function addResponse() {
                console.log('addResponse');

                var val = $('#table-response tfoot tr td:nth-child(2) input').val();
                var validation = $('#table-response tfoot tr td:nth-child(3) select').val();
                var subquery = $('#table-response tfoot tr td:nth-child(4) textarea').val();
                //console.log(subquery);
                var is_check = $('#table-response tfoot tr td:nth-child(5) select').val();

                var check_yes, check_no;

                if (is_check == '1') {
                    check_yes = 'selected';
                } else {
                    check_no = 'selected';
                }

                var htm = "<tr class='tr-response tr-additional'>";
                htm += "<td>#</td>";
                htm += "<td>&nbsp;&nbsp;- " + val + "<input type='hidden' name='responses_name[]' value='" + val + "'/></td><td>" + validation + "<input type='hidden' name='responses_type[]' value='" + validation + "'/></td><td>" + subquery + "<input type='hidden' name='responses_subquery[]' value='" + subquery + "'/></td>";
                htm += "<td><select class='form-select form-select-sm responses_used' name='responses_used[]'><option " + check_yes + " value='1'>YES</option><option " + check_no + " value='0'>NO</option></select></td>";
                htm += "<td><a class='btn btn-danger' href='javascript:void(0)' onclick='deleteResponse(this)'><i class='bi bi-slash-circle'></i></a></td></tr>";

                if (val == '') return false;
                // if(subquery == '') return false;

                $('#table-response .row-no-data').remove();
                $('#table-response tbody').append(htm);

                $('#table-response tfoot tr').find('input[type=text]').val('');
                $('#table-response tfoot tr').find('option').removeAttr('selected');

                var no_params = 0;
                $('#table-response tbody tr').each(function () {
                    no_params += 1;

                    $(this).addClass('tr-response');

                    if ($(this).hasClass('tr-additional')) {
                        if ($(this).find('select').val() == '1') {
                            $(this).addClass('success');
                        }
                    }

                    $(this).find('td:nth-child(1)').text(no_params);
                    $(this).find('.col-delete').html("<a class='btn btn-danger' href='javascript:void(0)' onclick='deleteResponse(this)'><i class='bi bi-slash-circle'></i></a>");
                });
            }

            function deleteResponse(t) {
                $(t).parent().parent().remove();
                var no_params = 0;
                $('#table-response tbody tr').each(function () {
                    no_params += 1;
                    $(this).find('td:nth-child(1)').text(no_params);
                });
            }

        </script>
        @endpush

        @push('head')
        <style>
            .selected_text {
                cursor: pointer;
            }

            .selected_text:hover {
                color: var(--ch-warning)
            }

            tfoot td {
                background: var(--ch-bg)
            }

            .tr-response {
                cursor: pointer
            }
        </style>
        @endpush


        <form method='post' action='{{ route("ApiCustomControllerPostSaveApiCustom")}}'>
            <input type="hidden" name="_token" value="{{ csrf_token() }}" />
            <input type="hidden" name="id" value="{{isset($row->id) ? $row->id : '' }}">

            <div class="api-editor">
                <div>
                    {{-- 1. Cosa espone --}}
                    <div class="api-card">
                        <div class="api-card-h"><span><span class="api-num">1</span>{{ trans('crudbooster.api_ed_sec_expose') }}</span></div>
                        <div class="api-card-b">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="api-lbl" for="input-nama">{{ trans('crudbooster.api_ed_name') }}</label>
                                    <input type='text' class='form-control' value='{{isset($row->nama) ? $row->nama : '' }}' required name='nama' id='input-nama' />
                                </div>
                                <div class="col-md-4">
                                    <label class="api-lbl" for="combo_tabel">{{ trans('crudbooster.api_ed_table') }}</label>
                                    <select id='combo_tabel' name='tabel' required class='form-select'>
                                        <option value=''>{{ trans('crudbooster.api_ed_choose_table') }}</option>
                                        @foreach($tables as $tab)
                                        <option {{(isset($row->tabel) && $row->tabel == $tab)?"selected":""}} value='{{$tab}}'>{{$tab}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="api-lbl" for="input-permalink">{{ trans('crudbooster.api_ed_slug') }}</label>
                                    <div class='input-group'>
                                        <span class="input-group-text">{{url("api")}}/</span>
                                        <input type='text' class='form-control' value='{{isset($row->permalink) ? $row->permalink : ''}}' required name='permalink' id='input-permalink' />
                                    </div>
                                    <div class="api-help">{{ trans('crudbooster.api_ed_slug_help') }}</div>
                                </div>
                                <div class="col-md-4">
                                    <span class="api-lbl">{{ trans('crudbooster.api_ed_method') }}</span>
                                    <div class="btn-group" role="group">
                                        <input type="radio" class="btn-check method_type" name="method_type" id="method-get" value="get" required {{ (isset($row->method_type) && $row->method_type == 'get') ? 'checked' : '' }}>
                                        <label class="btn btn-secondary" for="method-get">GET</label>
                                        <input type="radio" class="btn-check method_type" name="method_type" id="method-post" value="post" {{ (isset($row->method_type) && $row->method_type == 'post') ? 'checked' : '' }}>
                                        <label class="btn btn-secondary" for="method-post">POST</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="api-lbl" for="tipe_action">{{ trans('crudbooster.api_ed_action') }}</label>
                                    <select id='tipe_action' name='aksi' required class='form-select'>
                                        <option value=''>{{ trans('crudbooster.api_ed_choose_action') }}</option>
                                        <option value='list' {{ (isset($row->aksi) && $row->aksi == 'list')?"selected":"" }}>{{ trans('crudbooster.api_action_list') }}</option>
                                        <option value='detail' {{ (isset($row->aksi) && $row->aksi == 'detail')?"selected":"" }}>{{ trans('crudbooster.api_action_detail') }}</option>
                                        <option value='save_add' {{ (isset($row->aksi) && $row->aksi == 'save_add')?"selected":"" }}>{{ trans('crudbooster.api_action_create') }}</option>
                                        <option value='save_edit' {{ (isset($row->aksi) && $row->aksi == 'save_edit')?"selected":"" }}>{{ trans('crudbooster.api_action_update') }}</option>
                                        <option value='delete' {{ (isset($row->aksi) && $row->aksi == 'delete')?"selected":"" }}>{{ trans('crudbooster.api_action_delete') }}</option>
                                    </select>
                                    <div class="api-help">{{ trans('crudbooster.api_ed_action_help') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Cosa riceve --}}
                    <div class="api-card">
                        <div class="api-card-h">
                            <span><span class="api-num">2</span>{{ trans('crudbooster.api_ed_sec_receive') }}</span>
                            <a class='btn btn-sm btn-secondary' href='javascript:void(0)' onclick="load_parameters()"><i class='bi bi-arrow-repeat'></i> {{ trans('crudbooster.api_ed_reset') }}</a>
                        </div>
                        <div class="api-card-b flush">
                            <div class="table-responsive">
                                <table id='table-parameters' class='table api-table mb-0'>
                                    <thead>
                                        <tr>
                                            <th width="3%">#</th>
                                            <th>{{ trans('crudbooster.api_doc_col_name') }}</th>
                                            <th>{{ trans('crudbooster.api_doc_col_type') }}</th>
                                            <th>{{ trans('crudbooster.api_ed_col_rule') }}</th>
                                            <th width="9%">{{ trans('crudbooster.api_ed_col_mandatory') }}</th>
                                            <th width="9%">{{ trans('crudbooster.api_ed_col_enable') }}</th>
                                            <th width="5%"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class='row-no-data'>
                                            <td colspan='7' class="text-center api-help">{{ trans('crudbooster.api_ed_no_data') }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot style="display:none">
                                        <tr>
                                            <td>#</td>
                                            <td width="20%"><input class='form-control form-control-sm' name='params_name[]' type='text' /></td>
                                            <td width="20%"><select class='form-select form-select-sm' name='params_type[]'>
                                                    <optgroup label='Common Validation'>
                                                        <option value='string'>String</option>
                                                        <option value='integer'>Integer</option>
                                                        <option value='email'>Email</option>
                                                        <option value='image'>Image (jpeg, png, bmp, gif, or svg)</option>
                                                        <option value='file'>File Upload</option>
                                                        <option value='exists'>Exists (table,column)</option>
                                                        <option value='unique'>Unique (table,column,except)</option>
                                                        <option value='password'>Password</option>
                                                        <option value='search'>Search</option>
                                                        <option value='custom'>Custom (Not In Table)</option>
                                                    </optgroup>
                                                    <optgroup label='Other Validation'>
                                                        <option value='array'>Array</option>
                                                        <option value='alpha'>Alpha</option>
                                                        <option value='alpha_num'>Alpha Numeric</option>
                                                        <option value='alpha_spaces'>Alpha Spaces</option>
                                                        <option value='base64_file'>Base64 File</option>
                                                        <option value='boolean'>Boolean</option>
                                                        <option value='date'>Date (Y-m-d)</option>
                                                        <option value='date_format:Y-m-d H:i:s'>DateTime (Y-m-d H:i:s)</option>
                                                        <option value='date_format'>Date Format Custom</option>
                                                        <option value='digits'>Digits</option>
                                                        <option value='digits_between'>Digits Between (Min,Max)</option>
                                                        <option value='in'>In (a,b,c)</option>
                                                        <option value='json'>Json Valid</option>
                                                        <option value='mimes'>Mimes Type</option>
                                                        <option value='min'>Min</option>
                                                        <option value='max'>Max</option>
                                                        <option value='numeric'>Numeric</option>
                                                        <option value='not_in'>Not In (a,b,c)</option>
                                                        <option value='url'>URL Valid</option>
                                                    </optgroup>
                                                    <optgroup label='Other'>
                                                        <option value='ref'>Child Table References</option>
                                                    </optgroup>
                                                </select></td>
                                            <td><input class='form-control form-control-sm' type='text' name='params_config[]'></td>
                                            <td><select class='form-select form-select-sm params_required' name='params_required[]'>
                                                    <option value='1'>{{ trans('crudbooster.api_yes') }}</option>
                                                    <option value='0'>{{ trans('crudbooster.api_no') }}</option>
                                                </select></td>
                                            <td><select class='form-select form-select-sm params_used' name='params_used[]'>
                                                    <option value='1'>{{ trans('crudbooster.api_yes') }}</option>
                                                    <option value='0'>{{ trans('crudbooster.api_no') }}</option>
                                                </select></td>
                                            <td class='col-delete'><a class='btn btn-sm btn-primary' href='javascript:void(0)' onclick='addParam()'><i class='bi bi-plus-lg'></i></a></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="api-card-f api-help m-0">{{ trans('crudbooster.api_ed_params_help') }}</div>
                    </div>

                    {{-- 3. Cosa restituisce --}}
                    <div class="api-card">
                        <div class="api-card-h">
                            <span><span class="api-num">3</span>{{ trans('crudbooster.api_ed_sec_return') }}</span>
                            <a class='btn btn-sm btn-secondary' href='javascript:void(0)' onclick='load_response()'><i class='bi bi-arrow-repeat'></i> {{ trans('crudbooster.api_ed_reset') }}</a>
                        </div>
                        <div class="api-card-b">
                            <label class="api-lbl" for="sql_where">{{ trans('crudbooster.api_ed_sql_where') }}</label>
                            <textarea id="sql_where" name='sql_where' rows='3' class='form-control api-mono' placeholder="status = [paramStatus]">{{isset($row->sql_where) ? $row->sql_where : ''}}</textarea>
                            <div class='api-help'>{!! trans('crudbooster.api_ed_sql_where_help') !!}</div>
                        </div>
                        <div class="api-card-b flush" id='response' style="border-top:1px solid var(--ch-border)">
                            <div class="table-responsive">
                                <table id='table-response' class='table api-table mb-0'>
                                    <thead>
                                        <tr>
                                            <th width="3%">#</th>
                                            <th>{{ trans('crudbooster.api_doc_col_name') }}</th>
                                            <th>{{ trans('crudbooster.api_doc_col_type') }}</th>
                                            <th>{{ trans('crudbooster.api_ed_col_subquery') }}</th>
                                            <th width="9%">{{ trans('crudbooster.api_ed_col_enable') }}</th>
                                            <th width="3%"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class='row-no-data'>
                                            <td colspan='6' class="text-center api-help">{{ trans('crudbooster.api_ed_no_data') }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot style="display:none">
                                        <tr class='tr-additional'>
                                            <td>#</td>
                                            <td width="20%"><input placeholder='{{ trans('crudbooster.api_ed_alias_placeholder') }}' name='responses_name[]' class='form-control form-control-sm' type='text' />
                                                <small class="api-help">{{ trans('crudbooster.api_ed_alias_help') }}</small>
                                            </td>
                                            <td>
                                                <select class='form-select form-select-sm' name='responses_type[]'>
                                                    <option value='integer'>Integer</option>
                                                    <option value='boolean'>Boolean</option>
                                                    <option value='string'>String</option>
                                                    <option value='file'>File</option>
                                                    <option value='date'>Date</option>
                                                    <option value='datetime'>DateTime</option>
                                                    <option value='double'>Double</option>
                                                    <option value='custom'>Custom (Not in Table)</option>
                                                </select>
                                            </td>
                                            <td>
                                                <textarea placeholder="E.g : select sum(total) from order_detail where id_order = order.id" name='responses_subquery[]' class='form-control form-control-sm' rows="2"></textarea>
                                            </td>
                                            <td><select class='form-select form-select-sm responses_used' name='responses_used[]'>
                                                    <option value='1'>{{ trans('crudbooster.api_yes') }}</option>
                                                    <option value='0'>{{ trans('crudbooster.api_no') }}</option>
                                                </select></td>
                                            <td class='col-delete'><a class='btn btn-sm btn-primary' href='javascript:void(0)' onclick='addResponse()'><i class='bi bi-plus-lg'></i></a></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- 4. Descrizione --}}
                    <div class="api-card">
                        <div class="api-card-h"><span><span class="api-num">4</span>{{ trans('crudbooster.api_doc_description') }}</span></div>
                        <div class="api-card-b">
                            <textarea name='keterangan' rows='3' class='form-control wysiwyg' placeholder='{{ trans('crudbooster.api_ed_description_placeholder') }}'>{{isset($row->keterangan) ? $row->keterangan : ''}}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Anteprima: si aggiorna mentre si compila --}}
                <aside class="api-preview">
                    <div class="api-card">
                        <div class="api-card-h">{{ trans('crudbooster.api_ed_preview') }}</div>
                        <div class="api-card-b d-flex flex-column gap-3">
                            <div class="d-flex gap-2 align-items-center flex-wrap" style="min-width:0">
                                <span class="api-method get" id="pv-method">GET</span>
                                <code id="pv-url" style="word-break:break-all">{{ url('api2') }}/...</code>
                            </div>
                            <pre class="api-code" id="pv-json">{}</pre>
                            <div class="api-help m-0">{{ trans('crudbooster.api_ed_preview_help') }}</div>
                        </div>
                    </div>
                </aside>
            </div>

            <div class="api-savebar">
                <span class="api-help m-0">{{ trans('crudbooster.api_ed_save_hint') }}</span>
                <span class="d-flex gap-2">
                    <a class="btn btn-secondary" href="{{ CRUDBooster::mainpath() }}">{{ trans('crudbooster.api_cancel') }}</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> {{ trans('crudbooster.api_ed_save') }}</button>
                </span>
            </div>
        </form>
</div>

@push('bottom')
<script>
$(function () {
    var base2 = {!! json_encode(url('api2')) !!};
    function sample(t) {
        if (['integer', 'numeric', 'double'].indexOf(t) !== -1) return 1;
        if (t === 'boolean') return true;
        if (String(t).indexOf('date') === 0) return '2026-01-31';
        return '...';
    }
    function updatePreview() {
        var m = ($('.method_type:checked').val() || 'get');
        $('#pv-method').attr('class', 'api-method ' + m).text(m.toUpperCase());
        var slug = $('#input-permalink').val() || '...';
        var url = base2 + '/' + slug;
        if (m === 'get') {
            var qs = [];
            $('#table-parameters tbody tr').each(function () {
                var n = $(this).find('td:nth-child(2) input').val();
                if (n && $(this).find('td:nth-child(6) select').val() === '1') qs.push(n + '=');
            });
            if (qs.length) url += '?' + qs.join('&');
        }
        $('#pv-url').text(url);

        var act = $('#tipe_action').val(), obj = {};
        $('#table-response tbody tr').each(function () {
            var n = $(this).find("input[name='responses_name[]']").val();
            var t = $(this).find("input[name='responses_type[]']").val();
            if (n && $(this).find('.responses_used').val() === '1') obj[n] = sample(t);
        });
        var out = {api_status: 1, api_message: 'success'};
        if (act === 'list') out.data = [obj];
        else if (act === 'detail') out.data = obj;
        else if (act === 'save_add') out.id = 1;
        $('#pv-json').text(JSON.stringify(out, null, 2));
    }
    $('form').on('input change', updatePreview);
    var obs = new MutationObserver(updatePreview);
    ['table-parameters', 'table-response'].forEach(function (id) {
        obs.observe(document.getElementById(id).tBodies[0], {childList: true, subtree: true});
    });
    setTimeout(updatePreview, 400);
});
</script>
@endpush

@endsection

