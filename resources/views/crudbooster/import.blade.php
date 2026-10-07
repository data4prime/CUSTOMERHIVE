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
                <span class="ch-step-circle"><i class="bi bi-check-lg"></i></span>
                <span class="ch-step-label">{{ trans('crudbooster.upload_a_file') }}</span>
            </a>
            <span class="ch-step-line is-done"></span>
            <a class="ch-step is-done" href='#'>
                <span class="ch-step-circle"><i class="bi bi-check-lg"></i></span>
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
                <i class="ch-card-icon bi bi-cloud-download"></i>
                <h3 class="card-title">{{ trans('crudbooster.importing') }}</h3>
                <div class="card-tools">
                </div>
            </div>

            <div class="card-body">

                <p id='status-import'><i class='bi ch-spin bi-arrow-repeat'></i> {{ trans('crudbooster.please_wait_importing') }}</p>
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
                                    $('#status-import').html("<i class='bi ch-spin bi-arrow-repeat'></i> Please wait importing... (" + resp.progress + "%)");
                                    $('#progress-import').attr('aria-valuenow', resp.progress);
                                    if (resp.progress >= 100) {
                                        $('#status-import').addClass('text-success').html("<i class='bi bi-check-square'></i> Import Data Completed !");
                                        clearInterval(int_prog);
                                    }
                                })


                            }, 2500);

                            var importFailTitle = {!! json_encode(trans('crudbooster.import_failed_title')) !!};
                            var importFailNote = {!! json_encode(trans('crudbooster.import_failed_note')) !!};
                            var importFailUnknown = {!! json_encode(trans('crudbooster.import_err_server')) !!};

                            // Import interrotto: barra rossa, motivo ben visibile, nulla e' stato scritto
                            function showImportError(message) {
                                clearInterval(int_prog);
                                $('#progress-import').removeClass('progress-bar-striped progress-bar-primary').addClass('bg-danger').css('width', '100%');
                                $('#status-import').addClass('text-danger').html("<i class='bi bi-x-octagon-fill'></i> " + $('<div>').text(importFailTitle).html());
                                $('#import-error-box').remove();
                                $('#status-import').after(
                                    $("<div id='import-error-box' class='alert alert-danger mt-3'></div>")
                                        .append($('<strong>').text(message))
                                        .append($('<div class="mt-2">').text(importFailNote))
                                );
                                $('#upload-footer').show();
                            }

                            $.post("{{ CRUDBooster::mainpath('do-import-chunk').'?file='.Request::get('file') }}", function (resp) {
                                if (resp.status == true) {
                                    $('#progress-import').css('width', '100%');
                                    $('#progress-import').attr('aria-valuenow', 100);
                                    $('#status-import').addClass('text-success').html("<i class='bi bi-check-square'></i> Import Data Completed !");
                                    if (resp.summary) {
                                        $('#status-import').after($("<div class='alert alert-success mt-3'></div>").text(resp.summary));
                                    }
                                    clearInterval(int_prog);
                                    $('#upload-footer').show();
                                } else {
                                    showImportError(resp.error || importFailUnknown);
                                }
                            }).fail(function () {
                                showImportError(importFailUnknown);
                            })

                        })

                    </script>
                @endpush

            </div><!-- /.card-body -->

            <div class="card-footer" id='upload-footer' style="display:none">
                <!--<div class='float-end'>-->
                    <a href='{{ CRUDBooster::mainpath("import-data") }}' class='btn btn-secondary'><i class='bi bi-upload'></i> {{ trans('crudbooster.upload_other_file') }}</a>
                    <a href='{{CRUDBooster::mainpath()}}' class='btn btn-success'>{{ trans('crudbooster.finish') }}</a>
                <!--</div>-->
            </div><!-- /.card-footer-->

        </div><!-- /.box -->
    @endif

    @if(Request::get('file') && !Request::get('import'))

        <div class="ch-import-stepper">
            <a class="ch-step is-done" href='javascript:;'
                onclick="if(confirm('Are you sure want to leave ?')) location.href='{{ CRUDBooster::mainpath("import-data") }}'">
                <span class="ch-step-circle"><i class="bi bi-check-lg"></i></span>
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
                <i class="ch-card-icon bi bi-sliders"></i>
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
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <ul>
                            <li>{{ trans('crudbooster.just_ignoring_the_column_where_you_are_not_sure_the_data_is_suit_with_the_column_or_not') }}</li>
                            <li>{{ trans('crudbooster.warning_cant_import') }}</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ trans('crudbooster.import_mode_label') }}</label>
                        <div class="ch-seg ch-seg-block" role="radiogroup">
                            <label>
                                <input type="radio" name="import_mode" value="insert" checked>
                                <span><i class="bi bi-plus-circle"></i> {{ trans('crudbooster.import_mode_insert') }}</span>
                            </label>
                            <label>
                                <input type="radio" name="import_mode" value="upsert">
                                <span><i class="bi bi-arrow-repeat"></i> {{ trans('crudbooster.import_mode_upsert') }}</span>
                            </label>
                        </div>
                        <div class="help-block" id="import-upsert-help" style="display:none">{{ trans('crudbooster.import_mode_upsert_help') }}</div>
                    </div>

                    <div class="ch-map-table-wrap">
                        <table class="ch-map-table">
                            <thead>
                                <tr>
                                    <th>Colonna nel file</th>
                                    <th>Corrispondenza</th>
                                    <th class="import-key-cell" style="display:none">{{ trans('crudbooster.import_key_column') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($table_columns as $k=>$column)
                                    @continue(in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by'], true))
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
                                                    <option value='{{$dk}}' @if(isset($auto_map[$k]) && (string) $auto_map[$k] === (string) $dk) selected @endif>{{$dcol}}</option>
                                                @endforeach
                                            </select>
                                            @if(isset($auto_map[$k]))
                                                <span class="ch-fk-hint" title="{{ trans('crudbooster.import_auto_mapped') }}"><i class="bi bi-magic"></i></span>
                                            @endif
                                        </td>
                                        <td class="import-key-cell" style="display:none">
                                            <input type="checkbox" class="form-check-input import-key-checkbox" name="key_column[{{$k}}]" value="1">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="ch-map-summary">
                        <i class="bi bi-info-circle-fill"></i>
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
                                @if(!empty($auto_map))
                                text += ' &middot; ' + {!! json_encode(trans('crudbooster.import_auto_mapped_summary', ['count' => count($auto_map)])) !!};
                                @endif
                                if (mapped < mapTotal) {
                                    text += ' &middot; ' + (mapTotal - mapped) + ' verranno ignorate';
                                }
                                $('#ch-map-summary-text').html(text);
                            }
                            $mapSelects.on('change', updateMapSummary);
                            updateMapSummary();

                            // Modalita' "aggiorna o inserisci": la colonna Chiave compare solo
                            // in quel caso, e solo le colonne abbinate si possono usare come chiave.
                            function syncKeyColumn() {
                                var upsert = $('input[name=import_mode]:checked').val() === 'upsert';
                                $('.import-key-cell').toggle(upsert);
                                $('#import-upsert-help').toggle(upsert);
                                $mapSelects.each(function () {
                                    var $cb = $(this).closest('tr').find('.import-key-checkbox');
                                    var mapped = !!$(this).val();
                                    $cb.prop('disabled', !mapped);
                                    if (!mapped) { $cb.prop('checked', false); }
                                });
                            }
                            $('input[name=import_mode]').on('change', syncKeyColumn);
                            $mapSelects.on('change', syncKeyColumn);
                            syncKeyColumn();
                        })

                        function check_selected_column() {
                            var total_selected_column = 0;
                            $('.select_column').each(function () {
                                var n = $(this).val();
                                if (n) total_selected_column = total_selected_column + 1;
                            })
                            if (total_selected_column == 0) {
                                chAlert({ title: {!! json_encode(trans('crudbooster.import_select_one_column_title')) !!}, text: {!! json_encode(trans('crudbooster.import_select_one_column')) !!}, type: "error" });
                                return false;
                            } else if ($('input[name=import_mode]:checked').val() === 'upsert' && $('.import-key-checkbox:checked').length == 0) {
                                chAlert({ title: {!! json_encode(trans('crudbooster.import_select_one_column_title')) !!}, text: {!! json_encode(trans('crudbooster.import_key_required')) !!}, type: "error" });
                                return false;
                            } else {
                                return true;
                            }
                        }
                    </script>
                @endpush

                <div class="card-footer">
                    <!--<div class='float-end'>-->
                        <a onclick="if(confirm('Are you sure want to leave ?')) location.href='{{ CRUDBooster::mainpath("import-data") }}'" href='javascript:;'
                           class='btn btn-secondary'>{{ trans('crudbooster.button_cancel') }}</a>
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
                <i class="ch-card-icon bi bi-upload"></i>
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
                        <i class="bi bi-info-circle-fill"></i>
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
                            <div class="ch-dropzone-icon"><i class="bi bi-cloud-upload"></i></div>
                            <p><strong>Trascina qui il file</strong> oppure</p>
                            <button type="button" class="btn btn-secondary" id="ch-choose-file-btn">Scegli file</button>
                            <input type='file' name='userfile' id="ch-import-file-input" class='form-control' required style="display:none" />
                            <div class="ch-dropzone-formats">
                                <span class="ch-format-chip">XLS</span>
                                <span class="ch-format-chip">XLSX</span>
                                <span class="ch-format-chip">CSV</span>
                            </div>
                        </div>
                        <div class="ch-file-picked" id="ch-file-picked" style="display:none">
                            <i class="bi bi-file-earmark-text"></i>
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
                    <!--<div class='float-end'>-->
                        <a href='{{ CRUDBooster::mainpath() }}' class='btn btn-secondary'>{{ trans("crudbooster.button_cancel") }}</a>
                        <input type='submit' class='btn btn-primary' name='submit' value='{{ trans("crudbooster.upload") }}'/>
                    <!--</div>-->
                </div><!-- /.card-footer-->
            </form>
        </div><!-- /.box -->


        @endif
        </div><!-- /.col -->


        </div><!-- /.row -->

@endsection
