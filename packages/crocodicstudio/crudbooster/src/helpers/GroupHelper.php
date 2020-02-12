<?php
namespace crocodicstudio\crudbooster\helpers;

use Session;
use Request;
use Schema;
use Cache;
use DB;
use Route;
use Validator;

class GroupHelper  {

		/**
		*	Verifica se l'utente corrente è abilitato a vedere un oggetto qlik
		* controlla se è admin o se ha un gruppo abilitato
		*
		* @param int id dell'item
		*
		* @return boolean true se l'utente è abilitato, false altrimenti
		*/
    public static function can_see_item($qlik_item_id) {
			//super admin sempre allowed
      if(CRUDBooster::isSuperadmin()){
				return true;
			}
			//check groups
			//get user groups
			$current_user_groups = GroupHelper::myGroups();
			//get item allowed groups
			$allowed_groups = \App\QlikItem::find($qlik_item_id)->allowedGroups();
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
    public static function myGroups() {
			$user_id = CRUDBooster::myId();
			$groups = \App\UsersGroup::where('user_id',$user_id)
																	->pluck('group_id')
																	->toArray();

			return $groups;
    }
}
