@extends('crudbooster::admin_template')
@section('content')


    @if(isset($button_show_data) || isset($button_reload_data) || isset($button_new_data) || isset($button_delete_data) || isset($index_button) || isset($columns))
        <!--<div id='card-actionmenu' class='box'>
            <div class='card-body'>

            </div>
        </div>-->
    @endif


    @if(Request::get('file') && Request::get('import'))

        <div class="ch-import-stepper">
            <a class="ch-step is-done" href='javascript:;'
                onclick="if(confirm('Are you sure want to leave ?')) location.href='{{ CRUDBooster::mainpath("import-data") }}'">
                <span class="ch-step-circle"><i class="fa fa-check"></i></span>
                <span class="ch-step-label">{{ trans('crudbooster.upload_a_file') }}</span>
            </a>
            <span class="ch-step-line is-done"></span>
            <a class="ch-step is-done" href='#'>
                <span class="ch-step-circle"><i class="fa fa-check"></i></span>
                <span class="ch-step-label">{{ trans('crudbooster.adjustment') }}</span>
            </a>
            <span class="ch-step-line is-done"></span>
            <a class="ch-step is-current" href='#'>
                <span class="ch-step-circle">3</span>
                <span class="ch-step-label">{{ trans('crudbooster.importing') }}</span>
            </a>
        </div>

        <!-- Box -->
        <div id='box_main' class="card card-primary">
            <div class="card-header mb-3 with-border">
                <i class="ch-card-icon fa fa-cloud-download"></i>
                <h3 class="card-title">{{ trans('crudbooster.importing') }}</h3>
                <div class="card-tools">
                </div>
            </div>

            <div class="card-body">

                <p id='status-import'><i class='fa fa-spin fa-spinner'></i> {{ trans('crudbooster.please_wait_importing') }}</p>
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

        <div class="ch-import-stepper">
            <a class="ch-step is-done" href='javascript:;'
                onclick="if(confirm('Are you sure want to leave ?')) location.href='{{ CRUDBooster::mainpath("import-data") }}'">
                <span class="ch-step-circle"><i class="fa fa-check"></i></span>
                <span class="ch-step-label">{{ trans('crudbooster.upload_a_file') }}</span>
            </a>
            <span class="ch-step-line is-done"></span>
            <a class="ch-step is-current" href='#'>
                <span class="ch-step-circle">2</span>
                <span class="ch-step-label">{{ trans('crudbooster.adjustment') }}</span>
            </a>
            <span class="ch-step-line"></span>
            <a class="ch-step" href='#'>
                <span class="ch-step-circle">3</span>
                <span class="ch-step-label">{{ trans('crudbooster.importing') }}</span>
            </a>
        </div>

        <!-- Box -->
        <div id='box_main' class="card card-primary">
            <div class="card-header mb-3 with-border">
                <i class="ch-card-icon fa fa-sliders"></i>
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
                    <div class="ch-info-panel is-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        <ul>
                            <li>{{ trans('crudbooster.just_ignoring_the_column_where_you_are_not_sure_the_data_is_suit_with_the_column_or_not') }}</li>
                            <li>{{ trans('crudbooster.warning_cant_import') }}</li>
                        </ul>
                    </div>

                    <div class="ch-map-table-wrap">
                        <table class="ch-map-table">
                            <thead>
                                <tr>
                                    <th>Colonna nel file</th>
                                    <th>Corrispondenza</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($table_columns as $k=>$column)
                                    @continue($column == 'id' || $column == 'created_at' || $column == 'updated_at' || $column == 'deleted_at')
                                    <?php
                                        $help = '';
                                        if (substr($column, 0, 3) == 'id_') {
                                            $relational_table = substr($column, 3);
                                            $help = "This is foreign key, so the System will be inserting new data to table `$relational_table` if doesn`t exists";
                                        }
                                    ?>
                                    <tr>
                                        <td data-no-column='{{$k}}'>
                                            <span class="ch-src-col">{{ $column }}</span>
                                            @if($help)
                                                <span class="ch-fk-hint" title="{{ $help }}">?</span>
                                            @endif
                                        </td>
                                        <td data-no-column='{{$k}}'>
                                            <select class='form-control select_column' name='select_column[{{$k}}]'>
                                                <option value=''>Non importare questa colonna</option>
                                                @foreach($data_import_column as $dk=>$dcol)
                                                    <option value='{{$dk}}'>{{$dcol}}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="ch-map-summary">
                        <i class="fa fa-info-circle"></i>
                        <span id="ch-map-summary-text"></span>
                    </div>

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

                            /*
                             * Contatore visibile "N di M colonne assegnate" nel nuovo
                             * pannello (vedi ch-map-summary sopra) - indipendente dal
                             * total_selected_column qui sopra (usato solo dalla
                             * validazione al submit), aggiornato ad ogni cambio invece
                             * che a polling.
                             */
                            var $mapSelects = $('.select_column');
                            var mapTotal = $mapSelects.length;
                            function updateMapSummary() {
                                var mapped = $mapSelects.filter(function () { return $(this).val(); }).length;
                                var text = mapped + ' di ' + mapTotal + ' colonne assegnate';
                                if (mapped < mapTotal) {
                                    text += ' &middot; ' + (mapTotal - mapped) + ' verranno ignorate';
                                }
                                $('#ch-map-summary-text').html(text);
                            }
                            $mapSelects.on('change', updateMapSummary);
                            updateMapSummary();
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
        <div class="ch-import-stepper">
            <a class="ch-step is-current" href='javascript:;'
                onclick="if(confirm('Are you sure want to leave ?')) location.href='{{ CRUDBooster::mainpath("import-data") }}'">
                <span class="ch-step-circle">1</span>
                <span class="ch-step-label">{{ trans("crudbooster.upload_a_file") }}</span>
            </a>
            <span class="ch-step-line"></span>
            <a class="ch-step" href='#'>
                <span class="ch-step-circle">2</span>
                <span class="ch-step-label">{{ trans("crudbooster.adjustment") }}</span>
            </a>
            <span class="ch-step-line"></span>
            <a class="ch-step" href='#'>
                <span class="ch-step-circle">3</span>
                <span class="ch-step-label">{{ trans("crudbooster.importing") }}</span>
            </a>
        </div>

        <!-- Box -->
        <div id='box_main' class="card card-primary">
            <div class="card-header mb-3 with-border">
                <i class="ch-card-icon fa fa-upload"></i>
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

                    <div class="ch-info-panel">
                        <i class="fa fa-info-circle"></i>
                        <div>
                            <p class="ch-info-title">{{ trans('crudbooster.welcome_to_data_importer_tool') }}</p>
                            <p class="ch-info-lede">{{ trans('crudbooster.before_doing_upload_a_file_its_better_to_read_this_below_instructions') }}:</p>
                            <ul>
                                <li>{{ trans('crudbooster.file_format_should_be') }} : xls / xlsx / csv</li>
                                <li>{{ trans('crudbooster.if_you_have_a_big_file_data') }}</li>
                                <li>{{ trans('crudbooster.this_tool_is_generate_data') }}.</li>
                                <li>{{ trans('crudbooster.table_structure') }}</li>
                            </ul>
                        </div>
                    </div>

                    <div class='mb-3 row'>
                        <label>File XLS / CSV</label>
                        <div class="ch-dropzone" id="ch-import-dropzone">
                            <div class="ch-dropzone-icon"><i class="fa fa-cloud-upload"></i></div>
                            <p><strong>Trascina qui il file</strong> oppure</p>
                            <button type="button" class="btn btn-default" id="ch-choose-file-btn">Scegli file</button>
                            <input type='file' name='userfile' id="ch-import-file-input" class='form-control' required style="display:none" />
                            <div class="ch-dropzone-formats">
                                <span class="ch-format-chip">XLS</span>
                                <span class="ch-format-chip">XLSX</span>
                                <span class="ch-format-chip">CSV</span>
                            </div>
                        </div>
                        <div class="ch-file-picked" id="ch-file-picked" style="display:none">
                            <i class="fa fa-file-text-o"></i>
                            <b id="ch-file-picked-name"></b>
                            <span id="ch-file-picked-size"></span>
                        </div>
                        <div class='help-block'>{{ trans('crudbooster.file_support_only') }} : XLS, XLSX, CSV</div>
                    </div>
                </div><!-- /.card-body -->

                @push('bottom')
                    <script type="text/javascript">
                        (function () {
                            var dropzone = document.getElementById('ch-import-dropzone');
                            var input = document.getElementById('ch-import-file-input');
                            var chooseBtn = document.getElementById('ch-choose-file-btn');
                            var picked = document.getElementById('ch-file-picked');
                            var pickedName = document.getElementById('ch-file-picked-name');
                            var pickedSize = document.getElementById('ch-file-picked-size');

                            function showPicked(file) {
                                if (!file) return;
                                pickedName.textContent = file.name;
                                pickedSize.textContent = (file.size / (1024 * 1024)).toFixed(1) + ' MB';
                                picked.style.display = 'flex';
                            }

                            chooseBtn.addEventListener('click', function () {
                                input.click();
                            });
                            input.addEventListener('change', function () {
                                showPicked(input.files[0]);
                            });

                            ['dragover', 'dragleave', 'drop'].forEach(function (evt) {
                                dropzone.addEventListener(evt, function (e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    dropzone.classList.toggle('is-drag', evt === 'dragover');
                                    if (evt === 'drop' && e.dataTransfer.files.length) {
                                        input.files = e.dataTransfer.files;
                                        showPicked(input.files[0]);
                                    }
                                });
                            });
                        })();
                    </script>
                @endpush

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
