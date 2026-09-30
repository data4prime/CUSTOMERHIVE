<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{ isset($page_title)?Session::get('appname').': '.strip_tags($page_title):"Admin Area" }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name='generator' content='CustomerHive' />
    <meta name='robots' content='noindex,nofollow' />
    <link rel="shortcut icon"
        href="{{ CRUDBooster::getSetting('favicon')?asset(CRUDBooster::getSetting('favicon')):asset('images/favicon.png') }}">
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <!-- Bootstrap 3.4.1 -->
    <!--<link href="{{ asset('/vendor/crudbooster/assets/adminlte/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet"
        type="text/css" />-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Font Awesome Icons -->
    <link href="{{asset('vendor/crudbooster/assets/adminlte/font-awesome/css')}}/font-awesome.min.css" rel="stylesheet"
        type="text/css" />
    <link href="{{asset('css/icons-lucide.css')}}" rel="stylesheet" type="text/css" />
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

    @php
    $main_css = asset("vendor/crudbooster/assets/css/main.css").'?r='.time();
    $custom_css = asset('css/custom.css').'?r='.time();
    $theme_css = asset('css/theme.css').'?r='.time();
    @endphp

    <link rel='stylesheet' href='{{ $main_css}}' type="text/css" />
    <link rel='stylesheet' href="{{$custom_css}}" type="text/css" />
    <!-- Revamp UI/UX (Fase 0+1): token di design + font self-hosted, vedi docs/refactoring -->
    <link rel='stylesheet' href="{{$theme_css}}" type="text/css" />
    <link rel='stylesheet' href="{{isset($style_css) ? $style_css : ''}}" type="text/css" />


    @if(isset($load_css))
    @foreach($load_css as $css)
    <link href="{{$css}}" rel="stylesheet" type="text/css" />
    @endforeach
    @endif

    <style type="text/css">
        .dropdown-menu-action {
            left: -130%;
        }

        .btn-group-action .btn-action {
            cursor: default
        }

        #box-header mb-3-module {
            box-shadow: 10px 10px 10px #dddddd;
        }

        .sub-module-tab li {
            background: #F9F9F9;
            cursor: pointer;
        }

        .sub-module-tab li.active {
            background: #ffffff;
            box-shadow: 0px -5px 10px #cccccc
        }

        .nav-tabs>li.active>a,
        .nav-tabs>li.active>a:focus,
        .nav-tabs>li.active>a:hover {
            border: none;
        }

        .nav-tabs>li>a {
            border: none;
        }

        .breadcrumb {
            margin: 0 0 0 0;
            padding: 0 0 0 0;
        }

        .mb-3 row>label:first-child {
            display: block
        }

    </style>



    @stack('head')


</head>
@if(isset($target_layout) && $target_layout == 1)
@yield('content')
@else

@php
    // Revamp UI/UX: cms_privileges.theme_color (select chiusa a 12 skin
    // AdminLTE storiche, vedi privileges.blade.php) non pilota piu' una
    // classe skin-* (il nuovo design ha un solo linguaggio visivo), ma
    // resta usato per un accento colore per ruolo (vedi theme.css) -
    // deciso con l'utente durante il piano del revamp. Le varianti
    // "-light" condividono lo stesso accento della base.
    $role_accent_map = [
        'skin-blue' => 'blue', 'skin-blue-light' => 'blue',
        'skin-yellow' => 'yellow', 'skin-yellow-light' => 'yellow',
        'skin-green' => 'green', 'skin-green-light' => 'green',
        'skin-purple' => 'purple', 'skin-purple-light' => 'purple',
        'skin-red' => 'red', 'skin-red-light' => 'red',
        'skin-black' => 'black', 'skin-black-light' => 'black',
    ];
    $role_accent = $role_accent_map[Session::get('theme_color')] ?? null;
