<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TenantsAllowed extends Model
{
    protected $table = 'tenants_allowed';

    public $timestamps = false;
}
