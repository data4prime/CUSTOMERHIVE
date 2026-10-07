{{-- Pulsanti del wizard come nel mockup: sotto la card, allineati a destra.
     "Indietro" (ghost) + "Avanti »" / "Salva modulo" (primario). Va dentro il <form> del passo.
     Parametri: $nav_back (url), $nav_back_ajax (true = navigazione senza ricarica, classe mg-nav;
     il passo 1 torna alla lista), $nav_next (testo del pulsante), $nav_next_name (opzionale). --}}
<div class="d-flex justify-content-end gap-2 pt-2 pb-3 mg-navbar">
    <a href="{{ $nav_back }}" class="btn btn-secondary{{ !empty($nav_back_ajax) ? ' mg-nav' : '' }}">@if(!empty($nav_back_ajax))&laquo; @endif{{ trans('crudbooster.button_back') }}</a>
    <input type="submit" @if(!empty($nav_next_name)) name="{{ $nav_next_name }}" @endif class="btn btn-primary" value="{{ $nav_next }}">
</div>
