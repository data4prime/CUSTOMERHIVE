<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{trans("crudbooster.mfa_recovery_status_title")}} : {{ isset($tenant->name) ? $tenant->name : '' }}</title>
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
                <div class="ch-auth-eyebrow">{{ strtoupper(trans('crudbooster.mfa_recovery_status_title')) }}</div>
                <h2>{{ trans('crudbooster.mfa_recovery_status_title') }}</h2>

                @if($state === 'invalid')
                <div class="ch-auth-alert ch-auth-alert-warning">{{ trans('crudbooster.mfa_recovery_invalid') }}</div>

                @elseif($state === 'cancelled')
                <p class="ch-auth-lede">{{ trans('crudbooster.mfa_recovery_cancelled') }}</p>

                @elseif($state === 'completed')
                <p class="ch-auth-lede">{{ trans('crudbooster.mfa_recovery_completed') }}</p>

                @else {{-- pending --}}
                <p class="ch-auth-lede">
                    {{ trans('crudbooster.mfa_recovery_pending_message', ['time' => \Illuminate\Support\Carbon::parse($row->effective_at)->format('H:i')]) }}
                </p>

                <form action="{{ route('postMfaRecoveryCancel', ['token' => $token]) }}" method="post">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                    <button type="submit" class="ch-auth-btn">
                        {{ trans('crudbooster.mfa_recovery_cancel_button') }}
                    </button>
                </form>
                @endif

                <p class="ch-auth-help">
                    <a href='{{route("getLogin")}}'>{{trans("crudbooster.mfa_link_back_to_login")}}</a>
                </p>
            </div>
        </div>

        <div class="ch-brand-footer">&copy; {{ date('Y') }} CustomerHive</div>
    </div>
</body>

</html>
