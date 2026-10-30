<?php

namespace App\Models;

use App\Actions\Menus\AssignDefaultMenuGroup;
use App\Actions\Menus\AssignDefaultMenuTenant;
use App\Helpers\UserHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Menu extends Model
{
    protected $table = 'cms_menus';

    /**
    * Voce "Dashboard" attiva marcata come dashboard (fallback dei content_view).
    */
    public function scopeActiveDashboard($query) {
      return $query->where('name', 'Dashboard')->where('is_active', 1)->where('is_dashboard', 1);
    }

    /**
    * Voce di menu che punta al contenuto di un item Qlik.
    */
    public function scopeQlikItemContent($query, $itemId) {
      return $query->where('path', 'qlik_items/content/' . $itemId);
    }

    /**
    * Tenants assigned to the menu (pivot menu_tenants). Like any Tenant query
    * it excludes soft-deleted tenants, unlike tenants() which reads the pivot.
    */
    public function assignedTenants(): BelongsToMany {
      return $this->belongsToMany(Tenant::class, 'menu_tenants', 'menu_id', 'tenant_id');
    }

    /**
    * Groups assigned to the menu (pivot menu_groups). Excludes soft-deleted groups.
    */
    public function assignedGroups(): BelongsToMany {
      return $this->belongsToMany(Group::class, 'menu_groups', 'menu_id', 'group_id');
    }

    /**
    * Returns the ids of the menu's tenants
    *
    * @return array[int]
    */
    public function tenants(): array {
      return MenuTenants::where('menu_id',$this->id)->pluck('tenant_id')->toArray();
    }

    /**
    * add user's tenant as menu default tenant.
    * Wrapper: la logica vive in AssignDefaultMenuTenant, i controller di
    * sistema chiamano direttamente l'Action; resta per i controller custom.
    */
    public function assign_default_tenant() {
      app(AssignDefaultMenuTenant::class)->handle($this, UserHelper::current_user_tenant());
    }

    /**
    * add user's primary group as menu default group.
    * Wrapper: la logica vive in AssignDefaultMenuGroup, i controller di
    * sistema chiamano direttamente l'Action; resta per i controller custom.
    */
    public function assign_default_group() {
      app(AssignDefaultMenuGroup::class)->handle($this, UserHelper::current_user_primary_group());
    }

    /**
    * Returns the names of the menu's tenants.
    *
    * @return string comma separated list of menu's tenants names
    */
    public function tenants_name(): string {
      $names = [];
      $tenants = $this->tenants();
      //una sola query invece di un find() per tenant: i soft-deleted restano
      //esclusi (come con find()) e si cicla sugli stessi id, nello stesso ordine
      $found = $tenants ? Tenant::whereIn('id', $tenants)->get()->keyBy('id') : collect();
      foreach ($tenants as $tenant_id) {
        $t = $found->get($tenant_id);
        if ($t) {
          $names[] = $t->name;
        }
      }
      //implode evita la virgola finale quando l'ultimo tenant e' mancante o soft-deleted
      return implode(', ', $names);
    }
}
