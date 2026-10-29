<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatAIConf extends Model
{
    use SoftDeletes;

    protected $table = 'chatai_confs';
    public $timestamps = false;

		/**
		*	Trova i gruppi autorizzati al ChatAIConf
		*
		* @return array[int] lista degli id dei gruppi autorizzati
		*/
    public function allowedGroups(){
      // tabella dedicata chatai_groups_allowed (non items_allowed, dei Qlik items)
      $allowed_groups = ChatAIGroupAllowed::where('chatai_conf_id',$this->id)
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
      // tabella dedicata chatai_tenants_allowed (non tenants_allowed, dei Qlik items)
      $allowed_tenants = ChatAITenantAllowed::where('chatai_conf_id',$this->id)
                                      ->pluck('tenant_id')
                                      ->toArray();
      return $allowed_tenants;
    }

    /**
     * Gruppi autorizzati come modelli (stessa tabella di allowedGroups()).
     */
    public function allowedGroupRecords(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'chatai_groups_allowed', 'chatai_conf_id', 'group_id');
    }

    /**
     * Tenant autorizzati come modelli (stessa tabella di allowedTenants()).
     */
    public function allowedTenantRecords(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'chatai_tenants_allowed', 'chatai_conf_id', 'tenant_id');
    }



}
