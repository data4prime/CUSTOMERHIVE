<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{trans("crudbooster.page_title_reset_password")}} : {{ isset($appname) ? $appname : ''}}</title>
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
                <div class="ch-auth-eyebrow">{{ strtoupper(trans('crudbooster.page_title_reset_password')) }}</div>
                <h2>{{trans("crudbooster.page_title_reset_password")}}</h2>
                <p class="ch-auth-lede">{{trans("crudbooster.reset_password_message")}}</p>

                @if ( Session::get('message') != '' )
                <div class="ch-auth-alert ch-auth-alert-warning">{{ Session::get('message') }}</div>
                @endif

                <form action="{{ route('postResetPassword') }}" method="post">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                    <input type="hidden" name="token" value="{{ $token }}" />

                    <div class="ch-auth-field">
                        <label>Email</label>
                        <div class="ch-auth-input">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3,7 12,13 21,7"/></svg>
                            <input type="email" name='email' value="{{ old('email', $email) }}" required placeholder="nome.cognome@azienda.com" />
                        </div>
                    </div>

                    <div class="ch-auth-field">
                        <label>{{trans("crudbooster.label_new_password")}}</label>
                        <div class="ch-auth-input">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                            <input autocomplete='new-password' type="password" name='password' required minlength="12" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" />
                        </div>
                    </div>

                    <div class="ch-auth-field">
                        <label>{{trans("crudbooster.label_new_password_confirmation")}}</label>
                        <div class="ch-auth-input">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                            <input autocomplete='new-password' type="password" name='password_confirmation' required minlength="12" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" />
                        </div>
                    </div>

                    <p class="ch-auth-help" style="margin-top:0;">{{trans("crudbooster.reset_password_policy_hint")}}</p>

                    <button type="submit" class="ch-auth-btn" style="margin-top:6px;">
                        {{trans("crudbooster.button_submit")}}
                    </button>
                </form>

                <p class="ch-auth-help">
                    <a href='{{route("getLogin")}}'>{{trans("crudbooster.click_here")}}</a>
                </p>
            </div>
        </div>

        <div class="ch-brand-footer">&copy; {{ date('Y') }} CustomerHive</div>
    </div>
</body>

</html>
