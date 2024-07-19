<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    protected $table = 'groups';
    use SoftDeletes;

    public function add_tenant($tenant_id) {
      $add_tenant = new GroupTenants;
      $add_tenant->group_id = $this->id;
      $add_tenant->tenant_id = $tenant_id;
      $add_tenant->save();
    }
}
