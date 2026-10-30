<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use QlikHelper;

class QlikItem extends Model
{
    use SoftDeletes;

    protected $table = 'qlik_items';
    public $timestamps = false;

    /**
     * Groups authorized on the item (pivot items_allowed). Excludes soft-deleted
     * groups and does not filter the pivot deleted_at, so allowedGroups() stays
     * the id source of truth.
     */
    public function allowedGroupRecords(): BelongsToMany {
      return $this->belongsToMany(Group::class, 'items_allowed', 'item_id', 'group_id');
    }

    /**
     * Tenants authorized on the item (pivot tenants_allowed). Same caveats as
     * allowedGroupRecords().
     */
    public function allowedTenantRecords(): BelongsToMany {
      return $this->belongsToMany(Tenant::class, 'tenants_allowed', 'item_id', 'tenant_id');
    }

		/**
		*	Trova i gruppi autorizzati all'item
		*
		* @return array[int] lista degli id dei gruppi autorizzati
		*/
    public function allowedGroups(): array {
      //TODO belongsTo
      $allowed_groups = ItemsAllowed::where('item_id',$this->id)
                                      ->pluck('group_id')
                                      ->toArray();
      return $allowed_groups;
    }

		/**
		*	Trova i tenant autorizzati all'item
		*
		* @return array[int] lista degli id dei tenant autorizzati
		*/
    public function allowedTenants(): array {
      //TODO belongsTo
      $allowed_tenants = TenantsAllowed::where('item_id',$this->id)
                                      ->pluck('tenant_id')
                                      ->toArray();
      return $allowed_tenants;
    }

		/**
		*	Enable a public page which grant access to the qlik item content anonymously
		*/
    public function enablePublicAccess(): bool {
      if(!$this->isPublic()){
        //enable
        //create token
        //token casuale: non deducibile da url/titolo e diverso a ogni riabilitazione
        $token = bin2hex(random_bytes(20));
        //save token
        $this->proxy_token = $token;
        //save proxy enabled at
        $now = date('Y-m-d H:i:s');
        $this->proxy_enabled_at = $now;
        $this->modified_at = $now;
        $this->save();

        return true;
      }
      //item already public
      return false;
    }

		/**
		*	Enable a public page which grant access to the qlik item content anonymously
		*/
    public function disablePublicAccess(): bool {
      if($this->isPublic()){
        //disable
        //delete token
        $this->proxy_token = null;
        //delete proxy enabled at
        $this->proxy_enabled_at = null;
        $now = date('Y-m-d H:i:s');
        $this->modified_at = $now;
        $this->save();

        return true;
      }
      //item already not public
      return false;
    }

		/**
		*	Check if a qlik item is currently enabled for public access
		*
		* @return boolean true if the qlik item is publicly accessible
    *                 false otherwise
		*/
    public function isPublic(): bool {
      if(!empty($this->proxy_token)){
        return true;
      }
      return false;
    }

    /**
    *	Check if a qlik item is currently enabled for public access
    *
    * @return string qlik item public URL
    */
    public function publicUrl() {
      if(!empty($this->proxy_token)){
        return QlikHelper::buildPublicUrl($this->proxy_token);
      }
      return false;
    }

}
