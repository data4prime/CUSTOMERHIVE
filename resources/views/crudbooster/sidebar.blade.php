<!-- Left side column. contains the sidebar -->
<aside class="main-sidebar">

  <!-- sidebar: style can be found in sidebar.less -->
  <section class="sidebar">

    <!-- Sidebar user card: in cima -->
    <div class="user-panel">
      <div class="pull-{{ trans('crudbooster.left') }} image">
        <img src="{{ CRUDBooster::myPhoto() }}" class="rounded-circle" alt="{{ trans('crudbooster.user_image') }}" />
      </div>
      <div class="pull-{{ trans('crudbooster.left') }} info">
        <p>{{ CRUDBooster::myName() }}</p>
      </div>
    </div>

    <div class='main-menu'>
      <!-- Sidebar Menu -->
      <ul class="sidebar-menu">
        <li class="header">{{__("crudbooster.menu_navigation")}}
          <div class="my-collapse-sidebar pull-right" data-collapse-btn="1">
            <i class="fa fa-minus"></i>
          </div>
        </li>

        <?php

          $dashboard = CRUDBooster::sidebarDashboard();


        ?>


        @if($dashboard)
        <li data-id='{{$dashboard->id}}' data-collapse="1"
          class="{{ (Request::is(config('crudbooster.ADMIN_PATH'))) ? 'active' : '' }}">
          <a href='{{CRUDBooster::adminPath()}}' class='{{($dashboard->color)?"text-".$dashboard->color:""}}'>
            <svg class="ch-nav-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
            <span>{{trans("crudbooster.text_dashboard")}}</span>
          </a>
        </li>
        @endif

        <?=\App\Helpers\MenuHelper::build_main_sidebar()?>

        @if(CRUDBooster::isSuperadmin() OR UserHelper::isTenantAdmin())
        <?php $current_path = "" ?>
        <li class="header">{{ trans('crudbooster.UserPermissions') }}
          <div class="my-collapse-sidebar pull-right" data-collapse-btn="2">
            <i class="fa fa-minus"></i>
          </div>
        </li>

        @if(CRUDBooster::isSuperadmin())

        <li data-collapse="2" class='treeview'>
          <a href='#'>
            <svg class="ch-nav-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="1.5"/><path d="M8 7h.01M12 7h.01M16 7h.01M8 11h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01"/></svg>
            <span>{{ trans('crudbooster.Tenants') }}</span> <svg class="ch-nav-chevron pull-{{ trans('crudbooster.right') }}" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9,6 15,12 9,18"/></svg>
          </a>
          <ul class='treeview-menu'>
            <li class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/tenants/add*')) ? 'active' : '' }}"><a
                href='{{Route("AdminTenantsControllerGetAdd")}}'>{{ $current_path }}<i class='fa fa-plus'></i>
                <span>{{ trans('crudbooster.Add_New_Tenant') }}</span></a></li>
            <li class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/tenants')) ? 'active' : '' }}"><a
                href='{{Route("AdminTenantsControllerGetIndex")}}'><i class='fa fa-bars'></i>
                <span>{{ trans('crudbooster.List_Tenants') }}</span></a></li>
          </ul>
        </li>

        <li data-collapse="2" class='treeview'>
          <a href='#'>
            <svg class="ch-nav-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3z"/></svg>
            <span>{{ trans('crudbooster.Roles') }}</span> <svg class="ch-nav-chevron pull-{{ trans('crudbooster.right') }}" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9,6 15,12 9,18"/></svg></a>
          <ul class='treeview-menu'>
            <li class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/privileges/add*')) ? 'active' : '' }}"><a
                href='{{Route("PrivilegesControllerGetAdd")}}'>{{ $current_path }}<i class='fa fa-plus'></i>
                <span>{{ trans('crudbooster.Add_New_Privilege') }}</span></a></li>
            <li class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/privileges')) ? 'active' : '' }}"><a
                href='{{Route("PrivilegesControllerGetIndex")}}'><i class='fa fa-bars'></i>
                <span>{{ trans('crudbooster.List_Privilege') }}</span></a></li>
          </ul>
        </li>
        @endif

        <li data-collapse="2" class='treeview'>
          <a href='#'>
            <svg class="ch-nav-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"/><path d="M3.5 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17" cy="9" r="2.5"/><path d="M15.5 14c2.6.3 4.5 2.5 4.5 5.2"/></svg>
            <span>{{ trans('crudbooster.Groups') }}</span> <svg class="ch-nav-chevron pull-{{ trans('crudbooster.right') }}" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9,6 15,12 9,18"/></svg></a>
          <ul class='treeview-menu'>
            <li class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/groups/add*')) ? 'active' : '' }}"><a
                href='{{Route("AdminGroupsControllerGetAdd")}}'>{{ $current_path }}<i class='fa fa-plus'></i>
                <span>{{ trans('crudbooster.Add_New_Group') }}</span></a></li>
            <li class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/groups')) ? 'active' : '' }}"><a
                href='{{Route("AdminGroupsControllerGetIndex")}}'><i class='fa fa-bars'></i>
                <span>{{ trans('crudbooster.List_Groups') }}</span></a></li>
          </ul>
        </li>

        <li data-collapse="2" class='treeview'>
          <a href='#'>
            <svg class="ch-nav-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.2"/><path d="M5 20c0-3.9 3.1-7 7-7s7 3.1 7 7"/></svg>
            <span>{{ trans('crudbooster.Users') }}</span>
            <svg class="ch-nav-chevron pull-{{ trans('crudbooster.right') }}" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9,6 15,12 9,18"/></svg>
          </a>
          <ul class='treeview-menu'>
            <li class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/users/add*')) ? 'active' : '' }}">
              <a href='{{Route("AdminCmsUsersControllerGetAdd")}}'>
                <i class='fa fa-plus'></i>
                <span>{{ trans('crudbooster.add_user') }}</span>
              </a>
            </li>
            <li class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/users')) ? 'active' : '' }}">
              <a href='{{Route("AdminCmsUsersControllerGetIndex")}}'>
                <i class='fa fa-bars'></i>
                <span>{{ trans('crudbooster.List_users') }}</span>
              </a>
            </li>
          </ul>
        </li>

        <li data-collapse="2" class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/logs*')) ? 'active' : '' }}">
          <a href='{{Route("LogsControllerGetIndex")}}'>
            <svg class="ch-nav-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h4l2-7 4 14 2-7h6"/></svg>
            <span>{{ trans('crudbooster.User_Access_Log') }}</span>
          </a>
        </li>

        @endif

        @if(CRUDBooster::isSuperadmin() OR UserHelper::isTenantAdmin() )
        <li class="header">{{ trans('crudbooster.superadmin') }}
          <div class="my-collapse-sidebar pull-right" data-collapse-btn="3">
            <i class="fa fa-minus"></i>
          </div>
        </li>

        <li data-collapse="3"
          class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/menu_management*')) ? 'active' : '' }}"><a
            href='{{Route("MenusControllerGetIndex")}}'>
            <svg class="ch-nav-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="7" x2="19" y2="7"/><line x1="5" y1="12" x2="19" y2="12"/><line x1="5" y1="17" x2="19" y2="17"/></svg>
            <span>{{ trans('crudbooster.Menu_Management') }}</span></a>
        </li>
