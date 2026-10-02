<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{ ($page_title)?Session::get('appname').': '.strip_tags($page_title):"Admin Area" }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name='generator' content='CustomerHive' />
    <meta name='robots' content='noindex,nofollow' />
    <link rel="shortcut icon"
        href="{{ CRUDBooster::getSetting('favicon')?asset(CRUDBooster::getSetting('favicon')):asset('vendor/crudbooster/assets/logo_crudbooster.png') }}">
    <meta content='width=device-width, initial-scale=1, viewport-fit=cover' name='viewport'>

    {{-- Bootstrap 5 + Bootstrap Icons + layout + tema e script di base, tutto in locale --}}
    @include('crudbooster::partials.ch_head')
    @include('crudbooster::partials.ch_scripts')

    <!--SWEET ALERT-->
    <script src="{{asset('vendor/crudbooster/assets/sweetalert/dist/sweetalert.min.js')}}"></script>
    <link rel="stylesheet" type="text/css" href="{{asset('vendor/crudbooster/assets/sweetalert/dist/sweetalert.css')}}">

    @stack('head')

    <style>

        /* Widget "Modulo incorporato" (panelcustom): nel builder legacy
           e' solo un'anteprima, non deve catturare il mouse (drag&drop
           dei widget, pulsanti modifica/elimina). */
        .ch-module-frame, .ch-module-open { pointer-events: none; }

        .skin-red .main-header .navbar .nav>li>a {
            text-decoration: none;
        }
        .card-header, .inner-box, .box-header mb-3, .btn-add-widget {
            text-decoration: none;
        }

    </style>
</head>

@php
    // Stesso accento di ruolo del layout principale (vedi admin_template.blade.php):
    // theme_color (skin-*) non pilota piu' una classe, ma l'attributo data-role-accent.
    $role_accent_map = [
        'skin-blue' => 'blue', 'skin-blue-light' => 'blue', 'skin-yellow' => 'yellow', 'skin-yellow-light' => 'yellow',
        'skin-green' => 'green', 'skin-green-light' => 'green', 'skin-purple' => 'purple', 'skin-purple-light' => 'purple',
        'skin-red' => 'red', 'skin-red-light' => 'red', 'skin-black' => 'black', 'skin-black-light' => 'black',
    ];
    $role_accent = $role_accent_map[Session::get('theme_color')] ?? null;
