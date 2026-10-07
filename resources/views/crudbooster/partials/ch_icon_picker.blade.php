{{-- Selettore di icone (intervento 236): sostituisce la select a tendina con 2000+ voci.
     Parametri:
       $name    nome dell'input inviato (default "icon")
       $current valore salvato: "bi bi-house" (default) oppure, con $bare, solo "house"; '' se nessuna
       $prefix  prefisso delle classi (default "bi bi-")
       $bare    true = il valore inviato e' solo il nome (widget KPI)
       $icons   elenco dei nomi (senza prefisso)
     Logica: public/js/ch-icon-picker.js. Stile: ch-components.css (.ch-iconpick). --}}
@php
    $ip_name = $name ?? 'icon';
    $ip_current = (string) ($current ?? '');
    $ip_prefix = $prefix ?? 'bi bi-';
    $ip_bare = ! empty($bare);
    $ip_label = (! $ip_bare && $ip_current !== '' && strpos($ip_current, $ip_prefix) === 0) ? substr($ip_current, strlen($ip_prefix)) : $ip_current;
    $ip_class = $ip_current === '' ? '' : ($ip_bare ? $ip_prefix . $ip_current : $ip_current);
@endphp
<div class="ch-iconpick" data-ch-iconpick data-prefix="{{ $ip_prefix }}" @if($ip_bare) data-bare @endif>
    <input type="hidden" name="{{ $ip_name }}" value="{{ $ip_current }}" data-ip-value>
    <button type="button" class="ch-ip-btn" data-ip-open aria-haspopup="true" aria-expanded="false">
        <span class="ch-ip-cur {{ $ip_current === '' ? 'is-empty' : '' }}">@if($ip_class !== '')<i class="{{ $ip_class }}"></i>@endif</span>
        <span class="ch-ip-name {{ $ip_current === '' ? 'is-empty' : '' }}" data-empty="{{ trans('crudbooster.icon_select') }}">{{ $ip_current !== '' ? $ip_label : trans('crudbooster.icon_select') }}</span>
        <i class="bi bi-chevron-down ch-ip-chev"></i>
    </button>
    <div class="ch-ip-pop" hidden>
        <input type="search" class="form-control ch-ip-search" placeholder="{{ trans('crudbooster.icon_search') }}" autocomplete="off">
        <div class="ch-ip-grid" data-empty="{{ trans('crudbooster.icon_no_results') }}"></div>
        <div class="ch-ip-more" hidden data-tpl="{{ trans('crudbooster.icon_more') }}"></div>
        <button type="button" class="ch-ip-clear" data-ip-clear><i class="bi bi-x-circle"></i> {{ trans('crudbooster.icon_none') }}</button>
    </div>
</div>

@once
{{-- Stile caricato qui (e non da ch-components.css) perche' il partial finisce anche in pagine che non lo caricano. --}}
<link href="{{ asset('css/ch-icon-picker.css') }}?v={{ @filemtime(public_path('css/ch-icon-picker.css')) }}" rel="stylesheet" type="text/css" />
<script type="application/json" id="ch-ip-data">{!! json_encode(array_values($icons ?? [])) !!}</script>
@php
    // nome Bootstrap Icons => [nomi FontAwesome equivalenti], per la ricerca
    $ip_aliases = [];
    foreach (\App\Helpers\IconMap::map() as $faName => $biName) {
        $ip_aliases[$biName][] = $faName;
    }
@endphp
<script type="application/json" id="ch-ip-aliases">{!! json_encode($ip_aliases ?: new \stdClass) !!}</script>
{{-- Script inline e non @push('bottom'): il partial viene spesso reso a parte (view()->render()
     dentro un controller, come in list_icon) e in quel caso gli stack di Blade si perdono. --}}
<script src="{{ asset('js/ch-icon-picker.js') }}?v={{ @filemtime(public_path('js/ch-icon-picker.js')) }}"></script>
@endonce