@if(App\Helpers\LicenseHelper::isActiveQlik())
        <li data-collapse="3" class='treeview'>
          <a href='#'>
            <svg class="ch-nav-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="20" x2="5" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="19" y1="20" x2="19" y2="14"/></svg>
            <span>{{ trans('crudbooster.Qlik_Items') }}</span> <svg class="ch-nav-chevron pull-{{ trans('crudbooster.right') }}" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9,6 15,12 9,18"/></svg></a>
          <ul class='treeview-menu'>
            <li class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/qlik_items/add')) ? 'active' : '' }}"><a
                href='{{Route("AdminQlikItemsControllerGetAdd")}}'><i class='fa fa-plus'></i>
                <span>{{ trans('crudbooster.Add_New_Qlikitem') }}</span></a></li>
            <li class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/qlik_items')) ? 'active' : '' }}"><a
                href='{{Route("AdminQlikItemsControllerGetIndex")}}'><i class='fa fa-bars'></i>
                <span>{{ trans('crudbooster.List_Qlikitem') }}</span></a></li>
          </ul>
        </li>
@endif

      @if(App\Helpers\LicenseHelper::isActiveQlik())

        <li data-collapse="3" class='treeview'>
          <a href='#'>
            <svg class="ch-nav-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><circle cx="9" cy="6" r="2"/><line x1="4" y1="12" x2="20" y2="12"/><circle cx="15" cy="12" r="2"/><line x1="4" y1="18" x2="20" y2="18"/><circle cx="7" cy="18" r="2"/></svg>
            <span>Qlik Settings</span> <svg class="ch-nav-chevron pull-{{ trans('crudbooster.right') }}" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9,6 15,12 9,18"/></svg>
          </a>

          <ul class='treeview-menu'>
            <li class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/qlik_items/add')) ? 'active' : '' }}">
              <a href='{{url("admin/qlik_confs")}}'>
            <span>{{ trans('crudbooster.Qlik_Configuration') }}</span> <svg class="ch-nav-chevron pull-{{ trans('crudbooster.right') }}" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9,6 15,12 9,18"/></svg>
          </a>
            </li>
            <li class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/qlik_items')) ? 'active' : '' }}">

              <a href='{{url("admin/qlik_apps")}}'>
                <span>{{ trans('crudbooster.Qlik_Apps') }}</span> <svg class="ch-nav-chevron pull-{{ trans('crudbooster.right') }}" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9,6 15,12 9,18"/></svg>
              </a>

            </li>
          </ul>


        </li>
        @endif
