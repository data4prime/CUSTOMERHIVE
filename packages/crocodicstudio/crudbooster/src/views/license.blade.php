<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>License</title>
    <meta name='generator' content='CustomerHive' />
    <meta name='robots' content='noindex,nofollow' />
    <link rel="shortcut icon"
        href="{{ CRUDBooster::getSetting('favicon')?asset(CRUDBooster::getSetting('favicon')):asset('vendor/crudbooster/assets/logo_crudbooster.png') }}">
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <!-- Bootstrap 3.3.2 -->
    <link href="{{asset('vendor/crudbooster/assets/adminlte/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet"
        type="text/css" />
    <!-- Font Awesome Icons -->
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet"
        type="text/css" />
    <!-- Theme style -->
    <link href="{{asset('vendor/crudbooster/assets/adminlte/dist/css/AdminLTE.min.css')}}" rel="stylesheet"
        type="text/css" />

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->

    <link rel='stylesheet' href='{{asset("vendor/crudbooster/assets/css/main.css")}}' />
    <style type="text/css">
        .lockscreen {
            background: @php echo CRUDBooster::getSetting("login_background_color")?:'#dddddd'@endphp 
            url('{{ CRUDBooster::getSetting("login_background_image")?asset(CRUDBooster::getSetting("login_background_image")):asset("/images/main-bg.jpg") }}');

            color: @php CRUDBooster::getSetting("login_font_color")?:'#ffffff'@endphp !important;
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
        }
    </style>

</head>

<body class="lockscreen">
    <!-- Automatic element centering -->
    <div class="lockscreen-wrapper">
        <div class="lockscreen-logo">
            <a href="{{url('/')}}">
                <img title=" {!! isset($appname) ? ($appname == 'CustomerHive' ? 'CustomerHive':$appname) : ''  !!}  "
                    src='{{ CRUDBooster::getSetting("logo")?asset(CRUDBooster::getSetting("logo")):asset("/images/customerhive_trasparente.png") }}'
                    style='max-width: 100%;max-height:170px' />
            </a>
        </div>

        @if (\Session::has('message'))
            <div class="alert alert-warning">
                <ul>
                    <li>{!! \Session::get('message') !!}</li>
                </ul>
            </div>
        @endif

        <!-- START LOCK SCREEN ITEM -->
        <div class="login-box-body">

            <!-- /.lockscreen-image -->

            <!-- lockscreen credentials (contains the form) -->
            <form  method='post'
                action="{{url(config('crudbooster.ADMIN_PATH').'/activate-license')}}">
                <input type="hidden" name="_token" value="{{ csrf_token() }}" />

                <div class="mb-3 row has-feedback form-group">
                    <input autocomplete='off' type="text" class="form-control" name='email' required
                        placeholder="Email" />
                </div>



                <div class="mb-3 row has-feedback form-group">
                    <input autocomplete='off' type="text" class="form-control" name='domain' required value="{{$tenant_domain_name}}"
                        placeholder="Domain" />
                </div>

<!--
                <div class="mb-3 row has-feedback">
                    <input autocomplete='off' type="text" class="form-control" name='mac_address' required value="{{$mac_address}}"
                        placeholder="MAC Address" />
                </div>
-->


<!--
                <div class="mb-3 row has-feedback">
                    <input autocomplete='off' type="text" class="form-control" name='path' value="{{$path}}" required
                        placeholder="Path" />
                </div>
-->


                <div class="mb-3 row has-feedback form-group">
                    <input autocomplete='off' type="number" class="form-control" name='clients_number' required
                        placeholder="Users Number" />
                </div> 
                <div class="mb-3 row has-feedback form-group">
                    <input autocomplete='off' type="number" class="form-control" name='tenants_number' required
                        placeholder="Tenants Number" />
                </div>


                <div  class='row'>
                    <div class='col-xs-12'>
                        <button type="submit" class="btn btn-primary btn-block btn-flat">
                            Activate</button>
                    </div>
                </div>

            </form><!-- /.lockscreen credentials -->

        </div><!-- /.lockscreen-item -->
        <div class="text-center">

        </div>

        <div class='lockscreen-footer text-center'>
            Copyright &copy; {{date("Y")}}<br>
            All rights reserved
        </div>
    </div><!-- /.center -->


    <!-- jQuery 2.2.3 -->
    <script src="{{asset('vendor/crudbooster/assets/adminlte/plugins/jQuery/jquery-2.2.3.min.js')}}"></script>
    <!-- Bootstrap 3.4.1 JS -->
    <script src="{{asset('vendor/crudbooster/assets/adminlte/bootstrap/js/bootstrap.min.js')}}"
        type="text/javascript"></script>
</body>

</html>