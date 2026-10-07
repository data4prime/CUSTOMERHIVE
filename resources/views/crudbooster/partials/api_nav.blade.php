{{-- Schede della sezione Generatore API (documentazione / chiavi / editor).
     Parametro: $active = 'doc' | 'keys' | 'editor'. Sostituisce il menu che
     ogni vista ripeteva a mano con URL fissi. --}}
@push('head')
<link href="{{ asset('css/ch-api.css') }}?v={{ @filemtime(public_path('css/ch-api.css')) }}" rel="stylesheet" type="text/css" />
@endpush
<nav class="api-tabs">
    <a class="{{ $active === 'doc' ? 'active' : '' }}" href="{{ CRUDBooster::mainpath() }}">
        <i class="bi bi-file-earmark-text"></i> {{ trans('crudbooster.api_tab_endpoints') }}
    </a>
    <a class="{{ $active === 'keys' ? 'active' : '' }}" href="{{ CRUDBooster::mainpath('screet-key') }}">
        <i class="bi bi-key"></i> {{ trans('crudbooster.api_secret_key') }}
    </a>
    <a class="{{ $active === 'editor' ? 'active' : '' }}" href="{{ CRUDBooster::mainpath('generator') }}">
        <i class="bi bi-plus-square"></i> {{ trans('crudbooster.api_tab_new_endpoint') }}
    </a>
</nav>
