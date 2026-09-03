<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test di caratterizzazione per il modulo Module Generator (ModulsController
 * + CRUDBooster::generateController()). Come per API Generator
 * ([065](../../docs/refactoring/065-api-generator-rce-e-test.md)) e
 * Statistic Builder ([067](../../docs/refactoring/067-statistic-builder-sql-arbitrario-e-test.md)),
 * l'analisi ha trovato la stessa classe di vulnerabilita' - RCE via
 * literal PHP grezzo scritto in un controller autoloaded - in PIU' punti
 * di questo controller, corretti prima di scrivere questi test (dettagli
 * in docs/refactoring/068-module-generator-rce-e-test.md):
 *
 * - CRUDBooster::generateController(): $table finiva incollato grezzo
 *   dentro un literal a doppi apici (postStep1() lo raggiunge con un
 *   table_name non risanificato quando si seleziona una tabella
 *   "esistente" - il <select> e' solo lato client). $controllername (nome
 *   CLASSE e FILE, var_export() non aiuta li') non era sanificato -> path
 *   traversal. Corretti con var_export() e sanificazione a [A-Za-z0-9_].
 * - ModulsController::postStep3(): column/name/join_table/join_field/
 *   width/callbackphp incollati grezzi (query aveva solo addslashes()) nel
 *   sorgente scritto per $this->col[]. Corretto con var_export() ovunque.
 *   Bug indipendente corretto nello stesso punto: il flag "download"
 *   controllava una variabile mai definita ($id_download invece di
 *   $is_download), quindi non veniva mai scritto.
 * - ModulsController::postStep5(): iterava su OGNI chiave POST (tranne
 *   _token/id/submit) scrivendo $this->{chiave} = "{valore}" - chiave E
 *   valore controllati dall'attaccante, nessuna whitelist, nessun
 *   escaping. Corretto con whitelist sulle chiavi realmente esposte dal
 *   form + var_export() sui valori. Inoltre questo metodo non aveva ALCUN
 *   controllo di privilegio (nemmeno il debole isView() usato dagli altri
 *   step) - aggiunto isSuperadmin(), stesso livello gia' richiesto da
 *   save_table() (step2, DDL) - aggiunto anche a postStep3() per lo stesso
 *   motivo.
 * - ModulsController::getDelete(): non ricontrollava is_protected (il
 *   filtro che nasconde i moduli di sistema dalla lista) prima di
 *   cancellare - un id diretto bypassava il filtro. Aggiunto il check +
 *   una null-safety mancante sull'id inesistente.
 * - getTableColumns()/getCheckSlug(): nessun controllo di privilegio -
 *   il primo esponeva lo schema di QUALUNQUE tabella (incluse cms_users,
 *   cms_apikey) a chiunque fosse autenticato. Aggiunto isView().
 *
 * Come per API Generator, i file generati/modificati finiscono DENTRO
 * L'ALBERO SORGENTE VERO E PROPRIO (app/Http/Controllers/, tracciato da
 * git): ogni test traccia i file che tocca in $fixtureFiles, ripuliti in
 * tearDown() insieme a uno sweep di sicurezza su qualunque
 * *PhpunitTest*.php rimasto.
 */
