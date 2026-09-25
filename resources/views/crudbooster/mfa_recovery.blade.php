<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{trans("crudbooster.mfa_page_title_recovery")}} : {{ isset($tenant->name) ? $tenant->name : '' }}</title>
    <meta name='generator' content='CustomerHive' />
    <meta name='robots' content='noindex,nofollow' />
    <link rel="shortcut icon" href="{{ $favicon }}">
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>

    <link rel='stylesheet' href="{{ asset('css/theme.css').'?r='.time() }}" type="text/css" />
</head>

<body class="ch-auth">
    <div class="ch-auth-shell">
        <div class="ch-brand">
            <img src="{{ $logo }}" alt="{{ isset($tenant->name) ? $tenant->name : 'CustomerHive' }}"
                style="max-width:200px;max-height:56px;">
        </div>

        <div class="ch-auth-formpanel">
            <div class="ch-auth-formbox">
                <div class="ch-auth-eyebrow">{{ strtoupper(trans('crudbooster.mfa_page_title_recovery')) }}</div>
                <h2>{{ trans('crudbooster.mfa_page_title_recovery') }}</h2>
                <p class="ch-auth-lede">{{ trans('crudbooster.mfa_recovery_lede') }}</p>

                @if ( Session::get('message') != '' )
                <div class="ch-auth-alert ch-auth-alert-warning">{{ Session::get('message') }}</div>
                @endif

                <form action="{{ route('postMfaRecovery') }}" method="post">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />

                    <div class="ch-auth-field">
                        <label>Email</label>
                        <div class="ch-auth-input">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3,7 12,13 21,7"/></svg>
                            <input type="email" name='email' required placeholder="nome.cognome@azienda.com" />
                        </div>
                    </div>

                    <button type="submit" class="ch-auth-btn">
                        {{trans("crudbooster.mfa_button_send_recovery")}}
                    </button>
                </form>

                <p class="ch-auth-help">
                    <a href='{{route("getLogin")}}'>{{trans("crudbooster.mfa_link_back_to_login")}}</a>
                </p>
            </div>
        </div>

        <div class="ch-brand-footer">&copy; {{ date('Y') }} CustomerHive</div>
    </div>
</body>

</html>
