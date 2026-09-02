<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test di caratterizzazione per il modulo Statistic Builder
 * (StatisticBuilderController). Scritti dopo aver corretto una
 * vulnerabilita' reale trovata in analisi (dettagli in
 * docs/refactoring/067-statistic-builder-sql-arbitrario-e-test.md):
 * postSaveComponent() (l'unico punto di scrittura di 'config', compresa la
 * chiave 'sql' che alcuni widget - Small Box/Table/Chart Area/Bar/Line/
 * Qlik - eseguono letteralmente via DB::select() in fase di rendering) non
 * aveva ALCUN controllo di privilegio: qualunque utente autenticato poteva
 * scrivere SQL arbitrario in un widget di una dashboard esistente ed
 * eseguirlo contro il DB reale con la semplice visualizzazione di quella
 * dashboard da parte di chiunque.
 *
 * getViewComponent()/getListComponent() NON sono state limitate al
 * superadmin (a differenza di postSaveComponent()): sono usate anche dalla
 * pagina di visualizzazione normale delle dashboard (show.blade.php,
 * @include('crudbooster::statistic_builder.index') - la stessa vista
 * inclusa da builder.blade.php), non solo dall'editor - limitarle
 * avrebbe rotto la visualizzazione per chiunque non sia superadmin.
 *
 * Deliberatamente rimandati (decisi con l'utente): il gap generico di
 * autorizzazione su postAddComponent()/postUpdateAreaComponent()/
 * getListComponent() (qui solo caratterizzato, non corretto) e i bug
 * null-safety trovati in analisi su getBuilder()/getEditComponent()/
 * getViewComponent() con un id/componentID inesistente - per questo i test
 * sui controlli di accesso già esistenti usano sempre un id/componentID
 * REALE (il controllo isSuperadmin() gira comunque prima di qualunque
 * dereferenziazione pericolosa, quindi non serve per quei test specifici,
 * ma si evita comunque di sfiorare il path che crasherebbe).
 */
class StatisticBuilderCrudTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAdminModules();
    }

    private function seedStatistic(array $overrides = []): array
    {
        $data = array_merge([
            'name' => 'Dashboard Test',
            'slug' => 'dashboard-test-' . uniqid(),
            'layout' => null,
            'created_at' => now(),
        ], $overrides);

        $id = DB::table('cms_statistics')->insertGetId($data);

        return array_merge($data, ['id' => $id]);
    }

    private function seedDashboardLayout(array $overrides = []): array
    {
        $data = array_merge([
            'layoutname' => 'Layout Test',
            'code_layout' => "<div id='layout-personalizzato-di-test'></div>",
            'created_at' => now(),
        ], $overrides);

        $id = DB::table('dashboard_layouts')->insertGetId($data);

        return array_merge($data, ['id' => $id]);
    }

    private function seedComponent(array $overrides = []): array
    {
        $data = array_merge([
            'id_cms_statistics' => 0,
            'componentID' => 'phpunit-test-component-' . uniqid(),
            'component_name' => 'smallbox',
            'area_name' => 'area1',
            'sorting' => 1,
            'name' => 'Componente Test',
            'config' => json_encode(['sql' => 'select 1 as valore']),
            'created_at' => now(),
        ], $overrides);

        $id = DB::table('cms_statistic_components')->insertGetId($data);

        return array_merge($data, ['id' => $id]);
    }

    // ---------------------------------------------------------------
    // Regressione del fix su postSaveComponent()
    // ---------------------------------------------------------------

    public function test_postsavecomponent_nega_accesso_a_non_superadmin(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: false, visibleModulePaths: []);
        $statistic = $this->seedStatistic();
        $component = $this->seedComponent(['id_cms_statistics' => $statistic['id']]);

        $response = $this->post('http://localhost/admin/statistic_builder/save-component', [
            'componentid' => $component['componentID'],
            'name' => 'Nome Modificato Da Non Superadmin',
            'config' => ['sql' => 'select * from cms_users'],
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('message', trans('crudbooster.denied_access'));
        $this->assertDatabaseHas('cms_statistic_components', [
            'componentID' => $component['componentID'],
            'name' => 'Componente Test',
        ]);
    }

    public function test_postsavecomponent_riesce_per_un_superadmin(): void
    {
        $this->actingAsSuperadmin();
        $statistic = $this->seedStatistic();
        $component = $this->seedComponent(['id_cms_statistics' => $statistic['id']]);

        $response = $this->post('http://localhost/admin/statistic_builder/save-component', [
            'componentid' => $component['componentID'],
            'name' => 'Nome Modificato Dal Superadmin',
            'config' => ['sql' => 'select count(*) as totale from cms_statistics'],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
        $row = DB::table('cms_statistic_components')->where('componentID', $component['componentID'])->first();
        $this->assertSame('Nome Modificato Dal Superadmin', $row->name);
        $config = json_decode($row->config, true);
        $this->assertSame('select count(*) as totale from cms_statistics', $config['sql']);
    }

    // ---------------------------------------------------------------
    // CRUD standard delle dashboard (cbInit() / motore CBController)
    // ---------------------------------------------------------------

    public function test_lista_mostra_le_dashboard_esistenti(): void
    {
        $this->actingAsSuperadmin();
        $this->seedStatistic(['name' => 'Dashboard Da Trovare In Lista']);

        $response = $this->get('http://localhost/admin/statistic_builder');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Da Trovare In Lista');
    }

    public function test_creazione_dashboard_genera_slug_da_name(): void
    {
        $this->actingAsSuperadmin();
        $layout = $this->seedDashboardLayout();

        $response = $this->post('http://localhost/admin/statistic_builder/add-save', [
            'name' => 'Dashboard Vendite',
            'layout' => $layout['id'],
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('cms_statistics', ['name' => 'Dashboard Vendite', 'slug' => 'dashboard-vendite']);
    }

    /**
     * Comportamento voluto (commentato nel codice, non un bug): lo slug e'
     * il permalink pubblico della dashboard (menu, /statistic_builder/show/{slug}) -
     * hook_before_edit() e' volutamente vuoto, il form standard non ha
     * nemmeno un campo 'slug', quindi rinominare la dashboard non lo tocca.
     */
    public function test_modifica_dashboard_non_rigenera_lo_slug(): void
    {
        $this->actingAsSuperadmin();
        $layout = $this->seedDashboardLayout();
        $statistic = $this->seedStatistic(['name' => 'Dashboard Originale', 'slug' => 'dashboard-originale']);

        $response = $this->post("http://localhost/admin/statistic_builder/edit-save/{$statistic['id']}", [
            'name' => 'Dashboard Rinominata',
            'layout' => $layout['id'],
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('cms_statistics', [
            'id' => $statistic['id'],
            'name' => 'Dashboard Rinominata',
            'slug' => 'dashboard-originale',
        ]);
    }

    public function test_cancellazione_dashboard_riesce(): void
    {
        $this->actingAsSuperadmin();
        $statistic = $this->seedStatistic();

        $response = $this->get("http://localhost/admin/statistic_builder/delete/{$statistic['id']}");

        $response->assertStatus(302);
        // cms_statistics non ha 'deleted_at': CBController::getDelete() fa
        // una DELETE fisica (stesso ramo di Settings/API Generator).
        $this->assertDatabaseMissing('cms_statistics', ['id' => $statistic['id']]);
    }

    // ---------------------------------------------------------------
    // Visualizzazione dashboard (getShow())
    // ---------------------------------------------------------------

    public function test_getshow_su_slug_inesistente_reindirizza(): void
    {
        $this->actingAsSuperadmin();

        $response = $this->get('http://localhost/admin/statistic_builder/show/slug-che-non-esiste');

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'warning');
    }

    public function test_getshow_con_layout_assegnato_risolve_code_layout(): void
    {
        $this->actingAsSuperadmin();
        $layout = $this->seedDashboardLayout(['code_layout' => "<div id='layout-personalizzato-di-test'></div>"]);
        $this->seedStatistic(['slug' => 'dashboard-con-layout', 'layout' => $layout['id']]);

        $response = $this->get('http://localhost/admin/statistic_builder/show/dashboard-con-layout');

        $response->assertStatus(200);
        $response->assertSee('layout-personalizzato-di-test', false);
    }

    public function test_getshow_senza_layout_usa_griglia_di_default(): void
    {
        $this->actingAsSuperadmin();
        $this->seedStatistic(['slug' => 'dashboard-senza-layout', 'layout' => null]);

        $response = $this->get('http://localhost/admin/statistic_builder/show/dashboard-senza-layout');

        $response->assertStatus(200);
        $response->assertSee("id='area1'", false);
        $response->assertSee("id='area9'", false);
    }

    // ---------------------------------------------------------------
    // Controlli di accesso già esistenti (getBuilder()/getEditComponent())
    // ---------------------------------------------------------------

    public function test_getbuilder_nega_accesso_a_non_superadmin(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: ['statistic_builder']);
        $statistic = $this->seedStatistic();

        $response = $this->get("http://localhost/admin/statistic_builder/builder/{$statistic['id']}");

        $response->assertStatus(302);
        $response->assertSessionHas('message', trans('crudbooster.denied_access'));
    }

    public function test_geteditcomponent_nega_accesso_a_non_superadmin(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: ['statistic_builder']);
        $statistic = $this->seedStatistic();
        $component = $this->seedComponent(['id_cms_statistics' => $statistic['id']]);

        $response = $this->get("http://localhost/admin/statistic_builder/edit-component/{$component['componentID']}");

        $response->assertStatus(302);
        $response->assertSessionHas('message', trans('crudbooster.denied_access'));
    }

    // ---------------------------------------------------------------
    // Caratterizzazione del punto A (deliberatamente non corretto)
    // ---------------------------------------------------------------

    public function test_caratterizzazione_postaddcomponent_e_raggiungibile_da_utente_senza_permessi(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: false, visibleModulePaths: []);
        $statistic = $this->seedStatistic();

        $response = $this->post('http://localhost/admin/statistic_builder/add-component', [
            'component_name' => 'smallbox',
            'id_cms_statistics' => $statistic['id'],
            'sorting' => 1,
            'area' => 'area1',
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertNotEmpty($data['componentID']);
        $this->assertDatabaseHas('cms_statistic_components', [
            'componentID' => $data['componentID'],
            'id_cms_statistics' => $statistic['id'],
        ]);
    }

    public function test_caratterizzazione_postupdateareacomponent_e_raggiungibile_da_utente_senza_permessi(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: false, visibleModulePaths: []);
        $statistic = $this->seedStatistic();
        $component = $this->seedComponent(['id_cms_statistics' => $statistic['id'], 'area_name' => 'area1', 'sorting' => 1]);

        $response = $this->post('http://localhost/admin/statistic_builder/update-area-component', [
            'componentid' => $component['componentID'],
            'areaname' => 'area5',
            'sorting' => 3,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
        $this->assertDatabaseHas('cms_statistic_components', [
            'componentID' => $component['componentID'],
            'area_name' => 'area5',
            'sorting' => 3,
        ]);
    }

    public function test_caratterizzazione_getlistcomponent_e_raggiungibile_da_utente_senza_permessi(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: false, visibleModulePaths: []);
        $statistic = $this->seedStatistic();
        $this->seedComponent(['id_cms_statistics' => $statistic['id'], 'area_name' => 'area2', 'name' => 'Componente Da Trovare']);

        $response = $this->get("http://localhost/admin/statistic_builder/list-component/{$statistic['id']}/area2");

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data['components']);
        $this->assertSame('Componente Da Trovare', $data['components'][0]['name']);
    }
}
