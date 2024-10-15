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

    <div class="container">
        <!-- Logo Row -->
        <div class="row align-items-center">
            <div class="col-12 text-center text-lg-left">
                <!-- Logo -->
                <a href="{{url(config('crudbooster.ADMIN_PATH'))}}" title='{{Session::get('appname')}}' class="logo">{{CRUDBooster::getSetting('appname')}}</a>
            </div>
        </div>

        <!-- Header Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light justify-content-between" role="navigation">
            <!-- Sidebar toggle button for mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Collapsible content -->
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <!-- Sidebar toggle button-->
                <a href="#" class="sidebar-toggle" data-bs-toggle="offcanvas" role="button">
                    <span class="visually-hidden">Toggle navigation</span>
                </a>

                <!-- Navbar Right Menu -->
                <ul class="navbar-nav ms-auto">
                    <!-- Assistance Menu Item -->
                    <li class="nav-item assistance-menu">
                        <a href="#" class="nav-link" id="toggle-chat" title="AI Assistance" aria-expanded="false">
                            <i id="icon_assistance" class="fa fa-comments-o"></i>
                            <span id="assistance_count" class="badge bg-danger" style="display:none">0</span>
                        </a>
                    </li>

                    <!-- Helper Link -->
                    @if (!empty($url))
                        <li class="nav-item">
                            <a class="nav-link" href="{{$url}}" target="_blank" title='Helper'>
                                <i id='icon_assistance' class="fa fa-question-circle"></i>
                            </a>
                        </li>
                    @endif

                    <!-- Notifications Menu -->
                    <li class="nav-item dropdown notifications-menu">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" title="Notifications" aria-expanded="false">
                            <i id="icon_notification" class="fa fa-bell-o"></i>
                            <span id="notification_count" class="badge bg-danger" style="display:none">0</span>
                        </a>
                        <ul class="dropdown-menu">
                            <li class="dropdown-header">{{trans("crudbooster.text_no_notification")}}</li>
                            <li>
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
                            <img src="{{ UserHelper::icon(CRUDBooster::myId()) }}" class="img-circle" alt="User Image" width="30" height="30">
                            <span class="d-none d-lg-inline">Super Admin</span>
                        </a>
                        <ul class="dropdown-menu">
                            <li class="user-header">
                                <img src="{{ UserHelper::icon(CRUDBooster::myId()) }}" class="img-circle" alt="User Image" width="80" height="80">
                                <p>
                                    {{ CRUDBooster::myName() }}
                                    <small>{{ CRUDBooster::myPrivilegeName() }}</small>
                                    <small><em><?php echo date('d F Y')?></em></small>
                                </p>
                            </li>
                            
                            <li class="user-footer d-flex justify-content-between">
                                <a href="{{ route('AdminCmsUsersControllerGetProfile') }}" class="btn btn-default btn-flat"><i class="fa fa-user"></i> Profile</a>
                                <a href="{{ route('getLockScreen') }}" class="btn btn-default btn-flat" title="Lock Screen"><i class="fa fa-key"></i></a>
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
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</header>

<!-- Custom Styles for Mobile Responsiveness -->
<style>
    /* Center the logo and ensure it is properly styled */
    .logo {
        font-size: 24px; /* Adjust size as needed */
        font-weight: bold; /* Make the logo text bold */
    }

    /* Adjust navbar toggler button for better mobile experience */
    .navbar-toggler {
        border-color: rgba(255, 255, 255, 0.1);
    }

    /* Ensure sidebar toggle icon aligns well on mobile */
    .sidebar-toggle {
        padding: 0.5rem 1rem;
    }

    /* Dropdown menu styling */
    .notifications-menu .dropdown-menu, .user-menu .dropdown-menu {
        padding: 0.5rem;
        width: 200px; /* Adjust dropdown width if needed */
    }

    /* Center-align items on mobile */
    @media (max-width: 576px) {
        .navbar-nav {
            text-align: center;
            margin: 0; /* Reset margins for alignment */
        }
        .user-menu img {
            margin: 0 auto; /* Center user image */
        }
    }
</style>
