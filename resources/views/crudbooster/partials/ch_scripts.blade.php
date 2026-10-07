{{-- Script di base comuni (in locale): jQuery, [jQuery UI], Bootstrap 5 (bundle
     con Popper), comportamento del guscio.
     Parametri opzionali: $ch_shell = false per le pagine senza sidebar/header
     (login, popup, 404...); $ch_jqui = true per includere jQuery UI PRIMA di
     Bootstrap (cosi' i plugin jQuery di Bootstrap, es. tooltip, restano
     quelli di Bootstrap e non quelli di jQuery UI). --}}
<script src="{{ asset('vendor/jquery/jquery-3.7.1.min.js') }}"></script>
@if (!empty($ch_jqui))
<script src="{{ asset('vendor/libs/jquery-ui/jquery-ui.min.js') }}"></script>
@endif
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/crudbooster/assets/select2/dist/js/select2.full.js') }}"></script>
<script>window.CH_SELECT_NO_RESULTS = {!! json_encode(trans('crudbooster.select_no_results')) !!};</script>
<script src="{{ asset('js/ch-select.js') }}?v={{ @filemtime(public_path('js/ch-select.js')) }}"></script>
<script src="{{ asset('js/ch-inputs.js') }}?v={{ @filemtime(public_path('js/ch-inputs.js')) }}"></script>
<script>window.CH_LOCALE = {!! json_encode(str_replace('_', '-', App::getLocale())) !!}; window.CH_I18N = {!! json_encode(['today' => trans('crudbooster.picker_today'), 'clear' => trans('crudbooster.picker_clear'), 'time' => trans('crudbooster.picker_time'), 'file_none' => trans('crudbooster.file_none_selected'), 'choose_file' => trans('crudbooster.chose_an_file'), 'choose_image' => trans('crudbooster.chose_an_image')]) !!};</script>
<script src="{{ asset('js/ch-datetime.js') }}?v={{ @filemtime(public_path('js/ch-datetime.js')) }}"></script>
@if (!isset($ch_shell) || $ch_shell)
<script src="{{ asset('js/ch-shell.js') }}?v={{ @filemtime(public_path('js/ch-shell.js')) }}"></script>
@endif
