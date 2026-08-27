@extends('crudbooster::admin_template')

@section('content')
@push('bottom')
    <script src="{{ asset('vendor/laravel-filemanager/js/lfm.js') }}"></script>
    <script src="//cdn.tinymce.com/4/tinymce.min.js"></script>
    <script defer src="{{ asset('js/qlik_conf.js') }}"></script>

    <script>
        $(function () {
            $('.label-setting').hover(function () {
                $(this).find('a').css("visibility", "visible");
            }, function () {
                $(this).find('a').css("visibility", "hidden");
            });
        });

        var editor_config = {
            path_absolute: "{{ asset('/') }}",
            selector: ".wysiwyg",
            height: 250,
            plugins: [
                "advlist autolink lists link image charmap print preview hr anchor pagebreak",
                "searchreplace wordcount visualblocks visualchars code fullscreen",
                "insertdatetime media nonbreaking save table contextmenu directionality",
                "emoticons template paste textcolor colorpicker textpattern"
            ],
            toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media",
            relative_urls: false,
            file_browser_callback: function (field_name, url, type, win) {
                var x = window.innerWidth || document.documentElement.clientWidth || document.getElementsByTagName('body')[0].clientWidth;
                var y = window.innerHeight || document.documentElement.clientHeight || document.getElementsByTagName('body')[0].clientHeight;
                
                var cmsURL = editor_config.path_absolute + 'laravel-filemanager?field_name=' + field_name;
                cmsURL += (type == 'image') ? "&type=Images" : "&type=Files";

                tinyMCE.activeEditor.windowManager.open({
                    file: cmsURL,
                    title: 'Filemanager',
                    width: x * 0.8,
                    height: y * 0.8,
                    resizable: "yes",
                    close_previous: "no"
                });
            }
        };

        tinymce.init(editor_config);
    </script>
@endpush

