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
    <nav class="navbar navbar-expand-lg navbar-light justify-content-between" role="navigation">
        <!-- Sidebar toggle button-->
        <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
            <span class="visually-hidden">Toggle navigation</span>
        </a>
        <!-- Navbar Right Menu -->
        <!--
        <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">

                <li class="assistance-menu">
                    <a href="#" class="toggle-sidebar-btn" id="toggle-chat" title='AI Assistance' aria-expanded="false">
                        <i id='icon_assistance' class="fa fa-comments-o">
                        </i>
                        <span id='assistance_count' class="label label-danger" style="display:none">0</span>
                    </a>

                </li>

                @if (!empty($url))
                    <li class="assistance-menu">
                        <a href="{{$url}}" target="_blank" title='Helper' >
                            <i id='icon_assistance' class="fa fa-question-circle">
                            </i>
                            <span id='assistance_count' class="label label-danger" style="display:none">0</span>
                        </a>

                    </li>
                @endif



                <li class="dropdown notifications-menu">

                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" title='Notifications' aria-expanded="false">
                        <i id='icon_notification' class="fa fa-bell-o"></i>
                        <span id='notification_count' class="label label-danger" style="display:none">0</span>
                    </a>
                    <ul id='list_notifications' class="dropdown-menu">
                        <li class="header">{{trans("crudbooster.text_no_notification")}}</li>
                        <li>
                            <div class="slimScrollDiv" style="position: relative; overflow: hidden; width: auto; height: 200px;">
                                <ul class="menu" style="overflow: hidden; width: 100%; height: 200px;">
                                    <li>
                                        <a href="#">
                                            <em>{{trans("crudbooster.text_no_notification")}}</em>
                                        </a>
                                    </li>

                                </ul>
                                <div class="slimScrollBar"
                                     style="width: 3px; position: absolute; top: 0px; opacity: 0.4; display: none; border-radius: 7px; z-index: 99; right: 1px; height: 195.122px; background: rgb(0, 0, 0);"></div>
                                <div class="slimScrollRail"
                                     style="width: 3px; height: 100%; position: absolute; top: 0px; display: none; border-radius: 7px; opacity: 0.2; z-index: 90; right: 1px; background: rgb(51, 51, 51);"></div>
                            </div>
                        </li>
                        <li class="footer"><a href="{{route('NotificationsControllerGetIndex')}}">{{trans("crudbooster.text_view_all_notification")}}</a></li>
                    </ul>
                </li>

                <li class="dropdown user user-menu">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <img src="{{ UserHelper::icon(CRUDBooster::myId()) }}" class="user-image" alt="User Image"/>
                        <span class="hidden-xs">{{ CRUDBooster::myName() }}</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="user-header">
                            <img src="{{ UserHelper::icon(CRUDBooster::myId()) }}" class="img-circle" alt="User Image"/>
                            <p>
                                {{ CRUDBooster::myName() }}
                                <small>{{ CRUDBooster::myPrivilegeName() }}</small>
                                <small><em><?php echo date('d F Y')?></em></small>
                            </p>
                        </li>

                        <li class="user-footer">
                            <div class="pull-{{ trans('crudbooster.left') }}">
                                <a href="{{ route('AdminCmsUsersControllerGetProfile') }}" class="btn btn-default btn-flat"><i
                                            class='fa fa-user'></i> {{trans("crudbooster.label_button_profile")}}</a>
                            </div>
                            <div class="pull-{{ trans('crudbooster.right') }}">
                                <a title='Lock Screen' href="{{ route('getLockScreen') }}" class='btn btn-default btn-flat'><i class='fa fa-key'></i></a>
                                <a href="javascript:void(0)" onclick="swal({
                                        title: '{{trans('crudbooster.alert_want_to_logout')}}',
                                        type:'info',
                                        showCancelButton:true,
                                        allowOutsideClick:true,
                                        confirmButtonColor: '#DD6B55',
                                        confirmButtonText: '{{trans('crudbooster.button_logout')}}',
                                        cancelButtonText: '{{trans('crudbooster.button_cancel')}}',
                                        closeOnConfirm: false
                                        }, function(){
                                        location.href = '{{ route("getLogout") }}';

                                        });" title="{{trans('crudbooster.button_logout')}}" class="btn btn-danger btn-flat"><i class='fa fa-power-off'></i></a>
                            </div>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>-->
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
        <li class="nav-item assistance-menu">
            <a href="https://help.thecustomerhive.com/books/manuale-amministratore/chapter/privileges" target="_blank" class="nav-link" title="Helper">
                <i id="icon_assistance" class="fa fa-question-circle"></i>
                <span id="assistance_count" class="badge bg-danger" style="display:none">0</span>
            </a>
        </li>

        <!-- Notifications Menu -->
        <li class="nav-item dropdown notifications-menu">
            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" title="Notifications" aria-expanded="false">
                <i id="icon_notification" class="fa fa-bell-o"></i>
                <span id="notification_count" class="badge bg-danger" style="display:none">0</span>
            </a>
            <ul id="list_notifications" class="dropdown-menu">
                <li class="dropdown-header">You Have 0 Notifications</li>
                <li>
                    <!-- inner menu: contains the actual data -->
                    <div class="overflow-auto" style="height: 200px;">
                        <ul class="menu list-unstyled" style="height: 200px;"></ul>
                    </div>
                </li>
                <li class="footer"><a href="https://staging.thecustomerhive.com/admin/notifications">View All</a></li>
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


