<?php
namespace crocodicstudio\crudbooster\helpers;

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
        )
      )
    ) {
      //log denied access
      CRUDBooster::insertLog(trans("crudbooster.log_try_view", [
          'name' => $module->{$this->title_field},
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
      !CRUDBooster::isSuperadmin() AND
      (
        //check correct privilege role
        (
          !CRUDBooster::isUpdate() &&
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
        )
      )
    ) {
      //log denied access
      CRUDBooster::insertLog(trans("crudbooster.log_try_add", [
        'name' => $module->{$this->title_field},
        'module' => CRUDBooster::getCurrentModule()->name
      ]));
      //kick out
      CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }
  }

  /*
  * Check if current user can delete the record $row
  *
  * @param object $module a module instance //TODO not really a module...
  * @param object a table row or record
  */
  public static function can_delete($module, $row) {
    // if it's a manually generated module..
    if (
      //admin can always see everything
      !CRUDBooster::isSuperadmin() AND
      (
        //check correct privilege role
        (
          !CRUDBooster::isDelete() &&
          $module->global_privilege == false ||
          $module->button_delete == false
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
        )
      )
    ) {
      //log denied access
      CRUDBooster::insertLog(trans("crudbooster.log_try_delete", [
        'name' => $module->{$this->title_field},
        'module' => CRUDBooster::getCurrentModule()->name
      ]));
      //kick out
      CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }
  }
}
