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
  <meta content='width=device-width, initial-scale=1, viewport-fit=cover' name='viewport'>
  {{-- Bootstrap 5 + Bootstrap Icons + layout + tema, in locale (vedi partials/ch_head) --}}
  @include('crudbooster::partials.ch_head')
    @stack(' head') </head>

<body
 class="{{ Session::get('theme_color', 'skin-blue') }} {{ config('crudbooster.ADMIN_LAYOUT') }} {{ isset($sidebar_mode) ? $sidebar_mode : '' }}"
>
  <div id='content_section' class="">

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

  const TENANT = {!! json_encode((string) $tenant) !!};

  const PREFIX = {!! json_encode((string) $prefix) !!};

  const WEBINTEGRATIONID = {!! json_encode((string) $web_int_id) !!};
  const APPID = '##APP##';
  const JWTTOKEN = {!! json_encode((string) $token) !!};
  const QLIK_I18N = {!! json_encode(['login_failed' => trans('crudbooster.qlik_login_failed')]) !!};
</script>
<script src="@php echo asset($js_login) @endphp"></script>


<style>
  /*set iframe size*/
  .qi_iframe {
    width: {{ \App\Helpers\QlikHelper::safeCssSize($frame_width) }} !important;

    height: {{ \App\Helpers\QlikHelper::safeCssSize($frame_height) }} !important;
  }
</style>
