<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Clienti extends Model
{
    protected $table = 'mg_cliente';

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}