@endphp
<body class="ch-shell layout-top-nav fixed"@if($role_accent) data-role-accent="{{ $role_accent }}"@endif>
    <div id='app' class="wrapper">

        <header class="main-header">
            <nav class="navbar navbar-expand-lg navbar-light justify-content-between">
                <div class="container">
                    <div class="navbar-header">
                        <a href="{{url(config('crudbooster.ADMIN_PATH'))}}" title="{{Session::get('appname')}}"
                            class="navbar-brand">{{CRUDBooster::getSetting('appname')}}</a>
                        <!--<button style="background-color: transparent;" type="button" class="navbar-toggle collapsed" data-bs-toggle="collapse"
                            data-bs-target="#navbar-collapse">
                            <i class="bi bi-list"></i>
                        </button>-->
                    </div>

                    <!-- Collect the nav links, forms, and other content for toggling -->
                    <div class="collapse navbar-collapse float-start" id="navbar-collapse">
                        <ul class="nav navbar-nav">
                            <li><a class='btn-save-statistic' href="#" title='Auto Save Status'><i
                                        class='bi bi-floppy-fill'></i> Auto Save Ready</a></li>
                        </ul>
                    </div>
                    <!-- /.navbar-collapse -->
                    <!-- Navbar Right Menu -->
                    <div class="navbar-custom-menu">
                        <ul class="nav navbar-nav">
                            <li class="nav-item"><a href="#" class='btn-show-sidebar nav-link' data-ch-toggle="control-sidebar"><i
                                        class='bi bi-list'></i> Add Widget</a></li>

                            <li  class="nav-item"><a class='nav-link' href="{{CRUDBooster::mainpath()}}"><i class='bi bi-box-arrow-right'></i> Exit</a></li>
                        </ul>
                    </div>
                    <!-- /.navbar-custom-menu -->
                </div>
                <!-- /.container-fluid -->
            </nav>
        </header>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">


            <!-- Main content -->
            <section id='content_section' class="content">
                <!-- Your Page Content Here -->
                @yield('content')
            </section><!-- /.content -->
        </div><!-- /.content-wrapper -->

        <!-- The Right Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Content of the sidebar goes here -->
            <ul>
                <li class='connectedSortable' title='Drag To Main Area'>
                    <div id='btn-smallbox' class='button-widget-area'>
                        <a href="#" data-component='smallbox' class='btn-add-widget add-small-box'>
                            <img src='{{asset("vendor/crudbooster/assets/statistic_builder/smallbox.png")}}' />
                            <div class='title'>Small Box</div>
                        </a>
                    </div>
                </li>
                <li class='connectedSortable' title='Drag To Main Area'>
                    <div id='btn-table' class='button-widget-area'>
                        <a href="#" data-component='table' class='btn-add-widget add-table'>
                            <img src='{{asset("vendor/crudbooster/assets/statistic_builder/table.png")}}' />
                            <div class='title'>Table</div>
                        </a>
                    </div>
                </li>
                <li class='connectedSortable' title='Drag To Main Area'>
                    <div id='btn-chartarea' class='button-widget-area'>
                        <a href="#" data-component='chartarea' class='btn-add-widget add-chart-area'>
                            <img src='{{asset("vendor/crudbooster/assets/statistic_builder/chart_area.png")}}' />
                            <div class='title'>Chart Area</div>
                        </a>
                    </div>
                </li>
                <li class='connectedSortable' title='Drag To Main Area'>
                    <div id='btn-panelarea' class='button-widget-area'>
                        <a href="#" data-component='panelarea' class='btn-add-widget add-panel-area'>
                            <img src='{{asset("vendor/crudbooster/assets/statistic_builder/panel.png")}}' />
                            <div class='title'>Panel Area</div>
                        </a>
                    </div>
                </li>

                <li class='connectedSortable' title='Drag To Main Area'>
                    <div id='btn-panelcustom' class='button-widget-area'>
                        <a href="#" data-component='panelcustom' class='btn-add-widget add-panel-custom'>
                            <img src='{{asset("vendor/crudbooster/assets/statistic_builder/panel.png")}}' />
                            <div class='title'>Module Panel</div>
                        </a>
                    </div>
                </li>

                <li class='connectedSortable' title='Drag To Main Area'>
                    <div id='btn-chartarea' class='button-widget-area'>
                        <a href="#" data-component='chartline' class='btn-add-widget add-chart-line'>
                            <img src='{{asset("vendor/crudbooster/assets/statistic_builder/chart_line.png")}}' />
                            <div class='title'>Chart Line</div>
                        </a>
                    </div>
                </li>

                <li class='connectedSortable' title='Drag To Main Area'>
                    <div id='btn-chartarea' class='button-widget-area'>
                        <a href="#" data-component='chartbar' class='btn-add-widget add-chart-bar'>
                            <img src='{{asset("vendor/crudbooster/assets/statistic_builder/chart_bar.png")}}' />
                            <div class='title'>Chart Bar</div>
                        </a>
                    </div>
                </li>
@if(App\Helpers\LicenseHelper::isActiveQlik())
                <li class='connectedSortable' title='Drag To Main Area'>
                    <div id='btn-qlikwidget' class='button-widget-area'>
                        <a href="#" data-component='qlikwidget' class='btn-add-widget add-qlikwidget'>
                            <img style="width: 50%;" src='/images/qlik_logo.png' />
                            <div class='title'>Qlik Widget</div>
                        </a>
                    </div>
                </li>
@endif
            </ul>
        </aside>
        <!-- The sidebar's background -->
        <!-- This div must placed right after the sidebar for it to work-->
        <div class="control-sidebar-bg"></div>

        <!-- Footer -->
        <footer class="main-footer">
            <!-- To the right -->
            <div class="float-end hidden-xs">
                Powered By {{Session::get('appname')}}
            </div>
            <!-- Default to the left -->
            <strong>Copyright &copy;
                <?php echo date('Y') ?>. All rights reserved.
            </strong>| 
        </footer>

    </div><!-- ./wrapper -->

    @stack('bottom')
</body>

</html>