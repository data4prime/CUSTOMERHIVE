<?php

namespace App\Actions\Menus;

use App\Models\Menu;
use App\Models\MenuGroups;

class AssignDefaultMenuGroup
{
    /**
     * Assegna il gruppo indicato alla voce di menu (pivot menu_groups).
     * Nessun controllo di duplicati, come prima: l'unique (menu_id, group_id)
     * della pivot lancia l'eccezione. $groupId resta senza tipo per salvare
     * lo stesso valore di prima.
     */
    public function handle(Menu $menu, $groupId): void
    {
        $menuGroup = new MenuGroups();
        $menuGroup->menu_id = $menu->id;
        $menuGroup->group_id = $groupId;
        $menuGroup->save();
    }
}
