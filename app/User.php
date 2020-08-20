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
}
