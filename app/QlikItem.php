<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class QlikItem extends Model
{
    protected $table = 'qlik_items';

		/**
		*	Trova i gruppi autorizzati all'item
		*
		* @return array[int] lista degli id dei gruppi autorizzati
		*/
    public function allowedGroups(){
      //TODO belongsTo
      $allowed_groups = ItemsAllowed::where('item_id',$this->id)
                                      ->pluck('group_id')
                                      ->toArray();
      return $allowed_groups;
    }
}
