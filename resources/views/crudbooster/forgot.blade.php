<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{trans("crudbooster.page_title_forgot")}} : {{ isset($appname) ? $appname : ''}}</title>
    <meta name='generator' content='CustomerHive' />
    <meta name='robots' content='noindex,nofollow' />
    <link rel="shortcut icon"
        href="{{ CRUDBooster::getSetting('favicon')?asset(CRUDBooster::getSetting('favicon')):asset('vendor/crudbooster/assets/logo_crudbooster.png') }}">
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
                <div class="ch-auth-eyebrow">{{ strtoupper(trans('crudbooster.page_title_forgot')) }}</div>
                <h2>{{trans("crudbooster.page_title_forgot")}}</h2>
                <p class="ch-auth-lede">{{trans("crudbooster.forgot_message")}}</p>

                @if ( Session::get('message') != '' )
                <div class="ch-auth-alert ch-auth-alert-warning">{{ Session::get('message') }}</div>
                @endif

                <form action="{{ route('postForgot') }}" method="post">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />

                    <div class="ch-auth-field">
                        <label>Email</label>
                        <div class="ch-auth-input">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3,7 12,13 21,7"/></svg>
                            <input type="email" name='email' required placeholder="nome.cognome@azienda.com" />
                        </div>
                    </div>

                    <button type="submit" class="ch-auth-btn" style="margin-top:6px;">
                        {{trans("crudbooster.button_submit")}}
                    </button>
                </form>

                <p class="ch-auth-help">
                    {{trans("crudbooster.forgot_text_try_again")}}
                    <a href='{{route("getLogin")}}'>{{trans("crudbooster.click_here")}}</a>
                </p>
            </div>
        </div>

        <div class="ch-brand-footer">&copy; {{ date('Y') }} CustomerHive</div>
    </div>
</body>

</html>
