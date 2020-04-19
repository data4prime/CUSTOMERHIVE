<?php
namespace crocodicstudio\crudbooster\helpers;

use \App\User;
use \App\Group;
use \App\Tenant;

class UserHelper  {

  /**
  *	get current user's primary group
  *
  * @return int id of the group
  */
  public static function current_user_primary_group() {
    $user_id = CRUDBooster::myId();
    $user = User::find($user_id);
    return $user->primary_group;
  }

  /**
  *	get current user's tenant
  *
  * @return int id of the tenant
  */
  public static function current_user_tenant() {
    $user_id = CRUDBooster::myId();
    $user = User::find($user_id);
    return $user->tenant;
  }
}
