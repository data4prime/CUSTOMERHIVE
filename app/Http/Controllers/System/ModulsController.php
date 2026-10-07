<?php

namespace App\Http\Controllers\System;

use crocodicstudio\crudbooster\controllers\CBController;

use CRUDBooster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Excel;
use Illuminate\Support\Facades\PDF;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use App\Helpers\Fontawesome;
use App\Helpers\ModuleGeneratorList;
use App\Helpers\ModuleGeneratorFields;
use App\Helpers\ModuleGeneratorLayout;
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
  	                  var \$resp = $('<span><i style=\"margin-top:5px\" class=\"float-end ' + $(originalOption).val() + '\"></i> ' + $(originalOption).data('label') + '</span>');
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
      'icon' => 'bi bi-wrench',
      'url' => CRUDBooster::mainpath('step1') . '/[id]',
      "showIf" => "[is_protected] == 0",
      'color' => 'primary',
    ];

    $this->addaction[] = [
      'label' => 'Export',
      'icon' => 'bi bi-download',
      'url' => CRUDBooster::mainpath('export') . '/[id]',
      "showIf" => "[is_protected] == 0",
      'color' => 'default',
    ];

    $this->index_button[] = ['label' => 'Generate New Module', 'icon' => 'bi bi-plus-lg', 'url' => CRUDBooster::mainpath('step1'), 'color' => 'success'];
    $this->index_button[] = ['label' => 'Import Module', 'icon' => 'bi bi-upload', 'url' => CRUDBooster::mainpath('import'), 'color' => 'info'];
  }

  // Modifica del modulo dal form standard: l'icona va anche sulle voci di menu collegate
  public function hook_after_edit($id)
  {
    \App\Helpers\MenuHelper::sync_module_icon($id);
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
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    $page_title = 'Edit Module Generator';

    $module = Modules::find($id);

    $page_menu = Route::getCurrentRoute()->getActionName();

    $tenants = Tenant::all();

    return view('crudbooster::module_generator.edit', compact('row', 'module', 'tenants', 'page_title', 'page_menu'));
  }

  /**
   * Dettaglio di un modulo: stessi controlli di accesso del dettaglio standard
   * (CBController::getDetail), vista dedicata con icona e matrice dei tenant.
   */
  public function getDetail($id)
  {
    $this->cbLoader();

    $row = DB::table($this->table)->where($this->primary_key, $id)->first();

    if (!$row || !ModuleHelper::can_view($this, $row)) {
      CRUDBooster::insertLog(trans("crudbooster.log_try_view", [
        'name' => $this->table,
        'module' => CRUDBooster::getCurrentModule()->name,
      ]));
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    $module = CRUDBooster::getCurrentModule();
    $page_menu = Route::getCurrentRoute()->getActionName();
    $page_title = trans("crudbooster.detail_data_page_title", ['module' => $module->name, 'name' => $row->{strtolower($this->title_field)}]);
    $tenants = Tenant::all();

    return view('crudbooster::module_generator.detail', compact('row', 'tenants', 'page_menu', 'page_title'));
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
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
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

    return CRUDBooster::redirect(Request::server('HTTP_REFERER'), trans('crudbooster.alert_update_data_success'), 'success');
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

    if (!$module) {
      return redirect()->back();
    }

    // hook_query_index() (usato da getIndex()) nasconde i moduli protetti
    // (is_protected=1) dalla lista, ma getDelete() carica la riga per id
    // senza rifiltrare - enumerando gli id si poteva cancellare/rompere un
    // modulo di sistema (riga cms_moduls + file controller unlinkato in
    // hook_before_delete() + voci di menu), bypassando il filtro della
    // lista. Vedi docs/refactoring/068.
    if ($module->is_protected) {
      CRUDBooster::insertLog(trans("crudbooster.log_try_delete", [
        'name' => $module->{$this->title_field},
        'module' => CRUDBooster::getCurrentModule()->name,
      ]));
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    if (!CRUDBooster::isDelete() && $this->global_privilege == false || $this->button_delete == false) {
      CRUDBooster::insertLog(trans("crudbooster.log_try_delete", [
        'name' => $module->{$this->title_field},
        'module' => CRUDBooster::getCurrentModule()->name,
      ]));
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    //insert log
    CRUDBooster::insertLog(trans("crudbooster.log_delete", ['name' => $module->{$this->title_field}, 'module' => CRUDBooster::getCurrentModule()->name]));

    // inizializzata: piu' sotto viene solo concatenata (.=), e in PHP 8 una
    // variabile non definita diventa un'eccezione in Laravel
    $alert = '';
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
      return CRUDBooster::redirect($url, $message, $message_type);
    } else {
      add_log_ch('delete module', 'Delete module ' . $module->name, 'log');
    }

    // Prima la parte su DB (menu, permessi, tenant, riga cms_moduls) in
    // un'unica transazione, poi - fuori, perche' il DDL in MySQL fa commit
    // implicito - il drop della tabella e l'unlink del controller. Se un
    // passo su DB fallisce non si perde ne' la tabella ne' il controller;
    // se fallisce il drop, il modulo e' gia' sparito ma i dati restano
    // recuperabili. Prima il drop veniva per primo e non c'era transazione.
    // Vedi docs/refactoring/202.
    DB::transaction(function () use ($id, $module) {
      $this->cleanupModuleRecords($module);

      if (CRUDBooster::isColumnExists($this->table, 'deleted_at')) {
        DB::table($this->table)
          ->where($this->primary_key, $id)
          ->update(['deleted_at' => date('Y-m-d H:i:s')]);
      } else {
        DB::table($this->table)
          ->where($this->primary_key, $id)
          ->delete();
      }
    });

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

    $this->removeModuleController($module);

    $this->refreshSessionPrivilegesRoles();

    $this->hook_after_delete($id);

    if (empty($message)) {
      $message = trans("crudbooster.alert_delete_data_success");
      $message_type = 'success';
    }

    return CRUDBooster::redirect($url, $message, $message_type);
  }

  /**
   * Usato dall'azione di massa di CBController::postActionSelected()
   * (riceve un array di id); getDelete() usa direttamente
   * cleanupModuleRecords() + removeModuleController() nell'ordine giusto.
   */
  function hook_before_delete($id)
  {
    foreach ((array) $id as $single_id) {
      $module = DB::table('cms_moduls')
        ->where('id', $single_id)
        ->first();

      if (!$module) {
        continue;
      }

      DB::transaction(function () use ($module) {
        $this->cleanupModuleRecords($module);
      });
      $this->removeModuleController($module);
    }
  }

  /**
   * Righe collegate a un modulo che sta per essere eliminato: menu (e i loro
   * pivot privilegi/tenant/gruppi), permessi per ruolo e abilitazione tenant.
   * La riga cms_moduls resta (soft delete) e il suo id non viene riusato
   * (vedi postStep1), ma senza questa pulizia i residui restavano nel DB.
   * Vedi docs/refactoring/202.
   */
  private function cleanupModuleRecords($module)
  {
    $menu_ids = $this->moduleMenuIds($module);

    if (!empty($menu_ids)) {
      DB::table('cms_menus_privileges')->whereIn('id_cms_menus', $menu_ids)->delete();
      DB::table('menu_tenants')->whereIn('menu_id', $menu_ids)->delete();
      DB::table('menu_groups')->whereIn('menu_id', $menu_ids)->delete();
      // un eventuale sottomenu non va perso ne' lasciato appeso a un padre
      // inesistente: passa al livello principale
      DB::table('cms_menus')->whereIn('parent_id', $menu_ids)->update(['parent_id' => 0]);
      DB::table('cms_menus')->whereIn('id', $menu_ids)->delete();
    }

    DB::table('cms_privileges_roles')->where('id_cms_moduls', $module->id)->delete();
    DB::table('module_tenants')->where('module_id', $module->id)->delete();
  }

  /**
   * Id dei menu che puntano a questo modulo: voce "Route"/"Controller &
   * Method" (path = nome controller + GetIndex/@metodo...) o voce "Module"
   * (path = path del modulo + ?m=<id>). Il match sul controller e' esatto
   * sul nome (seguito da Get/Post/@), non per sottostringa: prima
   * `LIKE '%AdminOrdini%'` cancellava anche i menu di AdminOrdiniArchivio.
   */
  private function moduleMenuIds($module)
  {
    $ids = [];
    $controller = (string) $module->controller;

    if ($controller !== '') {
      $pattern = '/^' . preg_quote($controller, '/') . '(@|Get|Post|Put|Delete|$)/';
      $candidates = DB::table('cms_menus')
        ->where('path', 'like', $controller . '%')
        ->get(['id', 'path']);
      foreach ($candidates as $menu) {
        if (preg_match($pattern, $menu->path)) {
          $ids[] = $menu->id;
        }
      }
    }

    if (!empty($module->path)) {
      $module_menus = DB::table('cms_menus')
        ->where('type', 'Module')
        ->where('path', $module->path . '?m=' . $module->id)
        ->pluck('id')
        ->all();
      $ids = array_merge($ids, $module_menus);
    }

    return array_values(array_unique($ids));
  }

  private function removeModuleController($module)
  {
    if (empty($module->controller)) {
      return;
    }
    // basename(): il nome arriva dal DB ma finisce in un unlink
    @unlink(app_path('Http/Controllers/' . basename($module->controller) . '.php'));
  }

  private function refreshSessionPrivilegesRoles()
  {
    $roles = DB::table('cms_privileges_roles')
      ->where('id_cms_privileges', CRUDBooster::myPrivilegeId())
      ->join('cms_moduls', 'cms_moduls.id', '=', 'id_cms_moduls')
      ->select('cms_moduls.name', 'cms_moduls.path', 'is_visible', 'is_create', 'is_read', 'is_edit', 'is_delete')
      ->where('cms_moduls.deleted_at', null)
      ->get();

    Session::put('admin_privileges_roles', $roles);
  }

  /**
   * Il wizard del Module Generator (step 1-5 + endpoint AJAX usati solo dal
   * wizard) crea/modifica tabelle, righe cms_moduls/privilegi/menu e
   * sorgente PHP dei controller generati: riservato al superadmin, come gia'
   * postStep3()/postStep5()/save_table()/getEdit(). Prima gli altri step
   * richiedevano solo il permesso di visualizzazione (e postStep4() nessun
   * controllo). Vedi docs/refactoring/080-module-generator-wizard-solo-superadmin.md.
   * Ritorna la risposta di accesso negato, o null se si puo' procedere.
   */
  private function denyWizardUnlessSuperadmin($moduleName, bool $json = false)
  {
    if (CRUDBooster::isSuperadmin()) {
      return null;
    }
    if ($json) {
      return response()->json([], 403);
    }
    CRUDBooster::insertLog(trans('crudbooster.log_try_view', ['module' => $moduleName]));

    return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
  }

  public function getTableColumns($table)
  {
    $this->cbLoader();

    // Nessun controllo qui prima: espone lo schema (nomi colonna) di
    // QUALUNQUE tabella - incluse cms_users, cms_apikey, ecc. - a chiunque
    // fosse autenticato, a prescindere dal privilegio sul modulo Module
    // Generator. Vedi docs/refactoring/068.
    if (!CRUDBooster::isView() && $this->global_privilege == false) {
      return response()->json([], 403);
    }
    if ($denied = $this->denyWizardUnlessSuperadmin('Module Generator', true)) {
      return $denied;
    }

    $columns = CRUDBooster::getTableColumns($table);

    return response()->json($columns);
  }

  public function getCheckSlug($slug)
  {
    $this->cbLoader();

    if (!CRUDBooster::isView() && $this->global_privilege == false) {
      return response()->json([], 403);
    }
    if ($denied = $this->denyWizardUnlessSuperadmin('Module Generator', true)) {
      return $denied;
    }

    $check = DB::table('cms_moduls')->where('path', $slug)->count();
    $lastId = DB::table('cms_moduls')->max('id') + 1;

    return response()->json(['total' => $check, 'lastid' => $lastId]);
  }

  /**
   * Bozza di un passo del wizard (flag wizard_v2): i campi del form non ancora
   * salvati, tenuti in sessione per modulo e passo. Si scrive cambiando passo o
   * uscendo dalla pagina e si cancella quando il passo viene salvato davvero o
   * l'utente la scarta. Vedi docs/refactoring/201.
   */
  public function postDraft($id = 0)
  {
    if ($denied = $this->denyWizardUnlessSuperadmin('Module Generator', true)) {
      return $denied;
    }
    $step = (int) Request::input('step');
    $fields = json_decode((string) Request::input('fields'), true);
    if (!config('module_generator.wizard_v2') || $step < 1 || $step > 5 || !is_array($fields)) {
      return response()->json([], 422);
    }

    $clean = [];
    foreach ($fields as $name => $values) {
      if (!is_string($name)) {
        continue;
      }
      $values = array_values(array_filter((array) $values, 'is_string'));
      $clean[$name] = count($values) === 1 ? $values[0] : $values;
    }
    if (strlen(json_encode($clean)) > 1048576) {
      return response()->json([], 413);
    }

    Session::put($this->wizardDraftKey($id, $step), $clean);

    return response()->json(['ok' => true]);
  }

  public function postDraftDiscard($id = 0)
  {
    if ($denied = $this->denyWizardUnlessSuperadmin('Module Generator', true)) {
      return $denied;
    }
    $step = (int) Request::input('step');
    if ($step < 1 || $step > 5) {
      return response()->json([], 422);
    }
    $this->forgetWizardDraft($id, $step);

    return response()->json(['ok' => true]);
  }

  private function wizardDraftKey($id, $step)
  {
    return 'mg_draft.' . (int) $id . '.' . (int) $step;
  }

  private function forgetWizardDraft($id, $step)
  {
    Session::forget($this->wizardDraftKey($id, $step));
  }

  // Per i passi che si ridisegnano da old('payload'): se c'e' una bozza e non c'e'
  // gia' un input di ritorno da un errore di validazione, la bozza fa da old input.
  private function applyWizardDraft($id, $step)
  {
    $draft = Session::get($this->wizardDraftKey($id, $step));
    if (config('module_generator.wizard_v2') && is_array($draft) && !Session::hasOldInput()) {
      Session::now('_old_input', $draft);
    }
  }

  public function getAdd()
  {
    $this->cbLoader();

    $module = CRUDBooster::getCurrentModule();

    if (!CRUDBooster::isView() && $this->global_privilege == false) {
      CRUDBooster::insertLog(trans('crudbooster.log_try_view', ['module' => $module->name]));
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }
    if ($denied = $this->denyWizardUnlessSuperadmin($module->name)) {
      return $denied;
    }

    return redirect()->route("ModulsControllerGetStep1");
  }

  public function getStep1($id = 0)
  {
    $this->cbLoader();

    $module = CRUDBooster::getCurrentModule();

    if (!CRUDBooster::isView() && $this->global_privilege == false) {
      CRUDBooster::insertLog(trans('crudbooster.log_try_view', ['module' => $module->name]));
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }
    if ($denied = $this->denyWizardUnlessSuperadmin($module->name)) {
      return $denied;
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
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }
    if ($denied = $this->denyWizardUnlessSuperadmin($module->name)) {
      return $denied;
    }

    //module name
    $name = Request::get('name');
    $table_name = Request::get('table');
    $icon = Request::get('icon');

    if ($table_name == 'new') {
      $table_name = ModuleHelper::sql_name_encode(config('app.module_generator_prefix') . $name);
    } else {
      // Il <select> della tabella e' solo lato client, mai rivalidato
      // server-side: senza sanificazione un table_name malevolo finiva
      // salvato grezzo in cms_moduls.table_name e raggiungeva sia il
      // sorgente PHP generato (CRUDBooster::generateController(), RCE) sia
      // query di introspezione schema con SQL injection vera
      // (Schema::getIndexes() usa una quoteString() non parametrizzata) -
      // vedi docs/refactoring/068. Nessun impatto per una selezione
      // legittima: i nomi tabella reali sono gia' in questo formato.
      $table_name = ModuleHelper::sql_name_encode($table_name);
    }
    $path = $table_name;

    if (!Request::get('id')) {
      //create new module
      $created_at = now();
      //$this->table equals cms_moduls
      $id = DB::table($this->table)->max('id') + 1; // id del nuovo modulo

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
          'path' => $path,
          'type' => 'Module',
          'is_active' => 1,
          'id_cms_privileges' => CRUDBooster::myPrivilegeId(),
          'sorting' => $parent_menu_sort,
          'parent_id' => 0
        ]);
        // come fa Menu Management per le voci Module: ?m=ID (layout di destinazione)
        DB::table('cms_menus')->where('id', $id_cms_menus)->update(['path' => $path . '?m=' . $id_cms_menus]);
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

      $this->forgetWizardDraft(0, 1);
      //return redirect(Route("ModulsControllerGetStep2", ["id" => $id]));
      return redirect(Route("ModulsControllerGetStep2") . "/{$id}");
    } else {
      //update existing module
      $id = Request::get('id');
      $oldPath = DB::table($this->table)->where('id', $id)->value('path');
      DB::table($this->table)
        ->where('id', $id)
        ->update(compact(
          "name",
          "table_name",
          "icon",
          "path"
        ));
      // l'icona del modulo vale anche per le sue voci di menu
      \App\Helpers\MenuHelper::sync_module_icon($id, $oldPath);

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
      $this->forgetWizardDraft($id, 1);
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
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }
    if ($denied = $this->denyWizardUnlessSuperadmin($module->name)) {
      return $denied;
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

    // Nuova interfaccia del passo Campi (flag, spento di default): vedi
    // docs/refactoring/196.
    if (config('module_generator.wizard_v2')) {
      return $this->getStep2V2($id, $module);
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
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }
    if ($denied = $this->denyWizardUnlessSuperadmin($module->name ?? 'Module Generator')) {
      return $denied;
    }
    // Nuova interfaccia: un solo campo "payload" (JSON) al posto degli array.
    if (isset($request['payload'])) {
      return $this->postStep2V2($module);
    }

    $messages = $this->save_table($request);

    $data = array();
    $data["id"] = $id;
    $data["messages"] = $messages;

    //return redirect(Route("ModulsControllerGetStep3", $data));
    return redirect(Route("ModulsControllerGetStep3") . "/{$id}")->with($messages);
  }

  /**
   * Passo Campi, nuova interfaccia (flag module_generator.wizard_v2): fonde
   * struttura della tabella e definizione dei campi del form. Non modifica,
   * rinomina ne' elimina colonne esistenti: puo' solo aggiungerne.
   * Vedi docs/refactoring/196.
   */
  private function getStep2V2($id, $module)
  {
    $this->applyWizardDraft($id, 2);
    $table = $module['table_name'];
    $structure = ($table == 'new') ? [] : CRUDBooster::getTableStructure($table);
    $tableColumns = ($table == 'new') ? [] : CRUDBooster::getTableColumns($table);

    $path = app_path('Http/Controllers/' . $module->controller . '.php');
    $contents = file_exists($path) ? file_get_contents($path) : '';
    $form = ModuleGeneratorList::readBlock($contents, 'FORM');

    $rows = ModuleGeneratorFields::describeFields($structure, $form, $tableColumns, [
      trans('crudbooster.confirmButtonText'),
      trans('crudbooster.confirmation_no'),
    ]);

    // dopo un errore di validazione si riparte da cio' che l'utente aveva inviato
    $old = json_decode((string) old('payload'), true);
    if (is_array($old) && isset($old['rows']) && is_array($old['rows'])) {
      $rows = $old['rows'];
    }

    $tables = [];
    foreach (CRUDBooster::listTables() as $tab) {
      foreach ($tab as $value) {
        $tables[] = $value;
      }
    }

    return view('crudbooster::module_generator.step2_v2', [
      'id' => $id,
      'active_tab' => 2,
      'rows' => $rows,
      // nel menu "Quale tabella?" solo le tabelle sensate (quelle gia' scelte nei campi restano)
      'table_list' => ModuleGeneratorFields::selectableTables($tables, array_merge(
        array_map(function ($r) { return (string) ($r['opts']['table'] ?? ''); }, $rows),
        array_map(function ($r) { return (string) ($r['opts']['mtable'] ?? ''); }, $rows)
      )),
      'table_name' => $table,
      'table_exists' => !empty($tableColumns),
      'type_names' => $this->componentTypeNames(),
    ]);
  }

  private function componentTypeNames()
  {
    $types = [];
    foreach (glob(resource_path('views/crudbooster/default/type_components') . '/*', GLOB_ONLYDIR) as $dir) {
      $types[] = basename($dir);
    }

    return $types;
  }

  private function postStep2V2($module)
  {
    if (!config('module_generator.wizard_v2') || !CRUDBooster::isSuperadmin()) {
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    $id = $module->id;
    $payload = json_decode((string) Request::input('payload'), true);
    if (!is_array($payload) || !isset($payload['rows']) || !is_array($payload['rows'])) {
      return redirect()->back()->with(['message' => trans('crudbooster.mg_list_err_payload'), 'message_type' => 'warning']);
    }

    $path = app_path('Http/Controllers/' . $module->controller . '.php');
    if (!file_exists($path)) {
      return redirect()->back();
    }

    // stesse regole di save_table(): tabelle riservate (cms_) mai modificabili
    if (substr($module->table_name, 0, strlen(config('app.reserved_tables_prefix'))) === config('app.reserved_tables_prefix')) {
      add_log_ch('mg save table', 'editing reserved tables is forbidden ' . $module->table_name, 'error');

      return redirect()->back()->withInput()->with(['message' => 'editing reserved tables is forbidden', 'message_type' => 'warning']);
    }
    $tableName = ModuleHelper::sql_name_encode($module->table_name);
    $tableExists = $module->table_name != 'new' && Schema::hasTable($tableName);
    $tableColumns = $tableExists ? CRUDBooster::getTableColumns($tableName) : [];

    $knownTables = [];
    foreach (CRUDBooster::listTables() as $tab) {
      foreach ($tab as $value) {
        $knownTables[] = $value;
      }
    }

    $contents = file_get_contents($path);
    $existingForm = ModuleGeneratorList::readBlock($contents, 'FORM');

    try {
      $rows = $payload['rows'];
      $dropNames = $this->collectColumnDrops($rows, $tableColumns, $tableExists, $contents);
      $built = ModuleGeneratorFields::build($rows, $existingForm, $tableColumns, $this->componentTypeNames(), $knownTables, (array) config('app.reserved_column_names'), $tableName);
    } catch (\InvalidArgumentException $e) {
      list($key, $param) = array_pad(explode('|', $e->getMessage(), 2), 2, '');

      return redirect()->back()->withInput()->with([
        'message' => trans('crudbooster.' . $key, ['name' => $param]),
        'message_type' => 'warning',
      ]);
    }

    $newContents = ModuleGeneratorFields::replaceFormBlock($contents, $built['entries']);

    // Se il modulo ha gia' un layout a blocchi: un campo nuovo non resterebbe
    // invisibile (i campi non posizionati non si disegnano), quindi si aggiunge in
    // coda al primo blocco; i campi tolti dal modulo escono dal layout.
    $currentLayout = ModuleGeneratorList::readBlock($contents, 'FORM LAYOUT');
    if (ModuleGeneratorLayout::isActive($currentLayout)) {
      $after = $this->layoutPlaceableFields($built['entries']);
      $before = $this->layoutPlaceableFields($existingForm);
      $currentLayout = ModuleGeneratorLayout::dropFields($currentLayout, array_values(array_diff(array_keys($before), array_keys($after))));
      $added = array_values(array_diff(array_keys($after), array_keys($before), ModuleGeneratorLayout::placedNames($currentLayout)));
      $currentLayout = ModuleGeneratorLayout::appendFields($currentLayout, $added, trans('crudbooster.mg_lay_default_title'));
      $newContents = ModuleGeneratorLayout::replaceLayoutBlock($newContents, $currentLayout);
    }

    // Colonne eliminate dal database: spariscono anche dalla lista (COLUMNS) e
    // dall'ordinamento predefinito, altrimenti la lista darebbe errore SQL.
    if ($dropNames) {
      $newContents = $this->removeDroppedColumnsFromList($newContents, $dropNames);
    }

    $this->backupControllerFile($path);

    // Struttura: solo se ci sono colonne nuove. Tabella nuova -> save_table()
    // (ramo di creazione, invariato); tabella esistente -> aggiunta di colonne
    // ed eliminazione di quelle esplicitamente marcate "drop" dall'utente
    // (mai modifica/rinomina, mai eliminazione implicita).
    if ($built['new_columns']) {
      if (!$tableExists) {
        $request = ['id' => $id, 'name' => [], 'type' => [], 'size' => []];
        foreach ($built['new_columns'] as $col) {
          $request['name'][] = $col['name'];
          $request['type'][] = $col['type'];
          $request['size'][] = $col['size'];
        }
        $result = $this->save_table($request);
        $failed = is_string($result) && strpos($result, 'danger,') === 0;
        $message = is_string($result) ? explode(',', $result)[1] ?? '' : '';
      } else {
        $message = $this->addTableColumns($tableName, $built['new_columns']);
        $failed = $message !== null;
      }
      if ($failed) {
        return redirect()->back()->withInput()->with(['message' => $message, 'message_type' => 'warning']);
      }
    }

    if ($dropNames) {
      $error = $this->dropTableColumns($tableName, array_keys($dropNames));
      if ($error !== null) {
        return redirect()->back()->withInput()->with(['message' => $error, 'message_type' => 'warning']);
      }
    }

    file_put_contents($path, $newContents);

    $this->forgetWizardDraft($id, 2);
    return redirect(Route('ModulsControllerGetStep3') . "/{$id}");
  }

  // Righe del passo Campi marcate "drop" (eliminazione definitiva della colonna).
  // Valida e restituisce [nome => true]; le righe marcate escono dal modulo.
  // Lancia InvalidArgumentException con chiave di traduzione, come build().
  private function collectColumnDrops(array &$rows, array $tableColumns, bool $tableExists, string $contents): array
  {
    $drop = [];
    $reserved = (array) config('app.reserved_column_names');
    foreach ($rows as $i => $row) {
      if (!is_array($row) || empty($row['drop'])) {
        continue;
      }
      $name = (string) ($row['name'] ?? '');
      if (!$tableExists || !in_array($name, $tableColumns, true) || in_array($name, $reserved, true)) {
        throw new \InvalidArgumentException('mg_fld_err_drop|' . $name);
      }
      $drop[$name] = true;
      $rows[$i]['in_module'] = false;
    }
    if (!$drop) {
      return [];
    }

    foreach ($rows as $row) {
      if (!is_array($row) || empty($row['in_module'])) {
        continue;
      }
      foreach (['lat', 'lng'] as $k) {
        $ref = (string) ($row['opts'][$k] ?? '');
        if (($row['type'] ?? '') === 'googlemaps' && $ref !== '' && isset($drop[$ref])) {
          throw new \InvalidArgumentException('mg_fld_err_drop_ref|' . $ref);
        }
      }
    }
    $title = ModuleGeneratorList::readConfigValue($contents, 'title_field');
    if (is_string($title) && isset($drop[$title])) {
      throw new \InvalidArgumentException('mg_fld_err_drop_title|' . $title);
    }

    return $drop;
  }

  // Toglie le colonne eliminate dal blocco COLUMNS e dall'orderby; riscrive
  // un blocco solo se contiene davvero riferimenti alle colonne eliminate.
  private function removeDroppedColumnsFromList(string $contents, array $dropNames): string
  {
    $cols = ModuleGeneratorList::readBlock($contents, 'COLUMNS');
    $kept = array_values(array_filter($cols, function ($c) use ($dropNames) {
      return !isset($dropNames[(string) ($c['name'] ?? '')]);
    }));
    if (count($kept) !== count($cols)) {
      $contents = ModuleGeneratorList::replaceColumnsBlock($contents, $kept);
    }

    $orderby = ModuleGeneratorList::orderbyToString(ModuleGeneratorList::readConfigValue($contents, 'orderby'));
    if ($orderby !== '') {
      $parts = array_values(array_filter(explode(';', $orderby), function ($p) use ($dropNames) {
        return !isset($dropNames[trim(explode(',', $p)[0])]);
      }));
      if (count($parts) !== count(explode(';', $orderby))) {
        $contents = ModuleGeneratorList::mergeConfig($contents, ['orderby' => $parts ? implode(';', $parts) : 'id,desc']);
      }
    }

    return $contents;
  }

  // Elimina definitivamente colonne (e i loro dati) da una tabella esistente.
  // Restituisce null se ok, altrimenti il messaggio d'errore.
  private function dropTableColumns($table_name, array $names)
  {
    if (substr($table_name, 0, strlen(config('app.reserved_tables_prefix'))) === config('app.reserved_tables_prefix')) {
      return 'editing reserved tables is forbidden';
    }
    foreach ($names as $name) {
      if (in_array($name, (array) config('app.reserved_column_names'), true) || !Schema::hasColumn($table_name, $name)) {
        add_log_ch('mg edit table drop column', 'error column ' . $name . ' not found or reserved', 'error');

        return 'column ' . $name . ' not found';
      }
      try {
        Schema::table($table_name, function (Blueprint $table) use ($name) {
          $table->dropColumn($name);
        });
      } catch (\Throwable $e) {
        add_log_ch('mg edit table drop column', 'error dropping ' . $name . ': ' . $e->getMessage(), 'error');

        return 'Cannot delete column ' . $name . ': ' . $e->getMessage();
      }
      add_log_ch('mg edit table drop column', 'delete column ' . $name);
    }

    return null;
  }

  // Aggiunge colonne nuove a una tabella esistente: stesse chiamate di
  // schema e stesso log del ramo "add column" di save_table(), che qui non
  // si usa perche' sullo stesso ramo elimina le colonne mancanti dalla richiesta.
  private function addTableColumns($table_name, array $columns)
  {
    if (substr($table_name, 0, strlen(config('app.reserved_tables_prefix'))) === config('app.reserved_tables_prefix')) {
      return 'editing reserved tables is forbidden';
    }
    foreach ($columns as $column) {
      $name = ModuleHelper::sql_name_encode($column['name']);
      if (ctype_digit($name)) {
        add_log_ch('mg edit table add column', 'digit only column name is invalid ' . $table_name . ' column ' . $name, 'error');

        return 'Digit only column name is not accepted';
      }
      if (in_array($name, config('app.reserved_column_names')) || Schema::hasColumn($table_name, $name)) {
        add_log_ch('mg edit table add column', 'Duplicate column name ' . $name, 'error');

        return 'Duplicate column name';
      }
      $existing = CRUDBooster::getTableStructure($table_name);
      $last = $existing ? end($existing) : null;
      $after = $last && isset($last['name']) ? $last['name'] : 'id';
      $kind = $column['type'];
      $size = (string) $column['size'];
      Schema::table($table_name, function (Blueprint $table) use ($name, $size, $kind, $after) {
        switch ($kind) {
          case 'number':
            $table->integer($name)->length((int) $size)->nullable()->after($after);
            break;
          case 'plaintext':
            $table->text($name)->nullable()->after($after);
            break;
          case 'longtext':
            $table->longText($name)->nullable()->after($after);
            break;
          case 'decimal':
            list($precision, $scale) = array_map('intval', explode(',', $size . ',0'));
            $table->decimal($name, $precision, $scale)->nullable()->after($after);
            break;
          case 'date':
            $table->date($name)->nullable()->after($after);
            break;
          case 'datetime':
            $table->dateTime($name)->nullable()->after($after);
            break;
          case 'time':
            $table->time($name)->nullable()->after($after);
            break;
          default:
            $table->string($name, (int) $size)->nullable()->after($after);
            break;
        }
      });
      add_log_ch('mg edit table add column', 'add column ' . $name . ' after ' . $after);
    }

    return null;
  }

  // Campi del form che si possono posizionare nel layout: tutti tranne quelli
  // di tipo hidden e quelli gestiti dal sistema (tenant/group/primary_group).
  private function layoutPlaceableFields(array $form)
  {
    $fields = [];
    foreach ($form as $e) {
      $name = (string) ($e['name'] ?? '');
      $type = $e['type'] ?? 'text';
      if ($name === '' || $type === 'hidden' || in_array($name, ModuleGeneratorLayout::ALWAYS_NAMES, true) || isset($fields[$name])) {
        continue;
      }
      $required = !empty($e['required']) || in_array('required', explode('|', (string) ($e['validation'] ?? '')), true);
      $fields[$name] = ['name' => $name, 'label' => (string) ($e['label'] ?? $name), 'type' => $type, 'required' => $required, 'help' => (string) ($e['help'] ?? '')];
    }

    return $fields;
  }

  /**
   * Passo Form, nuova interfaccia (flag module_generator.wizard_v2): layout a
   * blocchi e schede su griglia a 12 colonne. Vedi docs/refactoring/197.
   */
  private function getStep4V2($id, $row)
  {
    $this->applyWizardDraft($id, 4);
    $path = app_path('Http/Controllers/' . $row->controller . '.php');
    $contents = file_exists($path) ? file_get_contents($path) : '';
    $form = ModuleGeneratorList::readBlock($contents, 'FORM');
    $layout = ModuleGeneratorList::readBlock($contents, 'FORM LAYOUT');
    $fields = $this->layoutPlaceableFields($form);

    if (ModuleGeneratorLayout::isActive($layout)) {
      // campi non piu' nel modulo: fuori dal layout
      $layout = ModuleGeneratorLayout::dropFields($layout, array_values(array_diff(ModuleGeneratorLayout::placedNames($layout), array_keys($fields))));
      $hasLayout = true;
    } else {
      $layout = ModuleGeneratorLayout::defaultLayout(array_keys($fields), trans('crudbooster.mg_lay_default_title'));
      $hasLayout = false;
    }

    // dopo un errore di validazione si riparte da cio' che l'utente aveva inviato
    $old = json_decode((string) old('payload'), true);
    if (is_array($old) && isset($old['tabs']) && is_array($old['tabs'])) {
      $layout = ['v' => 2, 'tabs' => $old['tabs']];
      foreach ((array) ($old['help'] ?? []) as $n => $h) {
        if (isset($fields[$n]) && is_string($h)) {
          $fields[$n]['help'] = $h;
        }
      }
    }

    return view('crudbooster::module_generator.step4_v2', [
      'id' => $id,
      'active_tab' => 4,
      'fields' => array_values($fields),
      'layout' => ModuleGeneratorLayout::normalize($layout),
      'has_layout' => $hasLayout,
      'system_fields' => ModuleGeneratorLayout::SYSTEM_FIELDS,
      'type_texts' => trans('crudbooster.mg_field_types'),
    ]);
  }

  private function postStep4V2(array $post)
  {
    if (!config('module_generator.wizard_v2') || !CRUDBooster::isSuperadmin()) {
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    $id = $post['id'];
    $row = DB::table('cms_moduls')->where('id', $id)->first();
    if (!$row) {
      return redirect()->back();
    }
    $path = app_path('Http/Controllers/' . $row->controller . '.php');
    if (!file_exists($path)) {
      return redirect()->back();
    }
    $payload = json_decode((string) $post['payload'], true);
    if (!is_array($payload)) {
      return redirect()->back()->with(['message' => trans('crudbooster.mg_list_err_payload'), 'message_type' => 'warning']);
    }

    $contents = file_get_contents($path);
    $form = ModuleGeneratorList::readBlock($contents, 'FORM');
    $fields = $this->layoutPlaceableFields($form);

    try {
      $layout = ModuleGeneratorLayout::build($payload, $fields);
    } catch (\InvalidArgumentException $e) {
      list($key, $param) = array_pad(explode('|', $e->getMessage(), 2), 2, '');

      return redirect()->back()->withInput()->with([
        'message' => trans('crudbooster.' . $key, ['name' => $param]),
        'message_type' => 'warning',
      ]);
    }

    // testi di aiuto: chiave "help" della voce del form (solo se cambiano)
    $newContents = $contents;
    $helps = is_array($payload['help'] ?? null) ? $payload['help'] : [];
    $changed = false;
    foreach ($form as $i => $entry) {
      $n = (string) ($entry['name'] ?? '');
      if ($n === '' || !isset($fields[$n]) || !array_key_exists($n, $helps) || !is_string($helps[$n])) {
        continue;
      }
      $h = mb_substr(trim($helps[$n]), 0, 500);
      if ($h !== (string) ($entry['help'] ?? '')) {
        if ($h === '') {
          unset($form[$i]['help']);
        } else {
          $form[$i]['help'] = $h;
        }
        $changed = true;
      }
    }
    if ($changed) {
      $newContents = ModuleGeneratorFields::replaceFormBlock($newContents, array_values($form));
    }
    $newContents = ModuleGeneratorLayout::replaceLayoutBlock($newContents, $layout);

    $this->backupControllerFile($path);
    file_put_contents($path, $newContents);

    $this->forgetWizardDraft($id, 4);
    return redirect(Route('ModulsControllerGetStep5') . "/{$id}");
  }

  public function getStep3($id, $messages = '')
  {
    $this->cbLoader();

    $module = CRUDBooster::getCurrentModule();

    if (!CRUDBooster::isView() && $this->global_privilege == false) {
      CRUDBooster::insertLog(trans('crudbooster.log_try_view', ['module' => $module->name]));
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }
    if ($denied = $this->denyWizardUnlessSuperadmin($module->name)) {
      return $denied;
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
    // Nuova interfaccia del passo Lista (flag, spento di default): vedi
    // docs/refactoring/194. Salva sullo stesso blocco COLUMNS.
    if (config('module_generator.wizard_v2')) {
      return $this->getStep3V2($id, $row, $columns, $table_list);
    }

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
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    // Scrive direttamente nel sorgente PHP del controller generato (vedi
    // sotto): stesso livello di rischio della modifica dello schema DB in
    // save_table() (che gia' richiede isSuperadmin()), quindi stesso check
    // qui - vedi docs/refactoring/068.
    if (!CRUDBooster::isSuperadmin()) {
      CRUDBooster::insertLog(trans('crudbooster.log_try_view', ['module' => $module->name]));
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

  // Nuova interfaccia: un solo campo "payload" (JSON) al posto degli array.
    if (Request::has('payload')) {
      return $this->postStep3V2();
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

      // column/name/join_table/join_field/width/callbackphp finiscono nel
      // sorgente PHP del controller generato (file_put_contents() piu' in
      // basso, su un file gia' autoloaded da Laravel): prima venivano
      // incollati grezzi dentro literal a doppi/singoli apici senza alcun
      // escaping (query era l'unico con addslashes()) - una virgoletta nel
      // valore rompeva il literal e permetteva di iniettare PHP arbitrario
      // (RCE). var_export() produce sempre un literal a singoli apici
      // correttamente escapato, mai interpolato - stesso fix gia' applicato
      // a CRUDBooster::generateAPI()/generateController(), vedi
      // docs/refactoring/065 e 068.
      $script_cols[$i] = "\t\t\t" . '$this->col[] = ["label"=>' . var_export($col, true) . ',"name"=>' . var_export($name[$i], true);

      if ($join_table[$i] && $join_field[$i]) {
        $script_cols[$i] .= ',"join"=>' . var_export($join_table[$i] . ',' . $join_field[$i], true);
      }

      if ($is_image[$i]) {
        $script_cols[$i] .= ',"image"=>true';
      }

      // Bug pre-esistente: controllava $id_download (mai definita, isset()
      // sempre falso) invece di $is_download - il flag "download" non
      // veniva mai scritto sulla colonna.
      if ($is_download[$i]) {
        $script_cols[$i] .= ',"download"=>true';
      }

      if ($width[$i]) {
        $script_cols[$i] .= ',"width"=>' . var_export($width[$i], true);
      }

      if ($callbackphp[$i]) {
        $script_cols[$i] .= ',"callback_php"=>' . var_export($callbackphp[$i], true);
      }

      if ($query[$i]) {
        $script_cols[$i] .= ',"query"=>' . var_export($query[$i], true);
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

  /**
   * Passo Lista, nuova interfaccia (flag module_generator.wizard_v2).
   * Parte dalle colonne reali della tabella e da cio' che il modulo ha gia'
   * nel blocco COLUMNS. Vedi docs/refactoring/194.
   */
  private function getStep3V2($id, $row, array $columns, array $table_list)
  {
    $this->applyWizardDraft($id, 3);
    $path = app_path('Http/Controllers/' . $row->controller . '.php');
    $contents = file_exists($path) ? file_get_contents($path) : '';
    $table = $row->table_name;

    $existing = ModuleGeneratorList::readBlock($contents, 'COLUMNS');
    $form = ModuleGeneratorList::readBlock($contents, 'FORM');
    $rows = ModuleGeneratorList::describeRows($columns, $existing, $form, function ($column) use ($table) {
      return CRUDBooster::getFieldType($table, $column);
    });
    $limit = ModuleGeneratorList::readConfigValue($contents, 'limit');
    $orderby = ModuleGeneratorList::orderbyToString(ModuleGeneratorList::readConfigValue($contents, 'orderby'));

    // dopo un errore di validazione si riparte da cio' che l'utente aveva inviato
    $old = json_decode((string) old('payload'), true);
    if (is_array($old) && isset($old['rows']) && is_array($old['rows'])) {
      $rows = $old['rows'];
      $limit = $old['limit'] ?? $limit;
      $orderby = isset($old['orderby']) && is_string($old['orderby']) ? $old['orderby'] : $orderby;
    }

    return view('crudbooster::module_generator.step3_v2', [
      'id' => $id,
      'active_tab' => 3,
      'rows' => $rows,
      'table_columns' => $columns,
      // stesso criterio del passo Campi (le tabelle gia' collegate restano)
      'table_list' => ModuleGeneratorFields::selectableTables($table_list, array_map(function ($r) {
        return (string) ($r['join']['table'] ?? '');
      }, $rows)),
      'limit' => $limit,
      'orderby' => $orderby,
    ]);
  }

  private function postStep3V2()
  {
    if (!config('module_generator.wizard_v2')) {
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    $id = Request::input('id');
    $row = DB::table('cms_moduls')->where('id', $id)->first();
    if (!$row) {
      return redirect()->back();
    }
    $path = app_path('Http/Controllers/' . $row->controller . '.php');
    if (!file_exists($path)) {
      return redirect()->back();
    }

    $payload = json_decode((string) Request::input('payload'), true);
    if (!is_array($payload) || !isset($payload['rows']) || !is_array($payload['rows'])) {
      return redirect()->back()->with(['message' => trans('crudbooster.mg_list_err_payload'), 'message_type' => 'warning']);
    }

    $contents = file_get_contents($path);
    $existing = ModuleGeneratorList::readBlock($contents, 'COLUMNS');
    $tableColumns = CRUDBooster::getTableColumns($row->table_name);
    $knownTables = [];
    foreach (CRUDBooster::listTables() as $tab) {
      foreach ($tab as $value) {
        $knownTables[] = $value;
      }
    }

    try {
      $cols = ModuleGeneratorList::buildColumns($payload['rows'], $existing, $tableColumns, $knownTables);
    } catch (\InvalidArgumentException $e) {
      list($key, $param) = array_pad(explode('|', $e->getMessage(), 2), 2, '');

      return redirect()->back()->withInput()->with([
        'message' => trans('crudbooster.' . $key, ['name' => $param]),
        'message_type' => 'warning',
      ]);
    }

    $new = ModuleGeneratorList::replaceColumnsBlock($contents, $cols);

    // Limit e Order By (prima nel passo Configurazione): stesse proprieta'
    // del blocco CONFIGURATION, aggiornate senza toccare le altre.
    $config = [];
    $limit = $payload['limit'] ?? '';
    if ($limit !== '' && ctype_digit((string) $limit) && (int) $limit >= 1 && (int) $limit <= 1000) {
      $config['limit'] = (string) (int) $limit;
    }
    $orderby = $payload['orderby'] ?? null;
    if (is_string($orderby) && preg_match('/^([A-Za-z0-9_.]+,(asc|desc)(;[A-Za-z0-9_.]+,(asc|desc))*)?$/', $orderby)) {
      $config['orderby'] = $orderby;
    }
    if ($config) {
      $new = ModuleGeneratorList::mergeConfig($new, $config);
    }

    $this->backupControllerFile($path);
    file_put_contents($path, $new);

    $this->forgetWizardDraft($id, 3);
    return redirect(Route('ModulsControllerGetStep4') . "/{$id}");
  }

  // Copia di sicurezza del controller prima di riscriverlo (ultime 20 per
  // controller, in storage/app/module_generator_backups).
  private function backupControllerFile($path)
  {
    $dir = storage_path('app/module_generator_backups');
    if (!is_dir($dir)) {
      @mkdir($dir, 0775, true);
    }
    $base = basename($path, '.php');
    @copy($path, $dir . '/' . $base . '-' . date('Ymd-His') . '.php.bak');
    $files = glob($dir . '/' . $base . '-*.php.bak') ?: [];
    sort($files);
    foreach (array_slice($files, 0, max(0, count($files) - 20)) as $oldFile) {
      @unlink($oldFile);
    }
  }

  public function getStep4($id)
  {
    $this->cbLoader();

    $module = CRUDBooster::getCurrentModule();

    if (!CRUDBooster::isView() && $this->global_privilege == false) {
      CRUDBooster::insertLog(trans('crudbooster.log_try_view', ['module' => $module->name]));
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }
    if ($denied = $this->denyWizardUnlessSuperadmin($module->name)) {
      return $denied;
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
    foreach (glob(resource_path('views/crudbooster/default/type_components') . '/*', GLOB_ONLYDIR) as $dir) {
      $types[] = basename($dir);
    }
    $active_tab = 4;

    // Nuova interfaccia del passo Form (layout a blocchi, flag): vedi
    // docs/refactoring/197.
    if (config('module_generator.wizard_v2')) {
      return $this->getStep4V2($id, $row);
    }

    return view('crudbooster::module_generator.step4', compact('columns', 'cb_form', 'types', 'id', 'active_tab'));
  }

  public function getTypeInfo($type = 'text')
  {
    header("Content-Type: application/json");
    echo file_get_contents(resource_path('views/crudbooster/default/type_components/' . $type . '/info.json'));
  }

  public function postStep4()
  {
    $this->cbLoader();

    // Prima nessun controllo: scrive il blocco FORM nel sorgente PHP del
    // controller generato, come postStep3()/postStep5().
    if ($denied = $this->denyWizardUnlessSuperadmin('Module Generator - Step 4')) {
      return $denied;
    }

    $post = Request::all();
    $id = $post['id'];

    // Nuova interfaccia: un solo campo "payload" (JSON) al posto degli array.
    if (isset($post['payload'])) {
      return $this->postStep4V2($post);
    }

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
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }
    if ($denied = $this->denyWizardUnlessSuperadmin($module->name)) {
      return $denied;
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
    $data['wizard_v2'] = (bool) config('module_generator.wizard_v2');
    // Colonne della tabella del modulo: elenco del campo "titolo candidato" (select ricercabile).
    // Se la tabella non esiste ancora, il campo resta con il solo valore attuale.
    $data['title_candidates'] = (!empty($row->table_name) && Schema::hasTable($row->table_name))
      ? Schema::getColumnListing($row->table_name)
      : [];

    return view('crudbooster::module_generator.step5', $data);
  }

  public function postStep5()
  {
    $this->cbLoader();

    // Prima non c'era ALCUN controllo qui (nemmeno il debole isView() usato
    // dagli altri step) - scrive direttamente nel sorgente PHP del
    // controller generato, stesso livello di rischio di save_table() (che
    // gia' richiede isSuperadmin()) - vedi docs/refactoring/068.
    if (!CRUDBooster::isSuperadmin()) {
      CRUDBooster::insertLog(trans('crudbooster.log_try_view', ['module' => 'Module Generator - Step 5']));
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    $id = Request::input('id');
    $row = DB::table('cms_moduls')->where('id', $id)->first();

    $post = Request::all();

    // Whitelist: solo le chiavi realmente esposte dal form dello step5
    // (resources/views/crudbooster/module_generator/step5.blade.php)
    // possono diventare proprieta' del controller generato. Prima si
    // iterava su OGNI chiave POST (tranne _token/id/submit): sia la chiave
    // (nome di proprieta' PHP) sia il valore erano controllati
    // dall'attaccante e incollati grezzi in un literal a doppi apici senza
    // alcun escaping - RCE. var_export() sui valori (literal a singoli
    // apici, mai interpolato) chiude anche l'iniezione via valore, ma senza
    // whitelist un attaccante potrebbe comunque impostare proprieta'
    // arbitrarie del controller (es. table) - la whitelist resta
    // necessaria.
    $allowed_keys = [
      'title_field', 'limit', 'orderby', 'global_privilege',
      'button_table_action', 'button_bulk_action', 'button_action_style',
      'button_add', 'button_edit', 'button_delete', 'button_detail',
      'button_filter', 'button_import', 'button_export',
    ];

    $config = ['table' => $row->table_name];
    foreach ($allowed_keys as $key) {
      if (array_key_exists($key, $post)) {
        $config[$key] = $post[$key];
      }
    }

    $script_config = [];
    $i = 0;
    foreach ($config as $key => $val) {
      if ($val === 'true' || $val === 'false') {
        $value = $val;
      } else {
        $value = var_export($val, true);
      }

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

    $this->forgetWizardDraft($id, 5);

    return redirect()->route('ModulsControllerGetIndex')->with(['message' => trans('crudbooster.alert_update_data_success'), 'message_type' => 'success']);
  }

  // Esporta la definizione di un modulo custom (non i suoi dati) in un
  // JSON portabile: riga cms_moduls, struttura tabella, e i tre blocchi
  // scritti dal wizard nel controller generato (config/col/form). Legge
  // col/form/config istanziando il controller generato e chiamando
  // cbInit() invece di riparsare il sorgente PHP con eval() (pattern gia'
  // usato da getStep3()/getStep4()/getStep5() per rileggere lo stato
  // corrente, ma solo per popolare il form del wizard) - stesso risultato,
  // senza eval() su testo estratto.
  public function getExport($id)
  {
    $this->cbLoader();

    if (!CRUDBooster::isSuperadmin()) {
      CRUDBooster::insertLog(trans('crudbooster.log_try_view', ['module' => 'Module Generator - Export']));
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    $row = DB::table('cms_moduls')->where('id', $id)->whereNull('deleted_at')->first();

    if (!$row || $row->is_protected) {
      return CRUDBooster::redirect(CRUDBooster::mainpath(), 'Questo modulo non e\' esportabile', 'warning');
    }

    $controller_class = 'App\\Http\\Controllers\\' . $row->controller;

    if (!class_exists($controller_class)) {
      return CRUDBooster::redirect(CRUDBooster::mainpath(), 'Controller del modulo non trovato sul disco', 'danger');
    }

    $instance = new $controller_class;
    $instance->cbInit();

    $config = ['table' => $row->table_name];
    foreach ([
      'title_field', 'limit', 'orderby', 'global_privilege',
      'button_table_action', 'button_bulk_action', 'button_action_style',
      'button_add', 'button_edit', 'button_delete', 'button_detail',
      'button_filter', 'button_import', 'button_export',
    ] as $key) {
      $config[$key] = $instance->$key;
    }

    $export = [
      'format_version' => 1,
      'exported_at' => now(),
      'module' => [
        'name' => $row->name,
        'icon' => $row->icon,
      ],
      'table' => [
        'name' => $row->table_name,
        'columns' => CRUDBooster::getTableStructure($row->table_name),
      ],
      'config' => $config,
      'col' => $instance->col,
      'form' => $instance->form,
    ];
    // layout a blocchi del form (docs/refactoring/197): chiave opzionale, assente nei moduli senza layout
    if (ModuleGeneratorLayout::isActive($instance->form_layout)) {
      $export['form_layout'] = $instance->form_layout;
    }

    $filename = ModuleHelper::sql_name_encode($row->table_name) . '_module_export.json';

    return response(json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 200, [
      'Content-Type' => 'application/json',
      'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ]);
  }

  // Pagina di upload del JSON esportato da getExport().
  public function getImport()
  {
    $this->cbLoader();

    if (!CRUDBooster::isSuperadmin()) {
      CRUDBooster::insertLog(trans('crudbooster.log_try_view', ['module' => 'Module Generator - Import']));
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    $page_title = 'Import Module';

    return view('crudbooster::module_generator.import', compact('page_title'));
  }

  // Importa un modulo da un JSON creato da getExport(): crea la tabella se
  // manca, genera il controller (stessa CRUDBooster::generateController()
  // usata da postStep1()) e vi scrive i blocchi col/form/config con la
  // stessa tecnica gia' in uso in postStep3()/postStep4()/postStep5()
  // (var_export/min_var_export, mai interpolazione grezza - vedi
  // docs/refactoring/068). Non crea ne' menu ne' righe cms_privileges_roles:
  // deciso con l'utente, l'assegnazione a menu/ruoli resta manuale in
  // Privileges dopo l'import (vedi docs/refactoring/071 sullo stesso
  // motivo per il modulo Logs).
  public function postImport()
  {
    $this->cbLoader();

    if (!CRUDBooster::isSuperadmin()) {
      CRUDBooster::insertLog(trans('crudbooster.log_try_view', ['module' => 'Module Generator - Import']));
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    if (!Request::hasFile('json_file') || !Request::file('json_file')->isValid()) {
      return redirect()->back()->with(['message' => 'Seleziona un file JSON valido', 'message_type' => 'warning']);
    }

    $file = Request::file('json_file');

    if (strtolower($file->getClientOriginalExtension()) !== 'json') {
      return redirect()->back()->with(['message' => 'Il file deve avere estensione .json', 'message_type' => 'warning']);
    }

    $data = json_decode(file_get_contents($file->getRealPath()), true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data) || empty($data['module']['name']) || empty($data['table']['name'])) {
      return redirect()->back()->with(['message' => 'File JSON non valido o incompleto', 'message_type' => 'danger']);
    }

    $name = $data['module']['name'];
    $icon = $data['module']['icon'] ?? 'bi bi-box';
    $table_name = ModuleHelper::sql_name_encode($data['table']['name']);

    if (DB::table('cms_moduls')->where('name', $name)->whereNull('deleted_at')->count()) {
      return redirect()->back()->with(['message' => "Esiste gia' un modulo chiamato \"{$name}\"", 'message_type' => 'warning']);
    }

    // stesso divieto di save_table() sulle tabelle riservate del framework
    if (substr($table_name, 0, strlen(config('app.reserved_tables_prefix'))) === config('app.reserved_tables_prefix')) {
      return redirect()->back()->with(['message' => 'Nome tabella riservato, non importabile', 'message_type' => 'danger']);
    }

    if (DB::table('cms_moduls')->where('table_name', $table_name)->whereNull('deleted_at')->count()) {
      return redirect()->back()->with(['message' => "La tabella \"{$table_name}\" e' gia' usata da un altro modulo", 'message_type' => 'warning']);
    }

    if (!Schema::hasTable($table_name)) {
      $columns = $data['table']['columns'] ?? [];

      if (empty($columns)) {
        return redirect()->back()->with(['message' => 'Il file JSON non contiene colonne per creare la tabella', 'message_type' => 'danger']);
      }

      $this->createImportedTable($table_name, $columns);
    }

    $controller = CRUDBooster::generateController($table_name, $name);

    $id = DB::table('cms_moduls')->max('id') + 1;
    DB::table('cms_moduls')->insert([
      'id' => $id,
      'name' => $name,
      'icon' => $icon,
      'path' => $table_name,
      'table_name' => $table_name,
      'controller' => $controller,
      'is_protected' => 0,
      'is_active' => 1,
      'created_at' => now(),
    ]);

    $this->writeImportedColumns($controller, $data['col'] ?? []);
    $this->writeImportedForm($controller, $data['form'] ?? []);
    $this->writeImportedLayout($controller, $data['form_layout'] ?? null, $data['form'] ?? []);
    $this->writeImportedConfig($controller, $table_name, $data['config'] ?? []);

    return CRUDBooster::redirect(CRUDBooster::mainpath(), "Modulo \"{$name}\" importato con successo", 'success');
  }

  // Crea la tabella per un modulo importato: stesse colonne "di cornice"
  // (id/group/tenant/created_at/created_by/updated_at/updated_by/deleted_at/
  // deleted_by) aggiunte da save_table() quando crea una tabella nuova dal
  // wizard - vedi save_table() sopra. Duplicato intenzionalmente invece di
  // richiamare save_table() (pensata per leggere da $_POST del wizard, non
  // da un array gia' pronto) per non rischiare di alterare il comportamento
  // del wizard esistente.
  private function createImportedTable($table_name, array $columns)
  {
    Schema::create($table_name, function (Blueprint $table) use ($columns) {
      foreach ($columns as $col) {
        if (empty($col['name'])) {
          continue;
        }

        $columnname = ModuleHelper::sql_name_encode($col['name']);
        $size = $col['size'] ?? null;

        switch ($col['type'] ?? 'text') {
          case 'number':
            $table->integer($columnname)->length($size ?: 11)->nullable();
            break;
          case 'boolean':
            $table->boolean($columnname)->nullable();
            break;
          default:
            $table->string($columnname, $size ?: 255)->nullable();
            break;
        }
      }

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
  }

  // Sostituisce il contenuto tra due marcatori "# START ... # END" nel
  // controller generato, stesso schema usato da postStep3()/postStep4()/
  // postStep5() per riscrivere i rispettivi blocchi.
  private function replaceControllerBlock($controller, $start_marker, $end_marker, $body)
  {
    $path = app_path('Http/Controllers/' . $controller . '.php');
    $raw = explode($start_marker, file_get_contents($path));
    $rraw = explode($end_marker, $raw[1]);

    $file_controller = trim($raw[0]) . "\n\n";
    $file_controller .= "\t\t\t{$start_marker}\n";
    $file_controller .= $body . "\n";
    $file_controller .= "\t\t\t{$end_marker}\n\n";
    $file_controller .= "\t\t\t" . trim($rraw[1]);

    file_put_contents($path, $file_controller);
  }

  // Scrive il blocco colonne ($this->col[]) da un array importato. Stessa
  // whitelist di chiavi che postStep3() sa scrivere - non e' un confine di
  // sicurezza (min_var_export() esclude gia' qualunque interpolazione
  // grezza, stesso fix di docs/refactoring/068), solo coerenza con cio' che
  // il wizard stesso genera.
  private function writeImportedColumns($controller, array $columns)
  {
    $allowed_keys = ['label', 'name', 'join', 'image', 'download', 'width', 'callback_php', 'query', 'visible'];
    $script_cols = ["\t\t\t" . '$this->col = [];'];

    foreach ($columns as $col) {
      if (empty($col['name']) || !isset($col['label'])) {
        continue;
      }

      $filtered = array_intersect_key($col, array_flip($allowed_keys));
      $script_cols[] = "\t\t\t" . '$this->col[] = ' . min_var_export($filtered) . ';';
    }

    $this->replaceControllerBlock(
      $controller,
      '# START COLUMNS DO NOT REMOVE THIS LINE',
      '# END COLUMNS DO NOT REMOVE THIS LINE',
      implode("\n", $script_cols)
    );
  }

  // Scrive il blocco form ($this->form[]) da un array importato. Stessa
  // whitelist minima di postStep4() (label/name obbligatori), il resto
  // delle chiavi passa cosi' com'e' - stesso motivo di sicurezza di sopra:
  // min_var_export() e' la protezione reale, la whitelist e' solo pulizia.
  private function writeImportedForm($controller, array $fields)
  {
    $script_form = ["\t\t\t" . '$this->form = [];'];

    foreach ($fields as $field) {
      if (empty($field['label']) || empty($field['name'])) {
        continue;
      }

      $script_form[] = "\t\t\t" . '$this->form[] = ' . min_var_export($field) . ';';
    }

    $this->replaceControllerBlock(
      $controller,
      '# START FORM DO NOT REMOVE THIS LINE',
      '# END FORM DO NOT REMOVE THIS LINE',
      implode("\n", $script_form)
    );
  }

  // Scrive il blocco FORM LAYOUT di un modulo importato (se il JSON lo
  // contiene), dopo averlo ripulito con la stessa validazione del wizard:
  // nomi che esistono nel form importato, posizioni nella griglia. Un layout
  // non valido si scarta (il modulo resta con il form piatto).
  private function writeImportedLayout($controller, $layout, array $form)
  {
    if (!is_array($layout)) {
      return;
    }
    try {
      $clean = ModuleGeneratorLayout::build($layout, $this->layoutPlaceableFields($form));
    } catch (\InvalidArgumentException $e) {
      return;
    }
    $path = app_path('Http/Controllers/' . $controller . '.php');
    file_put_contents($path, ModuleGeneratorLayout::replaceLayoutBlock(file_get_contents($path), $clean));
  }

  // Scrive il blocco configurazione, stessa whitelist di chiavi di
  // postStep5() (l'unica differenza: qui non c'e' un $post da cui leggere,
  // i valori arrivano gia' pronti dal JSON importato).
  private function writeImportedConfig($controller, $table_name, array $config)
  {
    $allowed_keys = [
      'title_field', 'limit', 'orderby', 'global_privilege',
      'button_table_action', 'button_bulk_action', 'button_action_style',
      'button_add', 'button_edit', 'button_delete', 'button_detail',
      'button_filter', 'button_import', 'button_export',
    ];

    $script_config = ["\t\t\t" . '$this->table = ' . var_export($table_name, true) . ';'];

    foreach ($allowed_keys as $key) {
      if (!array_key_exists($key, $config)) {
        continue;
      }

      $value = $config[$key];
      $script_config[] = "\t\t\t" . '$this->' . $key . ' = ' . var_export($value, true) . ';';
    }

    $this->replaceControllerBlock(
      $controller,
      '# START CONFIGURATION DO NOT REMOVE THIS LINE',
      '# END CONFIGURATION DO NOT REMOVE THIS LINE',
      implode("\n", $script_config)
    );
  }

  public function postAddSave()
  {
    $this->cbLoader();

    if (!CRUDBooster::isCreate() && $this->global_privilege == false) {
      CRUDBooster::insertLog(trans('crudbooster.log_try_add_save', [
        'name' => Request::input($this->title_field),
        'module' => CRUDBooster::getCurrentModule()->name,
      ]));
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
    }

    $validationResult = $this->validation();
    if ($validationResult instanceof \Symfony\Component\HttpFoundation\Response) {
      return $validationResult;
    }
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
        'icon' => 'bi bi-plus-lg',
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
        'icon' => 'bi bi-list',
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
      return CRUDBooster::redirect(Request::get('return_url'), trans("crudbooster.alert_add_data_success"), 'success');
    } else {
      if (Request::get('submit') == trans('crudbooster.button_save_more')) {
        return CRUDBooster::redirect(CRUDBooster::mainpath('add'), trans("crudbooster.alert_add_data_success"), 'success');
      } else {
        return CRUDBooster::redirect(CRUDBooster::mainpath(), trans("crudbooster.alert_add_data_success"), 'success');
      }
    }
  }

  public function postEditSave($id, $validate = null)
  {
    $this->cbLoader();

    $row = DB::table($this->table)->where($this->primary_key, $id)->first();

    if (!CRUDBooster::isUpdate() && $this->global_privilege == false) {
      CRUDBooster::insertLog(trans("crudbooster.log_try_add", ['name' => $row->{$this->title_field}, 'module' => CRUDBooster::getCurrentModule()->name]));
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    $validationResult = $this->validation();
    if ($validationResult instanceof \Symfony\Component\HttpFoundation\Response) {
      return $validationResult;
    }
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

    return CRUDBooster::redirect(Request::server('HTTP_REFERER'), trans('crudbooster.alert_update_data_success'), 'success');
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
      return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
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
          // tipi aggiuntivi, inviati solo dal passo Campi del wizard v2
          case 'plaintext':
            $column->type = 'text';
            break;
          case 'longtext':
            $column->type = 'longText';
            break;
          case 'decimal':
            $column->type = 'decimal';
            break;
          case 'date':
            $column->type = 'date';
            break;
          case 'datetime':
            $column->type = 'dateTime';
            break;
          case 'time':
            $column->type = 'time';
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
          } elseif (in_array($type, ['text', 'longText', 'date', 'dateTime', 'time'], true)) {
            $table->$type("{$columnname}")->nullable();
          } elseif ($type == 'decimal') {
            list($precision, $scale) = array_map('intval', explode(',', $dynamic_column->size . ',0'));
            $table->decimal("{$columnname}", $precision, $scale)->nullable();
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
            // Le colonne dinamiche di questo generatore sono sempre create
            // nullable() (mai NOT NULL/unsigned/con default) - vedi il ramo
            // "add column" piu' sopra e la Schema::create() per la nuova
            // tabella. Da Laravel 11 change() droppa i modificatori non
            // ripetuti esplicitamente: senza questo nullable() la colonna
            // diventerebbe NOT NULL dopo un cambio di tipo dall'admin.
            $table->$type("{$columnname}")->nullable()->change();
          });
          add_log_ch('mg edit table change column data type', 'Column ' . $columnname . ' from ' . $existing_table[$index]['type'] . ' to ' . $column->type);
        }

        if ($request['size'][$loop_index] != $existing_table[$index]['size']) {
          $result = Schema::table($table_name, function (Blueprint $table) use ($column) {
            $type = $column->type;
            $columnname = ModuleHelper::sql_name_encode($column->name);
            if ($type == 'integer') {
              //integer defaults to autoincrement without second parameter set to false if length is set as third attribute of the integer method
              // nullable(): vedi commento sul change() di tipo poco sopra.
              $table->integer("{$columnname}")->length($column->size)->nullable()->change();
            } else {
              $table->$type("{$columnname}", $column->size)->nullable()->change();
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
