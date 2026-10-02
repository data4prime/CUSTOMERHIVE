<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title ?? 'Qlik' }}</title>
  <style>
    html, body { margin: 0; height: 100%; }
    .qi_iframe { display: block; width: 100%; height: 100%; border: 0; }
    .qi_login_error, .qs_error { padding: 8px 12px; color: #b02a37; font-family: var(--ch-font); font-size: 14px; }
  </style>
</head>
<body>
@if(!empty($error))
  <div class="qs_error" role="alert">{{ $error }}</div>
@else
  {{-- Widget Qlik in modalita' foglio: il login JS (lo stesso degli item) imposta il src dell'iframe --}}
  <iframe id="qlik_frame" class="qi_iframe" data-src="{{ $item_url }}" src="" title="{{ $title }}"></iframe>
  <script>
    const TENANT = {!! json_encode((string) $tenant) !!};
    const PREFIX = {!! json_encode((string) $prefix) !!};
    const WEBINTEGRATIONID = {!! json_encode((string) $web_int_id) !!};
    const APPID = '##APP##';
    const JWTTOKEN = {!! json_encode((string) $token) !!};
    const QLIK_I18N = {!! json_encode(['login_failed' => trans('crudbooster.qlik_login_failed')]) !!};
  </script>
  <script src="{{ asset($js_login) }}"></script>
@endif
</body>
</html>
