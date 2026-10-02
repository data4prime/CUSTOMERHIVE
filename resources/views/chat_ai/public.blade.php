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
  <meta content='width=device-width, initial-scale=1, viewport-fit=cover' name='viewport'>
  {{-- Bootstrap 5 + Bootstrap Icons + layout + tema, in locale (vedi partials/ch_head) --}}
  @include('crudbooster::partials.ch_head')
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