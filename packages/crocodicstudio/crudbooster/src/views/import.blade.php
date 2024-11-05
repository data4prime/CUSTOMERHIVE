@extends('crudbooster::admin_template')
@section('content')


    @if(isset($button_show_data) || isset($button_reload_data) || isset($button_new_data) || isset($button_delete_data) || isset($index_button) || isset($columns))
        <!--<div id='card-actionmenu' class='box'>
            <div class='card-body'>
                
            </div>
        </div>-->
    @endif


    @if(Request::get('file') && Request::get('import'))

        <ul class='nav nav-tabs'>
            <li class="nav-item" style="background:#eeeeee">
                <a class="nav-link" style="color:#111" 
                    onclick="if(confirm('Are you sure want to leave ?')) location.href='{{ CRUDBooster::mainpath("import-data") }}'"
                    href='javascript:;'><i class='fa fa-download'></i> {{ trans('crudbooster.upload_a_file') }} &raquo;
                </a>
            </li>
            <li class="nav-item" style="background:#eeeeee">
                <a class="nav-link" style="color:#111" href='#'><i class='fa fa-cogs'></i> {{ trans('crudbooster.adjustment') }} &raquo;</a>
            </li>
            <li class="nav-item" style="background:#ffffff" class='active'>
                <a class="nav-link" style="color:#111" href='#'><i class='fa fa-cloud-download'></i> {{ trans('crudbooster.importing') }} &raquo;</a>
            </li>
        </ul>

        <!-- Box -->
        <div id='box_main' class="card card-primary">
            <div class="card-header mb-3 with-border">
                <h3 class="card-title">{{ trans('crudbooster.importing') }}</h3>
                <div class="card-tools">
                </div>
            </div>

            <div class="card-body">

                <p style='font-weight: bold' id='status-import'><i class='fa fa-spin fa-spinner'></i>{{ trans('crudbooster.please_wait_importing') }}</p>
                <div class="progress">
                    <div id='progress-import' class="progress-bar progress-bar-primary progress-bar-striped" role="progressbar" aria-valuenow="40"
                         aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                        <span class="visually-hidden">{{ trans('crudbooster.40_complete_success') }}</span>
                    </div>
                </div>

                @push('bottom')
                    <script type="text/javascript">
                        $(function () {
                            var total = {{ intval(Session::get('total_data_import')) }};

                            var int_prog = setInterval(function () {

                                $.post("{{ CRUDBooster::mainpath('do-import-chunk?file='.Request::get('file')) }}", {resume: 1}, function (resp) {
                                    console.log(resp.progress);
                                    $('#progress-import').css('width', resp.progress + '%');
                                    $('#status-import').html("<i class='fa fa-spin fa-spinner'></i> Please wait importing... (" + resp.progress + "%)");
                                    $('#progress-import').attr('aria-valuenow', resp.progress);
                                    if (resp.progress >= 100) {
                                        $('#status-import').addClass('text-success').html("<i class='fa fa-check-square-o'></i> Import Data Completed !");
                                        clearInterval(int_prog);
                                    }
                                })


                            }, 2500);

                            $.post("{{ CRUDBooster::mainpath('do-import-chunk').'?file='.Request::get('file') }}", function (resp) {
                                if (resp.status == true) {
                                    $('#progress-import').css('width', '100%');
                                    $('#progress-import').attr('aria-valuenow', 100);
                                    $('#status-import').addClass('text-success').html("<i class='fa fa-check-square-o'></i> Import Data Completed !");
                                    clearInterval(int_prog);
                                    $('#upload-footer').show();
                                    console.log('Import Success');
                                }
                            })

                        })

                    </script>
                @endpush

            </div><!-- /.card-body -->

            <div class="card-footer" id='upload-footer' style="display:none">
                <!--<div class='pull-right'>-->
                    <a href='{{ CRUDBooster::mainpath("import-data") }}' class='btn btn-default'><i class='fa fa-upload'></i> {{ trans('crudbooster.upload_other_file') }}</a>
                    <a href='{{CRUDBooster::mainpath()}}' class='btn btn-success'>{{ trans('crudbooster.finish') }}</a>
                <!--</div>-->
            </div><!-- /.card-footer-->

        </div><!-- /.box -->
    @endif

    @if(Request::get('file') && !Request::get('import'))

        <ul class='nav nav-tabs'>
            <li  class="nav-item" style="background:#eeeeee">
                <a class="nav-link" style="color:#111"
                                              onclick="if(confirm('Are you sure want to leave ?')) location.href='{{ CRUDBooster::mainpath("import-data") }}'"
                                              href='javascript:;'><i class='fa fa-download'></i> {{ trans('crudbooster.upload_a_file') }} &raquo;</a>
            </li>
            <li  class="nav-item" style="background:#ffffff" class='active'>
                <a class="nav-link" style="color:#111" href='#'><i class='fa fa-cogs'></i> {{ trans('crudbooster.adjustment') }} &raquo;</a>
            </li>
            <li  class="nav-item" style="background:#eeeeee">
                <a  class="nav-link" style="color:#111" href='#'><i class='fa fa-cloud-download'></i> {{ trans('crudbooster.importing') }} &raquo;</a>
            </li>
        </ul>

        <!-- Box -->
        <div id='box_main' class="card card-primary">
            <div class="card-header mb-3 with-border">
                <h3 class="card-title">{{ trans('crudbooster.adjustment') }}</h3>
                <div class="card-tools">

                </div>
            </div>

            <?php
            if (isset($data_sub_module)) {
                $action_path = Route($data_sub_module->controller."GetIndex");
            } else {
                $action_path = CRUDBooster::mainpath();
            }

            $action = $action_path."/done-import?file=".Request::get('file').'&import=1';
            ?>

            <form method='post' id="form" enctype="multipart/form-data" action='{{$action}}'>
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <div class="card-body table-responsive no-padding">
                    <div class='callout callout-info'>
                        * {{ trans('crudbooster.just_ignoring_the_column_where_you_are_not_sure_the_data_is_suit_with_the_column_or_not') }}<br/>
                        * {{ trans('crudbooster.warning_cant_import') }}
                    </div>
                    @push('head')
                        <style type="text/css">
                            th, td {
                                white-space: nowrap;
                            }
                        </style>
                    @endpush
                    <table class='table table-bordered' style="width:130%">
                        <thead>
                        <tr class='success'>
                            @foreach($table_columns as $k=>$column)
                                <?php
                                $help = '';
                                if ($column == 'id' || $column == 'created_at' || $column == 'updated_at' || $column == 'deleted_at') continue;
                                if (substr($column, 0, 3) == 'id_') {
                                    $relational_table = substr($column, 3);
                                    $help = "<a href='#' title='This is foreign key, so the System will be inserting new data to table `$relational_table` if doesn`t exists'><strong>(?)</strong></a>";
                                }
                                ?>
                                <th data-no-column='{{$k}}'>{{ $column }} {!! $help !!}</th>
                            @endforeach
                        </tr>
                        </thead>
                        <tbody>

                        <tr>
                            @foreach($table_columns as $k=>$column)
                                <?php if ($column == 'id' || $column == 'created_at' || $column == 'updated_at' || $column == 'deleted_at') continue;?>
                                <td data-no-column='{{$k}}'>
                                    <select style='width:120px' class='form-control select_column' name='select_column[{{$k}}]'>
                                        <option value=''>** Set Column for {{$column}}</option>
                                        @foreach($data_import_column as $import_column)
                                            <option value='{{$import_column}}'>{{$import_column}}</option>
                                        @endforeach
                                    </select>
                                </td>
                            @endforeach
                        </tr>
                        </tbody>
                    </table>


                </div><!-- /.card-body -->

                @push('bottom')
                    <script type="text/javascript">
                        $(function () {
                            var total_selected_column = 0;
                            setInterval(function () {
                                total_selected_column = 0;
                                $('.select_column').each(function () {
                                    var n = $(this).val();
                                    if (n) total_selected_column = total_selected_column + 1;
                                })
                            }, 200);
                        })

                        function check_selected_column() {
                            var total_selected_column = 0;
                            $('.select_column').each(function () {
                                var n = $(this).val();
                                if (n) total_selected_column = total_selected_column + 1;
                            })
                            if (total_selected_column == 0) {
                                swal("Oops...", "Please at least 1 column that should adjusted...", "error");
                                return false;
                            } else {
                                return true;
                            }
                        }
                    </script>
                @endpush

                <div class="card-footer">
                    <!--<div class='pull-right'>-->
                        <a onclick="if(confirm('Are you sure want to leave ?')) location.href='{{ CRUDBooster::mainpath("import-data") }}'" href='javascript:;'
                           class='btn btn-default'>{{ trans('crudbooster.button_cancel') }}</a>
                        <input type='submit' class='btn btn-primary' name='submit' onclick='return check_selected_column()' value='{{ trans("crudbooster.button_import") }}'/>
                    <!--</div>-->
                </div><!-- /.card-footer-->
            </form>
        </div><!-- /.box -->


    @endif

    @if(!Request::get('file'))
        <ul class='nav nav-tabs'>
            <li  class="nav-item" style="background:#ffffff" >
                <a class="nav-link" style="color:#111"
                                                             onclick="if(confirm('Are you sure want to leave ?')) 
                                                                location.href='{{ CRUDBooster::mainpath("import-data") }}'"
                                                             href='javascript:;'><i class='fa fa-download'></i> {{ trans("crudbooster.upload_a_file") }} &raquo;</a>
            </li>
            <li class="nav-item" style="background:#eeeeee"><a class="nav-link" style="color:#111" href='#'><i class='fa fa-cogs'></i> {{ trans("crudbooster.adjustment") }} &raquo;</a></li>
            <li class="nav-item" style="background:#eeeeee"><a class="nav-link" style="color:#111" href='#'><i class='fa fa-cloud-download'></i> {{ trans("crudbooster.importing") }} &raquo;</a></li>
        </ul>

        <!-- Box -->
        <div id='box_main' class="card card-primary">
            <div class="card-header mb-3 with-border">
                <h3 class="card-title">{{ trans("crudbooster.upload_a_file") }}</h3>
                <div class="card-tools">

                </div>
            </div>

            <?php
            if (isset($data_sub_module)) {
                $action_path = Route($data_sub_module->controller."GetIndex");
            } else {
                $action_path = CRUDBooster::mainpath();
            }

            $action = $action_path."/do-upload-import-data";
            ?>

            <form method='post' id="form" enctype="multipart/form-data" action='{{$action}}'>
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <div class="card-body">

                    <div class='callout callout-success'>
                        <h4>{{ trans('crudbooster.welcome_to_data_importer_tool') }}</h4>
                        {{ trans('crudbooster.before_doing_upload_a_file_its_better_to_read_this_below_instructions') }} : <br/>
                        * {{ trans('crudbooster.file_format_should_be') }} : xls / xlsx / csv<br/>
                        * {{ trans('crudbooster.if_you_have_a_big_file_data') }}<br/>
                        * {{ trans('crudbooster.this_tool_is_generate_data') }}.<br/>
                        * {{ trans('crudbooster.table_structure') }}
                    </div>

                    <div class='mb-3 row'>
                        <label>File XLS / CSV</label>
                        <input type='file' name='userfile' class='form-control' required/>
                        <div class='help-block'>{{ trans('crudbooster.file_support_only') }} : XLS, XLSX, CSV</div>
                    </div>
                </div><!-- /.card-body -->

                <div class="card-footer">
                    <!--<div class='pull-right'>-->
                        <a href='{{ CRUDBooster::mainpath() }}' class='btn btn-default'>{{ trans("crudbooster.button_cancel") }}</a>
                        <input type='submit' class='btn btn-primary' name='submit' value='{{ trans("crudbooster.upload") }}'/>
                    <!--</div>-->
                </div><!-- /.card-footer-->
            </form>
        </div><!-- /.box -->


        @endif
        </div><!-- /.col -->


        </div><!-- /.row -->

@endsection