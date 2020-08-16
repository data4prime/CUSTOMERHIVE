<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModuleTenants extends Model
{
    protected $table = 'module_tenants';
    public $timestamps = false;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}
