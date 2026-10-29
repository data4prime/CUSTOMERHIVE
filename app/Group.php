<?php

namespace App;

use App\Actions\Groups\AddTenantToGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    use SoftDeletes;

    protected $table = 'groups';

    /**
     * Tenants assigned to the group (pivot group_tenants). Excludes soft-deleted tenants.
     */
    public function assignedTenants(): BelongsToMany
    {
      return $this->belongsToMany(Tenant::class, 'group_tenants', 'group_id', 'tenant_id');
    }

    /**
     * Wrapper: la logica vive in AddTenantToGroup, i controller di sistema
     * chiamano direttamente l'Action; resta per i controller custom.
     */
    public function add_tenant($tenant_id) {
      app(AddTenantToGroup::class)->handle($this, $tenant_id);
    }
}
