<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use QlikHelper;

class ChatAIConf extends Model
{
    protected $table = 'chatai_confs';
    public $timestamps = false;
    use SoftDeletes;
    protected $dates = ['deleted_at'];

		/**
		*	Trova i gruppi autorizzati al ChatAIConf
		*
		* @return array[int] lista degli id dei gruppi autorizzati
		*/
    public function allowedGroups(){
      //TODO belongsTo
      $allowed_groups = ItemsAllowed::where('chataiconf_id',$this->id)
                                      ->pluck('group_id')
                                      ->toArray();
      return $allowed_groups;
    }

		/**
		*	Trova i tenant autorizzati al ChatAIConf
		*
		* @return array[int] lista degli id dei tenant autorizzati
		*/
    public function allowedTenants(){
      //TODO belongsTo
      $allowed_tenants = TenantsAllowed::where('chataiconf_id',$this->id)
                                      ->pluck('tenant_id')
                                      ->toArray();
      return $allowed_tenants;
    }



}
