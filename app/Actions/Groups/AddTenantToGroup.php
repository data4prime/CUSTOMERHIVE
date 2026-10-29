<?php

namespace App\Actions\Groups;

use App\Group;
use App\GroupTenants;

class AddTenantToGroup
{
    /**
     * Associa il tenant al gruppo (pivot group_tenants). Nessun controllo di
     * duplicati, come prima: l'unique (group_id, tenant_id) lancia l'eccezione.
     * $tenantId resta senza tipo per salvare lo stesso valore di prima.
     */
    public function handle(Group $group, $tenantId): void
    {
        $groupTenant = new GroupTenants();
        $groupTenant->group_id = $group->id;
        $groupTenant->tenant_id = $tenantId;
        $groupTenant->save();
    }
}
