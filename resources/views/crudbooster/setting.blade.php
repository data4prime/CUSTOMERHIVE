@extends('crudbooster::admin_template')

@section('content')
@include('crudbooster::partials.ch_select2_assets')
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

        $(function () {
            // Test invio email (solo gruppo "Email Setting"): AJAX a parte,
            // non invia il form - i valori vengono letti dai campi cosi'
            // come sono in quel momento, anche se non ancora salvati.
            $('#btn-email-test').on('click', function () {
                var $btn = $(this);
                var $result = $('#email-test-result');

                $result.removeClass('text-success text-danger').text({!! json_encode(trans('crudbooster.email_test_sending')) !!});
                $btn.prop('disabled', true);

                $.ajax({
                    url: '{{ CRUDBooster::mainpath("test-email") }}',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        _token: '{{ csrf_token() }}',
                        test_to: $('#email-test-to').val(),
                        email_sender: $('[name="email_sender"]').val(),
                        smtp_driver: $('[name="smtp_driver"]').val(),
                        smtp_host: $('[name="smtp_host"]').val(),
                        smtp_port: $('[name="smtp_port"]').val(),
                        smtp_username: $('[name="smtp_username"]').val(),
                        smtp_password: $('[name="smtp_password"]').val(),
                        tls_ssl: $('[name="tls_ssl"]').val()
                    }
                }).done(function (resp) {
                    var text = resp.message + (resp.error ? ' — ' + resp.error : '');
                    $result.addClass(resp.success ? 'text-success' : 'text-danger').text(text);
                }).fail(function () {
                    $result.addClass('text-danger').text({!! json_encode(trans('crudbooster.email_test_unexpected_error')) !!});
                }).always(function () {
                    $btn.prop('disabled', false);
                });
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

{{-- Gruppo "Login Register Style" (riconosciuto dai campi, il nome varia con la lingua):
     form + anteprima della pagina di login affiancati, come nel mockup. --}}
{{-- Idem "Application Setting" (appname + logo): anteprima dell'applicazione a destra (intervento 237/249). --}}
@php
    $isLoginGroup = $settings->contains('name', 'login_background_color') && $settings->contains('name', 'button_color');
    $isAppGroup = $settings->contains('name', 'appname') && $settings->contains('name', 'logo');
    $hasSidePreview = $isLoginGroup || $isAppGroup;
@endphp
<div style="width: {{ $hasSidePreview ? '1100px; max-width: 100%' : '750px' }}; margin: 0 auto;">
    <p align="right">
        <a title="{{ trans('crudbooster.Add_Field_Setting') }}" class="btn btn-sm btn-primary" href="{{ route('SettingsControllerGetAdd') }}?group_setting={{ urlencode($page_title) }}">
            <i class="bi bi-plus-lg"></i> {{ trans('crudbooster.Add_Field_Setting') }}
        </a>
    </p>

    @if($hasSidePreview)<div class="ch-setgrid">@endif
    <div class="card card-default">
        <div class="card-header">
            <i class="bi bi-gear-fill"></i> {{ $page_title }}
        </div>
        <div class="card-body">
            <form method="post" id="form" enctype="multipart/form-data" action="{{ CRUDBooster::mainpath('save-setting?group_setting=' . urlencode($page_title)) }}">
                @csrf
                <div class="box-body">
                    {{-- Il gruppo e' salvato tradotto (varia con la lingua del seed): si riconosce dai campi. --}}
                    {{-- $settings arriva da SettingsController::getShow(): prima la
                         query (e la UPDATE che ripara le label vuote) stavano qui. --}}
                    @foreach($settings as $s)
                        @php
                            $value = $s->content;
                            $dataenum = array_map('trim', explode(',', $s->dataenum));
                            // Label/help tradotti per i setting che hanno le chiavi lang
                            // (es. email_enabled); gli altri restano quelli salvati a DB.
                            $label = \Illuminate\Support\Facades\Lang::has('crudbooster.setting_label_' . $s->name)
                                ? trans('crudbooster.setting_label_' . $s->name) : $s->label;
                            $helper = \Illuminate\Support\Facades\Lang::has('crudbooster.setting_helper_' . $s->name)
                                ? trans('crudbooster.setting_helper_' . $s->name) : $s->helper;
                        @endphp

                        <div class="mb-3 row">
                            <label class="label-setting" title="{{ $s->name }}">
                                {{ $label }}
                                <a style="visibility: hidden" href="{{ CRUDBooster::mainpath('edit/' . $s->id) }}" title="Edit This Meta Setting" class="btn btn-box-tool">
                                    <i class="bi bi-pencil-fill"></i>
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
                                    <i class="bi bi-trash-fill"></i>
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
                                                <i class="bi bi-exclamation-triangle-fill"></i>
                                                File not found on the server: <code>{{ $value }}</code>
                                            </p>
                                        @else
                                            <p style="margin-bottom: 5px">
                                                <a href="{{ asset($value) }}" data-lightbox="roadtrip" title="{{ $s->label }}">
                                                    <img src="{{ asset($value) }}" alt="{{ $s->label }}"
                                                         style="max-height: 120px; max-width: 100%; padding: 3px; border: 1px solid var(--ch-border-strong); background: var(--ch-surface)" />
                                                </a>
                                            </p>
                                            <p>
                                                <a href="{{ asset($value) }}" target="_blank" title="{{ trans('crudbooster.button_download_file') }} {{ $s->label }}">
                                                    <i class="bi bi-download"></i> {{ trans('crudbooster.button_download_file') }} {{ $s->label }}
                                                </a>
                                            </p>
                                        @endif

                                        <input type="hidden" name="{{ $s->name }}" value="{{ $value }}" />
                                        <div class="float-end">
                                            <a class="btn btn-danger btn-sm" onclick="if (confirm('{{ trans('crudbooster.delete_title_confirm') }}')) window.location.href='{{ CRUDBooster::mainpath('delete-file-setting?id=' . $s->id) }}';" title="Click here to delete">
                                                <i class="bi bi-trash-fill"></i>
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
                                                <i class="bi bi-download"></i> {{ trans('crudbooster.button_download_file') }} {{ $s->label }}
                                            </a>
                                        </p>
                                        <input type="hidden" name="{{ $s->name }}" value="{{ $value }}" />
                                        <div class="float-end">
                                            <a class="btn btn-danger btn-sm" onclick="if (confirm('Are you sure want to delete?')) location.href='{{ CRUDBooster::mainpath("delete-file-setting?id=" . $s->id) }}';" title="Click here to delete">
                                                <i class="bi bi-trash-fill"></i>
                                            </a>
                                        </div>
                                    @else
                                        <input type="file" name="{{ $s->name }}" class="form-control" />
                                    @endif
                                    <div class="help-block">{{ trans('crudbooster.file_support_only') }} pem,doc,docx,xls,xlsx,ppt,pptx,pdf,zip,rar, Max 20 MB</div>
                                    @break

                                @case('datepicker')
                                    <input type="text" class="form-control" data-ch-picker="date" readonly name="{{ $s->name }}" value="{{ $value }}" />
                                    @break

                                @case('radio')
                                    @php
                                        // yes/no -> switch standard (partials/ch_check). Il campo nascosto con
                                        // il valore "off" serve perche' una checkbox spenta non viene inviata
                                        // e postSaveSetting lascerebbe invariato il valore precedente.
                                        $enumLower = array_map(fn ($e) => strtolower(trim($e)), (array) $dataenum);
                                        $isYesNo = count($enumLower) === 2 && in_array('yes', $enumLower) && in_array('no', $enumLower);
                                        if ($isYesNo) {
                                            $onVal = (array) $dataenum;
                                            $onVal = $onVal[array_search('yes', $enumLower)];
                                            $offVal = ((array) $dataenum)[array_search('no', $enumLower)];
                                        }
                                    @endphp
                                    @if ($isYesNo)
                                        <input type="hidden" name="{{ $s->name }}" value="{{ $offVal }}">
                                        @include('crudbooster::partials.ch_check', [
                                            'name' => $s->name,
                                            'value' => $onVal,
                                            'label' => '',
                                            'checked' => strtolower(trim((string) $value)) === 'yes',
                                            'disabled' => false,
                                            'switch' => true,
                                            'aria' => $s->label,
                                        ])
                                    @elseif ($dataenum)
                                        @include('crudbooster::partials.ch_radio', [
                                            'name' => $s->name,
                                            'disabled' => false,
                                            'rd_options' => collect($dataenum)->map(fn ($enum) => [
                                                'value' => $enum,
                                                'label' => $enum,
                                                'checked' => $enum == $value,
                                            ])->all(),
                                        ])
                                    @endif
                                    @break

                                @case('select')
                                    <select name="{{ $s->name }}" class="form-control" data-ch-select>
                                        <option value="">Select {{ $s->label }}</option>
                                        @foreach ($dataenum as $enum)
                                            @php
                                                // "valore|Etichetta" (intervento 237): senza "|" valore ed etichetta coincidono, come prima.
                                                $enumParts = explode('|', $enum, 2);
                                                $enumVal = $enumParts[0];
                                                $enumLabel = $enumParts[1] ?? $enumParts[0];
                                            @endphp
                                            <option value="{{ $enumVal }}" {{ $enumVal == $value ? 'selected' : '' }}>{{ $enumLabel }}</option>
                                        @endforeach
                                    </select>
                                    @break

                                @case('color')
                                    {{-- Intervento 237: pallini + colore libero + codice (partials/ch_color) --}}
                                    @include('crudbooster::partials.ch_color', ['name' => $s->name, 'value' => $value])
                                    @break
                            @endswitch

                            <div class="help-block">{{ $helper }}</div>
                        </div>
                    @endforeach

                    @if($page_title === 'Email Setting')
                    {{-- Test invio email: usa i valori digitati nel form COSI'
                         COME SONO, prima di un eventuale salvataggio - non
                         invia il form stesso (niente submit), solo una
                         chiamata AJAX a parte. Vedi docs/refactoring/101-*.
                         Stessa struttura (label sopra, campo a tutta
                         larghezza, help-block sotto) degli altri campi di
                         questa stessa pagina, non il grid a due colonne
                         usato altrove nel pannello. --}}
                    <div class="mb-3 row">
                        <label>{{ trans('crudbooster.email_test_button') }}</label>
                        <div class="d-flex gap-2">
                            <input type="email" id="email-test-to" class="form-control flex-grow-1"
                                   placeholder="{{ trans('crudbooster.email_test_recipient_placeholder') }}"
                                   value="{{ CRUDBooster::me()->email }}">
                            <button type="button" id="btn-email-test" class="btn btn-secondary text-nowrap">
                                <i class="bi bi-send-fill"></i> {{ trans('crudbooster.email_test_button') }}
                            </button>
                        </div>
                        <div id="email-test-result" class="help-block"></div>
                    </div>
                    @endif
                </div><!-- /.box-body -->

                <div class="box-footer">
                    <div class="float-end">
                        <input type="submit" name="submit" value="Save" class="btn btn-success" />
                    </div>
                </div><!-- /.box-footer-->
            </form>
        </div>
    </div>
    @if($isLoginGroup)
    @include('crudbooster::partials.login_setting_preview', ['settings' => $settings])
    @elseif($isAppGroup)
    @include('crudbooster::partials.app_setting_preview', ['settings' => $settings])
    @endif
    @if($hasSidePreview)
    </div>
    @endif
</div>

@endsection
