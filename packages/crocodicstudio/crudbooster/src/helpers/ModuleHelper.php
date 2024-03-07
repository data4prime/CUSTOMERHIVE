<?php

namespace crocodicstudio\crudbooster\helpers;

use App\ModuleTenants;
use App\Tenant;
use App\GroupTenants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModuleHelper
{

  /**
   *	Encode a column or table name
   *
   * @param string name as user input
   *
   * @return string cleaned name
   */
  public static function sql_name_encode($name)
  {
    //lower case only
    $name = strtolower(trim($name));
    //one or multiple spaces to single underscore
    $name = preg_replace('/\s+/', '_', $name);
    //spaces to underscores
    // $name = str_replace(' ', '_', $name );
    //lettere accentate
    $name = str_replace('à', 'a', $name);
    $name = str_replace('è', 'e', $name);
    $name = str_replace('é', 'e', $name);
    $name = str_replace('ì', 'i', $name);
    $name = str_replace('ò', 'o', $name);
    $name = str_replace('ù', 'u', $name);
    //remove all special characters except underscore
    return preg_replace('/[^A-Za-z0-9\_]/', '', $name);
  }

  /**
   *	Decode a column or table name into a more human friendly label
   *
   * @param string name as user input
   *
   * @return string cleaned name
   */
  public static function sql_name_decode($name)
  {
    //single underscore to spaces
    $name = str_replace('_', ' ', $name);
    //first letter of each word uppercase
    return ucwords($name);
  }

  public static function is_manually_generated($table_name)
  {
    //if table name start with mg_
    if (substr($table_name, 0, strlen(config('app.module_generator_prefix'))) === config('app.module_generator_prefix')) {
      //it's a manually generated table
      return true;
    }
    return false;
  }

  /**
   * Check if current user can view the record $row
   *
   * @param object $module a module instance //TODO not really a module...
   * @param object a table row or record
   *
   * @return boolean true if user can view module's row, false otherwise
   */
  public static function can_view($module, $row)
  {
    //admin can always see everything
    if (CRUDBooster::isSuperadmin()) {
      return true;
    }

    //check correct privilege role
    if (
      !CRUDBooster::isRead() && //isread o isview? TODO chiarire con view / details
      $module->global_privilege == false
    ) {
      return false;
    }

    if ($module->button_detail == false) {
      //module's rows details shouldn't be view
      return false;
    }

    //check group/tenant
    if (
      //check this only on manually generated modules
      ModuleHelper::is_manually_generated($module->table) &&
      //..row group is one of user's groups
      in_array($row->group, UserHelper::current_user_groups()) &&
      //..row tenant is user's tenant
      $row->tenant == UserHelper::current_user_tenant()
    ) {
      return true;
    }

    //check tenant
    if (
      //check this only on users
      $module->table == 'cms_users' &&
      //..then filter by tenant
      $row->tenant == UserHelper::current_user_tenant()
    ) {
      return false;
    }

    //check tenant
    if (
      //check this only on groups
      $module->table == 'groups' &&
      //..then filter by tenant
      GroupTenants::where('group_id', $row->id)->where('tenant_id', UserHelper::current_user_tenant())->count() > 0
    ) {
      return true;
    }

    return false;
  }

  /**
   * Check if current user can edit the record $row
   *
   * @param object $module a module instance (not a model...)
   * @param object a table row or record
   *
   * @return boolean true if user can edit module's row, false otherwise
   */
  public static function can_edit($module, $row)
  {
    //admin can always see everything
    if (CRUDBooster::isSuperadmin()) {
      //var_dump('can edit true1'.$row->id);
      return true;
    }

    if ($module->table == 'cms_menus') {
      return UserHelper::can_menu('edit', $row->id);
    }

    if ($module->table == 'cms_users') {
      return UserHelper::can_do_on_user('edit', $row->id);
    }

    //check correct privilege role
    if (
      //this should filter basic users
      !CRUDBooster::isUpdate()
      &&
      $module->global_privilege == false
    ) {
      //user doesn't have the correct privilege role for this module
      //var_dump('can edit false1'.$row->id);
      return false;
    }

    if ($module->button_edit == false) {
      //module's rows shouldn't be edited
      //var_dump('can edit false2'.$row->id);
      return false;
    }

    //from here checks should be only for tenantadmin

    //get row tenant
    if (empty($row->tenant) && Schema::hasColumn($module->table, 'tenant')) {
      $row->tenant = DB::select(DB::raw("select tenant from " . $module->table . " where id='" . $row->id . "' "))[0]->tenant;
    }
    //get row group
    if (empty($row->group) && Schema::hasColumn($module->table, 'group')) {
      $row->group = DB::select(DB::raw("select `group` from " . $module->table . " where id='" . $row->id . "' "))[0]->group;
    }

    //check group/tenant on manually generated modules
    if (
      //check this
      ModuleHelper::is_manually_generated($module->table)
      &&
      (
        //..then filter on group && tenant columns
        //TODO tenantadmin deve essere limitato dai gruppi sull'edit degli mg?
        //user must be member of the record's group or tenantadmin
        (in_array($row->group, UserHelper::current_user_groups())
          or
          UserHelper::isTenantAdmin()
        )
        &&
        $row->tenant == UserHelper::current_user_tenant()
      )
    ) {
      //var_dump('can edit true2'.$row->id);
      return true;
    }

    //check tenant
    if (
      //check this only on users table (for Tenantadmin)
      $module->table == 'cms_users' &&
      //..then filter by tenant
      $row->tenant == UserHelper::current_user_tenant()
    ) {
      //var_dump('can edit true3'.$row->id);
      return true;
    }

    //check tenant
    if (
      //Tenantadmin can edit groups only if group is part of his tenant && no other
      $module->table == 'groups'
      &&
      //..group must be part of his tenant
      GroupTenants::where('group_id', $row->id)->where('tenant_id', UserHelper::current_user_tenant())->count() > 0
      &&
      //..group must not have other tenants
      GroupTenants::where('group_id', $row->id)->where('tenant_id', '!=', UserHelper::current_user_tenant())->count() == 0
    ) {
      //var_dump('can edit true4'.$row->id);
      return true;
    }

    //var_dump('can edit false3'.$row->id);
    return false;
  }

  /**
   * Check if current user can delete the record $row
   *
   * @param object $module a module instance //TODO not really a module...
   * @param object a table row or record
   *
   * @return boolean true if user can delete module's row, false otherwise
   */
  public static function can_delete($module, $row)
  {

    //admin can always do everything
    if (CRUDBooster::isSuperadmin()) {
      return true;
    }

    if ($module->table == 'cms_menus') {
      return UserHelper::can_menu('delete', $row->id);
    }

    if ($module->table == 'cms_users') {
      return UserHelper::can_do_on_user('edit', $row->id);
    }

    //check correct privilege role
    if (
      !CRUDBooster::isDelete()
      &&
      $module->global_privilege == false
    ) {
      //user doesn't have the correct privilege role for this module
      //var_dump('false2');
      return false;
    }

    if ($module->button_delete == false) {
      //module's rows shouldn't be deleted
      //var_dump('false3');
      return false;
    }

    if (empty($row->tenant) && Schema::hasColumn($module->table, 'tenant')) {
      $row->tenant = DB::select(DB::raw("select tenant from " . $module->table . " where id='" . $row->id . "' "))[0]->tenant;
    }
    if (empty($row->group) && Schema::hasColumn($module->table, 'group')) {
      $row->group = DB::select(DB::raw("select group from " . $module->table . " where id='" . $row->id . "' "))[0]->group;
    }

    //check group/tenant
    if (
      //check this only on manually generated modules
      ModuleHelper::is_manually_generated($module->table)
      &&
      //..then filter on group && tenant columns
      //user must be member of the record's group or tenantadmin
      (in_array($row->group, UserHelper::current_user_groups())
        or
        UserHelper::isTenantAdmin()
      )
      &&
      //user must be member of the record's tenant
      $row->tenant == UserHelper::current_user_tenant()
    ) {
      return true;
    }

    //check tenant
    if (
      //check this only on users
      $module->table == 'cms_users'
      &&
      //..filter by tenant
      $row->tenant == UserHelper::current_user_tenant()
    ) {
      //var_dump('true4');
      return true;
    }

    //check tenant
    if (
      //check this only on groups
      $module->table == 'groups'
      &&
      //..group must be part of his tenant
      GroupTenants::where('group_id', $row->id)->where('tenant_id', UserHelper::current_user_tenant())->count() > 0
      &&
      //..group must not have other tenants
      GroupTenants::where('group_id', $row->id)->where('tenant_id', '!=', UserHelper::current_user_tenant())->count() == 0
    ) {
      return true;
    }

    //var_dump('false6');
    return false;
  }

  /**
   * Add default columns to MG_ modules index table
   *
   * add tenant && group columns for superadmin
   * add group column for Tenantadmin
   */
  public static function add_default_column_headers($table_name, $columns)
  {
    //only add columns for manually generated modules
    if (ModuleHelper::is_manually_generated($table_name)) {
      $tenant_is_present = false;
      $group_is_present = false;
      //check if group or tenant columns are already set
      foreach ($columns as $column) {
        if ($column['name'] == "tenant") {
          $tenant_is_present = true;
        }
        if ($column['name'] == "group") {
          $group_is_present = true;
        }
      }
      //only superadmin can see tenant
      if (CRUDBooster::isSuperadmin() && !$tenant_is_present) {
        $columns[] = ["label" => "Tenant", "name" => "tenant", "join" => "tenants,name"];
      }
      //only superadmin && Tenantadmin can see group
      if ((UserHelper::isTenantAdmin() or CRUDBooster::isSuperadmin()) && !$group_is_present) {
        $columns[] = ["label" => "Group", "name" => "group", "join" => "groups,name"];
      }
    }
    return $columns;
  }

  /**
   * Add default fields to MG_ modules forms
   *
   * add tenant && group columns for superadmin
   * add group column for Tenantadmin
   */
  public static function add_default_form_fields($table_name, $fields)
  {
    //only add fields for manually generated modules
    if (ModuleHelper::is_manually_generated($table_name)) {
      $tenant_is_present = false;
      $group_is_present = false;
      //check if group or tenant columns are already set in this form
      foreach ($fields as $field) {
        if ($field['name'] == "tenant") {
          $tenant_is_present = true;
        }
        if ($field['name'] == "group") {
          $group_is_present = true;
        }
      }
      //only superadmin can edit tenant
      if (CRUDBooster::isSuperadmin() && !$tenant_is_present) {
        $fields[] = [
          'label' => 'Tenant',
          'name' => 'tenant',
          "type" => "select2",
          "datatable" => "tenants,name",
          'required' => true,
          'validation' => 'required|int|min:1',
          'value' => UserHelper::current_user_tenant() //default value per creazione nuovo record
        ];
      }
      //only superadmin && Tenantadmin can see group
      if ((UserHelper::isTenantAdmin() or CRUDBooster::isSuperadmin()) && !$group_is_present) {
        if (CRUDBooster::isSuperadmin()) {
          //superadmin vede i gruppi come cascading dropdown in base al tenant
          $fields[] = [
            'label' => 'Group',
            'name' => 'group',
            "type" => "select2",
            "datatable" => "groups,name",
            'required' => true,
            'validation' => 'required|int|min:1',
            'value' => UserHelper::current_user_primary_group(), //default value per creazione nuovo record
            'parent_select' => 'tenant',
            'parent_crosstable' => 'group_tenants',
            'fk_name' => 'tenant_id',
            'child_crosstable_fk_name' => 'group_id'
          ];
        } else {
          //Tenantadmin vede tenant in readonly (disabled) ma può modificare il group
          $fields[] = [
            "label" => "Tenant",
            "name" => "tenant",
            'required' => true,
            'type' => 'select',
            'datatable' => "tenants,name",
            'default' => UserHelper::current_user_tenant_name(),
            'value' => UserHelper::current_user_tenant(),
            'disabled' => true,
            'style' => "display:none;"
          ];
          //Tenantadmin vede solo i gruppi del proprio tenant
          $fields[] = [
            'label' => 'Group',
            'name' => 'group',
            "type" => "select2",
            "datatable" => "groups,name",
            "required" => true,
            'validation' => 'required|int|min:1',
            'default' => UserHelper::current_user_primary_group_name(),
            'value' => UserHelper::current_user_primary_group(),
            //Tenantadmin vede nella dropdown solo i gruppi del proprio tenant
            'parent_select' => 'tenant',
            'parent_crosstable' => 'group_tenants',
            'fk_name' => 'tenant_id',
            'child_crosstable_fk_name' => 'group_id'
          ];
        }
      }
    }
    return $fields;
  }

  /**
   * Check if a module is enabled for a tenant
   *
   * @param int module's id
   * @param int tenant's id
   *
   * @return boolean true if the module is enabled,
   * @return boolean false otherwise
   */
  public static function is_enabled($module_id, $tenant_id)
  {
    $result = ModuleTenants::where('tenant_id', $tenant_id)
      ->where('module_id', $module_id)
      ->first();
    if (empty($result)) {
      return false;
    }
    return true;
  }

  /**
   * Check if a module is enabled for all tenants
   *
   * @param int module's id
   *
   * @return boolean true if the module is enabled,
   * @return boolean false otherwise
   */
  public static function is_bulk_enabled($module_id)
  {
    $tenants_enabled_count = ModuleTenants::where('module_id', $module_id)->count();
    $tenants_count = Tenant::count();

    if ($tenants_enabled_count == $tenants_count) {
      return true;
    }
    return false;
  }

  /**
   * Check if a module is enabled for all tenants
   *
   * @param int module's id
   *
   * @return boolean true if the module is enabled,
   * @return boolean false otherwise
   */
  public static function update_enabled_tenants($module_tenant_data)
  {
    //reset the table at every save
    $clean = ModuleTenants::query()->truncate();
    if ($module_tenant_data)
      foreach ($module_tenant_data as $module_id => $value) {
        foreach ($value as $tenant_id => $nothing) {
          if (!empty($module_id) && !empty($tenant_id)) {
            $insert = new ModuleTenants;
            $insert->module_id = $module_id;
            $insert->tenant_id = $tenant_id;
            $insert->save();
          }
        }
      }
  }

  // get a collection of the modules that can be enabled or disabled to tenants
  public static function getEditableModules()
  {
    return DB::table("cms_moduls")
      ->where('is_protected', 0)
      ->where('deleted_at', null)
      ->where('table_name', 'like', config('app.module_generator_prefix') . '%')
      ->orWhere('table_name', 'groups')
      ->orWhere('table_name', 'cms_users')
      ->orWhere('table_name', 'cms_menus')
      ->select("cms_moduls.*")
      ->orderby("name", "asc")
      ->get();
  }
}
