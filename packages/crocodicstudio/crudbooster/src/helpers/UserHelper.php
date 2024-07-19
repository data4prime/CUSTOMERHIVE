<?php

namespace crocodicstudio\crudbooster\helpers;

use \App\User;
use \App\Group;
use \App\Tenant;
use \App\UsersGroup;
use \App\Role;
use Illuminate\Support\Facades\DB;

class UserHelper
{

  /**
   * Check if current user has permission over a user
   *
   * @param string 'edit' or 'delete'
   * @param int id of the passive user
   * @param int id of the current user
   *
   * @return boolean true if current user has the permission on the user
   */
  public static function can_do_on_user($mode, $target_user_id, $current_user_id = null)
  {

    if (empty($user_id)) {
      $user_id = CRUDBooster::myId();
    }

    if ($user_id == $target_user_id && $mode == 'edit') {
      //user can edit himself
      return true;
    }

    if (UserHelper::isSuperAdmin($user_id)) {
      //superadmin can do everything
      return true;
    }

    if (!UserHelper::isTenantAdmin($user_id)) {
      //basic can't do anything on users
      return false;
    }
    if (UserHelper::tenant($target_user_id) != UserHelper::tenant($user_id)) {
      return false;
    }

    switch ($mode) {
      case 'edit':
        //tenantadmin can edit self
        if ($target_user_id == $user_id) {
          return true;
        }
        //tenantadmin can't edit superadmins or tenantadmins
        if (UserHelper::isSuperAdmin($target_user_id) or UserHelper::isTenantAdmin($target_user_id)) {
          return false;
        } else {
          return true;
        }
        break;

      case 'delete':
        //tenantadmin can't delete superadmins or tenantadmins
        if (UserHelper::isSuperAdmin($target_user_id) or UserHelper::isTenantAdmin($target_user_id)) {
          return false;
        } else {
          return true;
        }
        break;

      default:
        //invalid mode
        return false;
        break;
    }
  }

  /**
   * Check if current user has permission over a menù
   *
   * @param string 'edit' or 'delete'
   * @param int menu's id
   *
   * @return boolean true if current user has the permission on the menu
   */
  public static function can_menu($mode, $menu_id)
  {

    $menu = \App\Menu::find($menu_id);
    if (empty($menu_id) or empty($menu)) {
      return false;
    }
    $tenants = $menu->tenants();
    //check role privilege
    switch ($mode) {
      case 'edit':
        $crudbooster_privilege_check = CRUDBooster::isUpdate();
        break;
      case 'delete':
        $crudbooster_privilege_check = CRUDBooster::isDelete();
        break;

      default:
        return false;
        break;
    }

    return (
      CRUDBooster::isSuperadmin()
      or
      (
        $crudbooster_privilege_check
        and
        (
          in_array(UserHelper::current_user_tenant(), $tenants)
          and
          //tenant admin can only edit menu items that are only available for his own tenant.
          //this way he cannot edit menus that are published on other tenants
          count($tenants) == 1
        )
      )
    );
  }

  /**
   * Get the number of new users added this week
   */
  public static function new_users_count()
  {
    $date = date('Y-m-d H:i:s', strtotime("-7 days"));
    return User::where('created_at', '>', $date)->count();
  }

  /**
   * Get the latest users added
   *
   * @param int number of users to collect
   * @return Collection[User] list of recent users
   */
  public static function latest_users($number = 8)
  {
    $users = User::orderby('created_at', 'desc')->limit($number)->get();
    foreach ($users as $key => $user) {
      if (empty($user->photo)) {
        $user->photo = UserHelper::icon($user->id);
      }
    }
    return $users;
  }

  /**
   * Get the latest users added
   *
   * @param int user's id
   *
   * @return string path to user's default icon
   */
  public static function icon($user_id = null)
  {
    $user = User::find($user_id);
    if (empty($user_id) or empty($user)) {
      return asset('/images/user/user.png');
    } else {
      return $user->photo();
    }
  }

