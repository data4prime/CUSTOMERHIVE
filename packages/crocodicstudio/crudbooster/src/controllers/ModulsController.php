<?php

namespace crocodicstudio\crudbooster\controllers;

use CRUDBooster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Excel;
use Illuminate\Support\Facades\PDF;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use crocodicstudio\crudbooster\fonts\Fontawesome;
// #RAMA
use ModuleHelper;
use UserHelper;
use App\Menu;
use App\Modules;
use App\Tenant;
use App\DynamicTable;
use App\DynamicColumn;
//use App\Facades\Schema;
use Illuminate\Support\Facades\Schema;
//use App\Classes\Database\Blueprint;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;

class ModulsController extends CBController
{
  public function cbInit()
  {
    $this->table = 'cms_moduls';
    $this->primary_key = 'id';
    $this->title_field = "name";
    $this->limit = 100;
    $this->button_add = false;
    $this->button_export = false;
    $this->button_import = false;
    $this->button_filter = false;
    $this->button_detail = false;
    $this->button_bulk_action = false;
    $this->button_action_style = 'button_icon';
    $this->orderby = ['is_protected' => 'asc', 'name' => 'asc'];

    $this->col = [];
    // str_replace(config('app.module_generator_prefix'), '', $module->name
    $this->col[] = ["label" => "Name", "name" => "name"];
    $this->col[] = ["label" => "Table", "name" => "table_name"];
    $this->col[] = ["label" => "Path", "name" => "path"];
    $this->col[] = ["label" => "Controller", "name" => "controller"];
    $this->col[] = ["label" => "Protected", "name" => "is_protected", "visible" => false];

    $this->form = [];
    $this->form[] = ["label" => "Name", "name" => "name", "placeholder" => "Module name here", 'required' => true];

    $tables = CRUDBooster::listTables();
    $tables_list = [];
    foreach ($tables as $tab) {
      foreach ($tab as $key => $value) {
        $label = $value;

        if (substr($value, 0, 4) == 'cms_') {
          continue;
        }

        $tables_list[] = $value . "|" . $label;
      }
    }
    foreach ($tables as $tab) {
      foreach ($tab as $key => $value) {
        $label = "[Default] " . $value;
        if (substr($value, 0, 4) == 'cms_') {
          $tables_list[] = $value . "|" . $label;
        }
      }
    }

    $this->form[] = ["label" => "Table Name", "name" => "table_name", "type" => "select2", "dataenum" => $tables_list, 'required' => true];

    $fontawesome = Fontawesome::getIcons();

    $row = CRUDBooster::first($this->table, CRUDBooster::getCurrentId());
    $custom = view('crudbooster::components.list_icon', compact('fontawesome', 'row'))->render();
    $this->form[] = ['label' => 'Icon', 'name' => 'icon', 'type' => 'custom', 'html' => $custom, 'required' => true];




    $this->script_js = "
     			$(function() {
  				function format(icon)
          {
  	                  var originalOption = icon.element;
  	                  var label = $(originalOption).text();
  	                  var val = $(originalOption).val();
  	                  if(!val) return label;
  	                  var \$resp = $('<span><i style=\"margin-top:5px\" class=\"pull-right ' + $(originalOption).val() + '\"></i> ' + $(originalOption).data('label') + '</span>');
  	                  return \$resp;
  	              }
            $('#list-icon').select2({
                        width: '100%',
                        templateResult: format,
                        templateSelection: format
                    });
     				$('#table_name').change(function() {
    					var v = $(this).val();
    					$('#path').val(v);
    				})
            $(document).ready(function() {
              //replace module generator prefix in all td
              $('#table_dashboard td').each(function() {
                var cell_value = $(this).html();
                if(cell_value.startsWith('" . config('app.module_generator_prefix') . "')){
                  cell_value = cell_value.replace('" . config('app.module_generator_prefix') . "', '');
                  console.log(cell_value);
                  $(this).html(cell_value);
                }
                else{
                }
             });

    				})
     			})
   			";

    $this->form[] = ["label" => "Path", "name" => "path", "required" => true, 'placeholder' => 'Optional'];
    $this->form[] = ["label" => "Controller", "name" => "controller", "type" => "text", "placeholder" => "(Optional) Auto Generated"];

    if (CRUDBooster::getCurrentMethod() == 'getAdd' || CRUDBooster::getCurrentMethod() == 'postAddSave') {

      $this->form[] = [
        "label" => "Global Privilege",
        "name" => "global_privilege",
        "type" => "radio",
        "dataenum" => ['0|No', '1|Yes'],
        'value' => 0,
        'help' => 'Global Privilege allows you to make the module to be accessible by all privileges',
        'exception' => true,
      ];

      $this->form[] = [
        "label" => "Button Action Style",
        "name" => "button_action_style",
        "type" => "radio",
        "dataenum" => ['button_icon', 'button_icon_text', 'button_text', 'dropdown'],
        'value' => 'button_icon',
        'exception' => true,
      ];
      $this->form[] = [
        "label" => "Button Table Action",
        "name" => "button_table_action",
        "type" => "radio",
        "dataenum" => ['Yes', 'No'],
        'value' => 'Yes',
        'exception' => true,
      ];
      $this->form[] = [
        "label" => "Button Add",
        "name" => "button_add",
        "type" => "radio",
        "dataenum" => ['Yes', 'No'],
        'value' => 'Yes',
        'exception' => true,
      ];
      $this->form[] = [
        "label" => "Button Delete",
        "name" => "button_delete",
        "type" => "radio",
        "dataenum" => ['Yes', 'No'],
        'value' => 'Yes',
        'exception' => true,
      ];
      $this->form[] = [
        "label" => "Button Edit",
        "name" => "button_edit",
        "type" => "radio",
        "dataenum" => ['Yes', 'No'],
        'value' => 'Yes',
        'exception' => true,
      ];
      $this->form[] = [
        "label" => "Button Detail",
        "name" => "button_detail",
        "type" => "radio",
        "dataenum" => ['Yes', 'No'],
        'value' => 'Yes',
        'exception' => true,
      ];
      $this->form[] = [
        "label" => "Button Show",
        "name" => "button_show",
        "type" => "radio",
        "dataenum" => ['Yes', 'No'],
        'value' => 'Yes',
        'exception' => true,
      ];
      $this->form[] = [
        "label" => "Button Filter",
        "name" => "button_filter",
        "type" => "radio",
        "dataenum" => ['Yes', 'No'],
        'value' => 'Yes',
        'exception' => true,
      ];
      $this->form[] = [
        "label" => "Button Export",
        "name" => "button_export",
        "type" => "radio",
        "dataenum" => ['Yes', 'No'],
        'value' => 'No',
        'exception' => true,
      ];
      $this->form[] = [
        "label" => "Button Import",
        "name" => "button_import",
        "type" => "radio",
        "dataenum" => ['Yes', 'No'],
        'value' => 'No',
        'exception' => true,
      ];
    }

    $this->addaction[] = [
      'label' => 'Module Wizard',
      'icon' => 'fa fa-wrench',
      'url' => CRUDBooster::mainpath('step1') . '/[id]',
      "showIf" => "[is_protected] == 0",
      'color' => 'primary',
    ];

    $this->index_button[] = ['label' => 'Generate New Module', 'icon' => 'fa fa-plus', 'url' => CRUDBooster::mainpath('step1'), 'color' => 'success'];
  }

