<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use SoftDeletes;

    protected $table = 'tenants';

    protected $fillable = [
        'domain_name', 'name', 'description'

    ];
}
