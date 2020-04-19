<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsersGroup extends Model
{
    protected $table = 'users_groups';
    use SoftDeletes;

    public $timestamps = false;
}
