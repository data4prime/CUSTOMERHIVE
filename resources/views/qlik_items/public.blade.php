<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <title>{{ isset($page_title)?Session::get('appname').': '.strip_tags($page_title):"Public Area" }}</title>
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <meta name='generator' content='CustomerHive' />
  <meta name='robots' content='noindex,nofollow' />
  <link rel="shortcut icon"
    href="{{ CRUDBooster::getSetting('favicon')?asset(CRUDBooster::getSetting('favicon')):asset('vendor/crudbooster/assets/logo_crudbooster.png') }}">
  <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

  <!-- Font Awesome Icons -->
  <link href="{{asset('vendor/crudbooster/assets/adminlte/font-awesome/css')}}/font-awesome.min.css" rel="stylesheet"
    type="text/css" />
  <!-- Ionicons -->
  <link href="{{asset('vendor/crudbooster/ionic/css/ionicons.min.css')}}" rel="stylesheet" type="text/css" />
  <!-- Theme style -->
  <link href="{{ asset('vendor/crudbooster/assets/adminlte/dist/css/AdminLTE.min.css')}}" rel="stylesheet"
    type="text/css" />
  <link href="{{ asset('vendor/crudbooster/assets/adminlte/dist/css/skins/_all-skins.min.css')}}" rel="stylesheet"
    type="text/css" />

  <!-- support rtl-->
  @if (in_array(App::getLocale(), ['ar', 'fa']))
  <link rel="stylesheet" href="//cdn.rawgit.com/morteza/bootstrap-rtl/v3.3.4/dist/css/bootstrap-rtl.min.css">
  <link href="{{ asset('vendor/crudbooster/assets/rtl.css')}}" rel="stylesheet" type="text/css" />
  @endif

<!--
  <link rel='stylesheet' href='{{asset("vendor/crudbooster/assets/css/main.css").' ?r='.time()}}' />
  <link rel='stylesheet' href="{{asset("css/custom.css").'?r='.time()}}" type="text/css"/>
-->

    @stack(' head') </head>

<body
 class="{{ Session::get('theme_color', 'skin-blue') }} {{ config('crudbooster.ADMIN_LAYOUT') }} {{ isset($sidebar_mode) ? $sidebar_mode : '' }}"
>
  <div id='app' class="">

    <!-- Header -->

    <!-- Sidebar -->

    <!-- Content Wrapper. Contains page content -->
    <!--<div class="public-wrapper">
      <section class="content-header">
        <div class="qi_iframe_container">
          <iframe class="qi_iframe" src="{{ $url }}"  ></iframe>
        </div>
      </section>
    </div>-->

@php
$debug_url = '';
if ($debug == 'Active') {
$debug_url = $item_url;
}
@endphp
@if(isset($target_layout) && $target_layout == 2)
<!-- fill content settings -->

<div class="card qi_iframe_container">
  <div class="card-header">
    <h4 class="qi_subtitle">{{ $subtitle }}</h4>
    <a href="{{$debug_url}}" target="_blank">{{$debug_url}}</a>
  </div>
  <div class="card-body">
    <iframe class="qi_iframe" data-src="{{$item_url}}" src=""  style="border:none;"></iframe>
  </div>
  
  
</div>

@else
<!-- default -->

<div class="card qi_box">
  <div class="card-header">
    <h4 class="qi_subtitle">{{ $subtitle }}</h4>
    <a href="{{$debug_url}}" target="_blank">{{$debug_url}}</a>
  </div>

<!--qi_iframe_container-->
  <div class="card-body">
    
    <iframe class="qi_iframe" data-src="{{$item_url}}" src=""  style="border:none;"></iframe>
  </div>
</div>

@endif

    <!-- Footer -->

  </div><!-- ./wrapper -->

</body>

</html>

<script>
  //const TENANT = '{{ $tenant }}/{{$prefix}}';

  const TENANT = '{{ $tenant }}';

  const PREFIX = '{{ $prefix }}';

  const WEBINTEGRATIONID = '{{ $web_int_id }}';
  const APPID = '##APP##';
  const JWTTOKEN = "{{ $token}}";
</script>
<script src="@php echo asset($js_login) @endphp"></script>

@push('head')
<style>
  /*set iframe size*/
  .qi_iframe {
    width: @php echo $frame_width @endphp !important;

    height: @php echo $frame_height @endphp !important;
  }
</style>
@endpush