class ModuleGeneratorCrudTest extends TestCase
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

    /**
     * Semina la riga cms_moduls del "modulo target" su cui operano gli
     * step 3/4/5 del wizard (distinta dalla riga 'module_generator' che
     * registerAdminModules() semina per instradare ModulsController
     * stesso).
     */
    private function seedFixtureModule(string $controller, string $tableName = 'phpunit_test_table'): array
    {
        $id = DB::table('cms_moduls')->insertGetId([
            'name' => 'Modulo Phpunit Test',
            'table_name' => $tableName,
            'controller' => $controller,
            'path' => 'phpunit_test_module_' . uniqid(),
            'is_protected' => 0,
            'is_active' => 1,
            'created_at' => now(),
        ]);

        return ['id' => $id];
    }

    /**
     * Scrive su disco reale un controller "esistente" minimo, con tutti e
     * 3 i blocchi di marcatori (CONFIGURATION/COLUMNS/FORM) che
     * postStep3()/postStep4()/postStep5() si aspettano per fare
     * l'explode() sul contenuto attuale del file. Non deve essere una
     * classe realmente istanziabile: questi metodi leggono/scrivono il
     * file come testo, non caricano la classe.
     */
    private function writeFixtureModuleControllerFile(string $className): string
    {
        $path = base_path('app/Http/Controllers/' . $className . '.php');
        $contents = '<?php namespace App\Http\Controllers;' . "\n"
            . 'class ' . $className . ' {' . "\n"
            . '    public function cbInit() {' . "\n"
            . "\t\t\t" . '# START CONFIGURATION DO NOT REMOVE THIS LINE' . "\n"
            . "\t\t\t" . '$this->table = "placeholder";' . "\n"
            . "\t\t\t" . '# END CONFIGURATION DO NOT REMOVE THIS LINE' . "\n\n"
            . "\t\t\t" . '# START COLUMNS DO NOT REMOVE THIS LINE' . "\n"
            . "\t\t\t" . '$this->col = [];' . "\n"
            . "\t\t\t" . '# END COLUMNS DO NOT REMOVE THIS LINE' . "\n\n"
            . "\t\t\t" . '# START FORM DO NOT REMOVE THIS LINE' . "\n"
            . "\t\t\t" . '$this->form = [];' . "\n"
            . "\t\t\t" . '# END FORM DO NOT REMOVE THIS LINE' . "\n"
            . '    }' . "\n"
            . '}' . "\n";
        File::put($path, $contents);
        $this->fixtureFiles[] = $path;

        return $path;
    }

    /**
     * Verifica che $value sia stato incollato in una riga
     * "$this->property = ...;" SOLO tramite var_export() (literal a
     * singoli apici correttamente escapato), mai come literal a doppi
     * apici grezzo (il pattern vulnerabile prima del fix).
     */
    private function assertPropertySafelyEmbedded(string $contents, string $property, string $value): void
    {
        $expected = var_export($value, true);
        $pattern = '/\$this->' . preg_quote($property, '/') . '\s*=\s*' . preg_quote($expected, '/') . '\s*;/';
        $this->assertMatchesRegularExpression($pattern, $contents, "\$this->$property non e' stato incollato via var_export().");
        $this->assertStringNotContainsString('"' . $value . '"', $contents, "Trovato un literal a doppi apici grezzo per $property - possibile regressione RCE.");
    }

    /**
     * Stessa verifica di assertPropertySafelyEmbedded(), ma per le chiavi
     * dell'array $this->col[] = ["chiave"=>...] scritto da postStep3().
     */
    private function assertColumnValueSafelyEmbedded(string $contents, string $key, string $value): void
    {
        $expected = '"' . $key . '"=>' . var_export($value, true);
        $this->assertStringContainsString($expected, $contents, "\"$key\" non e' stato incollato via var_export().");
        $this->assertStringNotContainsString('"' . $key . '"=>"' . $value . '"', $contents, "Trovato un literal a doppi apici grezzo per $key - possibile regressione RCE.");
        $this->assertStringNotContainsString('"' . $key . '"=>\'' . $value . '\'', $contents, "Trovato un literal a singoli apici grezzo per $key - possibile regressione RCE.");
    }

    // ---------------------------------------------------------------
    // Regressione RCE - CRUDBooster::generateController(), raggiunta da
    // postStep1() (creazione modulo)
    // ---------------------------------------------------------------

    public function test_step1_con_nome_tabella_pericoloso_lo_salva_come_dato_letterale_nel_controller_generato(): void
    {
        $this->actingAsSuperadmin();
        $maliciousTable = 'mg_phpunit_test_tabella"; system(\'touch /tmp/pwned-modgen-table\'); //';

        $response = $this->post('http://localhost/admin/module_generator/step1', [
            'name' => 'Phpunit Test RCE Table',
            'table' => $maliciousTable,
            'icon' => 'fa fa-bug',
        ]);

        $response->assertStatus(302);
        $row = DB::table('cms_moduls')->where('name', 'Phpunit Test RCE Table')->first();
        $this->assertNotNull($row);
        $generatedPath = base_path('app/Http/Controllers/' . $row->controller . '.php');
        $this->fixtureFiles[] = $generatedPath;
        $this->assertFileExists($generatedPath);
        $contents = file_get_contents($generatedPath);

        // ModulsController::postStep1() ora fa passare table_name da
        // ModuleHelper::sql_name_encode() PRIMA di salvarlo/passarlo a
        // generateController() (stesso filtro gia' applicato al ramo
        // "nuova tabella"): il valore pericoloso arriva gia' neutralizzato
        // - non solo il literal PHP e' safely embedded (var_export, difesa
        // in profondita'), il valore stesso non contiene piu' nulla di
        // pericoloso. Vedi anche il fix indipendente dentro
        // generateController() stesso (CB::pk()/Schema::getIndexes() con
        // quella stringa grezza era una SQL injection vera, non solo
        // un'iniezione PHP: Schema::getIndexes() di Laravel usa una
        // quoteString() non parametrizzata).
        $sanitized = \App\Helpers\ModuleHelper::sql_name_encode($maliciousTable);
        $this->assertNotSame($maliciousTable, $sanitized);
        $this->assertPropertySafelyEmbedded($contents, 'table', $sanitized);
        $this->assertStringNotContainsString('system(', $contents);
        $this->assertSame($sanitized, DB::table('cms_moduls')->where('id', $row->id)->value('table_name'));
    }

    public function test_step1_con_nome_contenente_slash_sanifica_il_controller_e_non_scrive_file_fuori_da_controllers(): void
    {
        $this->actingAsSuperadmin();

        $response = $this->post('http://localhost/admin/module_generator/step1', [
            'name' => 'Phpunit Test ../../evil',
            'table' => 'new',
            'icon' => 'fa fa-bug',
        ]);

        $response->assertStatus(302);
        $row = DB::table('cms_moduls')->where('name', 'Phpunit Test ../../evil')->first();
        $this->assertNotNull($row);
        $this->assertMatchesRegularExpression('/^Admin[A-Za-z0-9_]+$/', $row->controller);

        $generatedPath = base_path('app/Http/Controllers/' . $row->controller . '.php');
        $this->fixtureFiles[] = $generatedPath;
        $this->assertFileExists($generatedPath);
        $this->assertSame(base_path('app/Http/Controllers'), dirname($generatedPath));
    }

    // ---------------------------------------------------------------
    // Regressione RCE - postStep3() (colonne lista)
    // ---------------------------------------------------------------

    public function test_step3_con_valori_pericolosi_nelle_colonne_li_salva_come_dato_letterale(): void
    {
        $this->actingAsSuperadmin();
        $module = $this->seedFixtureModule('PhpunitTestStep3FixtureController');
        $this->writeFixtureModuleControllerFile('PhpunitTestStep3FixtureController');

        $maliciousLabel = 'Label"; system(\'x\'); //';
        $maliciousName = 'nome_campo"; system(\'x\'); //';
        $maliciousJoinTable = 'jtab"; system(\'x\'); //';
        $maliciousJoinField = 'jfield';
        $maliciousWidth = '10"; system(\'x\'); //';
        $maliciousCallback = "callback'; system('x'); //";
        $maliciousQuery = "select 1'; DROP TABLE x; --";

        $response = $this->post('http://localhost/admin/module_generator/step3', [
            'id' => $module['id'],
            'column' => [$maliciousLabel],
            'name' => [$maliciousName],
            'join_table' => [$maliciousJoinTable],
            'join_field' => [$maliciousJoinField],
            'is_image' => [''],
            'is_download' => ['1'],
            'callbackphp' => [$maliciousCallback],
            'query' => [$maliciousQuery],
            'width' => [$maliciousWidth],
        ]);

        $response->assertStatus(302);
        $contents = file_get_contents(base_path('app/Http/Controllers/PhpunitTestStep3FixtureController.php'));

        $this->assertColumnValueSafelyEmbedded($contents, 'label', $maliciousLabel);
        $this->assertColumnValueSafelyEmbedded($contents, 'name', $maliciousName);
        $this->assertColumnValueSafelyEmbedded($contents, 'join', $maliciousJoinTable . ',' . $maliciousJoinField);
        $this->assertColumnValueSafelyEmbedded($contents, 'width', $maliciousWidth);
        $this->assertColumnValueSafelyEmbedded($contents, 'callback_php', $maliciousCallback);
        $this->assertColumnValueSafelyEmbedded($contents, 'query', $maliciousQuery);
    }

    /**
     * Regressione del bug indipendente: $id_download (mai definita) invece
     * di $is_download - il flag "download" non veniva mai scritto.
     */
    public function test_step3_scrive_il_flag_download_quando_richiesto(): void
    {
        $this->actingAsSuperadmin();
        $module = $this->seedFixtureModule('PhpunitTestStep3DownloadFixtureController');
        $this->writeFixtureModuleControllerFile('PhpunitTestStep3DownloadFixtureController');

        $response = $this->post('http://localhost/admin/module_generator/step3', [
            'id' => $module['id'],
            'column' => ['Allegato'],
            'name' => ['allegato'],
            'join_table' => [''],
            'join_field' => [''],
            'is_image' => [''],
            'is_download' => ['1'],
            'callbackphp' => [''],
            'query' => [''],
            'width' => [''],
        ]);

        $response->assertStatus(302);
        $contents = file_get_contents(base_path('app/Http/Controllers/PhpunitTestStep3DownloadFixtureController.php'));
        $this->assertStringContainsString('"download"=>true', $contents);
    }

    public function test_step3_nega_accesso_a_non_superadmin_anche_con_pieno_accesso_al_modulo(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: ['module_generator']);
        $module = $this->seedFixtureModule('PhpunitTestStep3DeniedFixtureController');
        $this->writeFixtureModuleControllerFile('PhpunitTestStep3DeniedFixtureController');
        $path = base_path('app/Http/Controllers/PhpunitTestStep3DeniedFixtureController.php');
        $originalContents = file_get_contents($path);

        $response = $this->post('http://localhost/admin/module_generator/step3', [
            'id' => $module['id'],
            'column' => ['Label'],
            'name' => ['campo'],
            'join_table' => [''],
            'join_field' => [''],
            'is_image' => [''],
            'is_download' => [''],
            'callbackphp' => [''],
            'query' => [''],
            'width' => [''],
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('message', trans('crudbooster.denied_access'));
        $this->assertSame($originalContents, file_get_contents($path));
    }

    // ---------------------------------------------------------------
    // Regressione RCE - postStep5() (configurazione)
    // ---------------------------------------------------------------

    public function test_step5_con_valori_pericolosi_li_salva_come_dato_letterale_e_forza_sempre_table(): void
    {
        $this->actingAsSuperadmin();
        $module = $this->seedFixtureModule('PhpunitTestStep5FixtureController', 'mg_phpunit_test_step5');
        $this->writeFixtureModuleControllerFile('PhpunitTestStep5FixtureController');

        $maliciousTitleField = 'name"; system(\'x\'); //';
        $maliciousOrderby = 'id,desc"; system(\'x\'); //';

        $response = $this->post('http://localhost/admin/module_generator/step5', [
            'id' => $module['id'],
            'title_field' => $maliciousTitleField,
            'limit' => '20',
            'orderby' => $maliciousOrderby,
            'global_privilege' => 'false',
            'button_action_style' => 'button_icon',
        ]);

        $response->assertStatus(302);
        $contents = file_get_contents(base_path('app/Http/Controllers/PhpunitTestStep5FixtureController.php'));

        $this->assertPropertySafelyEmbedded($contents, 'title_field', $maliciousTitleField);
        $this->assertPropertySafelyEmbedded($contents, 'orderby', $maliciousOrderby);
        $this->assertPropertySafelyEmbedded($contents, 'table', 'mg_phpunit_test_step5');
        $this->assertStringContainsString('$this->global_privilege = false;', $contents);
    }

    /**
     * Regressione della whitelist: prima si iterava su OGNI chiave POST -
     * una chiave non prevista dal form diventava comunque una proprieta'
     * PHP del controller generato.
     */
    public function test_step5_ignora_le_chiavi_post_non_previste_dal_form(): void
    {
        $this->actingAsSuperadmin();
        $module = $this->seedFixtureModule('PhpunitTestStep5WhitelistFixtureController', 'mg_phpunit_test_step5_wl');
        $this->writeFixtureModuleControllerFile('PhpunitTestStep5WhitelistFixtureController');

        $response = $this->post('http://localhost/admin/module_generator/step5', [
            'id' => $module['id'],
            'title_field' => 'name',
            'evil_property' => 'x"; system(\'y\'); //',
        ]);

        $response->assertStatus(302);
        $contents = file_get_contents(base_path('app/Http/Controllers/PhpunitTestStep5WhitelistFixtureController.php'));
        $this->assertStringNotContainsString('evil_property', $contents);
    }

    public function test_step5_nega_accesso_a_non_superadmin_anche_con_pieno_accesso_al_modulo(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: ['module_generator']);
        $module = $this->seedFixtureModule('PhpunitTestStep5DeniedFixtureController', 'mg_phpunit_test_step5_denied');
        $this->writeFixtureModuleControllerFile('PhpunitTestStep5DeniedFixtureController');
        $path = base_path('app/Http/Controllers/PhpunitTestStep5DeniedFixtureController.php');
        $originalContents = file_get_contents($path);

        $response = $this->post('http://localhost/admin/module_generator/step5', [
            'id' => $module['id'],
            'title_field' => 'name',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('message', trans('crudbooster.denied_access'));
        $this->assertSame($originalContents, file_get_contents($path));
    }

    // ---------------------------------------------------------------
    // Regressione - getDelete() (is_protected + null-safety)
    // ---------------------------------------------------------------

    public function test_getdelete_nega_cancellazione_di_un_modulo_protetto_anche_via_id_diretto(): void
    {
        $this->actingAsSuperadmin();
        $protectedId = DB::table('cms_moduls')->insertGetId([
            'name' => 'Phpunit Test Protected Module',
            'table_name' => 'phpunit_test_protected_table',
            'controller' => 'PhpunitTestProtectedFixtureController',
            'path' => 'phpunit_test_protected_' . uniqid(),
            'is_protected' => 1,
            'is_active' => 1,
            'created_at' => now(),
        ]);

        $response = $this->get("http://localhost/admin/module_generator/delete/{$protectedId}");

        $response->assertStatus(302);
        $response->assertSessionHas('message', trans('crudbooster.denied_access'));
        $this->assertDatabaseHas('cms_moduls', ['id' => $protectedId, 'deleted_at' => null]);
    }

    public function test_getdelete_su_id_inesistente_non_va_in_crash(): void
    {
        $this->actingAsSuperadmin();

        $response = $this->get('http://localhost/admin/module_generator/delete/999999');

        $response->assertStatus(302);
    }

    public function test_getdelete_cancella_un_modulo_non_protetto(): void
    {
        $this->actingAsSuperadmin();
        $id = DB::table('cms_moduls')->insertGetId([
            'name' => 'Phpunit Test Deletable Module',
            'table_name' => 'phpunit_test_deletable_table',
            'controller' => 'PhpunitTestDeletableFixtureController',
            'path' => 'phpunit_test_deletable_' . uniqid(),
            'is_protected' => 0,
            'is_active' => 1,
            'created_at' => now(),
        ]);

        $response = $this->get("http://localhost/admin/module_generator/delete/{$id}");

        $response->assertStatus(302);
        $this->assertDatabaseMissing('cms_moduls', ['id' => $id, 'deleted_at' => null]);
    }

    // ---------------------------------------------------------------
    // Regressione - getTableColumns()/getCheckSlug() (nessun controllo
    // di privilegio prima del fix)
    // ---------------------------------------------------------------

    public function test_gettablecolumns_nega_accesso_senza_privilegio_di_visualizzazione(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: false, visibleModulePaths: []);

        $response = $this->get('http://localhost/admin/module_generator/table-columns/cms_moduls');

        $response->assertStatus(403);
    }

    public function test_gettablecolumns_restituisce_le_colonne_con_privilegio(): void
    {
        $this->actingAsSuperadmin();

        $response = $this->get('http://localhost/admin/module_generator/table-columns/cms_moduls');

        $response->assertStatus(200);
        $this->assertContains('table_name', $response->json());
    }

    public function test_getcheckslug_nega_accesso_senza_privilegio_di_visualizzazione(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: false, visibleModulePaths: []);

        $response = $this->get('http://localhost/admin/module_generator/check-slug/whatever');

        $response->assertStatus(403);
    }

    public function test_getcheckslug_conta_gli_slug_esistenti_con_privilegio(): void
    {
        $this->actingAsSuperadmin();
        DB::table('cms_moduls')->insert([
            'name' => 'Phpunit Test Slug Module',
            'table_name' => 'x',
            'controller' => 'PhpunitTestSlugPlaceholderController',
            'path' => 'phpunit-test-existing-slug',
            'is_protected' => 0,
            'is_active' => 1,
            'created_at' => now(),
        ]);

        $response = $this->get('http://localhost/admin/module_generator/check-slug/phpunit-test-existing-slug');

        $response->assertStatus(200);
        $response->assertJson(['total' => 1]);
    }
}
