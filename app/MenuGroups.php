<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MenuGroups extends Model
{
    protected $table = 'menu_groups';
    public $timestamps = false;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}
