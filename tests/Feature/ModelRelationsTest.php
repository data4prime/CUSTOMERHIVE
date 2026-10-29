<?php

namespace Tests\Feature;

use App\Group;
use App\Menu;
use App\QlikItem;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Batch 2 del piano 317: le relazioni Eloquent nuove (assignedTenants,
 * assignedGroups, tenantRecord, primaryGroupRecord, memberGroups,
 * allowedGroupRecords, allowedTenantRecords) devono restituire gli stessi
 * insiemi dei metodi "a mano" che restano in uso (tenants(), tenants_name(),
 * tenant(), primary_group(), allowedGroups(), allowedTenants()).
 * Differenza voluta e fotografata: le relazioni passano dalle query dei
 * modelli Tenant/Group e quindi escludono i soft-deleted, mentre
 * tenants()/allowedTenants() leggono la pivot e li includono.
 */
class ModelRelationsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    private function seedNamedTenant(string $name, bool $deleted = false): int
    {
        $id = $this->seedTenant();
        DB::table('tenants')->where('id', $id)->update([
            'name' => $name,
            'deleted_at' => $deleted ? now() : null,
        ]);

        return $id;
    }

    public function test_menu_assigned_tenants_coincide_con_tenants_meno_i_soft_deleted(): void
    {
        $menu = $this->seedMenu();
        $a = $this->seedNamedTenant('Alfa');
        $deleted = $this->seedNamedTenant('Beta', deleted: true);
        $c = $this->seedNamedTenant('Gamma');
        foreach ([$a, $deleted, $c] as $tenantId) {
            DB::table('menu_tenants')->insert(['menu_id' => $menu['id'], 'tenant_id' => $tenantId]);
        }

        $model = Menu::find($menu['id']);

        // il wrapper legge la pivot: include il soft-deleted, nell'ordine naturale
        $this->assertSame([$a, $deleted, $c], $model->tenants());
        // la relazione passa da Tenant: esclude il soft-deleted
        $this->assertEqualsCanonicalizing([$a, $c], $model->assignedTenants()->pluck('tenants.id')->all());
    }

    public function test_menu_tenants_name_senza_n_piu_uno_resta_identico(): void
    {
        $menu = $this->seedMenu();
        foreach (['Alfa', 'Beta'] as $name) {
            DB::table('menu_tenants')->insert([
                'menu_id' => $menu['id'],
                'tenant_id' => $this->seedNamedTenant($name),
            ]);
        }
        $emptyMenu = $this->seedMenu(['name' => 'Vuoto']);

        $this->assertSame('Alfa, Beta', Menu::find($menu['id'])->tenants_name());
        $this->assertSame('', Menu::find($emptyMenu['id'])->tenants_name());
    }

    public function test_menu_tenants_name_senza_virgola_finale_con_ultimo_tenant_soft_deleted(): void
    {
        // Comportamento CAMBIATO di proposito dal batch 2b del piano 317: prima
        // dava "Alfa, " (la virgola era decisa sulla posizione nella pivot, non
        // sui nomi trovati); ora i nomi presenti sono uniti con implode.
        $menu = $this->seedMenu();
        foreach ([['Alfa', false], ['Beta', true]] as [$name, $deleted]) {
            DB::table('menu_tenants')->insert([
                'menu_id' => $menu['id'],
                'tenant_id' => $this->seedNamedTenant($name, $deleted),
            ]);
        }

        $this->assertSame('Alfa', Menu::find($menu['id'])->tenants_name());
    }

    public function test_menu_tenants_name_con_soft_deleted_in_testa_e_in_mezzo_resta_identico(): void
    {
        // Caso invariato dal batch 2b: i soft-deleted sono esclusi e non
        // lasciano virgole in eccesso, ne' in testa ne' in mezzo.
        $menu = $this->seedMenu();
        foreach ([['Testa', true], ['Alfa', false], ['Mezzo', true], ['Gamma', false]] as [$name, $deleted]) {
            DB::table('menu_tenants')->insert([
                'menu_id' => $menu['id'],
                'tenant_id' => $this->seedNamedTenant($name, $deleted),
            ]);
        }

        $this->assertSame('Alfa, Gamma', Menu::find($menu['id'])->tenants_name());
    }

    public function test_menu_assigned_groups_restituisce_i_gruppi_della_pivot(): void
    {
        $menu = $this->seedMenu();
        $g1 = $this->seedGroup();
        $g2 = $this->seedGroup();
        DB::table('menu_groups')->insert(['menu_id' => $menu['id'], 'group_id' => $g1]);
        DB::table('menu_groups')->insert(['menu_id' => $menu['id'], 'group_id' => $g2]);

        $ids = Menu::find($menu['id'])->assignedGroups()->pluck('groups.id')->all();

        $this->assertEqualsCanonicalizing([$g1, $g2], $ids);
    }

    public function test_group_assigned_tenants_restituisce_i_tenant_della_pivot(): void
    {
        $groupId = $this->seedGroup();
        $t1 = $this->seedTenant();
        $t2 = $this->seedTenant();
        $group = Group::find($groupId);
        $group->add_tenant($t1);
        $group->add_tenant($t2);

        $ids = $group->assignedTenants()->pluck('tenants.id')->all();

        $this->assertEqualsCanonicalizing([$t1, $t2], $ids);
    }

    public function test_user_tenant_record_e_primary_group_record_coincidono_con_i_metodi_esistenti(): void
    {
        $user = User::find($this->seedUser()['id']);

        $this->assertSame($user->tenant()->id, $user->tenantRecord->id);
        $this->assertSame($user->primary_group()->id, $user->primaryGroupRecord->id);
    }

    public function test_user_member_groups_esclude_le_appartenenze_cancellate(): void
    {
        $user = $this->seedUser();
        $g1 = $this->seedGroup();
        $g2 = $this->seedGroup();
        DB::table('users_groups')->insert(['user_id' => $user['id'], 'group_id' => $g1]);
        DB::table('users_groups')->insert(['user_id' => $user['id'], 'group_id' => $g2, 'deleted_at' => now()]);

        $ids = User::find($user['id'])->memberGroups()->pluck('groups.id')->all();

        $this->assertSame([$g1], $ids);
    }

    public function test_qlik_item_allowed_records_coincidono_con_i_metodi_che_restituiscono_id(): void
    {
        $itemId = DB::table('qlik_items')->insertGetId(['title' => 'Item', 'created_at' => now()]);
        $g1 = $this->seedGroup();
        $g2 = $this->seedGroup();
        $t1 = $this->seedTenant();
        $t2 = $this->seedTenant();
        $tDeleted = $this->seedNamedTenant('Cancellato', deleted: true);
        foreach ([$g1, $g2] as $groupId) {
            DB::table('items_allowed')->insert(['item_id' => $itemId, 'group_id' => $groupId]);
        }
        foreach ([$t1, $t2, $tDeleted] as $tenantId) {
            DB::table('tenants_allowed')->insert(['item_id' => $itemId, 'tenant_id' => $tenantId]);
        }

        $item = QlikItem::find($itemId);

        $this->assertEqualsCanonicalizing($item->allowedGroups(), $item->allowedGroupRecords()->pluck('groups.id')->all());
        $this->assertSame([$t1, $t2, $tDeleted], $item->allowedTenants());
        $this->assertEqualsCanonicalizing([$t1, $t2], $item->allowedTenantRecords()->pluck('tenants.id')->all());
    }
}
