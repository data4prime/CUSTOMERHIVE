<style>

/* Basic styling for the right sidebar */
.main-sidebar-right {
  position: fixed;
  right: -250px; /* Hide initially */
  top: 0;
  height: 100%;
  width: 250px;
  background-color: #222d32;
  transition: right 0.3s ease;
  z-index: 1000;
}

/* Sidebar open state */
.main-sidebar-right.open {
  right: 0;
}

/* Styling for toggle button */
.toggle-sidebar-btn {
  position: fixed;
  right: 10px;
  top: 10px;
  z-index: 1100;
  background-color: #fff;
  border: none;
  cursor: pointer;
  padding: 5px 10px;
  border-radius: 4px;
  font-size: 16px;
}

/* Sidebar inner components */
.main-sidebar-right .user-panel {
  padding: 10px;
  color: #fff;
}

.main-sidebar-right .sidebar-menu {
  list-style: none;
  padding: 0;
  margin: 0;
}

.main-sidebar-right .sidebar-menu li {
  padding: 10px;
  color: #b8c7ce;
}

.main-sidebar-right .sidebar-menu li.active > a {
  background-color: #1e282c;
  color: #fff;
}

.main-sidebar-right .sidebar-menu a {
  color: #b8c7ce;
  text-decoration: none;
}

</style>

<!-- Toggle Sidebar Button 
<button class="toggle-sidebar-btn">Toggle Sidebar</button>
-->

<!-- Right side column. contains the sidebar -->
<aside class="main-sidebar main-sidebar-right">
  <!-- sidebar: style can be found in sidebar.less -->
  <section class="sidebar">
    <!-- Sidebar user panel (optional) -->
    <div class="user-panel">
      <div class="pull-right image">
        <img src="{{ CRUDBooster::myPhoto() }}" class="img-circle" alt="{{ trans('crudbooster.user_image') }}" />
      </div>
      <div class="pull-right info">
        <p>{{ CRUDBooster::myName() }}</p>
        <!-- Status -->
        <a href="#"><i class="fa fa-circle text-success"></i> {{ trans('crudbooster.online') }}</a>
      </div>
    </div>

    <div class='main-menu'>
      <!-- Sidebar Menu -->
      <ul class="sidebar-menu">
        <li class="header">{{trans("crudbooster.menu_navigation")}}
          <div class="my-collapse-sidebar pull-right" data-collapse-btn="1">
            <i class="fa fa-minus"></i>
          </div>
        </li>

        <?php $dashboard = CRUDBooster::sidebarDashboard();?>
        @if($dashboard)
        <li data-id='{{$dashboard->id}}' data-collapse="1" class="{{ (Request::is(config('crudbooster.ADMIN_PATH'))) ? 'active' : '' }}">
          <a href='{{CRUDBooster::adminPath()}}' class='{{($dashboard->color)?"text-".$dashboard->color:""}}'>
            <i class='fa fa-dashboard'></i>
            <span>{{trans("crudbooster.text_dashboard")}}</span>
          </a>
        </li>
        @endif

        <?=\crocodicstudio\crudbooster\helpers\MenuHelper::build_main_sidebar()?>

        <!-- Additional Menu Items here... -->
        <!-- The rest of your menu items go here -->
        
      </ul><!-- /.sidebar-menu -->
    </div>
  </section>
  <!-- /.sidebar -->
</aside>


