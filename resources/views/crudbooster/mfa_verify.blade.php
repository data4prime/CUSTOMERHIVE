<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{trans("crudbooster.mfa_page_title_verify")}} : {{ isset($tenant->name) ? $tenant->name : '' }}</title>
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
                <div class="ch-auth-eyebrow">{{ strtoupper(trans('crudbooster.mfa_page_title_verify')) }}</div>
                <h2>{{ trans('crudbooster.mfa_page_title_verify') }}</h2>
                <p class="ch-auth-lede">
                    {{ $method === 'totp' ? trans('crudbooster.mfa_verify_lede_totp') : trans('crudbooster.mfa_verify_lede_email') }}
                </p>

                @if ( Session::get('message') != '' )
                <div class="ch-auth-alert ch-auth-alert-warning">{{ Session::get('message') }}</div>
                @endif

                <form action="{{ route('postMfaVerify') }}" method="post">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />

                    <div class="ch-auth-field">
                        <label>{{trans("crudbooster.mfa_label_code")}}</label>
                        <div class="ch-auth-input">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                            <input type="text" name='code' inputmode="numeric" autocomplete="one-time-code" required autofocus placeholder="000000" />
                        </div>
                    </div>

                    @if($method === 'totp')
                    <p class="ch-auth-help" style="margin-top:0;">{{trans("crudbooster.mfa_label_backup_code")}}</p>
                    @endif

                    <button type="submit" class="ch-auth-btn">
                        {{trans("crudbooster.mfa_button_verify")}}
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="13,6 19,12 13,18"/></svg>
                    </button>
                </form>

                @if($method === 'email')
                <form action="{{ route('postMfaResendEmailOtp') }}" method="post" style="margin-top:10px;">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                    <button type="submit" class="ch-auth-btn ch-auth-btn-secondary">{{trans("crudbooster.mfa_resend_email_code")}}</button>
                </form>
                @endif

                <p class="ch-auth-help">
                    <a href='{{route("getMfaRecovery")}}'>{{trans("crudbooster.mfa_link_lost_device")}}</a>
                </p>

                <p class="ch-auth-help">
                    <a href='{{route("getLogin")}}'>{{trans("crudbooster.mfa_link_back_to_login")}}</a>
                </p>
            </div>
        </div>

        <div class="ch-brand-footer">&copy; {{ date('Y') }} CustomerHive</div>
    </div>
</body>

</html>
