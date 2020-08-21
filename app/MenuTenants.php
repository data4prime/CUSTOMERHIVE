<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MenuTenants extends Model
{
    protected $table = 'menu_tenants';
    public $timestamps = false;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}
