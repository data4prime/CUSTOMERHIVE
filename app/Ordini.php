<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ordini extends Model
{
    protected $table = 'mg_ordini';

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}
