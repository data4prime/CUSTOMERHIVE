<?php
namespace crocodicstudio\crudbooster\helpers;

use Session;
use Request;
use Schema;
use Cache;
use DB;
use Route;
use Validator;
use \App\UsersGroup;

class GroupHelper  {

		/**
		*	Verifica se l'utente corrente è abilitato a vedere un oggetto qlik
		* controlla se è admin o se ha un gruppo abilitato
		*
		* @param int id dell'item
		*
		* @return boolean true se l'utente è abilitato, false altrimenti
		*/
    public static function can_see_item($qlik_item_id)
    {

			//super admin sempre allowed
      if(CRUDBooster::isSuperadmin()){
				return true;
			}

      $qlik_item = \App\QlikItem::find($qlik_item_id);

      //check tenant
      if(UserHelper::current_user_tenant() !== $qlik_item->tenant){
        return false;
      }
      if(UserHelper::isTenantAdmin()){
        //Advanced non è limitato dal ruolo per la visibilità dei qlik item
				return true;
			}

			//check groups
			//get user groups
			$current_user_groups = GroupHelper::myGroups();
			//get item allowed groups
      if(empty($qlik_item)){
        add_log('can see item', 'qlik item not found id: '.$qlik_item_id);
        return false;
      }
			$allowed_groups = $qlik_item->allowedGroups();
			foreach ($allowed_groups as $allowed_group) {
				if(in_array($allowed_group,$current_user_groups)){
					//trovato un gruppo abilitato tra i gruppi di cui fa parte l'utente
					return true;
				}
			}
      return false;
    }

		/**
		*	Trova i gruppi dell'utente corrente
		*
		* @return array[int] lista degli id dei gruppi di cui fa parte l'utente corrente
		*/
    public static function myGroups()
    {
			$user_id = CRUDBooster::myId();
			$groups = \App\UsersGroup::where('user_id',$user_id)
																	->pluck('group_id')
																	->toArray();

			return $groups;
    }

    /**
    * Aggiungi un membro ad un gruppo
    * Utile per aggiornare i gruppi di un utente al salvataggio del primary group
    *
    * @param int id del gruppo
    * @param int id dell'utente
    *
    * @return int id dell'associazione
    * @return boolean false se manca un parametro
    * @return boolean true se l'utente era già membro del gruppo
    */
    public static function add($group_id, $user_id)
    {
      if(!is_numeric($group_id) OR !is_numeric($user_id)){
        add_log('add membership', 'group '.$group_id.' or user '.$user_id.' not found','error');
        return false;
      }
      $exists = UsersGroup::where('user_id',$user_id)
      ->where('group_id',$group_id)
      ->count();
      if($exists>0)
      {
        //l'utente era già membro del gruppo
        return true;
      }

      $membership = new UsersGroup;
      $membership->user_id = $user_id;
      $membership->group_id = $group_id;
      $membership->created_by = CRUDBooster::myId();
      $membership->created_at = date('Y-m-d H:i:s');
      $membership->save();

      add_log('add membership', 'group '.$group_id.' has a new member user '.$user_id);

      return $membership->id;
    }

    /**
    * Rimuovi un membro da un gruppo
    * Utile per aggiornare i gruppi di un utente al salvataggio del primary group
    *
    * @param int id del gruppo
    * @param int id dell'utente
    *
    * @return boolean false se manca un parametro o non trova l'associazione
    * @return boolean true altrimenti
    */
    public static function remove($group_id, $user_id)
    {
      if(!is_numeric($group_id) OR !is_numeric($user_id)){
        add_log('remove membership', 'group '.$group_id.' or user '.$user_id.' not found','error');
        return false;
      }
      $exists = UsersGroup::where('user_id',$user_id)
      ->where('group_id',$group_id)
      ->where('deleted_at',null)
      ->count();
      if($exists==0)
      {
        //l'utente non era membro del gruppo
        return false;
      }

      $result = UsersGroup::where('user_id',$user_id)
      ->where('group_id',$group_id)
      ->delete();

      add_log('remove membership', 'group '.$group_id.' has lost user '.$user_id);

      return $result;
    }
}
