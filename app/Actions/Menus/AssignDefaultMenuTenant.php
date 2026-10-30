<?php

namespace App\Actions\Menus;

use App\Models\Menu;
use App\Models\MenuTenants;

class AssignDefaultMenuTenant
{
    /**
     * Assegna il tenant indicato alla voce di menu (pivot menu_tenants).
     * Nessun controllo di duplicati, come prima: la pivot ha l'unique
     * (menu_id, tenant_id) e un secondo insert lancia l'eccezione del DB.
     * $tenantId resta senza tipo per salvare lo stesso valore di prima.
     */
    public function handle(Menu $menu, $tenantId): void
    {
        $menuTenant = new MenuTenants();
        $menuTenant->menu_id = $menu->id;
        $menuTenant->tenant_id = $tenantId;
        $menuTenant->save();
    }
}
