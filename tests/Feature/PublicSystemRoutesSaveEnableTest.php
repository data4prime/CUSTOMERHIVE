<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Caso 1 del piano 324 (docs/refactoring/piani/324-rotte-custom-sotto-login.md):
 * POST admin/module_generator/save_enable. Comportamento OBIETTIVO: un ospite
 * e un non superadmin non toccano module_tenants; solo il superadmin la
 * riscrive. Oggi (prima dei passi 2-3) ospite e non superadmin la svuotano:
 * i due casi negativi sono ROSSI di proposito.
 *
 * ModuleHelper::update_enabled_tenants() fa TRUNCATE, che in MySQL e' un
 * commit implicito: la transazione di RefreshDatabase non puo' piu' annullare
 * nulla di quanto seminato prima. Per non sporcare i test successivi,
 * tearDown() cancella le righe create da questo test (id oltre il massimo
 * registrato in setUp) quando si accorge che il commit e' avvenuto.
 *
 * saveEnable() legge $_POST direttamente e il client di test non lo popola:
 * postSaveEnable() lo imposta a mano e lo ripristina.
 */
class PublicSystemRoutesSaveEnableTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    private const TRACKED_TABLES = [
        'tenants',
        'groups',
        'cms_users',
        'cms_privileges',
        'cms_privileges_roles',
        'cms_moduls',
        'cms_logs',
    ];

    /** @var array<string,int> */
    private array $idMarks = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::TRACKED_TABLES as $table) {
            $this->idMarks[$table] = (int) DB::table($table)->max('id');
        }

        $this->registerAdminModules();
    }

    protected function tearDown(): void
    {
        $this->removeRowsCommittedByTruncate();

        parent::tearDown();
    }

    private function removeRowsCommittedByTruncate(): void
    {
        if (DB::connection()->getPdo()->inTransaction()) {
            return; // nessun TRUNCATE: il rollback di RefreshDatabase basta
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('module_tenants')->truncate();
        foreach ($this->idMarks as $table => $mark) {
            DB::table($table)->where('id', '>', $mark)->delete();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * module_tenants pre-popolata con due righe valide (FK su cms_moduls e
     * tenants, unique su module_id+tenant_id) e payload che le sostituirebbe
     * con una riga diversa.
     *
     * @return array{payload:array,before:array,after:array}
     */
    private function seedModuleTenants(): array
    {
        $moduleA = (int) DB::table('cms_moduls')->where('path', 'users')->value('id');
        $moduleB = (int) DB::table('cms_moduls')->where('path', 'groups')->value('id');
        $tenantA = $this->seedTenant();
        $tenantB = $this->seedTenant();

        DB::table('module_tenants')->insert([
            ['module_id' => $moduleA, 'tenant_id' => $tenantA],
            ['module_id' => $moduleA, 'tenant_id' => $tenantB],
        ]);

        return [
            // la chiave module_tenant_enabler deve esistere: $_POST[...] senza dà un warning (500)
            'payload' => ['module_tenant_enabler' => [$moduleB => [$tenantB => '1']]],
            'before' => [[$moduleA, $tenantA], [$moduleA, $tenantB]],
            'after' => [[$moduleB, $tenantB]],
        ];
    }

    /**
     * @return array<int,array{0:int,1:int}>
     */
    private function moduleTenantsRows(): array
    {
        return DB::table('module_tenants')
            ->orderBy('module_id')
            ->orderBy('tenant_id')
            ->get(['module_id', 'tenant_id'])
            ->map(fn ($row) => [(int) $row->module_id, (int) $row->tenant_id])
            ->all();
    }

    private function postSaveEnable(array $payload): TestResponse
    {
        $previous = $_POST;
        $_POST = $payload;

        try {
            return $this->post(
                'http://localhost/admin/module_generator/save_enable',
                $payload,
                ['Referer' => 'http://localhost/admin/module_generator/enable']
            );
        } finally {
            $_POST = $previous;
        }
    }

    public function test_ospite_non_tocca_module_tenants(): void
    {
        $seed = $this->seedModuleTenants();

        $response = $this->postSaveEnable($seed['payload']);

        $response->assertStatus(302);
        $response->assertRedirect(url('admin/login'));
        $this->assertSame($seed['before'], $this->moduleTenantsRows());
    }

    public function test_non_superadmin_riceve_denied_access_e_module_tenants_resta_identica(): void
    {
        $seed = $this->seedModuleTenants();
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: ['module_generator']);

        $response = $this->postSaveEnable($seed['payload']);

        $response->assertStatus(302);
        $response->assertSessionHas('message', trans('crudbooster.denied_access'));
        $this->assertSame($seed['before'], $this->moduleTenantsRows());
    }

    /**
     * Caso positivo con lo stesso setup dei due negativi: la richiesta, se
     * autorizzata, arriva al controller e riscrive la tabella.
     */
    public function test_superadmin_riscrive_module_tenants_secondo_il_payload(): void
    {
        $seed = $this->seedModuleTenants();
        $this->actingAsSuperadmin();

        $response = $this->postSaveEnable($seed['payload']);

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'success');
        $this->assertSame($seed['after'], $this->moduleTenantsRows());
    }
}
