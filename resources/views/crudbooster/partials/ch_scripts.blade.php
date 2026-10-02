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
@if (!isset($ch_shell) || $ch_shell)
<script src="{{ asset('js/ch-shell.js') }}?v={{ @filemtime(public_path('js/ch-shell.js')) }}"></script>
@endif