@endphp
<body style="font-size: 14px;"
    @if($role_accent) data-role-accent="{{ $role_accent }}" @endif
    class="ch-shell @php echo config('crudbooster.ADMIN_LAYOUT'); @endphp {{isset($sidebar_mode) ?: ''}} {{ !empty($ch_embed) ? 'ch-embed' : '' }}">
    <div id='app' class="wrapper">

        {{-- Modalita' embed (?embed=1, vedi CBBackend e docs/refactoring/162-*):
             la pagina vive dentro l'iframe del widget "Modulo incorporato",
             quindi niente guscio dell'app (header, chat, licenza, sidebar,
             footer) - restano titolo e pulsanti d'azione del modulo. --}}
        @if(empty($ch_embed))
        <!-- Header -->
        @include('crudbooster::header')

        <!-- AI Chat -->
        @include('crudbooster::chat3')

        <!-- License -->
        @include('crudbooster::license_modal')

        <!-- Sidebar -->
        @include('crudbooster::sidebar')
        @endif

        


        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">

            <section class="content-header">
                @php
                $module = CRUDBooster::getCurrentModule();
                @endphp
                @if(isset($module))

                <h1>



                    <i id='title_icon' class='{!! isset($page_icon) ? $page_icon : $module->icon !!}'></i> 
                    {!! isset($page_title) ? $page_title : '' !!} 
                    @if(isset($help)) 
                        <a href="{{ $help }}" target="_blank"><i id="help_icon" class="fa fa-question-circle" title="{{ $help }}"></i></a>
                         
                    @endif &nbsp;&nbsp;

                    <!-- START BUTTON -->

                    @if(CRUDBooster::getCurrentMethod() == 'getIndex')
                    @if($button_show)
                    <!--<a href="{{ CRUDBooster::mainpath().'?'.http_build_query(Request::all()) }}" id='btn_show_data'
                        class="btn btn-sm btn-primary" title="{{trans('crudbooster.action_show_data')}}">
                        <i class="fa fa-table"></i> {{trans('crudbooster.action_show_data')}}
                    </a>
                    -->
                    @endif

                    @if($button_add && CRUDBooster::isCreate())
                    <a href="{{ CRUDBooster::mainpath('add').'?return_url='.urlencode(Request::fullUrl()).'&parent_id='.g('parent_id').'&parent_field='.$parent_field }}"
                        id='btn_add_new_data' class="btn btn-sm btn-success"
                        title="{{trans('crudbooster.action_add_data')}}">
                        <i class="fa fa-plus-circle"></i> {{trans('crudbooster.action_add_data')}}
                    </a>
                    @endif
                    @endif


                    @if($button_export && CRUDBooster::getCurrentMethod() == 'getIndex')
                    <a href="javascript:void(0)" id='btn_export_data' data-url-parameter='{{$build_query}}'
                        title="{{trans('crudbooster.button_export')}}" class="btn btn-sm btn-primary btn-export-data">
                        <i class="fa fa-upload"></i> {{trans("crudbooster.button_export")}}
                    </a>
                    @endif

                    @if($button_import && CRUDBooster::getCurrentMethod() == 'getIndex')
                    <a href="{{ CRUDBooster::mainpath('import-data') }}" id='btn_import_data'
                        data-url-parameter='{{$build_query}}' title="{{trans('crudbooster.button_import')}}"
                        class="btn btn-sm btn-primary btn-import-data">
                        <i class="fa fa-download"></i> {{trans("crudbooster.button_import")}}
                    </a>
                    @endif

                    <!--ADD ACTIon-->
                    @if(!empty($index_button))

                    @foreach($index_button as $ib)
                    <!--<a href='{{$ib["url"]}}' id='{{str_slug($ib["label"])}}' class="btn 
                    {{isset($ib['color'])?'btn-'.$ib['color']:'btn-primary'}} btn-sm" @if(isset($ib['onClick']))
                        onClick="return {{$ib['onClick']}}" @endif @if(isset($ib['onMouseOver']))
                        onMouseOver="return {{$ib["onMouseOver"]}}" @endif @if(isset($ib['onMouseOut']))
                        onMouseOut='return {{$ib["onMouseOut"]}}' @endif @if(isset($ib['onKeyDown']))
                        onKeyDown='return {{$ib["onKeyDown"]}}' @endif @if(isset($ib['onLoad']))
                        onLoad='return {{$ib["onLoad"]}}' @endif>
                        <i class='{{$ib["icon"]}}'></i> {{$ib["label"]}}
                    </a>
<a href='{{$ib["url"]}}' id='{{str_slug($ib["label"])}}' class="btn 
                    {{isset($ib['color'])?'btn-'.$ib['color']:'btn-primary'}} btn-sm" 
@if(isset($ib['onClick'])) onClick="return {{$ib['onClick']}}" @endif 
@if(isset($ib['onMouseOver'])) onMouseOver="return {{$ib["onMouseOver"]}}" @endif 
@if(isset($ib['onMouseOut'])) onMouseOut='return {{$ib["onMouseOut"]}}' @endif 
@if(isset($ib['onKeyDown'])) onKeyDown='return {{$ib["onKeyDown"]}}' @endif 
@if(isset($ib['onLoad'])) onLoad='return {{$ib["onLoad"]}}' @endif
>
-->
                    @php

                    $onclick = isset($ib['onClick']) ? $ib['onClick'] : '';
                    $onmouseover = isset($ib['onMouseOver']) ? $ib['onMouseOver'] : '';
                    $onmouseout = isset($ib['onMouseOut']) ? $ib['onMouseOut'] : '';
                    $onkeydown = isset($ib['onKeyDown']) ? $ib['onKeyDown'] : '';
                    $onload = isset($ib['onLoad']) ? $ib['onLoad'] : '';

                    @endphp
                    <a href='{{$ib["url"]}}' id='{{str_slug($ib["label"])}}' class="btn 
                    {{isset($ib['color'])?'btn-'.$ib['color']:'btn-primary'}} btn-sm" onClick="{{$onclick}}"
                        onMouseOver="{{$onmouseover}}" onMouseOut="{{$onmouseout}}" onKeyDown="{{$onkeydown}}"
                        onLoad="{{$onload}}">



                        <i class='{{$ib["icon"]}}'></i> {{$ib["label"]}}
                    </a>
                    @endforeach
                    @endif
                    <!-- END BUTTON -->
                </h1>


                <ol class="breadcrumb">
                    <li><a href="{{CRUDBooster::adminPath()}}"><i class="fa fa-dashboard"></i> {{
                            trans('crudbooster.home') }}</a></li>
                    <li class="active">{{isset($module->name) ? $module->name :$module }}</li>
                </ol>
                @else
                <h1>{{Session::get('appname')}}
                    <small> {{ trans('crudbooster.text_dashboard') }} </small>
                </h1>
                @endif
            </section>


            <!-- Main content -->
            <section id='content_section' class="content">

                @if(@$alerts || Session::get('message') != '')
                @php
                /*
                 * Icona per tipo di toast - stesso set usato altrove nel
                 * revamp (badge Si/No, popup filtro/licenza). "primary"
                 * mappato come "info": stesso trattamento testuale che
                 * avevano gia' in crudbooster.alert_primary/alert_info.
                 */
                $ch_toast_icon = [
                    'success' => 'fa-check-circle',
                    'danger' => 'fa-times-circle',
                    'warning' => 'fa-exclamation-triangle',
                    'info' => 'fa-info-circle',
                    'primary' => 'fa-info-circle',
                ];
                @endphp
                <div class="ch-toast-container" aria-live="polite" aria-atomic="true">
                    @if(@$alerts)
                    @foreach(@$alerts as $alert)
                    <div class="toast ch-toast ch-toast-{{ $alert['type'] }}" role="alert" aria-live="assertive"
                        aria-atomic="true" data-bs-delay="6000">
                        <i class="fa {{ $ch_toast_icon[$alert['type']] ?? 'fa-info-circle' }} ch-toast-icon"></i>
                        <div class="ch-toast-body">{!! $alert['message'] !!}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    @endforeach
                    @endif

                    @if (Session::get('message') != '')
                    <div class="toast ch-toast ch-toast-{{ Session::get('message_type') }}" role="alert"
                        aria-live="assertive" aria-atomic="true" data-bs-delay="6000">
                        <i class="fa {{ $ch_toast_icon[Session::get('message_type')] ?? 'fa-info-circle' }} ch-toast-icon"></i>
                        <div class="ch-toast-body">
                            <strong>{{ trans('crudbooster.alert_'.Session::get('message_type')) }}</strong>
                            {!! Session::get('message') !!}
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    @endif
                </div>
                @endif

                <!-- Your Page Content Here -->
                @yield('content')
            </section><!-- /.content -->
        </div><!-- /.content-wrapper -->

        <!-- Footer -->
        @if(empty($ch_embed))
        @include('crudbooster::footer')
        @endif

    </div><!-- ./wrapper -->
    @endif

    @include('crudbooster::admin_template_plugins')

    <!-- load js -->
    @isset($load_js)
    @foreach($load_js as $js)
    <script src="{{$js}}"></script>
    @endforeach
    @endisset
    <script type="text/javascript">

        var site_url = "{{ url('/') }}";
    </script>

    @if(!empty($ch_embed))
    {{-- Modalita' embed: mantiene ?embed=1 su link, form e chiamate ajax
         dello stesso sito, cosi' navigare/salvare dentro il widget non
         porta mai alla pagina completa (i redirect lato server li copre
         CBBackend). target="_blank"/"_top" e link esterni restano invariati. --}}
    <script type="text/javascript">
        (function () {
            function addEmbed(u) {
                try {
                    var a = new URL(u, location.href);
                    if (a.origin !== location.origin) { return u; }
                    if (a.searchParams.get('embed') === '1') { return u; }
                    a.searchParams.set('embed', '1');
                    return a.pathname + a.search + a.hash;
                } catch (e) { return u; }
            }

            function decorateLink(a) {
                var h = a.getAttribute('href');
                if (!h || h.charAt(0) === '#' || /^(javascript|mailto|tel|data):/i.test(h)) { return; }
                if (a.target && a.target !== '_self') { return; }
                a.setAttribute('href', addEmbed(h));
            }

            function decorateForm(f) {
                var method = (f.getAttribute('method') || 'get').toLowerCase();
                if (method === 'get') {
                    if (!f.querySelector('input[name="embed"]')) {
                        var i = document.createElement('input');
                        i.type = 'hidden'; i.name = 'embed'; i.value = '1';
                        f.appendChild(i);
                    }
                } else {
                    f.setAttribute('action', addEmbed(f.getAttribute('action') || location.href));
                }
            }

            function decorateAll() {
                document.querySelectorAll('a[href]').forEach(decorateLink);
                document.querySelectorAll('form').forEach(decorateForm);
            }

            document.addEventListener('DOMContentLoaded', decorateAll);

            // Contenuti aggiunti dopo il caricamento (ajax, modali): al click/submit.
            document.addEventListener('click', function (e) {
                var a = e.target.closest ? e.target.closest('a[href]') : null;
                if (a) { decorateLink(a); }
            }, true);
            document.addEventListener('submit', function (e) {
                if (e.target && e.target.tagName === 'FORM') { decorateForm(e.target); }
            }, true);

            if (window.jQuery) {
                jQuery.ajaxPrefilter(function (options) {
                    if (options.url) { options.url = addEmbed(options.url); }
                });
                // Sessione scaduta durante una chiamata ajax: login a pagina intera.
                jQuery(document).ajaxSuccess(function (event, xhr, settings, data) {
                    if (data && data.break_frame && data.redirect_url) {
                        window.top.location.href = data.redirect_url;
                    }
                });
            }
        })();
    </script>
    @endif



    <script type="text/javascript">@php echo  $script_js @endphp</script>

    <script type="text/javascript">
        // Toast di notifica (torna alla lista/salvataggio/errore ecc.) -
        // autohide nativo di Bootstrap 5, niente da gestire a mano oltre
        // all'inizializzazione.
        document.querySelectorAll('.ch-toast').forEach(function (el) {
            new bootstrap.Toast(el).show();
        });
    </script>




    @stack('bottom')

    @if(isset($target_layout) && $target_layout == 2)
    <script type="text/javascript">
        $(document).ready(function () {
            //shrink sidebar
            $('.sidebar-toggle').click();
            //expand container
            $('#content_section').css('padding', '0px');
            $('#content_section').css('height', 'calc(100vh - 42px'));
            $('.qi_iframe_container').css('height', '100%');
            $('.qi_iframe').css('padding-bottom', '0px');
            $('.content-wrapper').css('min-height', '0px !important');
            //hide section header
            $('.content-header').css('display', 'none');
        })
    </script>
    @endif
    <script type="text/javascript">
        //sidebar #RAMA
        $(document).ready(function () {
            //collapsable navigation groups
            $('.my-collapse-sidebar').click(function () {
                var collapse_id = $(this).data('collapse-btn');
                var icon = $(this).children().first();
                $content = $('li[data-collapse="' + collapse_id + '"]');
                $content.slideToggle(500, function () {
                    //execute this after slideToggle is done
                    //change text of header based on visibility of content div
                    if ($content.is(":visible")) {
                        icon.removeClass('fa-plus');
                        icon.addClass('fa-minus');
                    }
                    else {
                        icon.removeClass('fa-minus');
                        icon.addClass('fa-plus');
                    }
                });
            })
        });
    </script>



    <!-- Optionally, you can add Slimscroll and FastClick plugins.
      Both of these plugins are recommended to enhance the
      user experience -->
</body>


</html>