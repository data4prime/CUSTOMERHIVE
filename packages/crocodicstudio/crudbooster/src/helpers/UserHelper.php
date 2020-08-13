<?php
namespace crocodicstudio\crudbooster\helpers;

use \App\User;
use \App\Group;
use \App\Tenant;
use \App\UsersGroup;

class UserHelper  {

  /**
  *	get current user's primary group
  *
  * @return int id of the group
  */
  public static function current_user_primary_group() {
    return UserHelper::me()->primary_group;
  }

  /**
  *	get current user's primary group name
  *
  * @return string name of the group
  */
  public static function current_user_primary_group_name() {
    return UserHelper::me()->primary_group()->name;
  }

  /**
  *	get current user's tenant
  *
  * @return int id of the tenant
  */
  public static function current_user_tenant() {
    return UserHelper::me()->tenant;
  }

  /**
  *	get current user's tenant's name
  *
  * @return string tenant's name
  */
  public static function current_user_tenant_name() {
    return UserHelper::me()->tenant()->name;
  }

  /**
  *	get current user's groups
  *
  * @return array[int] list of the group's ids of which current user is a member
  */
  public static function current_user_groups() {
    $user_id = CRUDBooster::myId();
    $groups = UsersGroup::where('user_id',$user_id)->pluck('group_id')->all();
    $primary_group = self::current_user_primary_group();
    if(!in_array($primary_group, $groups)){
      array_push($groups, $primary_group);
    }
    return $groups;
  }

  /**
  *	get current user's groups
  *
  * @return array[string] list of the group's names of which current user is a member
  */
  public static function current_user_allowed_groups_names() {
    if(CRUDBooster::isSuperadmin()){
      return Group::pluck('name')->toArray();
    }
    else{
      $groups = UserHelper::current_user_groups();
      foreach ($groups as $key => $value) {
        $result[] = Group::find($value)->name;
      }
      return $result;
    }
  }

  public static function isAdvanced()
  {
    // TODO migliorare se aggiungiamo colonna is advanced nei ruoli in modo da
    // non basarsi solo sull'id del ruolo
    return CRUDBooster::me()->id_cms_privileges === 3;
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
  public static function primary_group($user_id) {
    return User::find($user_id)->primary_group;
  }

  /**
  *	get user's tenant id
  *
  * @param int user's id
  *
  * @return int tenant's id
  */
  public static function tenant($user_id) {
    return User::find($user_id)->tenant;
  }

  /**
  *	get user's primary group name
  *
  * @param int user's id
  *
  * @return string primary group's name
  */
  public static function primary_group_name($user_id) {
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
    if(!is_numeric($user_id)){
      add_log('remove all groups', 'user '.$user_id.' not found','error');
      return false;
    }

    $result = UsersGroup::where('user_id',$user_id)
                        ->where('deleted_at',null)
                        ->delete();

    add_log('remove all groups', 'user '.$user_id);

    return $result;
  }
}
