<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use QlikHelper;

class QlikItem extends Model
{
    protected $table = 'qlik_items';
    public $timestamps = false;
    use SoftDeletes;
    protected $dates = ['deleted_at'];

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

		/**
		*	Trova i tenant autorizzati all'item
		*
		* @return array[int] lista degli id dei tenant autorizzati
		*/
    public function allowedTenants(){
      //TODO belongsTo
      $allowed_tenants = TenantsAllowed::where('item_id',$this->id)
                                      ->pluck('tenant_id')
                                      ->toArray();
      return $allowed_tenants;
    }

		/**
		*	Enable a public page which grant access to the qlik item content anonymously
		*/
    public function enablePublicAccess() {
      if(!$this->isPublic()){
        //enable
        //create token
        $token = md5(config('app.salt').$this->url.$this->title);
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
    public function disablePublicAccess() {
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
    public function isPublic() {
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
