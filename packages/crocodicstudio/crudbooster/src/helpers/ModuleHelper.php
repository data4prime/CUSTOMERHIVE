<?php
namespace crocodicstudio\crudbooster\helpers;

use App\ModuleTenants;
use App\Tenant;

class ModuleHelper  {

  /**
  *	Encode a column or table name
  *
  * @param string name as user input
  *
  * @return string cleaned name
  */
  public static function sql_name_encode($name) {
    //lower case only
    $name = strtolower(trim($name));
    //one or multiple spaces to single underscore
    $name = preg_replace('/\s+/', '_',$name);
    //spaces to underscores
    // $name = str_replace(' ', '_', $name );
    //lettere accentate
    $name = str_replace('à', 'a',$name);
    $name = str_replace('è', 'e',$name);
    $name = str_replace('é', 'e',$name);
    $name = str_replace('ì', 'i',$name);
    $name = str_replace('ò', 'o',$name);
    $name = str_replace('ù', 'u',$name);
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
  public static function sql_name_decode($name) {
    //single underscore to spaces
    $name = str_replace('_', ' ',$name);
    //first letter of each word uppercase
    return ucwords($name);
  }

  public static function is_manually_generated($table_name) {
    //if table name start with mg_
    if(substr($table_name, 0, strlen(config('app.module_generator_prefix'))) === config('app.module_generator_prefix')){
      //it's a manually generated table
      return true;
    }
    return false;
  }

  /*
  * Check if current user can view the record $row
  *
  * @param object $module a module instance //TODO not really a module...
  * @param object a table row or record
  */
  public static function can_view($module, $row) {
    // if it's a manually generated module..
    if (
      //admin can always see everything
      !CRUDBooster::isSuperadmin() AND
      (
        //check correct privilege role
        (
          !CRUDBooster::isRead() &&
          $module->global_privilege == false ||
          $module->button_edit == false
        ) OR
        //check group/tenant
        (
          //check this only on manually generated modules
          ModuleHelper::is_manually_generated($module->table) AND
          !(
            //..then filter on group and tenant columns
            in_array($row->group, UserHelper::current_user_groups()) AND
            $row->tenant == UserHelper::current_user_tenant()
          )
        ) OR
        //check tenant
        (
          //check this only on groups and users for Tenantadmin
          UserHelper::isTenantAdmin() AND
          (
            $module->table == 'groups' OR
            $module->table == 'cms_users'
          ) AND
          //..then filter by tenant
          $row->tenant !== UserHelper::current_user_tenant()
        )
      )
    ) {
      //log denied access
      CRUDBooster::insertLog(trans("crudbooster.log_try_view", [
          'name' => $module->table,
          'module' => CRUDBooster::getCurrentModule()->name,
      ]));
      //kick out
      CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }
  }

  /*
  * Check if current user can edit the record $row
  *
  * @param object $module a module instance //TODO not really a module...
  * @param object a table row or record
  */
  public static function can_edit($module, $row) {
    // if it's a manually generated module..
    if (
      //admin can always see everything
      !CRUDBooster::isSuperadmin()
      AND
      (
        //check correct privilege role
        (
          !CRUDBooster::isUpdate()
          AND
          $module->global_privilege == false
          OR
          $module->button_edit == false
        )
        OR
        //check group/tenant
        (
          //check this only on manually generated modules
          ModuleHelper::is_manually_generated($module->table)
          AND
          !(
            //..then filter on group and tenant columns
            in_array($row->group, UserHelper::current_user_groups())
            AND
            $row->tenant == UserHelper::current_user_tenant()
          )
        )
        OR
        //check tenant
        (
          //check this only on groups and users for Tenantadmin
          UserHelper::isTenantAdmin()
          AND
          (
            $module->table == 'groups'
            OR
            $module->table == 'cms_users'
          )
          AND
          //..then filter by tenant
          $row->tenant !== UserHelper::current_user_tenant()
        )
      )
    ) {
      //log denied access
      CRUDBooster::insertLog(trans("crudbooster.log_try_add", [
        'name' => $row->{$module->title_field},//TODO not sure this is ok. before helper: $module->{$this->title_field}
        'module' => CRUDBooster::getCurrentModule()->name
      ]));
      //kick out
      CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }
  }

  /**
  * Check if current user can delete the record $row
  *
  * @param object $module a module instance //TODO not really a module...
  * @param object a table row or record
  */
  public static function can_delete($module, $row) {

    //admin can always do everything
    if(CRUDBooster::isSuperadmin()) {
      return true;
    }
    if($module->table == 'cms_menus') {
      return UserHelper::can_menu('delete', $row->id);
    }

    //check correct privilege role
    if(
        !CRUDBooster::isDelete()
        AND
        $module->global_privilege == false
    ) {
      //user doesn't have the correct privilege role for this module
      return false;
    }

    if($module->button_delete == false) {
      //module's rows shouldn't be deleted
      return false;
    }

    //check group/tenant
    if(
      //check this only on manually generated modules
      ModuleHelper::is_manually_generated($module->table)
      AND
      //..then filter on group and tenant columns
      //user must be member of the record's group
      in_array($row->group, UserHelper::current_user_groups())
      AND
      //user must be member of the record's tenant
      $row->tenant == UserHelper::current_user_tenant()
    ) {
      return true;
    }

    if(
        //this rule is only for Tenantadmin
        UserHelper::isTenantAdmin()
        AND
        //check this only on groups and users
        in_array($module->table, ['groups','cms_users'])
        AND
        //..then filter by tenant
        $row->tenant !== UserHelper::current_user_tenant()
      ) {
        return true;
      }

      return false;
  }

  /**
  * Add default columns to MG_ modules index table
  *
  * add tenant and group columns for superadmin
  * add group column for Tenantadmin
  */
  public static function add_default_column_headers($table_name, $columns)
  {
    //only add columns for manually generated modules
    if(ModuleHelper::is_manually_generated($table_name))
    {
      $tenant_is_present = false;
      $group_is_present = false;
      //check if group or tenant columns are already set
      foreach ($columns as $column) {
        if($column['name']=="tenant")
        {
          $tenant_is_present = true;
        }
        if($column['name']=="group")
        {
          $group_is_present = true;
        }
      }
      //only superadmin can see tenant
      if(CRUDBooster::isSuperadmin() AND !$tenant_is_present)
      {
        $columns[] = ["label"=>"Tenant","name"=>"tenant","join"=>"tenants,name"];
      }
      //only superadmin and Tenantadmin can see group
      if((UserHelper::isTenantAdmin() OR CRUDBooster::isSuperadmin()) AND !$group_is_present)
      {
  			$columns[] = ["label"=>"Group","name"=>"group","join"=>"groups,name"];
      }
    }
    return $columns;
  }

  /**
  * Add default fields to MG_ modules forms
  *
  * add tenant and group columns for superadmin
  * add group column for Tenantadmin
  */
  public static function add_default_form_fields($table_name, $fields)
  {
    //only add fields for manually generated modules
    if(ModuleHelper::is_manually_generated($table_name))
    {
      $tenant_is_present = false;
      $group_is_present = false;
      //check if group or tenant columns are already set in this form
      foreach ($fields as $field) {
        if($field['name']=="tenant")
        {
          $tenant_is_present = true;
        }
        if($field['name']=="group")
        {
          $group_is_present = true;
        }
      }
      //only superadmin can edit tenant
      if(CRUDBooster::isSuperadmin() AND !$tenant_is_present)
      {
        $fields[] = [
          'label'=>'Tenant',
          'name'=>'tenant',
          "type"=>"select2",
          "datatable"=>"tenants,name",
          'required'=>true,
          'validation'=>'required|int|min:1',
          'value'=>UserHelper::current_user_tenant()//default value per creazione nuovo record
        ];
      }
      //only superadmin and Tenantadmin can see group
      if((UserHelper::isTenantAdmin() OR CRUDBooster::isSuperadmin()) AND !$group_is_present)
      {
        if(CRUDBooster::isSuperadmin())
        {
          //superadmin vede i gruppi come cascading dropdown in base al tenant
          $field = [
            'label'=>'Group',
            'name'=>'group',
            "type"=>"select",
            "datatable"=>"groups,name",
            'required'=>true,
            'validation'=>'required|int|min:1',
            'value'=>UserHelper::current_user_primary_group(),//default value per creazione nuovo record
            'parent_select'=>'tenant'
          ];
          // $field = ['label'=>'Group','name'=>'group',"type"=>"select","datatable"=>"groups,name",'required'=>true,'validation'=>'required|int|min:1','default'=>UserHelper::current_user_primary_group_name(),'value'=>UserHelper::current_user_primary_group(),'parent_select'=>'tenant'];
        }
        else
        {
          //Tenantadmin vede solo i gruppi del proprio tenant
          $field = [
            'label'=>'Group',
            'name'=>'group',
            "type"=>"select2",
            "datatable"=>"groups,name",
            'required'=>true,
            'validation'=>'required|int|min:1',
            'default'=>UserHelper::current_user_primary_group_name(),
            'value'=>UserHelper::current_user_primary_group(),
            //Tenantadmin vede nella dropdown solo i gruppi del proprio tenant
            'datatable_where'=>'tenant = '.UserHelper::current_user_tenant()
          ];
        }
  			$fields[] = $field;
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
    $result = ModuleTenants::where('tenant_id',$tenant_id)
                  ->where('module_id',$module_id)
                  ->first();
    if(empty($result))
    {
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
    $tenants_enabled_count = ModuleTenants::where('module_id',$module_id)->count();
    $tenants_count = Tenant::count();

    if($tenants_enabled_count == $tenants_count)
    {
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
    foreach ($module_tenant_data as $module_id => $value) {
      foreach ($value as $tenant_id => $nothing) {
        if(!empty($module_id) AND !empty($tenant_id))
        {
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
    return \DB::table("cms_moduls")
                  ->where('is_protected', 0)
                  ->where('deleted_at', null)
                  ->where('table_name','like',config('app.module_generator_prefix').'%')
                  ->orWhere('table_name','groups')
                  ->orWhere('table_name','cms_users')
                  ->orWhere('table_name','cms_menus')
                  ->select("cms_moduls.*")
                  ->orderby("name", "asc")
                  ->get();
  }

}
