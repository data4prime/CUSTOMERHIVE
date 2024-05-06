<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <title>{{ ($page_title)?Session::get('appname').': '.strip_tags($page_title):"Public Area" }}</title>
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <meta name='generator' content='CustomerHive' />
  <meta name='robots' content='noindex,nofollow' />
  <link rel="shortcut icon"
    href="{{ CRUDBooster::getSetting('favicon')?asset(CRUDBooster::getSetting('favicon')):asset('vendor/crudbooster/assets/logo_crudbooster.png') }}">
  <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
  <!-- Bootstrap 3.4.1 -->
  <link href="{{ asset(" vendor/crudbooster/assets/adminlte/bootstrap/css/bootstrap.min.css") }}" rel="stylesheet"
    type="text/css" />
  <!-- Font Awesome Icons -->
  <link href="{{asset(" vendor/crudbooster/assets/adminlte/font-awesome/css")}}/font-awesome.min.css" rel="stylesheet"
    type="text/css" />
  <!-- Ionicons -->
  <link href="{{asset(" vendor/crudbooster/ionic/css/ionicons.min.css")}}" rel="stylesheet" type="text/css" />
  <!-- Theme style -->
  <link href="{{ asset(" vendor/crudbooster/assets/adminlte/dist/css/AdminLTE.min.css")}}" rel="stylesheet"
    type="text/css" />
  <link href="{{ asset(" vendor/crudbooster/assets/adminlte/dist/css/skins/_all-skins.min.css")}}" rel="stylesheet"
    type="text/css" />

  <!-- support rtl-->
  @if (in_array(App::getLocale(), ['ar', 'fa']))
  <link rel="stylesheet" href="//cdn.rawgit.com/morteza/bootstrap-rtl/v3.3.4/dist/css/bootstrap-rtl.min.css">
  <link href="{{ asset(" vendor/crudbooster/assets/rtl.css")}}" rel="stylesheet" type="text/css" />
  @endif

  <link rel='stylesheet' href='{{asset("vendor/crudbooster/assets/css/main.css").' ?r='.time()}}' />
  <link rel='stylesheet' href="{{asset(" css/custom.css").'?r='.time()}}" type="text/css"/>

    @stack(' head') </head>

<body
  class="@php echo (Session::get('theme_color'))?:'skin-blue'; echo ' '; echo config('crudbooster.ADMIN_LAYOUT'); @endphp {{($sidebar_mode)?:''}}">
  <div id='app' class="wrapper">

    <!-- Header -->

    <!-- Sidebar -->

    <!-- Content Wrapper. Contains page content -->
    <div class="public-wrapper">
      <section class="content-header">
        <div class="qi_iframe_container">
          <iframe class="qi_iframe" src="{{ $url }}"  ></iframe>
        </div>
      </section><!-- /.content -->
    </div><!-- /.content-wrapper -->

    <!-- Footer -->

  </div><!-- ./wrapper -->

</body>

</html>