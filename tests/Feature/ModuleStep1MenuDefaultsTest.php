<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test di caratterizzazione (batch 0 del piano 317) per
 * Menu::assign_default_tenant() e Menu::assign_default_group(), raggiunti da
 * ModulsController::postStep1() quando si crea un modulo con "create_menu":
 * la voce di menu creata riceve una riga in menu_tenants (tenant dell'attore)
 * e una in menu_groups (gruppo primario dell'attore).
 *
 * MenusCrudTest::test_creazione_di_un_tenantadmin_assegna_automaticamente_il_
 * suo_tenant copre solo la parte tenant e solo via MenusController: qui si
 * copre il flusso del Module Generator, che chiama entrambi i metodi.
 *
 * Come ModuleGeneratorCrudTest, il controller generato finisce nell'albero
 * sorgente (app/Http/Controllers/): il file viene ripulito in tearDown().
 */
class ModuleStep1MenuDefaultsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    private array $fixtureFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAdminModules();
    }

    protected function tearDown(): void
    {
        foreach ($this->fixtureFiles as $path) {
            @unlink($path);
        }
        foreach (glob(base_path('app/Http/Controllers/*PhpunitTest*.php')) ?: [] as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    private function trackGeneratedController(string $moduleName): void
    {
        $controller = DB::table('cms_moduls')->where('name', $moduleName)->value('controller');
        if ($controller) {
            $this->fixtureFiles[] = base_path('app/Http/Controllers/' . $controller . '.php');
        }
    }

    public function test_step1_con_create_menu_assegna_tenant_e_gruppo_primario_dell_attore_alla_voce(): void
    {
        $actor = $this->actingAsSuperadmin();
        $actorPrimaryGroup = (int) DB::table('cms_users')->where('id', $actor['userId'])->value('primary_group');

        $response = $this->post('http://localhost/admin/module_generator/step1', [
            'name' => 'Phpunit Test Menu Defaults',
            'table' => 'new',
            'icon' => 'fa fa-bug',
            'create_menu' => 1,
        ]);
        $this->trackGeneratedController('Phpunit Test Menu Defaults');

        $response->assertStatus(302);
        $menuId = DB::table('cms_menus')->where('name', 'Phpunit Test Menu Defaults')->value('id');
        $this->assertNotNull($menuId, 'Precondizione: la voce di menu deve essere stata creata.');

        $this->assertSame(
            [$actor['tenantId']],
            DB::table('menu_tenants')->where('menu_id', $menuId)->pluck('tenant_id')->map(fn ($id) => (int) $id)->all()
        );
        $this->assertSame(
            [$actorPrimaryGroup],
            DB::table('menu_groups')->where('menu_id', $menuId)->pluck('group_id')->map(fn ($id) => (int) $id)->all()
        );
    }

    public function test_step1_senza_create_menu_non_crea_voce_ne_associazioni(): void
    {
        $this->actingAsSuperadmin();

        $response = $this->post('http://localhost/admin/module_generator/step1', [
            'name' => 'Phpunit Test Senza Menu',
            'table' => 'new',
            'icon' => 'fa fa-bug',
        ]);
        $this->trackGeneratedController('Phpunit Test Senza Menu');

        $response->assertStatus(302);
        $this->assertDatabaseMissing('cms_menus', ['name' => 'Phpunit Test Senza Menu']);
        $this->assertSame(0, DB::table('menu_tenants')->count());
        $this->assertSame(0, DB::table('menu_groups')->count());
    }
}