  public function getEdit($id)
  {
    $this->cbLoader();

    $row = DB::table($this->table)->where("id", $id)->first();

    //only superadmin can edit modules
    if (!CRUDBooster::isSuperadmin()) {
      CRUDBooster::insertLog(trans("crudbooster.log_try_edit", [
        'name' => $row->{$this->title_field},
        'module' => CRUDBooster::getCurrentModule()->name,
      ]));
      CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    $page_title = 'Edit Module Generator';

    $module = Modules::find($id);

    $page_menu = Route::getCurrentRoute()->getActionName();

    $tenants = Tenant::all();

    return view('crudbooster::module_generator.edit', compact('row', 'module', 'tenants', 'page_title', 'page_menu'));
  }

  function enable()
  {
    $this->cbLoader();

    //only superadmin can edit modules
    if (!CRUDBooster::isSuperadmin()) {
      CRUDBooster::insertLog(trans("crudbooster.log_try_edit", [
        'name' => $row->{$this->title_field},
        'module' => CRUDBooster::getCurrentModule()->name,
      ]));
      CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    $modules = ModuleHelper::getEditableModules();
    $tenants = Tenant::all();

    $page_title = 'Modules Settings';
    $page_menu = Route::getCurrentRoute()->getActionName();

    return view('crudbooster::module_generator.enable', compact('modules', 'tenants', 'page_title', 'page_menu'));
  }

  function saveEnable()
  {
    //if (isset($_POST['module_tenant_enabler']) && !empty($_POST['module_tenant_enabler'])) {
    ModuleHelper::update_enabled_tenants($_POST['module_tenant_enabler']);
    //}

    CRUDBooster::redirect(Request::server('HTTP_REFERER'), trans('crudbooster.alert_update_data_success'), 'success');
  }

  function hook_query_index(&$query)
  {
    $query->where('is_protected', 0);
    $query->where('path', '!=', 'groups');
    $query->where('path', '!=', 'qlik_items');
    $query->where('path', '!=', 'tenants');
    $query->whereNotIn('cms_moduls.controller', ['AdminCmsUsersController', 'AdminChatAIController', 'AdminModuleHelperController']);
  }

  public function getDelete($id)
  {
    $this->cbLoader();
    $url = g('return_url') ?: CRUDBooster::referer();
    $module = DB::table($this->table)
      ->where($this->primary_key, $id)
      ->first();

    if (!CRUDBooster::isDelete() && $this->global_privilege == false || $this->button_delete == false) {
      CRUDBooster::insertLog(trans("crudbooster.log_try_delete", [
        'name' => $module->{$this->title_field},
        'module' => CRUDBooster::getCurrentModule()->name,
      ]));
      CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    //insert log
    CRUDBooster::insertLog(trans("crudbooster.log_delete", ['name' => $module->{$this->title_field}, 'module' => CRUDBooster::getCurrentModule()->name]));

    $drop_table = true;
    $drop_module = true;
    //if table name start with mg_
    if (ModuleHelper::is_manually_generated($module->table_name)) {
      //might want to drop table
    } else {
      //don't drop protected table;
      $drop_table = false;
      $alert = 'table is protected<br>';
    }

    //check if other modules are using this table
    $modules_sharing_table = DB::table('cms_moduls')
      ->where('table_name', $module->table_name)
      ->where('deleted_at', null)
      ->get();

    if ($modules_sharing_table->count() <= 1) {
      //might want to drop table
      // var_dump('no other modules are using this table');
      $is_last_module_for_this_table = true;
    } else {
      //don't drop this table, other modules use it;
      $drop_table = false;
      foreach ($modules_sharing_table as $module_sharing_table) {
        //skip current module name
        if ($module_sharing_table->id !== $module->id) {
          $alert .= $module->table_name . ' is also used in module ' . $module_sharing_table->name . '<br>';
        }
      }
      $is_last_module_for_this_table = false;
    }

    //get all modules, skip current
    $modules_list = Modules::where('table_name', 'like', config('app.module_generator_prefix') . '%')->get();
    //foreach module load controller
    foreach ($modules_list as $key => $value) {
      //foreach column check if has a join with current module's table
      if (file_exists(app_path('Http/Controllers/' . str_replace('.', '', $value->controller) . '.php'))) {
        $response = file_get_contents(app_path('Http/Controllers/' . $value->controller . '.php'));
        $column_datas = extract_unit($response, "# START COLUMNS DO NOT REMOVE THIS LINE", "# END COLUMNS DO NOT REMOVE THIS LINE");
        $column_datas = str_replace('$this->', '$cb_', $column_datas);
        eval($column_datas);
      }
      //check if in the column definition there is a join with this table
      if (strpos(isset($column_datas) ? $column_datas : '', '"join"=>"' . $module->table_name)) {
        $drop_table = false;
        // if it's the last module for this table but another module have a join referring to this table,
        // then I can't drop the table. If I delete the module while not dropping the table,
        // the user would have no way of dropping or editing the table, therefore i forbid deleting the module too
        if ($is_last_module_for_this_table) {
          //keep module
          $drop_module = false;
          $alert .= $module->table_name . ' is also used in a join in module ' . $value->name . '<br>';
        }
      }
    }

    if (!$drop_module) {
      $message = "Didn't delete module " . $module->name;
      $message .= '<br><br>' . $alert;
      $message_type = 'warning';
      add_log_ch('delete module', $alert, 'log');
      //stop module delete
      CRUDBooster::redirect($url, $message, $message_type);
      exit;
    } else {
      add_log_ch('delete module', 'Delete module ' . $module->name, 'log');
    }

    if (isset($drop_table) && !empty($drop_table)) {
      //Drop table
      if (isset($module->table_name)  && !empty($module->table_name)) {
        if (Schema::hasTable($module->table_name)) {
          Schema::dropIfExists($module->table_name);
          add_log_ch('delete module', 'Drop table ' . $module->table_name, 'log');
        }
      }
    } else {
      $message = "Didn't delete table " . $module->table_name;
      $message .= '<br><br>' . $alert;
      $message_type = 'info';
      add_log_ch('delete module', 'Didn\'t drop table ' . $module->table_name, 'log');
    }

    $this->hook_before_delete($id);

    if (CRUDBooster::isColumnExists($this->table, 'deleted_at')) {
      DB::table($this->table)
        ->where($this->primary_key, $id)
        ->update(['deleted_at' => date('Y-m-d H:i:s')]);
    } else {
      DB::table($this->table)
        ->where($this->primary_key, $id)
        ->delete();
    }

    $this->hook_after_delete($id);

    if (empty($message)) {
      $message = trans("crudbooster.alert_delete_data_success");
      $message_type = 'success';
    }

    CRUDBooster::redirect($url, $message, $message_type);
  }

  function hook_before_delete($id)
  {
    //get module
    $module = DB::table('cms_moduls')
      ->where('id', $id)
      ->first();

    //On Cascade Delete Menu
    $menus = DB::table('cms_menus')
      ->where('path', 'like', '%' . $module->controller . '%')
      ->delete();

    

    //On Cascade Delete Controller
    @unlink(app_path('Http/Controllers/' . $module->controller . '.php'));

    //$module->delete();
  }

  public function getTableColumns($table)
  {
    $columns = CRUDBooster::getTableColumns($table);

    return response()->json($columns);
  }

  public function getCheckSlug($slug)
  {
    $check = DB::table('cms_moduls')->where('path', $slug)->count();
    $lastId = DB::table('cms_moduls')->max('id') + 1;

    return response()->json(['total' => $check, 'lastid' => $lastId]);
  }

  public function getAdd()
  {
    $this->cbLoader();

    $module = CRUDBooster::getCurrentModule();

    if (!CRUDBooster::isView() && $this->global_privilege == false) {
      CRUDBooster::insertLog(trans('crudbooster.log_try_view', ['module' => $module->name]));
      CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    return redirect()->route("ModulsControllerGetStep1");
  }

  public function getStep1($id = 0)
  {
    $this->cbLoader();

    $module = CRUDBooster::getCurrentModule();

    if (!CRUDBooster::isView() && $this->global_privilege == false) {
      CRUDBooster::insertLog(trans('crudbooster.log_try_view', ['module' => $module->name]));
      CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    $tables = CRUDBooster::listTables('mg');
    $tables_list = [];
    foreach ($tables as $tab) {
      foreach ($tab as $key => $value) {
        $label = $value;

        if (substr($label, 0, 4) == 'cms_' && $label != config('crudbooster.USER_TABLE')) {
          continue;
        }
        if ($label == 'migrations') {
          continue;
        }

        $tables_list[] = $value;
      }
    }

    $fontawesome = Fontawesome::getIcons();


    $row = CRUDBooster::first($this->table, ['id' => $id]);

    $custom = view('crudbooster::components.list_icon', compact('fontawesome', 'row'))->render();
    //dd($custom);
    $active_tab = 1;

    return view("crudbooster::module_generator.step1", compact("tables_list", "fontawesome", "row", "id", 'active_tab', 'custom'));
  }

  // triggered when on step1 click go to step2
  // create module
  public function postStep1()
  {
    $this->cbLoader();

    $module = CRUDBooster::getCurrentModule();

    if (!CRUDBooster::isView() && $this->global_privilege == false) {
      CRUDBooster::insertLog(trans('crudbooster.log_try_view', ['module' => $module->name]));
      CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    //module name
    $name = Request::get('name');
    $table_name = Request::get('table');
    $icon = Request::get('icon');
    $path = $table_name;

    if (!Request::get('id')) {
      //create new module
      $created_at = now();
      //$this->table equals cms_moduls
      $id = DB::table($this->table)->max('id') + 1; // id del nuovo modulo

      if ($table_name == 'new') {
        $table_name = ModuleHelper::sql_name_encode(config('app.module_generator_prefix') . $name);
        $path = $table_name;
      }

      if (
        DB::table('cms_moduls')
        ->where('name', $name)
        ->where('deleted_at', null)
        ->count()
      ) {
        $message = 'Sorry ' . $table_name . ' already exists, please choose a different module name';
        return redirect()->back()->with(['message' => $message, 'message_type' => 'warning']);
      }

      //create a controller for the new module
      $controller = CRUDBooster::generateController($table_name, $name);

      //cms_moduls
      DB::table($this->table)
        ->insert(compact(
          "controller",
          "name",
          "table_name",
          "icon",
          "path",
          "created_at",
          "id"
        ));

      //create menu
      if ($controller && Request::get('create_menu')) {
        $parent_menu_sort = DB::table('cms_menus')->where('parent_id', 0)->max('sorting') + 1;

        $id_cms_menus = DB::table('cms_menus')->insertGetId([
          'created_at' => date('Y-m-d H:i:s'),
          'name' => $name,
          'icon' => $icon,
          'path' => $controller . 'GetIndex',
          'type' => 'Route',
          'is_active' => 1,
          'id_cms_privileges' => CRUDBooster::myPrivilegeId(),
          'sorting' => $parent_menu_sort,
          'parent_id' => 0
        ]);
        $menu = Menu::find($id_cms_menus);
        $menu->assign_default_tenant();
        $menu->assign_default_group();
        DB::table('cms_menus_privileges')->insert(['id_cms_menus' => $id_cms_menus, 'id_cms_privileges' => CRUDBooster::myPrivilegeId()]);
      }

      $user_id_privileges = CRUDBooster::myPrivilegeId();
      DB::table('cms_privileges_roles')->insert([
        'id' => DB::table('cms_privileges_roles')->max('id') + 1,
        'id_cms_moduls' => $id,
        'id_cms_privileges' => $user_id_privileges,
        'is_visible' => 1,
        'is_create' => 1,
        'is_read' => 1,
        'is_edit' => 1,
        'is_delete' => 1,
      ]);

      //Refresh Session Roles
      $roles = DB::table('cms_privileges_roles')
        ->where('id_cms_privileges', CRUDBooster::myPrivilegeId())
        ->join('cms_moduls', 'cms_moduls.id', '=', 'id_cms_moduls')
        ->select('cms_moduls.name', 'cms_moduls.path', 'is_visible', 'is_create', 'is_read', 'is_edit', 'is_delete')
        ->where('cms_moduls.deleted_at', null)
        ->get();

      Session::put('admin_privileges_roles', $roles);


      //return redirect(Route("ModulsControllerGetStep2", ["id" => $id]));
      return redirect(Route("ModulsControllerGetStep2") . "/{$id}");
    } else {
      //update existing module
      $id = Request::get('id');
      DB::table($this->table)
        ->where('id', $id)
        ->update(compact(
          "name",
          "table_name",
          "icon",
          "path"
        ));

      $row = DB::table('cms_moduls')
        ->where('id', $id)
        ->first();

      if (file_exists(app_path('Http/Controllers/' . $row->controller . '.php'))) {
        $response = file_get_contents(app_path('Http/Controllers/' . str_replace('.', '', $row->controller) . '.php'));
      } else {
        $response = file_get_contents(__DIR__ . '/' . str_replace('.', '', $row->controller) . '.php');
      }

      if (strpos($response, "# START COLUMNS") !== true) {
        // return redirect()->back()->with(['message'=>'Sorry, is not possible to edit the module with Module Generator Tool. Prefix and or Suffix tag is missing !','message_type'=>'warning']);
      }
      //return redirect(Route("ModulsControllerGetStep2", ["id" => $id]));
      return redirect(Route("ModulsControllerGetStep2") . "/{$id}");
    }
  }

  //#RAMA
  public function getStep2($id)
  {
    $this->cbLoader();

    $module = CRUDBooster::getCurrentModule();



    if (!CRUDBooster::isView() && $this->global_privilege == false) {
      CRUDBooster::insertLog(trans('crudbooster.log_try_view', ['module' => $module->name]));
      CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    $module = Modules::find($id);
    //if $modules['table_name'] is not set, go back
    if (!isset($module['table_name'])) {
      return redirect()->back();
    }

    if ($module['table_name'] == 'new') {
      // creating new table
      $cb_form = array();
    } else {
      // skip table creation
      // return redirect(Route("ModulsControllerGetStep3", ["id" => $id]));

      // table edit
      $columns = CRUDBooster::getTableStructure($module['table_name']);
      $cb_form = $columns;
    }

    //column data types
    //TODO add more types
    $types = config('app.mg_valid_data_types');
    //TODO add PK NN AI
    //TODO add FK

    $data = array();
    $data['id'] = $id;
    $data['active_tab'] = 2;
    $data['cb_form'] = $cb_form;
    $data['table_name'] = $module->table_name;
    $data['table_exists'] = !empty($cb_form);
    $data['types'] = $types;
    $data['box_title'] = 'Table ' . str_replace(config('app.module_generator_prefix'), '', $module->table_name);

    return view('crudbooster::module_generator.step2', $data);
  }

  // after step 2 form submit create/update table
  public function postStep2()
  {
    $this->cbLoader();

    $request = Request::all();
    $id = $request['id'];
    $module = Modules::find($id);

    if (!CRUDBooster::isView() && $this->global_privilege == false) {
      CRUDBooster::insertLog(trans('crudbooster.log_try_view', ['module' => $module->name]));
      CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }
    $messages = $this->save_table($request);

    $data = array();
    $data["id"] = $id;
    $data["messages"] = $messages;

    //return redirect(Route("ModulsControllerGetStep3", $data));
    return redirect(Route("ModulsControllerGetStep3") . "/{$id}")->with($messages);
  }

  public function getStep3($id, $messages = '')
  {
    $this->cbLoader();

    $module = CRUDBooster::getCurrentModule();

    if (!CRUDBooster::isView() && $this->global_privilege == false) {
      CRUDBooster::insertLog(trans('crudbooster.log_try_view', ['module' => $module->name]));
      CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    $row = DB::table('cms_moduls')->where('id', $id)->first();

    if (!$row) {

      return redirect()->back();

    }

    $columns = CRUDBooster::getTableColumns($row->table_name);

    //if $columns is empty, go back
    if (empty($columns)) {
      return redirect()->back();
    }

    $columns_human_readable = array();
    foreach ($columns as $column) {
      $columns_human_readable[] = ModuleHelper::sql_name_decode($column);
    }

    $tables = CRUDBooster::listTables();
    $table_list = [];
    foreach ($tables as $tab) {
      foreach ($tab as $key => $value) {
        $label = $value;
        $table_list[] = $value;
      }
    }

    if (file_exists(app_path('Http/Controllers/' . str_replace('.', '', $row->controller) . '.php'))) {
      $response = file_get_contents(app_path('Http/Controllers/' . $row->controller . '.php'));
      $column_datas = extract_unit($response, "# START COLUMNS DO NOT REMOVE THIS LINE", "# END COLUMNS DO NOT REMOVE THIS LINE");
      $column_datas = str_replace('$this->', '$cb_', $column_datas);
      eval($column_datas);
    }

    $data = [];
    $data['id'] = $id;
    $data['columns'] = $columns;
    $data['columns_human_readable'] = $columns_human_readable;
    $data['table_list'] = $table_list;
    $data['cb_col'] = isset($cb_col) ? $cb_col : [];
    $data['active_tab'] = 3;
    $messages = explode(',', $messages);
    $data['messages'] = array();
    for ($i = 0; $i < count($messages) - 1;) {
      $type = $messages[$i];
      $content = $messages[$i + 1];
      $data['messages'][] = ['type' => $type, 'content' => $content];
      $i += 2;
    }

    return view('crudbooster::module_generator.step3', $data);
  }

  public function postStep3()
  {
    $this->cbLoader();

    $module = CRUDBooster::getCurrentModule();

    if (!CRUDBooster::isView() && $this->global_privilege == false) {
      CRUDBooster::insertLog(trans('crudbooster.log_try_view', ['module' => $module->name]));
      CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

  /**Prende i campi di input*/
    $column = Request::input('column');
    $name = Request::input('name');
    $join_table = Request::input('join_table');
    $join_field = Request::input('join_field');
    $is_image = Request::input('is_image');
    $is_download = Request::input('is_download');
    $callbackphp = Request::input('callbackphp');
    $query = Request::input('query');
    $id = Request::input('id');
    $width = Request::input('width');


    $row = DB::table('cms_moduls')->where('id', $id)->first();

    $i = 0;
    $script_cols = [];
    foreach ($column as $col) {

      if (!$name[$i]) {
        $i++;
        continue;
      }

      $script_cols[$i] = "\t\t\t" . '$this->col[] = ["label"=>"' . $col . '","name"=>"' . $name[$i] . '"';

      if ($join_table[$i] && $join_field[$i]) {
        $script_cols[$i] .= ',"join"=>"' . $join_table[$i] . ',' . $join_field[$i] . '"';
      }

      if ($is_image[$i]) {
        $script_cols[$i] .= ',"image"=>true';
      }

      if (isset($id_download[$i])) {
        $script_cols[$i] .= ',"download"=>true';
      }

      if ($width[$i]) {
        $script_cols[$i] .= ',"width"=>"' . $width[$i] . '"';
      }

      if ($callbackphp[$i]) {
        $script_cols[$i] .= ',"callback_php"=>\'' . $callbackphp[$i] . '\'';
      }

      if ($query[$i]) {
        $script_cols[$i] .= ',"query"=>\'' . addslashes($query[$i]) . '\'';
      }

      $script_cols[$i] .= "];";

      $i++;
    }

    $scripts = implode("\n", $script_cols);
    $raw = file_get_contents(app_path('Http/Controllers/' . $row->controller . '.php'));
    $raw = explode("# START COLUMNS DO NOT REMOVE THIS LINE", $raw);
    $rraw = explode("# END COLUMNS DO NOT REMOVE THIS LINE", $raw[1]);

    $file_controller = trim($raw[0]) . "\n\n";
    $file_controller .= "\t\t\t# START COLUMNS DO NOT REMOVE THIS LINE\n";
    $file_controller .= "\t\t\t" . '$this->col = [];' . "\n";
    $file_controller .= $scripts . "\n";
    $file_controller .= "\t\t\t# END COLUMNS DO NOT REMOVE THIS LINE\n\n";
    $file_controller .= "\t\t\t" . trim($rraw[1]);

    file_put_contents(app_path('Http/Controllers/' . $row->controller . '.php'), $file_controller);

    //return redirect(Route("ModulsControllerGetStep4", ["id" => $id]));
    return redirect(Route("ModulsControllerGetStep4") . "/{$id}");
  }

  public function getStep4($id)
  {
    $this->cbLoader();

    $module = CRUDBooster::getCurrentModule();

    if (!CRUDBooster::isView() && $this->global_privilege == false) {
      CRUDBooster::insertLog(trans('crudbooster.log_try_view', ['module' => $module->name]));
      CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    $row = DB::table('cms_moduls')->where('id', $id)->first();

    if (!$row) {

      return redirect()->back();

    }

    if (!$row) {

      return redirect()->back();

    }

    $columns = CRUDBooster::getTableColumns($row->table_name);

    if (file_exists(app_path('Http/Controllers/' . $row->controller . '.php'))) {
      $response = file_get_contents(app_path('Http/Controllers/' . $row->controller . '.php'));
      $column_datas = extract_unit($response, "# START FORM DO NOT REMOVE THIS LINE", "# END FORM DO NOT REMOVE THIS LINE");
      $column_datas = str_replace('$this->', '$cb_', $column_datas);
      eval($column_datas);
    }

    $types = [];
    foreach (glob(base_path('packages/crocodicstudio/crudbooster/src/views/default/type_components') . '/*', GLOB_ONLYDIR) as $dir) {
      $types[] = basename($dir);
    }
    $active_tab = 4;

    return view('crudbooster::module_generator.step4', compact('columns', 'cb_form', 'types', 'id', 'active_tab'));
  }

  public function getTypeInfo($type = 'text')
  {
    header("Content-Type: application/json");
    echo file_get_contents(base_path('packages/crocodicstudio/crudbooster/src/views/default/type_components/' . $type . '/info.json'));
  }

  public function postStep4()
  {
    $this->cbLoader();

    $post = Request::all();
    $id = $post['id'];

    $label = $post['label'];
    $name = $post['name'];
    $width = $post['width'];
    $type = $post['type'];
    $option = isset($post['option']) ? $post['option'] : [];
    $validation = $post['validation'];

    $row = DB::table('cms_moduls')->where('id', $id)->first();

    $i = 0;
    $script_form = [];
    foreach ($label as $l) {

      if ($l != '') {

        $form = [];
        $form['label'] = $l;
        $form['name'] = $name[$i];
        $form['type'] = $type[$i];
        $form['validation'] = $validation[$i];
        $form['width'] = $width[$i];
        if (isset($option[$i])) {
          $form = array_merge($form, $option[$i]);
        }

        foreach ($form as $k => $f) {
          if ($f == '') {
            unset($form[$k]);
          }
        }

        $script_form[$i] = "\t\t\t" . '$this->form[] = ' . min_var_export($form) . ";";
      }

      $i++;
    }

    $scripts = implode("\n", $script_form);
    $raw = file_get_contents(app_path('Http/Controllers/' . $row->controller . '.php'));
    $raw = explode("# START FORM DO NOT REMOVE THIS LINE", $raw);
    $rraw = explode("# END FORM DO NOT REMOVE THIS LINE", $raw[1]);

    $top_script = trim($raw[0]);
    $current_scaffolding_form = trim($rraw[0]);
    $bottom_script = trim($rraw[1]);

    //IF FOUND OLD, THEN CLEAR IT
    if (strpos($bottom_script, '# OLD START FORM') !== false) {
      $line_end_count = strlen('# OLD END FORM');
      $line_start_old = strpos($bottom_script, '# OLD START FORM');
      $line_end_old = strpos($bottom_script, '# OLD END FORM') + $line_end_count;
      $get_string = substr($bottom_script, $line_start_old, $line_end_old);
      $bottom_script = str_replace($get_string, '', $bottom_script);
    }

    //ARRANGE THE FULL SCRIPT
    $file_controller = $top_script . "\n\n";
    $file_controller .= "\t\t\t# START FORM DO NOT REMOVE THIS LINE\n";
    $file_controller .= "\t\t\t" . '$this->form = [];' . "\n";
    $file_controller .= $scripts . "\n";
    $file_controller .= "\t\t\t# END FORM DO NOT REMOVE THIS LINE\n\n";

    //CREATE A BACKUP SCAFFOLDING TO OLD TAG
    if ($current_scaffolding_form) {
      $current_scaffolding_form = preg_split("/\\r\\n|\\r|\\n/", $current_scaffolding_form);
      foreach ($current_scaffolding_form as &$c) {
        $c = "\t\t\t//" . trim($c);
      }
      $current_scaffolding_form = implode("\n", $current_scaffolding_form);

      $file_controller .= "\t\t\t# OLD START FORM\n";
      $file_controller .= $current_scaffolding_form . "\n";
      $file_controller .= "\t\t\t# OLD END FORM\n\n";
    }

    $file_controller .= "\t\t\t" . trim($bottom_script);

    //CREATE FILE CONTROLLER
    file_put_contents(app_path('Http/Controllers/' . $row->controller . '.php'), $file_controller);

    //return redirect(Route("ModulsControllerGetStep5", ["id" => $id]));
    return redirect(Route("ModulsControllerGetStep5") . "/{$id}");
  }

  public function getStep5($id)
  {
    $this->cbLoader();

    $module = CRUDBooster::getCurrentModule();

    if (!CRUDBooster::isView() && $this->global_privilege == false) {
      CRUDBooster::insertLog(trans('crudbooster.log_try_view', ['module' => $module->name]));
      CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    $row = DB::table('cms_moduls')->where('id', $id)->first();

    if (!$row) {

      return redirect()->back();

    }

    $data = [];
    $data['id'] = $id;
    if (file_exists(app_path('Http/Controllers/' . $row->controller . '.php'))) {
      $response = file_get_contents(app_path('Http/Controllers/' . $row->controller . '.php'));
      $column_datas = extract_unit($response, "# START CONFIGURATION DO NOT REMOVE THIS LINE", "# END CONFIGURATION DO NOT REMOVE THIS LINE");
      $column_datas = str_replace('$this->', '$data[\'cb_', $column_datas);
      $column_datas = str_replace(' = ', '\'] = ', $column_datas);
      $column_datas = str_replace([' ', "\t"], '', $column_datas);
      eval($column_datas);
    }
    $data['active_tab'] = 5;

    return view('crudbooster::module_generator.step5', $data);
  }

  public function postStep5()
  {
    $this->cbLoader();
    $id = Request::input('id');
    $row = DB::table('cms_moduls')->where('id', $id)->first();

    $post = Request::all();

    $post['table'] = $row->table_name;

    $script_config = [];
    $exception = ['_token', 'id', 'submit'];
    $i = 0;
    foreach ($post as $key => $val) {
      if (in_array($key, $exception)) {
        continue;
      }

      if ($val != 'true' && $val != 'false') {
        $value = '"' . $val . '"';
      } else {
        $value = $val;
      }

      // if($key == 'orderby') {
      // 	$value = ;
      // }

      $script_config[$i] = "\t\t\t" . '$this->' . $key . ' = ' . $value . ';';
      $i++;
    }

    $scripts = implode("\n", $script_config);
    $raw = file_get_contents(app_path('Http/Controllers/' . $row->controller . '.php'));
    $raw = explode("# START CONFIGURATION DO NOT REMOVE THIS LINE", $raw);
    $rraw = explode("# END CONFIGURATION DO NOT REMOVE THIS LINE", $raw[1]);

    $file_controller = trim($raw[0]) . "\n\n";
    $file_controller .= "\t\t\t# START CONFIGURATION DO NOT REMOVE THIS LINE\n";
    $file_controller .= $scripts . "\n";
    $file_controller .= "\t\t\t# END CONFIGURATION DO NOT REMOVE THIS LINE\n\n";
    $file_controller .= "\t\t\t" . trim($rraw[1]);

    file_put_contents(app_path('Http/Controllers/' . $row->controller . '.php'), $file_controller);

    // #RAMA sposta creazione tabella qui?

    return redirect()->route('ModulsControllerGetIndex')->with(['message' => trans('crudbooster.alert_update_data_success'), 'message_type' => 'success']);
  }

  public function postAddSave()
  {
    $this->cbLoader();

    if (!CRUDBooster::isCreate() && $this->global_privilege == false) {
      CRUDBooster::insertLog(trans('crudbooster.log_try_add_save', [
        'name' => Request::input($this->title_field),
        'module' => CRUDBooster::getCurrentModule()->name,
      ]));
      CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
    }

    $this->validation();
    $this->input_assignment();

    //Generate Controller
    $route_basename = basename(Request::get('path'));
    if ($this->arr['controller'] == '') {
      $this->arr['controller'] = CRUDBooster::generateController(Request::get('table_name'), $route_basename);
    }

    $this->arr['created_at'] = date('Y-m-d H:i:s');
    $this->arr['id'] = DB::table($this->table)->max('id') + 1;

    DB::table($this->table)->insert($this->arr);

    //Insert Menu
    if ($this->arr['controller']) {
      $parent_menu_sort = DB::table('cms_menus')->where('parent_id', 0)->max('sorting') + 1;
      $parent_menu_id = DB::table('cms_menus')->max('id') + 1;
      DB::table('cms_menus')->insert([
        'id' => $parent_menu_id,
        'created_at' => date('Y-m-d H:i:s'),
        'name' => $this->arr['name'],
        'icon' => $this->arr['icon'],
        'path' => '#',
        'type' => 'URL External',
        'is_active' => 1,
        'id_cms_privileges' => CRUDBooster::myPrivilegeId(),
        'sorting' => $parent_menu_sort,
        'parent_id' => 0,
      ]);
      DB::table('cms_menus')->insert([
        'id' => DB::table('cms_menus')->max('id') + 1,
        'created_at' => date('Y-m-d H:i:s'),
        'name' => trans("crudbooster.text_default_add_new_module", ['module' => $this->arr['name']]),
        'icon' => 'fa fa-plus',
        'path' => $this->arr['controller'] . 'GetAdd',
        'type' => 'Route',
        'is_active' => 1,
        'id_cms_privileges' => CRUDBooster::myPrivilegeId(),
        'sorting' => 1,
        'parent_id' => $parent_menu_id,
      ]);
      DB::table('cms_menus')->insert([
        'id' => DB::table('cms_menus')->max('id') + 1,
        'created_at' => date('Y-m-d H:i:s'),
        'name' => trans("crudbooster.text_default_list_module", ['module' => $this->arr['name']]),
        'icon' => 'fa fa-bars',
        'path' => $this->arr['controller'] . 'GetIndex',
        'type' => 'Route',
        'is_active' => 1,
        'id_cms_privileges' => CRUDBooster::myPrivilegeId(),
        'sorting' => 2,
        'parent_id' => $parent_menu_id,
      ]);
    }

    $id_modul = $this->arr['id'];

    $user_id_privileges = CRUDBooster::myPrivilegeId();
    DB::table('cms_privileges_roles')->insert([
      'id' => DB::table('cms_privileges_roles')->max('id') + 1,
      'id_cms_moduls' => $id_modul,
      'id_cms_privileges' => $user_id_privileges,
      'is_visible' => 1,
      'is_create' => 1,
      'is_read' => 1,
      'is_edit' => 1,
      'is_delete' => 1,
    ]);

    //Refresh Session Roles
    $roles = DB::table('cms_privileges_roles')->where('id_cms_privileges', CRUDBooster::myPrivilegeId())->join('cms_moduls', 'cms_moduls.id', '=', 'id_cms_moduls')->select('cms_moduls.name', 'cms_moduls.path', 'is_visible', 'is_create', 'is_read', 'is_edit', 'is_delete')->get();
    Session::put('admin_privileges_roles', $roles);

    $ref_parameter = Request::input('ref_parameter');
    if (Request::get('return_url')) {
      CRUDBooster::redirect(Request::get('return_url'), trans("crudbooster.alert_add_data_success"), 'success');
    } else {
      if (Request::get('submit') == trans('crudbooster.button_save_more')) {
        CRUDBooster::redirect(CRUDBooster::mainpath('add'), trans("crudbooster.alert_add_data_success"), 'success');
      } else {
        CRUDBooster::redirect(CRUDBooster::mainpath(), trans("crudbooster.alert_add_data_success"), 'success');
      }
    }
  }

  public function postEditSave($id, $validate = null)
  {
    $this->cbLoader();

    $row = DB::table($this->table)->where($this->primary_key, $id)->first();

    if (!CRUDBooster::isUpdate() && $this->global_privilege == false) {
      CRUDBooster::insertLog(trans("crudbooster.log_try_add", ['name' => $row->{$this->title_field}, 'module' => CRUDBooster::getCurrentModule()->name]));
      CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    $this->validation();
    $this->input_assignment();

    //Generate Controller
    $route_basename = basename(Request::get('path'));
    if ($this->arr['controller'] == '') {
      $this->arr['controller'] = CRUDBooster::generateController(Request::get('table_name'), $route_basename);
    }

    if (isset($_POST['module_tenant_enabler'])) {
      ModuleHelper::update_enabled_tenants($_POST['module_tenant_enabler']);
    }



    DB::table($this->table)->where($this->primary_key, $id)->update($this->arr);

    //Refresh Session Roles
    $roles = DB::table('cms_privileges_roles')->where('id_cms_privileges', CRUDBooster::myPrivilegeId())->join('cms_moduls', 'cms_moduls.id', '=', 'id_cms_moduls')->select('cms_moduls.name', 'cms_moduls.path', 'is_visible', 'is_create', 'is_read', 'is_edit', 'is_delete')->get();
    Session::put('admin_privileges_roles', $roles);

    CRUDBooster::redirect(Request::server('HTTP_REFERER'), trans('crudbooster.alert_update_data_success'), 'success');
  }

  /*
    * Create a new database table
    *
    *  @param Modules instance of the Modules class containing the new module being generated
    *
    */
  private function save_table($request)
  {

    if (!CRUDBooster::isSuperadmin()) {
      CRUDBooster::insertLog(trans('crudbooster.log_try_view', ['module' => $module->name]));
      CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }
    $id = $request['id'];
    $module = Modules::find($id);

    $table_name = $module->table_name;

    if (substr($module->table_name, 0, strlen(config('app.reserved_tables_prefix'))) === config('app.reserved_tables_prefix')) {
      // editing reserved tables is forbidden
      add_log_ch('mg save table', 'editing reserved tables is forbidden ' . $table_name . ' starts with ' . config('app.reserved_tables_prefix'), 'error');
      $message['type'] = 'danger';
      $message['content'] = 'editing reserved tables is forbidden';
      $messages = $message['type'] . ',' . $message['content'] . ',';
      return $messages;
    }

    if (ModuleHelper::is_manually_generated($table_name)) {
      //add table name prefix to new tables
      if (!substr($module->table_name, 0, strlen(config('app.module_generator_prefix'))) === config('app.module_generator_prefix')) {
        $table_name = config('app.module_generator_prefix') . $table_name;
      }
    }

    //table name transformation
    $table_name = ModuleHelper::sql_name_encode($table_name);

    $table_exist = Schema::hasTable($table_name);

    $dynamic_table = new DynamicTable;
    $dynamic_table->name = $table_name;
    $dynamic_columns = array();

    //if table doesn't exists..
    if (!$table_exist) {
      //..create new table
      foreach ($request['name'] as $loop_index => $dynamic_column_name) {
        if ($request['name'][$loop_index] == '') {
          // empty row
          continue;
        }
        // create new column
        $column = new DynamicColumn;
        if (ctype_digit($dynamic_column_name)) {
          // error digit only column name is invalid
          add_log_ch('mg create table add column', 'digit only column name is invalid creating table ' . $table_name . ' column ' . $dynamic_column_name, 'error');
          $message['type'] = 'danger';
          $message['content'] = 'Digit only column name is not accepted';
          $messages = $message['type'] . ',' . $message['content'] . ',';
          return $messages;
        }
        //column name transformation
        $dynamic_column = ModuleHelper::sql_name_encode($dynamic_column_name);
        //dd($dynamic_column);

        $column->name = $dynamic_column_name;
        if (Schema::hasColumn($table_name, $column->name)) {
          // error Duplicate column name
          add_log_ch('mg create table add column', 'Duplicate column name ' . $column->name, 'error');
          $message['type'] = 'danger';
          $message['content'] = 'Duplicate column name';
          $messages = $message['type'] . ',' . $message['content'] . ',';
          return $messages;
        }
        switch ($request['type'][$loop_index]) {
          case 'text':
            $column->type = 'string';
            break;
          case 'number':
            $column->type = 'integer';
            break;
          case 'boolean':
            $column->type = 'boolean';
            break;

          default:
            $column->type = 'string';
            break;
        }
        $column->validation = '';
        $column->sorting_order = 1; //insert after?
        $column->isNullable = 1; // true / false
        $column->hasAI = 0; //Auto Increment true / false
        $column->isPrimaryKey = 0; // true / false
        $column->isRequired = 0; // true / false
        if (empty($request['size'][$loop_index])) {
          //set default data size based on type
          switch ($request['type'][$loop_index]) {
            case 'text':
              $column->size = 255;
              break;
            case 'number':
              $column->size = 11;
              break;
            case 'boolean':
              $column->size = 1;
              break;

            default:
              //shouldn't be applied, never
              $column->size = 1;
              break;
          }
        } else {
          //set user custom size
          $column->size = $request['size'][$loop_index];
        }

        $dynamic_columns[] = $column;
      }

      $dynamic_table->columns = $dynamic_columns;

      //TODO validate table name: check protected table names
      $result = Schema::create($table_name, function (Blueprint $table) use ($dynamic_columns) {
        //dd($table);
        foreach ($dynamic_columns as $key => $dynamic_column) {
          $type = $dynamic_column->type;
          $columnname = ModuleHelper::sql_name_encode($dynamic_column->name);
          // $table->call_dynamic_method($dynamic_column->type);
          if ($type == 'integer') {
            //integer defaults to autoincrement without second parameter set to false if length is set as third attribute of the integer method
            $table->integer("{$columnname}")->length($dynamic_column->size)->nullable();
          } else {
            $table->$type("{$columnname}", "{$dynamic_column->size}")->nullable();
          }
        }
        //$table->defaults();
        $table->increments('id');
        $table->unsignedInteger('group')->nullable();
        $table->unsignedInteger('tenant')->nullable();
        $table->dateTime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
        $table->unsignedInteger('created_by')->nullable();
        $table->dateTime('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        $table->unsignedInteger('updated_by')->nullable();
        $table->dateTime('deleted_at')->nullable();
        $table->unsignedInteger('deleted_by')->nullable();
      });

      //update module
      if ($module->table_name == 'new') {
        $module->table_name = $table_name;
        $module->name = $table_name;
        $module->path = $table_name;
        $module->save();
      }
    } else {
      //edit table

      $existing_table = CRUDBooster::getTableStructure($table_name);

      foreach ($request['name'] as $loop_index => $dynamic_column_name) {
        if ($request['name'][$loop_index] == '') {
          // empty row
          continue;
        }

        // save column object
        $column = new DynamicColumn;
        $column->name = ModuleHelper::sql_name_encode($dynamic_column_name);
        switch ($request['type'][$loop_index]) {
          case 'text':
            $column->type = 'string';
            break;
          case 'number':
            $column->type = 'integer';
            break;
          case 'boolean':
            $column->type = 'boolean';
            break;

          default:
            $column->type = 'string';
            break;
        }
        $column->validation = '';
        $column->sorting_order = 1; //insert after?
        $column->isNullable = 1; // true / false
        $column->hasAI = 0; //Auto Increment true / false
        $column->isPrimaryKey = 0; // true / false
        $column->isRequired = 0; // true / false
        if (empty($request['size'][$loop_index])) {
          //set default data size based on type
          switch ($request['type'][$loop_index]) {
            case 'text':
              $column->size = 255;
              break;
            case 'number':
              $column->size = 11;
              break;
            case 'boolean':
              $column->size = 1;
              break;

            default:
              //shouldn't be applied, never
              $column->size = 1;
              break;
          }
        } else {
          //set user custom size
          $column->size = $request['size'][$loop_index];
        }

        $dynamic_columns[] = $column;

        $index = $request['index'][$loop_index];


        // if $index doesn't exist in the table..
        if (!array_key_exists($index, $existing_table)) {
          // ..add new column

          if (ctype_digit($column->name)) {
            // error digit only column name is invalid
            add_log_ch('mg edit table add column', 'digit only column name is invalid creating table ' . $table_name . ' column ' . $dynamic_column->name, 'error');
            $message['type'] = 'danger';
            $message['content'] = 'Digit only column name is not accepted';
            $messages = $message['type'] . ',' . $message['content'] . ',';
            return $messages;
          }
          if (Schema::hasColumn($table_name, $column->name)) {
            // error Duplicate column name
            add_log_ch('mg edit table add column', 'Duplicate column name ' . $column->name, 'error');
            $message['type'] = 'danger';
            $message['content'] = 'Duplicate column name';
            $messages = $message['type'] . ',' . $message['content'] . ',';
            return $messages;
          }
          if ($index == 0) {
            $col_index = 0;
          } else {
            $col_index = $index - 1;
          }
          $after = isset($existing_table[$col_index]['name']) ? $existing_table[$col_index]['name'] : 'id';
          //add new column to existing table
          $result = Schema::table($table_name, function (Blueprint $table) use ($column, $after) {
            $type = $column->type;
            $columnname = ModuleHelper::sql_name_encode($column->name);
            if ($type == 'integer') {
              //integer defaults to autoincrement without second parameter set to false if length is set as third attribute of the integer method
              $table->integer("{$columnname}")->length($column->size)->nullable()->after($after);
            } else {
              $table->$type("{$columnname}", $column->size)->nullable()->after($after);
            }
          });
          $description = 'add column ' . $column->name . ' after ' . $existing_table[$col_index]['name'];
          add_log_ch('mg edit table add column', $description);

          // reload table to detect multiple new columns and insert them in proper order
          $existing_table = CRUDBooster::getTableStructure($table_name);
        }
        //TODO sort

        // if request column name at index $loop_index is not equal table column name at the same index..
        elseif ($request['name'][$loop_index] !== $existing_table[$index]['name']) {
          // ..rename column
          $source = $existing_table[$index]['name'];
          $target = $request['name'][$loop_index];
          if (in_array($target, config('app.reserved_column_names')) or Schema::hasColumn($table_name, $target)) {
            // invalid column name
            add_log_ch('mg edit table rename column', 'invalid target column name. Renaming ' . $source . ' into ' . $target, 'error');
            $message['type'] = 'danger';
            $message['content'] = 'Invalid target column name. Renaming ' . $source . ' into ' . $target;
            $messages = $message['type'] . ',' . $message['content'] . ',';
            return $messages;
          }
          if (!Schema::hasColumn($table_name, $source)) {
            // error source column not found
            add_log_ch('mg edit table rename column', 'source column not found. Renaming ' . $source . ' into ' . $target, 'error');
            $message['type'] = 'danger';
            $message['content'] = 'Column not found renaming ' . $source . ' into ' . $target;
            $messages = $message['type'] . ',' . $message['content'] . ',';
            return $messages;
          }
          if (ctype_digit($target)) {
            // error digit only column name is invalid
            add_log_ch('mg edit table rename column', 'digit only column name is invalid. Renaming ' . $source . ' into ' . $target, 'error');
            $message['type'] = 'danger';
            $message['content'] = 'Digit only column name is not accepted';
            $messages = $message['type'] . ',' . $message['content'] . ',';
            return $messages;
          }
          $result = Schema::table($table_name, function (Blueprint $table) use ($source, $target) {
            $target = ModuleHelper::sql_name_encode($target);
            $table->renameColumn($source, $target);
          });
          add_log_ch('mg edit table rename column', 'Rename ' . $source . ' into ' . $target);
          //TODO reload table?
          $existing_table = CRUDBooster::getTableStructure($table_name);
        }

        if ($request['type'][$loop_index] != $existing_table[$index]['type']) {
          $result = Schema::table($table_name, function (Blueprint $table) use ($column) {
            $type = $column->type;
            $columnname = ModuleHelper::sql_name_encode($column->name);
            $table->$type("{$columnname}")->change();
          });
          add_log_ch('mg edit table change column data type', 'Column ' . $columnname . ' from ' . $existing_table[$index]['type'] . ' to ' . $column->type);
        }

        if ($request['size'][$loop_index] != $existing_table[$index]['size']) {
          $result = Schema::table($table_name, function (Blueprint $table) use ($column) {
            $type = $column->type;
            $columnname = ModuleHelper::sql_name_encode($column->name);
            if ($type == 'integer') {
              //integer defaults to autoincrement without second parameter set to false if length is set as third attribute of the integer method
              $table->integer("{$columnname}")->length($column->size)->change();
            } else {
              $table->$type("{$columnname}", $column->size)->change();
            }
          });
          add_log_ch('mg edit table change column data size', 'Column ' . $column->name. ' from ' . $existing_table[$index]['type'] . ' to ' . $column->type);
        }
      } //end loop through request columns

      $dynamic_table->columns = $dynamic_columns;

      //loop through existing table columns
      foreach ($existing_table as $key => $value) {
        // if column index is missing in the request..
        if (!in_array($key, $request['index'])) {
          // ..delete column
          if (!Schema::hasColumn($table_name, $value['name'])) {
            // error column not found
            add_log_ch('mg edit table drop column', 'error column ' . $value['name'] . ' not found', 'error');
            $message['type'] = 'danger';
            $message['content'] = 'column ' . $value['name'] . ' not found';
            $messages = $message['type'] . ',' . $message['content'] . ',';
            return $messages;
          } else {
            $result = Schema::table($table_name, function (Blueprint $table) use ($value) {

              $table->dropColumn("{$value['name']}");
            });
            add_log_ch('mg edit table drop column', 'delete column ' . $value['name']);
          }
        }
      }

      //TODO update table: sort columns
    }

    if (empty($messages)) {
      $message['type'] = 'success';
      $message['content'] = 'Database updated';
      $messages = $message['type'] . ',' . $message['content'] . ',';
    }

    return $messages;
  }
}
