@php 

use App\Helpers\CRUDBooster;
use App\Helpers\ModuleHelperHelper;

//AdminQlikItemsController
//AdminChatAIController

$content_view_mode = ['AdminQlikItemsController', 'AdminChatAIController'];

$mod = CRUDBooster::getCurrentModule();
$method = CRUDBooster::getCurrentMethod();
$id = CRUDBooster::getCurrentId();

//dd($method);

if ($method != 'content_view') {
    $url = ModuleHelperHelper::getUrl($mod);

} else {

    $url = ModuleHelperHelper::getUrlCV($mod, $id);
}







@endphp 

<!-- Main Header -->
<header class="main-header">

<!--navbar navbar-expand-lg navbar-light justify-content-between-->
    <!-- Header Navbar -->
    <nav style="padding: 0;" class="navbar navbar-expand-sm navbar-light justify-content-between" role="navigation">
        {{-- Toggle della sidebar: agganciato da public/js/ch-shell.js tramite
             data-ch-toggle="sidebar" (il vecchio data-bs-toggle="offcanvas"
             di AdminLTE e' ancora accettato come selettore di ripiego). --}}
        <a href="#" class="sidebar-toggle" data-ch-toggle="sidebar" role="button">
            <span class="visually-hidden">Toggle navigation</span>
        </a>
        <!-- Navbar Right Menu -->

<div class="navbar-custom-menu">
    <ul class="nav navbar-nav">
@if(App\Helpers\LicenseHelper::isActiveChatAI())
        <!-- Assistance Menu Item -->
        <li class="nav-item assistance-menu">
            <a href="#" class="nav-link toggle-sidebar-btn" id="toggle-chat" title="AI Assistance" aria-expanded="false">
                <i id="icon_assistance" class="bi bi-chat-dots"></i>
                <span id="assistance_count" class="badge bg-danger" style="display:none">0</span>
            </a>
        </li>
@endif

        <!-- Helper Link -->


                @if (!empty($url))
                    <li class="nav-item assistance-menu">
                        <a class="nav-link" href="{{$url}}" target="_blank" title='Helper' >
                            <i id='icon_assistance' class="bi bi-question-circle-fill">
                            </i>
                            <span id='assistance_count' class="badge bg-danger" style="display:none">0</span>
                        </a>

                    </li>
                @endif

        @if(config('crudbooster.UI_V2'))
        {{-- Tema chiaro/scuro: logica in public/js/ch-theme.js (intervento 235) --}}
        <li class="nav-item">
            <a href="#" class="nav-link" data-ch-toggle="theme" title="{{ trans('crudbooster.ui_theme_toggle') }}" role="button">
                <i class="bi bi-moon-stars"></i>
            </a>
        </li>
        @endif

        <!-- Notifications Menu -->
        <li class="nav-item dropdown notifications-menu">
            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" title="Notifications" aria-expanded="false">
                <i id="icon_notification" class="bi bi-bell"></i>
                <span id="notification_count" class="badge bg-danger" style="display:none">0</span>
            </a>
            {{-- Tendina notifiche: markup Bootstrap 5 (dropdown-header / dropdown-item /
                 dropdown-divider). Le voci sono generate da public/vendor/crudbooster/
                 assets/js/main.js (loader_notification) dentro ".menu"; il titolo
                 ".dropdown-header" ne mostra il conteggio. Stili: .ch-notifications in theme.css. --}}
            <ul id="list_notifications" class="dropdown-menu dropdown-menu-end ch-notifications">
                <li><h6 class="dropdown-header">{{trans("crudbooster.text_no_notification")}}</h6></li>
                <li><hr class="dropdown-divider m-0"></li>
                <li>
                    <ul class="menu list-unstyled mb-0"></ul>
                </li>
                <li><hr class="dropdown-divider m-0"></li>
                <li><a class="dropdown-item text-center" href="{{route('NotificationsControllerGetIndex')}}">{{trans("crudbooster.text_view_all_notification")}}</a></li>
            </ul>
        </li>

        <!-- User Account Menu -->
        <li class="nav-item dropdown user-menu">
            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                <!-- The user image in the navbar-->
                <?php $hd_photo = optional(CRUDBooster::me())->photo; ?>
                {!! \App\Http\Controllers\System\LogsController::avatarHtml(CRUDBooster::myName(), $hd_photo, CRUDBooster::myId(), 30) !!}
                <!-- hidden-xs hides the username on small devices so only the image appears. -->
                <span class="d-none d-sm-inline">{{ CRUDBooster::myName() }}</span>
            </a>
            <ul class="dropdown-menu">
                <!-- The user image in the menu -->
                <li class="user-header">
                    {!! \App\Http\Controllers\System\LogsController::avatarHtml(CRUDBooster::myName(), $hd_photo, CRUDBooster::myId(), 80) !!}
                            <p>
                                {{ CRUDBooster::myName() }}
                                <small>{{ CRUDBooster::myPrivilegeName() }}</small>
                                <small><em><?php echo date('d F Y')?></em></small>
                            </p>
                </li>

                <!-- Menu Footer-->
                <li class="user-footer">
                    <div class="pull-{{ trans('crudbooster.left') }}">
                        <a href="{{ route('AdminCmsUsersControllerGetProfile') }}" class="btn btn-secondary"><i class="bi bi-person-fill"></i> Profile</a>
                    </div>
                    <div class="pull-{{ trans('crudbooster.right') }}">
                        <a title="Lock Screen" href="{{ route('getLockScreen') }}" class="btn btn-secondary"><i class="bi bi-key-fill"></i></a>
                        <a href="javascript:void(0)" onclick="swal({
                                title:'{{trans('crudbooster.alert_want_to_logout')}}',
                                type: 'info',
                                showCancelButton: true,
                                allowOutsideClick: true,
                                confirmButtonColor: '#DD6B55',
                                confirmButtonText: '{{trans('crudbooster.button_logout')}}',
                                cancelButtonText: '{{trans('crudbooster.button_cancel')}}',
                                closeOnConfirm: false
                            }, function(){
                                location.href = '{{ route("getLogout") }}';
                            });" title="{{trans('crudbooster.button_logout')}}" class="btn btn-danger">
                            <i class="bi bi-power"></i></a>
                    </div>
                    
                </li>
            </ul>
        </li>
    </ul>
</div>

    </nav>
</header>


