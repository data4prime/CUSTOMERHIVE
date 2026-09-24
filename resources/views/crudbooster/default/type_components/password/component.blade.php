<div class='mb-3 row {{$header_group_class}} {{ ($errors->first($name))?"has-error":"" }}' id='form-group-{{$name}}'
    style="{!! @$form['style'] !!}">
    <label class='col-form-label col-sm-2'>{{$form['label']}}
        @if($required)
        <span class='text-danger' title='{!! trans("crudbooster.this_field_is_required") !!}'>*</span>
        @endif
    </label>

    <div class="{{$col_width?:'col-sm-10'}}">
        <input type='password' title="{{$form['label']}}" id="{{$name}}" {{$required}} {!!$placeholder!!} {{$readonly}}
            {{$disabled}} @php echo isset($validation['max'])?'maxlength="'.$validation['max'].'"':'' @endphp
            class=' form-control' name="{{$name}}" />
        <div class="text-danger">{!! $errors->first($name)?"<i class='fa fa-info-circle'></i> ".$errors->first($name):""
            !!}</div>
        <p class='help-block'>{{ @$form['help'] }}</p>

        @if(isset($validation['not_common_password']))
        <div id="{{$name}}-strength-hint" class="ch-password-strength" style="display:none;font-size:12px;margin-top:4px;"></div>
        @push('bottom')
        <script type="text/javascript">
            (function () {
                // Hint live per la password, in stile NIST 800-63B (vedi
                // app/Helpers/PasswordPolicy.php, stessa filosofia lato
                // server): niente regole di composizione, solo lunghezza
                // minima e "prevedibilita'" (sequenze/ripetizioni, password
                // comuni, nome/email dell'utente). E' solo un aiuto visivo:
                // la validazione che conta resta quella server-side su
                // submit, questa lista comuni e' volutamente ridotta.
                var minLength = {{ (int) ($validation['min'] ?? 12) }};
                var commonPasswords = ['password', 'password1', '123456', '123456789', 'qwerty', 'querty123', 'admin', 'admin123', 'welcome', 'welcome1', 'letmein', 'iloveyou', 'abc123', 'password123', 'changeme', '12345678', '111111', '123123'];
                var messages = {
                    tooShort: {!! json_encode(trans('crudbooster.password_strength_too_short')) !!},
                    common: {!! json_encode(trans('crudbooster.password_strength_common')) !!},
                    sequential: {!! json_encode(trans('crudbooster.password_strength_sequential')) !!},
                    context: {!! json_encode(trans('crudbooster.password_strength_context_name_email')) !!},
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
                    ['name', 'email'].forEach(function (fieldName) {
                        var el = document.getElementById(fieldName);
                        if (el && el.value) {
                            el.value.toLowerCase().split(/[\s@.]+/).forEach(function (t) {
                                if (t.length >= 4) tokens.push(t);
                            });
                        }
                    });
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

                var input = document.getElementById('{{$name}}');
                var hint = document.getElementById('{{$name}}-strength-hint');
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
                    hint.innerHTML = (result.ok ? '<i class="fa fa-check-circle"></i> ' : '<i class="fa fa-exclamation-circle"></i> ') + result.text;
                });
            })();
        </script>
        @endpush
        @endif
    </div>
</div>