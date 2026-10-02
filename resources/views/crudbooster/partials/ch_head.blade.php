{{-- Fogli di stile comuni a tutte le pagine dell'admin (layout principale e
     pagine standalone): Bootstrap 5 + Bootstrap Icons + layout + tema, tutti
     serviti in locale (nessun CDN: funziona anche on-premise senza Internet).
     Ordine importante: libreria -> compatibilita'/layout -> tema (token --ch-*).
     Parametro opzionale $ch_theme = false per saltare theme.css. --}}
@php
    $ch_rtl = in_array(App::getLocale(), ['ar', 'fa']);
    $ch_v = function ($path) {
        $f = public_path($path);
        return asset($path) . (is_file($f) ? '?v=' . filemtime($f) : '');
    };
@endphp
<link href="{{ $ch_v($ch_rtl ? 'vendor/bootstrap/css/bootstrap.rtl.min.css' : 'vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ $ch_v('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ $ch_v('css/ch-icons-compat.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ $ch_v('css/ch-layout.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ $ch_v('css/ch-compat.css') }}" rel="stylesheet" type="text/css" />
@if ($ch_rtl)
<link href="{{ $ch_v('vendor/crudbooster/assets/rtl.css') }}" rel="stylesheet" type="text/css" />
@endif
<link href="{{ $ch_v('vendor/crudbooster/assets/css/main.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ $ch_v('css/custom.css') }}" rel="stylesheet" type="text/css" />
@if (!isset($ch_theme) || $ch_theme)
<link href="{{ $ch_v('css/theme.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ $ch_v('css/ch-components.css') }}" rel="stylesheet" type="text/css" />
@endif
