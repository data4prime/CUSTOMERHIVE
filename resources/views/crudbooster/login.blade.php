<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{trans("crudbooster.page_title_login")}} : {{ isset($tenant->name) ? $tenant->name : '' }}</title>
    <meta name='generator' content='CustomerHive' />
    <meta name='robots' content='noindex,nofollow' />
    <link rel="shortcut icon" href="{{ $favicon }}">
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>

    <!-- support rtl-->
    @if (in_array(App::getLocale(), ['ar', 'fa']))
    <link rel="stylesheet" href="//cdn.rawgit.com/morteza/bootstrap-rtl/v3.3.4/dist/css/bootstrap-rtl.min.css">
    <link href="{{ asset('vendor/crudbooster/assets/rtl.css')}}" rel="stylesheet" type="text/css" />
    @endif

    <link rel='stylesheet' href="{{ asset('css/theme.css').'?r='.time() }}" type="text/css" />
    {{--
        Su richiesta esplicita dell'utente (2026-09-03): il pannello brand
        resta sempre quello scuro del mockup, senza l'immagine/colore di
        sfondo personalizzabili per tenant (CRUDBooster::getBackgroundColor()/
        getBackgroundImage()/frontColor() - $background/$front_color restano
        calcolati dal controller ma non piu' usati qui). Il logo resta
        dinamico per tenant (vedi sotto).
    --}}
</head>

<body class="ch-auth">
    <div class="ch-auth-shell">
        <div class="ch-brand">
            <img src="{{ $logo }}" alt="{{ isset($tenant->name) ? $tenant->name : 'CustomerHive' }}"
                style="max-width:200px;max-height:56px;">
        </div>

        <div class="ch-auth-formpanel">
            <div class="ch-auth-formbox">
                <div class="ch-auth-eyebrow">{{ strtoupper(trans('crudbooster.button_sign_in')) }}</div>
                <h2>{{ trans('crudbooster.page_title_login') }}</h2>
                <p class="ch-auth-lede">{{ trans('crudbooster.login_message') }}</p>

                @if ( Session::get('message') != '' )
                <div class="ch-auth-alert ch-auth-alert-warning">{{ Session::get('message') }}</div>
                @endif

                @if(!empty(config('services.google')))
                <a href='{{route("redirect", "google")}}' class="ch-auth-btn ch-auth-btn-secondary" style="margin-bottom:18px;">
                    <i class='fa fa-google'></i> Google Login
                </a>
                <div style="text-align:center;font-size:12px;color:var(--ch-text-muted);margin-bottom:18px;">oppure</div>
                @endif

                <form autocomplete='off' action="{{ route('postLogin') }}" method="post">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />

                    <div class="ch-auth-field">
                        <label>Email</label>
                        <div class="ch-auth-input">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3,7 12,13 21,7"/></svg>
                            <input autocomplete='off' type="text" name='email' required placeholder="nome.cognome@azienda.com" />
                        </div>
                    </div>

                    <div class="ch-auth-field">
                        <label>Password</label>
                        <div class="ch-auth-input">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                            <input autocomplete='off' type="password" name='password' required placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" />
                        </div>
                    </div>

                    <div class="ch-auth-row">
                        <span></span>
                        <a href='{{route("getForgot")}}'>{{trans("crudbooster.text_forgot_password")}}</a>
                    </div>

                    <button type="submit" class="ch-auth-btn">
                        {{trans("crudbooster.button_sign_in")}}
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="13,6 19,12 13,18"/></svg>
                    </button>
                </form>
            </div>
        </div>

        <div class="ch-brand-footer">&copy; {{ date('Y') }} CustomerHive</div>
    </div>
</body>

</html>
