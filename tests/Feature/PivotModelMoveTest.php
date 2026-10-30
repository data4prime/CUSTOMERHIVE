<?php

namespace Tests\Feature;

use App\Models\ChatAIConf;
use App\Models\Menu;
use App\Models\QlikItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Piano 338 (batch B4): spostamento in App\Models dei modelli pivot/
 * autorizzazioni (GroupTenants, MenuTenants, MenuGroups, ModuleTenants,
 * TenantsAllowed, ItemsAllowed, ChatAIGroupAllowed, ChatAITenantAllowed) e di
 * Modules, con alias sui vecchi nomi.
 *
 * Il test sull'alias verifica che i vecchi nomi (App\GroupTenants, ...) siano
 * alias dei modelli in App\Models; gli altri fotografano il comportamento e
 * usano apposta i vecchi nomi, cosi' passano dall'alias come il codice custom
 * dei clienti. Menu, QlikItem e ChatAIConf restano in App\ e usavano questi
 * modelli senza `use`: i test sui loro metodi proteggono quel legame.
 *
 * Gia' coperti altrove (non duplicati): Menu::tenants() da ModelRelationsTest,
 * QlikItem::allowedGroups()/allowedTenants() da ModelRelationsTest, scrittura
 * di module_tenants da PublicSystemRoutesSaveEnableTest, GroupTenants in
 * ModuleHelper da GroupsCrudTest/CrossModuleRelationsTest.
 */
class PivotModelMoveTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    protected function setUp(): void
    {
        parent::setUp();

        // il layout incluso dalla Blade del popup referenzia le route dei moduli di sistema
        $this->registerAdminModules();
    }

    /** @return array<string, array{0: string}> */
    public static function modelNames(): array
    {
        return [
            'GroupTenants' => ['GroupTenants'],
            'MenuTenants' => ['MenuTenants'],
            'MenuGroups' => ['MenuGroups'],
            'ModuleTenants' => ['ModuleTenants'],
            'TenantsAllowed' => ['TenantsAllowed'],
            'ItemsAllowed' => ['ItemsAllowed'],
            'ChatAIGroupAllowed' => ['ChatAIGroupAllowed'],
            'ChatAITenantAllowed' => ['ChatAITenantAllowed'],
            'Modules' => ['Modules'],
        ];
    }

    /** @return array<string, array{0: string, 1: string}> nome => [tabella, ...] */
    public static function pivotTables(): array
    {
        return [
            'GroupTenants' => ['GroupTenants', 'group_tenants'],
            'MenuTenants' => ['MenuTenants', 'menu_tenants'],
            'MenuGroups' => ['MenuGroups', 'menu_groups'],
            'ModuleTenants' => ['ModuleTenants', 'module_tenants'],
            'TenantsAllowed' => ['TenantsAllowed', 'tenants_allowed'],
            'ItemsAllowed' => ['ItemsAllowed', 'items_allowed'],
            'ChatAIGroupAllowed' => ['ChatAIGroupAllowed', 'chatai_groups_allowed'],
            'ChatAITenantAllowed' => ['ChatAITenantAllowed', 'chatai_tenants_allowed'],
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

    #[DataProvider('pivotTables')]
    public function test_tabella_e_assenza_di_timestamp_dei_pivot_restano_invariate(string $name, string $table): void
    {
        $model = new ('App\\' . $name);

        $this->assertSame($table, $model->getTable());
        $this->assertFalse($model->usesTimestamps());
    }

    public function test_modules_tabella_invariata_e_soft_delete_attivo(): void
    {
        $this->assertSame('cms_moduls', (new \App\Modules())->getTable());

        $insert = fn (string $path, ?string $deletedAt) => DB::table('cms_moduls')->insertGetId([
            'name' => ucfirst($path),
            'path' => $path,
            'table_name' => $path,
            'controller' => 'Controller' . $path,
            'is_protected' => 0,
            'is_active' => 1,
            'created_at' => now(),
            'deleted_at' => $deletedAt,
        ]);
        $liveId = $insert('modulo_vivo', null);
        $deletedId = $insert('modulo_cancellato', now()->toDateTimeString());

        // positivo: il modulo non cancellato e' visibile
        $this->assertNotNull(\App\Modules::find($liveId));
        // negativo, stesso setup: il soft-deleted e' escluso dal global scope...
        $this->assertNull(\App\Modules::find($deletedId));
        // ...ma torna con withTrashed()
        $this->assertNotNull(\App\Modules::withTrashed()->find($deletedId));
    }

    public function test_menu_tenants_legge_la_pivot_tramite_il_modello(): void
    {
        $tenantId = $this->seedTenant();
        $otherTenantId = $this->seedTenant();
        $menu = $this->seedMenu();
        DB::table('menu_tenants')->insert(['menu_id' => $menu['id'], 'tenant_id' => $tenantId]);

        $this->assertSame([$tenantId], Menu::find($menu['id'])->tenants());
        $this->assertNotContains($otherTenantId, Menu::find($menu['id'])->tenants());
        $this->assertSame([$tenantId], \App\MenuTenants::where('menu_id', $menu['id'])->pluck('tenant_id')->all());
    }

    public function test_chatai_conf_allowed_groups_e_tenants_leggono_le_tabelle_dedicate(): void
    {
        $groupId = $this->seedGroup();
        $otherGroupId = $this->seedGroup();
        $tenantId = $this->seedTenant();
        $otherTenantId = $this->seedTenant();
        $confId = DB::table('chatai_confs')->insertGetId(['title' => 'Agente', 'created_at' => now()]);
        DB::table('chatai_groups_allowed')->insert(['chatai_conf_id' => $confId, 'group_id' => $groupId]);
        DB::table('chatai_tenants_allowed')->insert(['chatai_conf_id' => $confId, 'tenant_id' => $tenantId]);

        $conf = ChatAIConf::find($confId);

        $this->assertSame([$groupId], $conf->allowedGroups());
        $this->assertNotContains($otherGroupId, $conf->allowedGroups());
        $this->assertSame([$tenantId], $conf->allowedTenants());
        $this->assertNotContains($otherTenantId, $conf->allowedTenants());
    }

    public function test_qlik_item_allowed_groups_e_tenants_leggono_items_e_tenants_allowed(): void
    {
        $groupId = $this->seedGroup();
        $otherGroupId = $this->seedGroup();
        $tenantId = $this->seedTenant();
        $otherTenantId = $this->seedTenant();
        $itemId = DB::table('qlik_items')->insertGetId(['title' => 'Item', 'created_at' => now()]);
        DB::table('items_allowed')->insert(['item_id' => $itemId, 'group_id' => $groupId]);
        DB::table('tenants_allowed')->insert(['item_id' => $itemId, 'tenant_id' => $tenantId]);

        $item = QlikItem::find($itemId);

        $this->assertSame([$groupId], $item->allowedGroups());
        $this->assertNotContains($otherGroupId, $item->allowedGroups());
        $this->assertSame([$tenantId], $item->allowedTenants());
        $this->assertNotContains($otherTenantId, $item->allowedTenants());
    }

    /**
     * La Blade usa GroupTenants inline (\App\GroupTenants): senza copertura un
     * nome sbagliato emergerebbe solo aprendo il popup dei membri del gruppo.
     */
    public function test_popup_membri_del_gruppo_mostra_solo_utenti_dei_tenant_del_gruppo(): void
    {
        $this->actingAsSuperadmin();
        $tenantId = $this->seedTenant();
        $otherTenantId = $this->seedTenant();
        $groupId = $this->seedGroup();
        DB::table('group_tenants')->insert(['group_id' => $groupId, 'tenant_id' => $tenantId]);
        $inside = $this->seedUser(['tenant' => $tenantId, 'name' => 'Utente Dentro']);
        $outside = $this->seedUser(['tenant' => $otherTenantId, 'name' => 'Utente Fuori']);

        request()->merge(['select_to' => $groupId, 'name_column' => 'name']);
        $html = view('crudbooster::default.type_components.group_members_datamodal.browser', [
            'columns' => ['id', 'name'],
            'q' => '',
        ])->render();

        $this->assertStringContainsString($inside['name'], $html);
        $this->assertStringNotContainsString($outside['name'], $html);
    }
}
