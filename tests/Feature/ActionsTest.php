<?php

namespace Tests\Feature;

use App\Actions\Groups\AddTenantToGroup;
use App\Actions\Menus\AssignDefaultMenuGroup;
use App\Actions\Menus\AssignDefaultMenuTenant;
use App\Group;
use App\Menu;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Batch 3 del piano 317: le tre Action che creano le righe pivot
 * (menu_tenants, menu_groups, group_tenants) e i wrapper dei modelli che
 * devono produrre la stessa riga. Le tre pivot hanno un unique composito
 * (migrazioni 2020_08_20/2020_08_13/2020_08_28): un secondo insert identico
 * lancia l'eccezione, come prima dell'estrazione (nessun controllo aggiunto).
 */
class ActionsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    public function test_assign_default_menu_tenant_crea_la_riga_nella_pivot(): void
    {
        $menu = Menu::find($this->seedMenu()['id']);
        $tenantId = $this->seedTenant();

        app(AssignDefaultMenuTenant::class)->handle($menu, $tenantId);

        $this->assertSame(1, DB::table('menu_tenants')
            ->where('menu_id', $menu->id)->where('tenant_id', $tenantId)->count());
    }

    public function test_assign_default_menu_tenant_due_volte_lancia_l_eccezione_di_unicita(): void
    {
        $menu = Menu::find($this->seedMenu()['id']);
        $tenantId = $this->seedTenant();
        $action = app(AssignDefaultMenuTenant::class);
        $action->handle($menu, $tenantId);

        $this->expectException(QueryException::class);
        $action->handle($menu, $tenantId);
    }

    public function test_assign_default_menu_group_crea_la_riga_nella_pivot(): void
    {
        $menu = Menu::find($this->seedMenu()['id']);
        $groupId = $this->seedGroup();

        app(AssignDefaultMenuGroup::class)->handle($menu, $groupId);

        $this->assertSame(1, DB::table('menu_groups')
            ->where('menu_id', $menu->id)->where('group_id', $groupId)->count());
    }

    public function test_assign_default_menu_group_due_volte_lancia_l_eccezione_di_unicita(): void
    {
        $menu = Menu::find($this->seedMenu()['id']);
        $groupId = $this->seedGroup();
        $action = app(AssignDefaultMenuGroup::class);
        $action->handle($menu, $groupId);

        $this->expectException(QueryException::class);
        $action->handle($menu, $groupId);
    }

    public function test_add_tenant_to_group_crea_la_riga_nella_pivot(): void
    {
        $group = Group::find($this->seedGroup());
        $tenantId = $this->seedTenant();

        app(AddTenantToGroup::class)->handle($group, $tenantId);

        $this->assertSame(1, DB::table('group_tenants')
            ->where('group_id', $group->id)->where('tenant_id', $tenantId)->count());
    }

    public function test_add_tenant_to_group_due_volte_lancia_l_eccezione_di_unicita(): void
    {
        $group = Group::find($this->seedGroup());
        $tenantId = $this->seedTenant();
        $action = app(AddTenantToGroup::class);
        $action->handle($group, $tenantId);

        $this->expectException(QueryException::class);
        $action->handle($group, $tenantId);
    }

    public function test_wrapper_add_tenant_del_modello_produce_la_stessa_riga(): void
    {
        $group = Group::find($this->seedGroup());
        $tenantId = $this->seedTenant();

        $group->add_tenant($tenantId);

        $this->assertSame(1, DB::table('group_tenants')
            ->where('group_id', $group->id)->where('tenant_id', $tenantId)->count());
    }

    public function test_wrapper_assign_default_del_menu_usano_tenant_e_gruppo_primario_dell_utente(): void
    {
        // UserHelper::me() legge l'utente autenticato: tenant e primary_group
        // arrivano dal suo record, come prima dell'estrazione.
        $actor = $this->actingAsSuperadmin();
        $primaryGroup = DB::table('cms_users')->where('id', $actor['userId'])->value('primary_group');
        $menu = Menu::find($this->seedMenu()['id']);

        $menu->assign_default_tenant();
        $menu->assign_default_group();

        $this->assertSame(1, DB::table('menu_tenants')
            ->where('menu_id', $menu->id)->where('tenant_id', $actor['tenantId'])->count());
        $this->assertSame(1, DB::table('menu_groups')
            ->where('menu_id', $menu->id)->where('group_id', $primaryGroup)->count());
    }
}
