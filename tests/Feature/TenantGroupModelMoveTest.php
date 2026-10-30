<?php

namespace Tests\Feature;

use App\Group;
use App\Models\ChatAIConf;
use App\Models\Menu;
use App\Models\QlikItem;
use App\Role;
use App\Tenant;
use App\UsersGroup;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Piano 337 (batch B3): spostamento di Tenant, Group, Role e UsersGroup in
 * App\Models con alias sui vecchi nomi.
 *
 * Il test sull'alias verifica che i vecchi nomi (App\Tenant, ...) siano alias
 * dei modelli in App\Models; gli altri fotografano il comportamento dei
 * modelli e usano apposta i vecchi nomi, cosi' passano dall'alias come il
 * codice custom dei clienti.
 */
class TenantGroupModelMoveTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    /** @return array<string, array{0: string}> */
    public static function modelNames(): array
    {
        return [
            'Tenant' => ['Tenant'],
            'Group' => ['Group'],
            'Role' => ['Role'],
            'UsersGroup' => ['UsersGroup'],
        ];
    }

    #[DataProvider('modelNames')]
    public function test_il_vecchio_nome_e_alias_del_modello_in_app_models(string $name): void
    {
        $this->assertTrue(class_exists('App\\' . $name));
        $this->assertTrue(class_exists('App\\Models\\' . $name));
        // stessa classe reale: il nome "canonico" e' quello nuovo
        $this->assertSame('App\\Models\\' . $name, (new ReflectionClass('App\\' . $name))->getName());
        $this->assertInstanceOf('App\\Models\\' . $name, new ('App\\' . $name));
    }

    public function test_tabelle_e_opzioni_dei_modelli_restano_invariate(): void
    {
        $this->assertSame('tenants', (new Tenant())->getTable());
        $this->assertSame('groups', (new Group())->getTable());
        $this->assertSame('cms_privileges', (new Role())->getTable());
        $this->assertSame('users_groups', (new UsersGroup())->getTable());
        $this->assertFalse((new UsersGroup())->usesTimestamps());
    }

    public function test_soft_delete_attivo_su_tenant_group_usersgroup_e_non_su_role(): void
    {
        // Si marca deleted_at via DB (non ->delete()): tenants non ha updated_at, quindi
        // il delete() Eloquent di Tenant fallirebbe oggi; qui si fotografa solo il global scope.
        $tenantId = $this->seedTenant();
        $groupId = $this->seedGroup();
        $role = Role::find($this->seedPrivilege());
        $membershipId = DB::table('users_groups')->insertGetId([
            'user_id' => $this->seedUser()['id'],
            'group_id' => $groupId,
        ]);
        $this->assertNotNull(Tenant::find($tenantId));
        $this->assertNotNull(Group::find($groupId));
        $this->assertNotNull(UsersGroup::find($membershipId));

        DB::table('tenants')->where('id', $tenantId)->update(['deleted_at' => now()]);
        DB::table('groups')->where('id', $groupId)->update(['deleted_at' => now()]);
        DB::table('users_groups')->where('id', $membershipId)->update(['deleted_at' => now()]);

        $this->assertNull(Tenant::find($tenantId));
        $this->assertNull(Group::find($groupId));
        $this->assertNull(UsersGroup::find($membershipId));
        // Role non ha SoftDeletes: il record e' ancora leggibile e non ha il metodo trashed()
        $this->assertNotNull(Role::find($role->id));
        $this->assertFalse(method_exists($role, 'trashed'));
    }

    public function test_relazioni_dei_modelli_ancora_in_app_restituiscono_tenant_e_group(): void
    {
        $tenantId = $this->seedTenant();
        $otherTenantId = $this->seedTenant();
        $groupId = $this->seedGroup();
        $otherGroupId = $this->seedGroup();

        $menu = $this->seedMenu();
        DB::table('menu_tenants')->insert(['menu_id' => $menu['id'], 'tenant_id' => $tenantId]);
        DB::table('menu_groups')->insert(['menu_id' => $menu['id'], 'group_id' => $groupId]);

        $itemId = DB::table('qlik_items')->insertGetId(['title' => 'Item', 'created_at' => now()]);
        DB::table('tenants_allowed')->insert(['item_id' => $itemId, 'tenant_id' => $tenantId]);
        DB::table('items_allowed')->insert(['item_id' => $itemId, 'group_id' => $groupId]);

        $confId = DB::table('chatai_confs')->insertGetId(['title' => 'Agente', 'created_at' => now()]);
        DB::table('chatai_tenants_allowed')->insert(['chatai_conf_id' => $confId, 'tenant_id' => $tenantId]);
        DB::table('chatai_groups_allowed')->insert(['chatai_conf_id' => $confId, 'group_id' => $groupId]);

        $menuModel = Menu::find($menu['id']);
        $itemModel = QlikItem::find($itemId);
        $confModel = ChatAIConf::find($confId);

        $tenantRelations = [
            $menuModel->assignedTenants(),
            $itemModel->allowedTenantRecords(),
            $confModel->allowedTenantRecords(),
        ];
        $groupRelations = [
            $menuModel->assignedGroups(),
            $itemModel->allowedGroupRecords(),
            $confModel->allowedGroupRecords(),
        ];

        foreach ($tenantRelations as $relation) {
            $related = $relation->get();
            // positivo: il tenant assegnato c'e' ed e' un modello Tenant
            $this->assertSame([$tenantId], $related->pluck('id')->all());
            $this->assertInstanceOf(Tenant::class, $related->first());
            // negativo, stesso setup: il tenant non assegnato non compare
            $this->assertNotContains($otherTenantId, $related->pluck('id')->all());
        }
        foreach ($groupRelations as $relation) {
            $related = $relation->get();
            $this->assertSame([$groupId], $related->pluck('id')->all());
            $this->assertInstanceOf(Group::class, $related->first());
            $this->assertNotContains($otherGroupId, $related->pluck('id')->all());
        }
    }

    public function test_group_add_tenant_assegna_il_tenant_e_la_seconda_chiamata_viola_l_unique(): void
    {
        $tenantId = $this->seedTenant();
        $group = Group::find($this->seedGroup());

        $group->add_tenant($tenantId);

        $this->assertSame([$tenantId], $group->assignedTenants()->pluck('tenants.id')->all());

        // stesso setup, caso negativo: nessun controllo duplicati, lancia l'unique (group_id, tenant_id)
        $this->expectException(QueryException::class);
        $group->add_tenant($tenantId);
    }
}
