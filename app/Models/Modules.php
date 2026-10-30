<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Modules extends Model
{
    use SoftDeletes;

    protected $table = 'cms_moduls';
}