@if(App\Helpers\LicenseHelper::isActiveChatAI())
        <li data-collapse="3" class='treeview'>
          <a href='{{url("admin/chat_ai")}}'>
            <svg class="ch-nav-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v11H8l-4 4V5z"/></svg>
            <span>Chat AI</span>
          </a>

        </li>
@endif

        <li data-collapse="3" class='treeview'>
          <a href='{{url("admin/module_helpers")}}'>
            <svg class="ch-nav-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 0 1 5 0c0 1.6-2.5 2-2.5 3.8"/><circle cx="12" cy="17" r="0.6" fill="currentColor" stroke="none"/></svg>
            <span>Module Helpers</span>
          </a>

        </li>

        <!--<li data-collapse="3" class='treeview'>
          <a href='{{url("admin/dashboard_layouts")}}'>
            <img class="menu qlik_logo" src=/images/apps.png />
            <span>{{ trans('crudbooster.Dashboard_Layouts') }}</span> <i class="fa fa-angle-{{ trans("
              crudbooster.right") }} pull-{{ trans("crudbooster.right") }}"></i>
          </a>

        </li>-->



        @if(CRUDBooster::isSuperadmin())
        <li data-collapse="3" class="treeview">
          <a href="#">
            <svg class="ch-nav-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/></svg>
            <span>{{ trans('crudbooster.settings') }}</span> <svg class="ch-nav-chevron pull-{{ trans('crudbooster.right') }}" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9,6 15,12 9,18"/></svg></a>
          <ul class="treeview-menu">
            <li class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/settings/add*')) ? 'active' : '' }}"><a
                href='{{route("SettingsControllerGetAdd")}}'><i class='fa fa-plus'></i>
                <span>{{ trans('crudbooster.Add_New_Setting') }}</span></a></li>
            <?php
                            $groupSetting = DB::table('cms_settings')->groupby('group_setting')->pluck('group_setting');
                            foreach($groupSetting as $gs):
                            ?>
            <li class="<?=($gs == Request::get('group')) ? 'active' : ''?>"><a
                href='{{route("SettingsControllerGetShow")}}?group={{urlencode($gs)}}&m=0'><i class='fa fa-wrench'></i>
                <span>{{$gs}}</span></a></li>
            <?php endforeach;?>
          </ul>
        </li>

        <li data-collapse="3" class='treeview'>
          <a href='#'>
            <svg class="ch-nav-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3h4v3.4a1.8 1.8 0 0 0 3.2 1.1H20v4h-3.4a1.8 1.8 0 0 0 0 3.2V19h-4v-3.4a1.8 1.8 0 0 0-3.2-1.1H6v-4h3.4A1.8 1.8 0 0 0 9 6.4V3z"/></svg>
            <span>{{ trans('crudbooster.Module_Generator') }}</span> <svg class="ch-nav-chevron pull-{{ trans('crudbooster.right') }}" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9,6 15,12 9,18"/></svg>
          </a>
          <ul class='treeview-menu'>
            <li class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/module_generator/step1')) ? 'active' : '' }}">
              <a href='{{Route("ModulsControllerGetStep1")}}'><i class='fa fa-plus'></i>
                <span>{{ trans('crudbooster.Add_New_Module') }}</span>
              </a>
            </li>
            <li class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/module_generator')) ? 'active' : '' }}">
              <a href='{{Route("ModulsControllerGetIndex")}}'><i class='fa fa-bars'></i>
                <span>{{ trans('crudbooster.List_Module') }}</span>
              </a>
            </li>
            <li class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/module_generator')) ? 'active' : '' }}">
              <a href="/{{ config('crudbooster.ADMIN_PATH')}}/module_generator/enable">
                <i class='fa fa-wrench'></i>
                <span>{{ trans('crudbooster.enable_disable') }} {{ trans('crudbooster.modules') }}</span>
              </a>
            </li>
          </ul>
        </li>



        <!--<li data-collapse="3" class='treeview'>
          <a href='{{url("admin/qlik_apps")}}'>
            <img class="menu qlik_logo" src=/images/qlik_logo.png />
            <span>{{ trans('crudbooster.Qlik_Apps') }}</span> <i class="fa fa-angle-{{ trans("
              crudbooster.right") }} pull-{{ trans("crudbooster.right") }}"></i>
          </a>

        </li>-->
        <li data-collapse="3" class='treeview'>
          <a href='#'>
            <svg class="ch-nav-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="4,16 10,10 14,14 20,6"/><polyline points="14,6 20,6 20,12"/></svg>
            <span>{{ trans('crudbooster.Statistic_Builder') }}</span> <svg class="ch-nav-chevron pull-{{ trans('crudbooster.right') }}" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9,6 15,12 9,18"/></svg></a>
          <ul class='treeview-menu'>
            <li class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/statistic_builder/add')) ? 'active' : '' }}">
              <a href='{{Route("StatisticBuilderControllerGetAdd")}}'><i class='fa fa-plus'></i>
                <span>{{ trans('crudbooster.Add_New_Statistic') }}</span></a>
            </li>
            <li class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/statistic_builder')) ? 'active' : '' }}"><a
                href='{{Route("StatisticBuilderControllerGetIndex")}}'><i class='fa fa-bars'></i>
                <span>{{ trans('crudbooster.List_Statistic') }}</span></a></li>
          </ul>
          <ul  class='treeview-menu'>
