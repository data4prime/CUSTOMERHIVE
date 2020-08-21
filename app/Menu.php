<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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
