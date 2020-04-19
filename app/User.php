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
}