<div style="width: 750px; margin: 0 auto;">
    <p align="right">
        <a title="{{ trans('crudbooster.Add_Field_Setting') }}" class="btn btn-sm btn-primary" href="{{ route('SettingsControllerGetAdd') }}?group_setting={{ urlencode($page_title) }}">
            <i class="fa fa-plus"></i> {{ trans('crudbooster.Add_Field_Setting') }}
        </a>
    </p>

    <div class="card card-default">
        <div class="card-header">
            <i class="fa fa-cog"></i> {{ $page_title }}
        </div>
        <div class="card-body">
            <form method="post" id="form" enctype="multipart/form-data" action="{{ CRUDBooster::mainpath('save-setting?group_setting=' . urlencode($page_title)) }}">
                @csrf
                <div class="box-body">
                    {{-- $settings arriva da SettingsController::getShow(): prima la
                         query (e la UPDATE che ripara le label vuote) stavano qui. --}}
                    @foreach($settings as $s)
                        @php
                            $value = $s->content;
                            $dataenum = array_map('trim', explode(',', $s->dataenum));
                        @endphp

                        <div class="mb-3 row">
                            <label class="label-setting" title="{{ $s->name }}">
                                {{ $s->label }}
                                <a style="visibility: hidden" href="{{ CRUDBooster::mainpath('edit/' . $s->id) }}" title="Edit This Meta Setting" class="btn btn-box-tool">
                                    <i class="fa fa-pencil"></i>
                                </a>
                                <a style="visibility: hidden" href="javascript:;" title="Delete this Setting" class="btn btn-box-tool" onClick="swal({
                                    title: '{{ trans('crudbooster.delete_title_confirm') }}',
                                    text: '{{ trans('crudbooster.delete_description_confirm') }} {{ $s->label }} {{ trans('crudbooster.and_may_be_can_cause_some_errors_on_your_system') }}',
                                    type: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#DD6B55',
                                    confirmButtonText: '{{ trans('crudbooster.yes_delete_it') }}',
                                    closeOnConfirm: false
                                }, function() { location.href='{{ CRUDBooster::mainpath("delete/" . $s->id) }}' });">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </label>

                            @switch($s->content_input_type)
                                @case('text')
                                    <input type="text" class="form-control" name="{{ $s->name }}" value="{{ $value }}" />
                                    @break

                                @case('password')
                                    {{-- Mai ripopolato col valore salvato: la password
                                         non deve comparire in chiaro nel sorgente.
                                         Campo vuoto = "non modificare" (vedi
                                         postSaveSetting). --}}
                                    <input type="password" class="form-control" name="{{ $s->name }}" value="" autocomplete="new-password" />
                                    <div class="help-block">Leave empty to keep the current value.</div>
                                    @break

                                @case('number')
                                    <input type="number" class="form-control" name="{{ $s->name }}" value="{{ $value }}" />
                                    @break

                                @case('email')
                                    <input type="email" class="form-control" name="{{ $s->name }}" value="{{ $value }}" />
                                    @break

                                @case('textarea')
                                    <textarea name="{{ $s->name }}" class="form-control">{{ $value }}</textarea>
                                    @break

                                @case('wysiwyg')
                                    <textarea name="{{ $s->name }}" class="form-control wysiwyg">{{ $value }}</textarea>
                                    @break

                                @case('upload')
                                @case('upload_image')
                                    @if ($value)
                                        @php
                                            // $value e' un path relativo alla public root (es.
                                            // "/storage/uploads/...") oppure, su installazioni non ancora
                                            // bonificate, un vecchio URL assoluto: solo nel primo caso si
                                            // puo' verificare il file su disco. La verifica evita di
                                            // mostrare un'immagine rotta quando il valore e' rimasto in
                                            // tabella ma il file no.
                                            $is_absolute_url = (bool) preg_match('~^https?://~i', $value);
                                            $file_missing = ! $is_absolute_url && ! is_file(public_path($value));
                                        @endphp

                                        @if ($file_missing)
                                            <p class="text-danger" style="margin-bottom: 5px">
                                                <i class="fa fa-exclamation-triangle"></i>
                                                File not found on the server: <code>{{ $value }}</code>
                                            </p>
                                        @else
                                            <p style="margin-bottom: 5px">
                                                <a href="{{ asset($value) }}" data-lightbox="roadtrip" title="{{ $s->label }}">
                                                    <img src="{{ asset($value) }}" alt="{{ $s->label }}"
                                                         style="max-height: 120px; max-width: 100%; padding: 3px; border: 1px solid #ddd; background: #fff" />
                                                </a>
                                            </p>
                                            <p>
                                                <a href="{{ asset($value) }}" target="_blank" title="{{ trans('crudbooster.button_download_file') }} {{ $s->label }}">
                                                    <i class="fa fa-download"></i> {{ trans('crudbooster.button_download_file') }} {{ $s->label }}
                                                </a>
                                            </p>
                                        @endif

                                        <input type="hidden" name="{{ $s->name }}" value="{{ $value }}" />
                                        <div class="pull-right">
                                            <a class="btn btn-danger btn-sm" onclick="if (confirm('{{ trans('crudbooster.delete_title_confirm') }}')) window.location.href='{{ CRUDBooster::mainpath('delete-file-setting?id=' . $s->id) }}';" title="Click here to delete">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </div>
                                    @else
                                        <input type="file" name="{{ $s->name }}" class="form-control" />
                                    @endif
                                    <div class="help-block">{{ trans('crudbooster.file_support_only') }} jpg,png,gif, Max 10 MB</div>
                                    @break

                                @case('upload_document')
                                @case('upload_file')
                                    @if ($value)
                                        <p>
                                            <a href="{{ asset($value) }}" target="_blank" title="{{ trans('crudbooster.button_download_file') }} {{ $s->label }}">
                                                <i class="fa fa-download"></i> {{ trans('crudbooster.button_download_file') }} {{ $s->label }}
                                            </a>
                                        </p>
                                        <input type="hidden" name="{{ $s->name }}" value="{{ $value }}" />
                                        <div class="pull-right">
                                            <a class="btn btn-danger btn-sm" onclick="if (confirm('Are you sure want to delete?')) location.href='{{ CRUDBooster::mainpath("delete-file-setting?id=" . $s->id) }}';" title="Click here to delete">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </div>
                                    @else
                                        <input type="file" name="{{ $s->name }}" class="form-control" />
                                    @endif
                                    <div class="help-block">{{ trans('crudbooster.file_support_only') }} pem,doc,docx,xls,xlsx,ppt,pptx,pdf,zip,rar, Max 20 MB</div>
                                    @break

                                @case('datepicker')
                                    <input type="text" class="datepicker form-control" name="{{ $s->name }}" value="{{ $value }}" />
                                    @break

                                @case('radio')
                                    @if ($dataenum)
                                        <br />
                                        @foreach ($dataenum as $enum)
                                            <label class="radio-inline">
                                                <input type="radio" name="{{ $s->name }}" value="{{ $enum }}" {{ $enum == $value ? 'checked' : '' }}> {{ $enum }}
                                            </label>
                                        @endforeach
                                    @endif
                                    @break

                                @case('select')
                                    <select name="{{ $s->name }}" class="form-control">
                                        <option value="">Select {{ $s->label }}</option>
                                        @foreach ($dataenum as $enum)
                                            <option value="{{ $enum }}" {{ $enum == $value ? 'selected' : '' }}>{{ $enum }}</option>
                                        @endforeach
                                    </select>
                                    @break
                            @endswitch

                            <div class="help-block">{{ $s->helper }}</div>
                        </div>
                    @endforeach
                </div><!-- /.box-body -->

                <div class="box-footer">
                    <div class="pull-right">
                        <input type="submit" name="submit" value="Save" class="btn btn-success" />
                    </div>
                </div><!-- /.box-footer-->
            </form>
        </div>
    </div>
</div>

@endsection