  /**
   *	get current user's primary group
   *
   * @return int id of the group
   */
  public static function current_user_primary_group()
  {
    return UserHelper::me()->primary_group;
  }

  /**
   *	get current user's primary group name
   *
   * @return string name of the group
   */
  public static function current_user_primary_group_name()
  {
    return UserHelper::me()->primary_group()->name;
  }

  /**
   *	get current user's tenant
   *
   * @return int id of the tenant
   */
  public static function current_user_tenant()
  {
    return UserHelper::me()->tenant;
  }

  /**
   *	get current user's tenant's name
   *
   * @return string tenant's name
   */
  public static function current_user_tenant_name()
  {
    return UserHelper::me()->tenant()->name;
  }

  /**
   *	get current user's groups
   *
   * @return array[int] list of the group's ids of which current user is a member
   */
  public static function current_user_groups()
  {
    $user_id = CRUDBooster::myId();
    $groups = UsersGroup::where('user_id', $user_id)->pluck('group_id')->all();
    $primary_group = self::current_user_primary_group();
    if (!in_array($primary_group, $groups)) {
      array_push($groups, $primary_group);
    }
    return $groups;
  }

  /**
   *	get current user's groups
   *
   * @return array[string] list of the group's names of which current user is a member
   */
  public static function current_user_allowed_groups_names()
  {
    if (CRUDBooster::isSuperadmin()) {
      return Group::pluck('name')->toArray();
    } else {
      $groups = UserHelper::current_user_groups();
      foreach ($groups as $key => $value) {
        $result[] = Group::find($value)->name;
      }
      return $result;
    }
  }

  public static function isTenantAdmin($user_id = null)
  {
    if (empty($user_id)) {
      //defaults to current user if no id is given
      $user_id = CRUDBooster::myId();
    }
    $user = User::find($user_id);
    if (empty($user)) {
      //defaults to current user if no id is given
      return false;
    }
    return $user->isTenantAdmin();
  }

  public static function isSuperAdmin($user_id = null)
  {
    if (empty($user_id)) {
      //defaults to current user if no id is given
      $user_id = CRUDBooster::myId();
    }
    $user = User::find($user_id);
    if (empty($user)) {
      //user not found
      return false;
    }
    return $user->isSuperAdmin();
  }

  /**
   *	get current user
   *
   * @return User model
   */
  public static function me()
  {
    $user_id = CRUDBooster::myId();
    $user = User::find($user_id);
    return $user;
  }

  /**
   *	get user's primary group id
   *
   * @param int user's id
   *
   * @return int primary group's id
   */
  public static function primary_group($user_id)
  {
    $user = User::find($user_id);
    if (!empty($user)) {
      return $user->primary_group;
    }
    return '';
  }

  /**
   *	get user's tenant id
   *
   * @param int user's id
   *
   * @return int tenant's id
   */
  public static function tenant($user_id)
  {

    return User::find($user_id)->tenant;
  }

  /**
   *	get user's primary group name
   *
   * @param int user's id
   *
   * @return string primary group's name
   */
  public static function primary_group_name($user_id)
  {
    $group_id = UserHelper::primary_group($user_id);
    $group = Group::find($group_id);
    return $group->name;
  }

  /**
   * Rimuovi tutti i gruppi di un utente
   * Utile per aggiornare i gruppi di un utente alla modifica del tenant
   *
   * @param int id dell'utente
   *
   * @return boolean false se manca un parametro
   * @return boolean true altrimenti
   */
  public static function remove_all_groups($user_id)
  {
    if (!MyHelper::is_int($user_id)) {
      add_log_ch('remove all groups', 'user ' . $user_id . ' not found', 'error');
      return false;
    }

    $result = UsersGroup::where('user_id', $user_id)
      ->where('deleted_at', null)
      ->delete();

    add_log_ch('remove all groups', 'user ' . $user_id);

    return $result;
  }
}
