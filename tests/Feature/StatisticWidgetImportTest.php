<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test del riquadro "Importa" del builder a griglia: copia INDIPENDENTE di
 * un widget di un'altra dashboard (StatisticBuilderController::
 * getImportSources/getImportSourceWidgets/postImportComponent). Solo
 * superadmin, come il resto del builder.
 */
class StatisticWidgetImportTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    private const BASE = 'http://localhost/admin/statistic_builder/';

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAdminModules();
    }

    private function seedStatistic(string $name): int
    {
        return DB::table('cms_statistics')->insertGetId([
            'name' => $name,
            'slug' => str_slug($name) . '-' . uniqid(),
            'layout' => null,
            'layout_mode' => 'grid',
            'created_at' => now(),
        ]);
    }

    private function seedComponent(int $statisticId, array $overrides = []): array
    {
        $data = array_merge([
            'id_cms_statistics' => $statisticId,
            'componentID' => 'phpunit-import-' . uniqid(),
            'component_name' => 'smallbox',
            'name' => 'Fatturato',
            'config' => json_encode(['name' => 'Fatturato', 'sql' => 'select 1', 'color' => '#ff0000']),
            'pos_x' => 0,
            'pos_y' => 0,
            'width' => 4,
            'height' => 3,
            'created_at' => now(),
        ], $overrides);

        DB::table('cms_statistic_components')->insert($data);

        return $data;
    }

    public function test_le_dashboard_sono_in_ordine_alfabetico_e_solo_quelle_con_widget(): void
    {
        $this->actingAsSuperadmin();
        $zeta = $this->seedStatistic('Zeta');
        $alfa = $this->seedStatistic('alfa');
        $this->seedStatistic('Vuota');
        $this->seedComponent($zeta);
        $this->seedComponent($alfa);

        $names = collect($this->getJson(self::BASE . 'import-sources')->assertStatus(200)->json('dashboards'))->pluck('name')->all();

        $this->assertSame(['alfa', 'Zeta'], $names);
    }

    public function test_i_widget_sono_in_ordine_alfabetico_con_nome_e_tipo(): void
    {
        $this->actingAsSuperadmin();
        $dashboard = $this->seedStatistic('Origine');
        $this->seedComponent($dashboard, ['config' => json_encode(['name' => 'Zebra']), 'name' => 'Zebra', 'component_name' => 'table']);
        $this->seedComponent($dashboard, ['config' => json_encode(['name' => 'Ape']), 'name' => 'Ape', 'component_name' => 'smallbox']);

        $widgets = $this->getJson(self::BASE . 'import-source-widgets/' . $dashboard)->assertStatus(200)->json('widgets');

        $this->assertCount(2, $widgets);
        $this->assertStringStartsWith('Ape', $widgets[0]['label']);
        $this->assertStringStartsWith('Zebra', $widgets[1]['label']);
        $this->assertStringContainsString(trans('crudbooster.statistic_builder_widget_type_table'), $widgets[1]['label']);
    }

    public function test_un_widget_senza_dimensioni_usa_quelle_di_default_del_suo_tipo(): void
    {
        $this->actingAsSuperadmin();
        $dashboard = $this->seedStatistic('Legacy');
        $this->seedComponent($dashboard, ['width' => null, 'height' => null, 'component_name' => 'smallbox']);

        $widget = $this->getJson(self::BASE . 'import-source-widgets/' . $dashboard)->json('widgets.0');

        $this->assertSame(3, $widget['width']);
        $this->assertSame(2, $widget['height']);
    }

    public function test_l_import_crea_una_copia_indipendente_nella_dashboard_di_destinazione(): void
    {
        $this->actingAsSuperadmin();
        $origine = $this->seedStatistic('Origine');
        $destinazione = $this->seedStatistic('Destinazione');
        $source = $this->seedComponent($origine);

        $response = $this->postJson(self::BASE . 'import-component', [
            'source_componentid' => $source['componentID'],
            'id_cms_statistics' => $destinazione,
            'pos_x' => 2, 'pos_y' => 5, 'width' => 4, 'height' => 3,
        ]);

        $response->assertStatus(200);
        $newId = $response->json('componentID');
        $this->assertNotEmpty($newId);
        $this->assertNotSame($source['componentID'], $newId);

        $copy = DB::table('cms_statistic_components')->where('componentID', $newId)->first();
        $this->assertSame($destinazione, (int) $copy->id_cms_statistics);
        $this->assertSame('smallbox', $copy->component_name);
        $this->assertSame('Fatturato', $copy->name);
        $this->assertSame($source['config'], $copy->config);
        $this->assertSame(2, (int) $copy->pos_x);
        $this->assertSame(5, (int) $copy->pos_y);

        // L'originale non e' stato toccato.
        $this->assertSame($origine, (int) DB::table('cms_statistic_components')->where('componentID', $source['componentID'])->value('id_cms_statistics'));

        // Modificare la copia non cambia l'originale.
        DB::table('cms_statistic_components')->where('componentID', $newId)->update(['config' => json_encode(['name' => 'Cambiato'])]);
        $this->assertSame($source['config'], DB::table('cms_statistic_components')->where('componentID', $source['componentID'])->value('config'));
    }

    public function test_copiare_nella_stessa_dashboard_aggiunge_il_suffisso_copia(): void
    {
        $this->actingAsSuperadmin();
        $dashboard = $this->seedStatistic('Stessa');
        $source = $this->seedComponent($dashboard);

        $newId = $this->postJson(self::BASE . 'import-component', [
            'source_componentid' => $source['componentID'],
            'id_cms_statistics' => $dashboard,
            'pos_x' => 0, 'pos_y' => 4,
        ])->assertStatus(200)->json('componentID');

        $copy = DB::table('cms_statistic_components')->where('componentID', $newId)->first();
        $suffix = trans('crudbooster.statistic_builder_import_copy_suffix');

        $this->assertSame('Fatturato ' . $suffix, $copy->name);
        $this->assertSame('Fatturato ' . $suffix, json_decode($copy->config)->name);
        $this->assertSame(2, DB::table('cms_statistic_components')->where('id_cms_statistics', $dashboard)->count());
    }

    public function test_import_con_widget_o_dashboard_inesistenti_risponde_404_e_non_crea_nulla(): void
    {
        $this->actingAsSuperadmin();
        $dashboard = $this->seedStatistic('Destinazione');
        $source = $this->seedComponent($this->seedStatistic('Origine'));
        $before = DB::table('cms_statistic_components')->count();

        $this->postJson(self::BASE . 'import-component', [
            'source_componentid' => 'non-esiste',
            'id_cms_statistics' => $dashboard,
        ])->assertStatus(404);

        $this->postJson(self::BASE . 'import-component', [
            'source_componentid' => $source['componentID'],
            'id_cms_statistics' => 999999,
        ])->assertStatus(404);

        $this->assertSame($before, DB::table('cms_statistic_components')->count());
    }

    public function test_un_utente_non_superadmin_non_puo_importare_ne_elencare(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: ['statistic_builder']);
        $origine = $this->seedStatistic('Origine');
        $destinazione = $this->seedStatistic('Destinazione');
        $source = $this->seedComponent($origine);
        $before = DB::table('cms_statistic_components')->count();

        $this->getJson(self::BASE . 'import-sources')->assertStatus(403);
        $this->getJson(self::BASE . 'import-source-widgets/' . $origine)->assertStatus(403);
        $this->postJson(self::BASE . 'import-component', [
            'source_componentid' => $source['componentID'],
            'id_cms_statistics' => $destinazione,
        ])->assertStatus(403);

        $this->assertSame($before, DB::table('cms_statistic_components')->count());
    }
}
