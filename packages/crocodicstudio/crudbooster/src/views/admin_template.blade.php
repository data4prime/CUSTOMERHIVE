<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{ isset($page_title)?Session::get('appname').': '.strip_tags($page_title):"Admin Area" }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name='generator' content='CustomerHive' />
    <meta name='robots' content='noindex,nofollow' />
    <link rel="shortcut icon"
        href="{{ CRUDBooster::getSetting('favicon')?asset(CRUDBooster::getSetting('favicon')):asset('vendor/crudbooster/assets/logo_crudbooster.png') }}">
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <!-- Bootstrap 3.4.1 -->
    <link href="{{ asset('/vendor/crudbooster/assets/adminlte/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet"
        type="text/css" />
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

    @php
    $main_css = asset("vendor/crudbooster/assets/css/main.css").'?r='.time();
    $custom_css = asset('css/custom.css').'?r='.time();
    @endphp

    <link rel='stylesheet' href='{{ $main_css}}' type="text/css" />
    <link rel='stylesheet' href="{{$custom_css}}" type="text/css" />
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

        #box-header-module {
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

        .form-group>label:first-child {
            display: block
        }
    </style>

    @stack('head')
</head>
@if(isset($target_layout) && $target_layout == 1)
@yield('content')
@else

<body
    class="@php echo (Session::get('theme_color'))?:'skin-blue'; echo ' '; echo config('crudbooster.ADMIN_LAYOUT'); @endphp {{isset($sidebar_mode) ?: ''}}">
    <div id='app' class="wrapper">

        <!-- Header -->
        @include('crudbooster::header')

        <!-- Sidebar -->
        @include('crudbooster::sidebar')

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">

            <section class="content-header">
                @php
                $module = CRUDBooster::getCurrentModule();
                @endphp
                @if(isset($module))

                <h1>

                    <i id='title_icon' class='{!! isset($page_icon) ? $page_icon : $module->icon !!}'></i> {!!
                    isset($page_title)? $page_title : '' !!} @if(isset($help)) <i id="help_icon"
                        class="fa fa-question-circle" title="{{ $help }}"></i> @endif &nbsp;&nbsp;

                    <!-- START BUTTON -->

                    @if(CRUDBooster::getCurrentMethod() == 'getIndex')
                    @if($button_show)
                    <a href="{{ CRUDBooster::mainpath().'?'.http_build_query(Request::all()) }}" id='btn_show_data'
                        class="btn btn-sm btn-primary" title="{{trans('crudbooster.action_show_data')}}">
                        <i class="fa fa-table"></i> {{trans('crudbooster.action_show_data')}}
                    </a>
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
                        title='Export Data' class="btn btn-sm btn-primary btn-export-data">
                        <i class="fa fa-upload"></i> {{trans("crudbooster.button_export")}}
                    </a>
                    @endif

                    @if($button_import && CRUDBooster::getCurrentMethod() == 'getIndex')
                    <a href="{{ CRUDBooster::mainpath('import-data') }}" id='btn_import_data'
                        data-url-parameter='{{$build_query}}' title='Import Data'
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
                @if(@$alerts)
                @foreach(@$alerts as $alert)
                <div class='alert alert-{{$alert["type"]}} alert-dismissable'>
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    {!! $alert['message'] !!}
                </div>
                @endforeach
                @endif


                @if (Session::get('message')!='')
                <div class='alert alert-{{ Session::get("message_type") }}'>
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h4><i class="icon fa fa-info"></i> {{ trans("crudbooster.alert_".Session::get("message_type")) }}
                    </h4>
                    {!!Session::get('message')!!}
                </div>
                @endif



                <!-- Your Page Content Here -->
                @yield('content')
            </section><!-- /.content -->
        </div><!-- /.content-wrapper -->

        <!-- Footer -->
        @include('crudbooster::footer')

    </div><!-- ./wrapper -->
    @endif

    @include('crudbooster::admin_template_plugins')

    <!-- load js -->
    @isset($load_js):
    @foreach($load_js as $js)
    <script src="{{$js}}"></script>
    @endforeach
    @endisset
    <script type="text/javascript">

        var site_url = "{{ url('/') }}";
    </script>



    <script type="text/javascript">@php echo  $script_js @endphp</script>


    @stack('bottom')

    @if(isset($target_layout) && $target_layout == 2)
    <script type="text/javascript">
        $(document).ready(function () {
            //shrink sidebar
            $('.sidebar-toggle').click();
            //expand container
            $('#content_section').css('padding', '0px');
            $('#content_section').css('height', 'calc(100vh - 42px');
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