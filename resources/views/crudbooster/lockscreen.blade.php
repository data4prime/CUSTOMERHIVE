<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>LOCKSCREEN</title>
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
            <img src='{{ CRUDBooster::getSetting("logo")?asset(CRUDBooster::getSetting("logo")):asset("/images/customerhive_trasparente.png") }}'
                alt="{{ isset($appname) ? $appname : 'CustomerHive' }}" style="max-width:200px;max-height:56px;">
        </div>

        <div class="ch-auth-formpanel">
            <div class="ch-auth-formbox" style="text-align:center;">
                <img class="ch-lockscreen-avatar"
                    src="{{ (Session::get('admin_photo'))?:asset('/images/user/user.png') }}" alt="user image" />

                <div class="ch-lockscreen-name">{{Session::get('admin_name')}}</div>

                <form method='post' action="{{url(config('crudbooster.ADMIN_PATH').'/unlock-screen')}}">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                    <div class="ch-auth-input" style="margin-bottom:16px;">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                        <input type="password" required name='password' placeholder="password" />
                    </div>
                    <button type="submit" class="ch-auth-btn">
                        {{trans("crudbooster.text_enter_the_password")}}
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="13,6 19,12 13,18"/></svg>
                    </button>
                </form>

                <div style="margin-top:18px;font-size:13px;">
                    <a href="{{route('getLogout')}}">{{trans('crudbooster.text_or_sign_in')}}</a>
                </div>
            </div>
        </div>

        <div class="ch-brand-footer">{{ trans('crudbooster.copyright') }} &copy; {{date("Y")}} &mdash; {{ trans('crudbooster.all_rights_reserved') }}</div>
    </div>
</body>

</html>
