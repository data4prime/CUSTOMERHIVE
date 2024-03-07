<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use \crocodicstudio\crudbooster\helpers\UserHelper;

class Menu extends Model
{
    protected $table = 'cms_menus';

    /**
    * Returns the ids of the menu's tenants
    *
    * @return array[int]
    */
    public function tenants() {
      return MenuTenants::where('menu_id',$this->id)->pluck('tenant_id')->toArray();
    }

    /**
    * add user's tenant as menu default tenant
    */
    public function assign_default_tenant() {
      $menu_tenant = new MenuTenants();
      $menu_tenant->menu_id = $this->id;
      $menu_tenant->tenant_id = UserHelper::current_user_tenant();
      $menu_tenant->save();
    }

    /**
    * add user's primary group as menu default group
    */
    public function assign_default_group() {
      $menu_group = new MenuGroups();
      $menu_group->menu_id = $this->id;
      $menu_group->group_id = UserHelper::current_user_primary_group();
      $menu_group->save();
    }

    /**
    * Returns the names of the menu's tenants.
    *
    * @return string comma separated list of menu's tenants names
    */
    public function tenants_name() {
      $names = '';
      $tenants = $this->tenants();
      foreach ($tenants as $key => $tenant_id) {
        $names .= Tenant::find($tenant_id)->name;
        //don't add the comma after the last tenant name
        if($key < count($tenants)-1){
          $names .= ', ';
        }
      }
      return $names;
    }
}
