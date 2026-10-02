<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>License</title>
    <meta name='generator' content='CustomerHive' />
    <meta name='robots' content='noindex,nofollow' />
    <link rel="shortcut icon"
        href="{{ CRUDBooster::getSetting('favicon')?asset(CRUDBooster::getSetting('favicon')):asset('vendor/crudbooster/assets/logo_crudbooster.png') }}">
    <meta content='width=device-width, initial-scale=1, viewport-fit=cover' name='viewport'>
    @include('crudbooster::partials.ch_icons')
    <link rel='stylesheet' href="{{ asset('css/theme.css').'?v='.@filemtime(public_path('css/theme.css')) }}" type="text/css" />
    {{-- Stesso linguaggio visivo delle pagine di accesso (classi ch-auth-*, vedi login.blade.php). --}}
</head>

<body class="ch-auth">
    <div class="ch-auth-shell">
        <div class="ch-brand">
            <a href="{{url('/')}}">
                <img title=" {!! isset($appname) ? ($appname == 'CustomerHive' ? 'CustomerHive':$appname) : ''  !!}  "
                    src='{{ CRUDBooster::getSetting("logo")?asset(CRUDBooster::getSetting("logo")):asset("/images/customerhive_trasparente.png") }}'
                    style='max-width:200px;max-height:56px;' />
            </a>
        </div>

        <div class="ch-auth-formpanel">
            <div class="ch-auth-formbox">
                <div class="ch-auth-eyebrow">LICENSE</div>
                <h2>License</h2>

                @if (\Session::has('message'))
                <div class="ch-auth-alert ch-auth-alert-warning">{!! \Session::get('message') !!}</div>
                @endif

                <form method='post' action="{{url(config('crudbooster.ADMIN_PATH').'/activate-license')}}">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />

                    <div class="ch-auth-field">
                        <div class="ch-auth-input">
                            <input autocomplete='off' type="text" name='email' required placeholder="Email" />
                        </div>
                    </div>

                    <div class="ch-auth-field">
                        <div class="ch-auth-input">
                            <input autocomplete='off' type="text" name='domain' required
                                value="{{$tenant_domain_name}}" placeholder="Domain" />
                        </div>
                    </div>

                    <div class="ch-auth-field">
                        <div class="ch-auth-input">
                            <input autocomplete='off' type="number" name='clients_number' required
                                placeholder="Users Number" />
                        </div>
                    </div>

                    <div class="ch-auth-field">
                        <div class="ch-auth-input">
                            <input autocomplete='off' type="number" name='tenants_number' required
                                placeholder="Tenants Number" />
                        </div>
                    </div>

                    <button type="submit" class="ch-auth-btn">Activate</button>
                </form>

                <div style="text-align:center;margin-top:14px;">
                    <a href="#"
                        onclick="document.getElementById('existing-license-form').style.display='block'; this.parentNode.style.display='none'; return false;">
                        Ho già una licenza
                    </a>
                </div>

                <form id="existing-license-form" method='post' style="display:none; margin-top: 15px;"
                    action="{{url(config('crudbooster.ADMIN_PATH').'/activate-existing-license')}}">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />

                    <div class="ch-auth-field">
                        <div class="ch-auth-input">
                            <input autocomplete='off' type="text" name='license_key' required
                                placeholder="Chiave di licenza" />
                        </div>
                    </div>

                    <button type="submit" class="ch-auth-btn ch-auth-btn-secondary">Attiva licenza esistente</button>
                </form>
            </div>
        </div>

        <div class="ch-brand-footer">Copyright &copy; {{date("Y")}} &middot; All rights reserved</div>
    </div>
</body>

</html>