<li data-collapse="3" class='treeview'>
          <a href='{{url("admin/dashboard_layouts")}}'>
            <svg class="ch-nav-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="9" x2="9" y2="21"/></svg>
            <span>{{ trans('crudbooster.Dashboard_Layouts') }}</span> <svg class="ch-nav-chevron pull-{{ trans('crudbooster.right') }}" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9,6 15,12 9,18"/></svg>
          </a>

        </li>
</ul>
        </li>

        <li data-collapse="3" class='treeview'>
          <a href='#'>
            <svg class="ch-nav-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><polyline points="7,9 10,12 7,15"/><line x1="12" y1="15" x2="17" y2="15"/></svg>
            <span>{{ trans('crudbooster.API_Generator') }}</span> <svg class="ch-nav-chevron pull-{{ trans('crudbooster.right') }}" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9,6 15,12 9,18"/></svg></a>
          <ul class='treeview-menu'>
            <li
              class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/api_generator/generator*')) ? 'active' : '' }}">
              <a href='{{Route("ApiCustomControllerGetGenerator")}}'><i class='fa fa-plus'></i>
                <span>{{ trans('crudbooster.Add_New_API') }}</span></a>
            </li>
            <li class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/api_generator')) ? 'active' : '' }}"><a
                href='{{Route("ApiCustomControllerGetIndex")}}'><i class='fa fa-bars'></i>
                <span>{{ trans('crudbooster.list_API') }}</span></a></li>
            <li
              class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/api_generator/screet-key*')) ? 'active' : '' }}">
              <a href='{{Route("ApiCustomControllerGetScreetKey")}}'><i class='fa fa-bars'></i>
                <span>{{ trans('crudbooster.Generate_Screet_Key') }}</span></a>
            </li>
          </ul>
        </li>

        <li data-collapse="3" class='treeview'>
          <a href='#'>
            <svg class="ch-nav-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3,7 12,13 21,7"/></svg>
            <span>{{ trans('crudbooster.Email_Templates') }}</span> <svg class="ch-nav-chevron pull-{{ trans('crudbooster.right') }}" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9,6 15,12 9,18"/></svg></a>
          <ul class='treeview-menu'>
            <li class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/email_templates/add*')) ? 'active' : '' }}"><a
                href='{{Route("EmailTemplatesControllerGetAdd")}}'><i class='fa fa-plus'></i>
                <span>{{ trans('crudbooster.Add_New_Email') }}</span></a></li>
            <li class="{{ (Request::is(config('crudbooster.ADMIN_PATH').'/email_templates')) ? 'active' : '' }}"><a
                href='{{Route("EmailTemplatesControllerGetIndex")}}'><i class='fa fa-bars'></i>
                <span>{{ trans('crudbooster.List_Email_Template') }}</span></a></li>
          </ul>
        </li>
        @endif

        @endif

      </ul><!-- /.sidebar-menu -->

    </div>

  </section>
  <!-- /.sidebar -->
</aside>
