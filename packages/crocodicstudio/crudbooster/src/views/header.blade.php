@php 

use crocodicstudio\crudbooster\helpers\CRUDBooster;
use crocodicstudio\crudbooster\helpers\ModuleHelperHelper;

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

    <!-- Logo -->
    <a href="{{url(config('crudbooster.ADMIN_PATH'))}}" title='{{Session::get('appname')}}' class="logo">{{CRUDBooster::getSetting('appname')}}</a>
<!--navbar navbar-expand-lg navbar-light justify-content-between-->
    <!-- Header Navbar -->
    <nav style="padding: 0;" class="navbar navbar-expand-sm navbar-light justify-content-between" role="navigation">
        <!-- Sidebar toggle button-->
        <a href="#" class="sidebar-toggle" data-bs-toggle="offcanvas" role="button">
            <span class="visually-hidden">Toggle navigation</span>
        </a>
        <!-- Navbar Right Menu -->

<div class="navbar-custom-menu">
    <ul class="nav navbar-nav">

        <!-- Assistance Menu Item -->
        <li class="nav-item assistance-menu">
            <a href="#" class="nav-link toggle-sidebar-btn" id="toggle-chat" title="AI Assistance" aria-expanded="false">
                <i id="icon_assistance" class="fa fa-comments-o"></i>
                <span id="assistance_count" class="badge bg-danger" style="display:none">0</span>
            </a>
        </li>

        <!-- Helper Link -->


                @if (!empty($url))
                    <li class="nav-item assistance-menu">
                        <a class="nav-link toggle-sidebar-btn" href="{{$url}}" target="_blank" title='Helper' >
                            <i id='icon_assistance' class="fa fa-question-circle">
                            </i>
                            <span id='assistance_count' class="badge bg-danger" style="display:none">0</span>
                        </a>

                    </li>
                @endif

        <!-- Notifications Menu -->
        <li class="nav-item dropdown notifications-menu">
            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" title="Notifications" aria-expanded="false">
                <i id="icon_notification" class="fa fa-bell-o"></i>
                <span id="notification_count" class="badge bg-danger" style="display:none">0</span>
            </a>
            <ul id="list_notifications" class="dropdown-menu">
                <li class="dropdown-header">{{trans("crudbooster.text_no_notification")}}</li>
                <li>
                    <!-- inner menu: contains the actual data -->
                    <div class="overflow-auto" style="height: 200px;">
                        <ul class="menu list-unstyled" style="height: 200px;"></ul>
                    </div>
                </li>
                <li class="dropdown-footer"><a href="{{route('NotificationsControllerGetIndex')}}">{{trans("crudbooster.text_view_all_notification")}}</a></li>
            </ul>
        </li>

        <!-- User Account Menu -->
        <li class="nav-item dropdown user-menu">
            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                <!-- The user image in the navbar-->
                <img src="{{ UserHelper::icon(CRUDBooster::myId()) }}" class="img-circle" alt="User Image" width="30" height="30">
                <!-- hidden-xs hides the username on small devices so only the image appears. -->
                <span class="d-none d-sm-inline">Super Admin</span>
            </a>
            <ul class="dropdown-menu">
                <!-- The user image in the menu -->
                <li class="user-header">
                    <img src="{{ UserHelper::icon(CRUDBooster::myId()) }}" class="img-circle" alt="User Image" width="80" height="80">
                            <p>
                                {{ CRUDBooster::myName() }}
                                <small>{{ CRUDBooster::myPrivilegeName() }}</small>
                                <small><em><?php echo date('d F Y')?></em></small>
                            </p>
                </li>

                <!-- Menu Footer-->
                <li class="user-footer">
                    <div class="pull-{{ trans('crudbooster.left') }}">
                        <a href="{{ route('AdminCmsUsersControllerGetProfile') }}" class="btn btn-default btn-flat"><i class="fa fa-user"></i> Profile</a>
                    </div>
                    <div class="pull-{{ trans('crudbooster.right') }}">
                        <a title="Lock Screen" href="{{ route('getLockScreen') }}" class="btn btn-default btn-flat"><i class="fa fa-key"></i></a>
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
                            });" title="{{trans('crudbooster.button_logout')}}" class="btn btn-danger btn-flat">
                            <i class="fa fa-power-off"></i></a>
                    </div>
                    
                </li>
            </ul>
        </li>
    </ul>
</div>

    </nav>
</header>


