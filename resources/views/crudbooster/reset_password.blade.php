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
                            <input id="email" type="email" name='email' value="{{ old('email', $email) }}" required placeholder="nome.cognome@azienda.com" />
                        </div>
                    </div>

                    <div class="ch-auth-field">
                        <label>{{trans("crudbooster.label_new_password")}}</label>
                        <div class="ch-auth-input">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                            <input id="password" autocomplete='new-password' type="password" name='password' required minlength="12" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" />
                        </div>
                        <div id="password-strength-hint" style="display:none;font-size:12px;margin-top:4px;"></div>
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
                    {{trans("crudbooster.forgot_text_try_again")}}
                    <a href='{{route("getLogin")}}'>{{trans("crudbooster.click_here")}}</a>
                </p>
            </div>
        </div>

        <div class="ch-brand-footer">&copy; {{ date('Y') }} CustomerHive</div>
    </div>

    <script type="text/javascript">
        (function () {
            // Hint live in stile NIST 800-63B, stessa filosofia della
            // policy server-side in app/Helpers/PasswordPolicy.php (usata
            // da postResetPassword()): niente regole di composizione, solo
            // lunghezza minima e "prevedibilita'". Solo un aiuto visivo -
            // la validazione che conta resta quella server-side su submit.
            var minLength = 12;
            var commonPasswords = ['password', 'password1', '123456', '123456789', 'qwerty', 'querty123', 'admin', 'admin123', 'welcome', 'welcome1', 'letmein', 'iloveyou', 'abc123', 'password123', 'changeme', '12345678', '111111', '123123'];
            var messages = {
                tooShort: {!! json_encode(trans('crudbooster.password_strength_too_short')) !!},
                common: {!! json_encode(trans('crudbooster.password_strength_common')) !!},
                sequential: {!! json_encode(trans('crudbooster.password_strength_sequential')) !!},
                context: {!! json_encode(trans('crudbooster.password_strength_context_email')) !!},
                valid: {!! json_encode(trans('crudbooster.password_strength_valid')) !!}
            };

            function hasSequentialOrRepeatedChars(value, runLength) {
                var chars = value.toLowerCase();
                var repeatRun = 1, ascRun = 1, descRun = 1;
                for (var i = 1; i < chars.length; i++) {
                    var diff = chars.charCodeAt(i) - chars.charCodeAt(i - 1);
                    repeatRun = (diff === 0) ? repeatRun + 1 : 1;
                    ascRun = (diff === 1) ? ascRun + 1 : 1;
                    descRun = (diff === -1) ? descRun + 1 : 1;
                    if (Math.max(repeatRun, ascRun, descRun) >= runLength) {
                        return true;
                    }
                }
                return false;
            }

            function contextTokens() {
                var tokens = [];
                var email = document.getElementById('email');
                if (email && email.value) {
                    email.value.toLowerCase().split(/[\s@.]+/).forEach(function (t) {
                        if (t.length >= 4) tokens.push(t);
                    });
                }
                return tokens;
            }

            function evaluate(value) {
                if (!value) {
                    return null;
                }
                if (value.length < minLength) {
                    return { ok: false, text: messages.tooShort.replace(':min', minLength) };
                }
                if (commonPasswords.indexOf(value.toLowerCase()) !== -1) {
                    return { ok: false, text: messages.common };
                }
                if (hasSequentialOrRepeatedChars(value, 6)) {
                    return { ok: false, text: messages.sequential };
                }
                var tokens = contextTokens();
                for (var i = 0; i < tokens.length; i++) {
                    if (value.toLowerCase().indexOf(tokens[i]) !== -1) {
                        return { ok: false, text: messages.context };
                    }
                }
                return { ok: true, text: messages.valid };
            }

            var input = document.getElementById('password');
            var hint = document.getElementById('password-strength-hint');
            if (!input || !hint) {
                return;
            }

            input.addEventListener('input', function () {
                var result = evaluate(input.value);
                if (!result) {
                    hint.style.display = 'none';
                    return;
                }
                hint.style.display = 'block';
                hint.style.color = result.ok ? '#1a7f37' : '#c0392b';
                hint.textContent = result.text;
            });
        })();
    </script>
</body>

</html>
