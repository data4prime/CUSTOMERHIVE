<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use \App\Group;
use \App\Tenant;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'cms_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function primary_group(){
      return Group::find($this->primary_group);
    }

    public function tenant(){
      return Tenant::find($this->tenant);
    }

    public function isTenantAdmin()
    {
      $my_role_id = $this->id_cms_privileges;
      return Role::find($my_role_id)->is_tenantadmin == 1;
    }

    public function isSuperAdmin()
    {
      $my_role_id = $this->id_cms_privileges;
      return Role::find($my_role_id)->is_superadmin == 1;
    }

    /**
    * returns user's photo path or a default one based on his privilege
    */
    public function photo()
    {
      if(!empty($this->photo)){
        return $this->photo;
      }
      elseif($this->isSuperAdmin()){
        return asset('/images/user/admin.jpeg');
      }
      elseif($this->isTenantAdmin()){
        return asset('/images/user/manager.jpeg');
      }
      else{
        return asset('/images/user/user.png');
      }
    }
}
