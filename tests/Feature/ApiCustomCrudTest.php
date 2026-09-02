<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test di caratterizzazione per il modulo API Generator (ApiCustomController
 * + CRUDBooster::generateAPI()). A differenza degli altri moduli, qui il
 * focus principale non e' il CRUD in se' ma 3 bug reali di sicurezza
 * corretti prima di scrivere questi test (dettagli in
 * docs/refactoring/065-api-generator-rce-e-test.md):
 *
 * - $table_name/$permalink/$method_type finivano incollati grezzi dentro un
 *   literal PHP a DOPPI apici nel sorgente di un controller che viene poi
 *   scritto su disco e diventa un file autoloaded (app/Http/Controllers) -
 *   una virgoletta nel valore rompeva il literal e permetteva di iniettare
 *   PHP arbitrario (RCE autenticata, raggiungibile - vedi punto A, non
 *   corretto in questo intervento - da QUALSIASI utente loggato, non solo
 *   superadmin). Corretto con var_export() (literal a singoli apici, mai
 *   interpolato) sia nella generazione (CRUDBooster::generateAPI()) sia
 *   nella modifica (preg_replace_callback su un controller gia' esistente).
 * - La stringa di REPLACEMENT di preg_replace() (usata nel ramo modifica)
 *   ha una sua sintassi speciale ($1, \1): un permalink con '$' o '\'
 *   produceva una sostituzione diversa da quella attesa. Corretto passando
 *   a preg_replace_callback().
 * - $controllerName (nome CLASSE e nome FILE del controller generato, non
 *   dentro una stringa PHP: var_export() li' non aiuta) non sanificato -
 *   un permalink con '/' o '..' permetteva path traversal nel file scritto.
 *   Corretto sanificando a [A-Za-z0-9_].
 *
 * Punto A (nessun controllo di privilegio su quasi tutti i metodi custom di
 * questo controller - CBBackend verifica solo "sei loggato") e'
 * deliberatamente NON corretto in questo intervento (deciso con l'utente) -
 * un solo test lo caratterizza esplicitamente in fondo a questo file.
 *
 * A differenza di Settings (che scrive in public/storage/), qui i file
 * generati/modificati finiscono DENTRO L'ALBERO SORGENTE VERO E PROPRIO
 * (app/Http/Controllers/, tracciato da git): ogni test traccia i file che
 * crea in $fixtureFiles, ripuliti in tearDown() insieme a uno sweep di
 * sicurezza su qualunque *PhpunitTest*.php rimasto (tutti i permalink/nomi
 * usati in questo file contengono "PhpunitTest" per essere intercettati).
 */
class ApiCustomCrudTest extends TestCase
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
     * generateAPI() cerca il controller del modulo via
     * cms_moduls.table_name (bug null-safety pre-esistente, non toccato in
     * questo intervento, se la riga manca): la si semina qui per non
     * inciampare in quel gap mentre si testa altro. $controller non deve
     * essere una classe reale per gli scopi di questi test (php -l non
     * serve a risolvere le classi referenziate, solo la sintassi).
     */
    private function seedApiModule(string $tableName, string $controller = 'PlaceholderController.php'): void
    {
        DB::table('cms_moduls')->insert([
            'name' => 'Modulo Phpunit Test',
            'table_name' => $tableName,
            'controller' => $controller,
            'path' => 'phpunit_test_module_' . uniqid(),
            'is_protected' => 0,
            'is_active' => 1,
            'created_at' => now(),
        ]);
    }

    /**
     * Scrive un controller "esistente" minimo sul disco reale, nella stessa
     * forma che postSaveApiCustom() si aspetta di poter patchare (le 3
     * righe $this->table/permalink/method_type cercate dai preg_replace_callback).
     */
    private function writeFixtureControllerFile(string $className, string $table, string $permalink, string $methodType): string
    {
        $path = base_path('app/Http/Controllers/' . $className . '.php');
        $contents = '<?php namespace App\Http\Controllers;' . "\n"
            . 'class ' . $className . ' {' . "\n"
            . '    function __construct() {' . "\n"
            . '        $this->table       = "' . $table . '";' . "\n"
            . '        $this->permalink   = "' . $permalink . '";' . "\n"
            . '        $this->method_type = "' . $methodType . '";' . "\n"
            . '    }' . "\n"
            . '}' . "\n";
        File::put($path, $contents);
        $this->fixtureFiles[] = $path;

        return $path;
    }

    private function baseApiPayload(array $overrides = []): array
    {
        return array_merge([
            'nama' => 'Phpunit Test API',
            'tabel' => 'x',
            'aksi' => 'read',
            'permalink' => 'phpunit_test_default',
            'method_type' => 'get',
            'params_name' => [],
            'params_type' => [],
            'params_config' => [],
            'params_required' => [],
            'params_used' => [],
            'sql_where' => '',
            'responses_name' => [],
            'responses_type' => [],
            'responses_subquery' => [],
            'responses_used' => [],
            'keterangan' => '',
        ], $overrides);
    }

    /**
     * Verifica che $value sia stato incollato nel file generato SOLO
     * tramite var_export() (literal a singoli apici correttamente
     * escapato), mai come literal a doppi apici grezzo (il pattern
     * vulnerabile prima del fix).
     */
    private function assertGeneratedFileSafelyEmbeds(string $fileContents, string $property, string $value): void
    {
        $expected = var_export($value, true);
        $pattern = '/\$this->' . preg_quote($property, '/') . '\s*=\s*' . preg_quote($expected, '/') . '\s*;/';
        $this->assertMatchesRegularExpression($pattern, $fileContents, "\$this->$property non e' stato incollato via var_export().");
        $this->assertStringNotContainsString('"' . $value . '"', $fileContents, "Trovato un literal a doppi apici grezzo per $property - possibile regressione RCE.");
    }

    // ---------------------------------------------------------------
    // Regressione RCE - creazione (CRUDBooster::generateAPI())
    // ---------------------------------------------------------------

    public function test_creazione_api_con_valori_pericolosi_in_tabella_e_method_type_vengono_salvati_come_dato_letterale(): void
    {
        $this->actingAsSuperadmin();
        $maliciousTable = 'mg_phpunit_test_tabella"; system(\'touch /tmp/pwned-tabella\'); //';
        // cms_apicustom.method_type e' VARCHAR(25) (schema legacy): payload
        // corto ma comunque capace di rompere un literal a doppi apici.
        $maliciousMethodType = 'x"; system(1); //';
        $this->seedApiModule($maliciousTable);

        $response = $this->post('http://localhost/admin/api_generator/save-api-custom', $this->baseApiPayload([
            'nama' => 'Phpunit Test Injection Tabella',
            'tabel' => $maliciousTable,
            'permalink' => 'phpunit_test_injection_tabella',
            'method_type' => $maliciousMethodType,
        ]));

        $response->assertStatus(302);
        $row = DB::table('cms_apicustom')->where('permalink', 'phpunit_test_injection_tabella')->first();
        $this->assertNotNull($row);
        $generatedPath = base_path('app/Http/Controllers/' . $row->controller);
        $this->fixtureFiles[] = $generatedPath;
        $this->assertFileExists($generatedPath);
        $contents = file_get_contents($generatedPath);

        $this->assertGeneratedFileSafelyEmbeds($contents, 'table', $maliciousTable);
        $this->assertGeneratedFileSafelyEmbeds($contents, 'method_type', $maliciousMethodType);
    }

    public function test_creazione_api_con_permalink_pericoloso_sanifica_il_nome_controller_e_salva_il_permalink_come_dato(): void
    {
        $this->actingAsSuperadmin();
        $table = 'mg_phpunit_test_' . uniqid();
        $this->seedApiModule($table);
        $maliciousPermalink = 'phpunit_test_permalink"; system(\'touch /tmp/pwned-permalink\'); //';

        $response = $this->post('http://localhost/admin/api_generator/save-api-custom', $this->baseApiPayload([
            'nama' => 'Phpunit Test Injection Permalink',
            'tabel' => $table,
            'permalink' => $maliciousPermalink,
            'method_type' => 'get',
        ]));

        $response->assertStatus(302);
        $row = DB::table('cms_apicustom')->where('tabel', $table)->first();
        $this->assertNotNull($row);
        // Nome classe/file derivato dal permalink ma sanificato: solo
        // lettere/cifre/underscore sopravvivono.
        $this->assertMatchesRegularExpression('/^Api[A-Za-z0-9_]+Controller\.php$/', $row->controller);

        $generatedPath = base_path('app/Http/Controllers/' . $row->controller);
        $this->fixtureFiles[] = $generatedPath;
        $contents = file_get_contents($generatedPath);
        // Il permalink GREZZO (non sanificato) finisce comunque nel file
        // generato (proprieta' $this->permalink) - ma solo come dato
        // letterale via var_export(), mai capace di rompere il literal.
        $this->assertGeneratedFileSafelyEmbeds($contents, 'permalink', $maliciousPermalink);
    }

    public function test_creazione_api_con_permalink_contenente_slash_non_scrive_file_fuori_da_controllers(): void
    {
        $this->actingAsSuperadmin();
        $table = 'mg_phpunit_test_' . uniqid();
        $this->seedApiModule($table);

        $response = $this->post('http://localhost/admin/api_generator/save-api-custom', $this->baseApiPayload([
            'nama' => 'Phpunit Test Traversal',
            'tabel' => $table,
            'permalink' => '../../evil_phpunit_test_traversal',
            'method_type' => 'get',
        ]));

        $response->assertStatus(302);
        $row = DB::table('cms_apicustom')->where('tabel', $table)->first();
        $this->assertNotNull($row);
        $this->assertMatchesRegularExpression('/^Api[A-Za-z0-9_]+Controller\.php$/', $row->controller);

        $generatedPath = base_path('app/Http/Controllers/' . $row->controller);
        $this->fixtureFiles[] = $generatedPath;
        $this->assertFileExists($generatedPath);
        $this->assertSame(base_path('app/Http/Controllers'), dirname($generatedPath));
    }

    // ---------------------------------------------------------------
    // Regressione RCE - modifica (preg_replace_callback su un controller
    // gia' esistente su disco)
    // ---------------------------------------------------------------

    public function test_modifica_api_con_valori_pericolosi_non_inietta_codice_nel_controller_esistente(): void
    {
        $this->actingAsSuperadmin();
        $filename = 'ApiPhpunitTestEditFixtureController.php';
        $this->writeFixtureControllerFile('ApiPhpunitTestEditFixtureController', 'tabella_originale', 'permalink_originale', 'get');
        $apiId = DB::table('cms_apicustom')->insertGetId([
            'nama' => 'Phpunit Test Edit',
            'tabel' => 'tabella_originale',
            'permalink' => 'permalink_originale',
            'method_type' => 'get',
            'controller' => $filename,
            'created_at' => now(),
        ]);
        $maliciousTable = 'tabella"; system(\'touch /tmp/pwned-edit\'); //';

        $response = $this->post('http://localhost/admin/api_generator/save-api-custom', $this->baseApiPayload([
            'id' => $apiId,
            'nama' => 'Phpunit Test Edit',
            'tabel' => $maliciousTable,
            'permalink' => 'permalink_originale',
            'method_type' => 'get',
        ]));

        $response->assertStatus(302);
        $contents = file_get_contents(base_path('app/Http/Controllers/' . $filename));
        $this->assertGeneratedFileSafelyEmbeds($contents, 'table', $maliciousTable);
    }

    /**
     * Regressione specifica del secondo bug (sintassi speciale della
     * stringa di replacement di preg_replace(): $1/\1 sostituiti con i
     * gruppi catturati) - con la sola preg_replace() (senza passare a
     * preg_replace_callback()) questo test avrebbe fallito: il permalink
     * sarebbe stato corrotto dalla sostituzione invece di essere salvato
     * cosi' com'e'.
     */
    public function test_modifica_api_con_permalink_contenente_dollaro_e_backslash_non_viene_alterato_dalla_sostituzione(): void
    {
        $this->actingAsSuperadmin();
        $filename = 'ApiPhpunitTestDollarFixtureController.php';
        $this->writeFixtureControllerFile('ApiPhpunitTestDollarFixtureController', 'tabella_originale', 'permalink_originale', 'get');
        $apiId = DB::table('cms_apicustom')->insertGetId([
            'nama' => 'Phpunit Test Dollar',
            'tabel' => 'tabella_originale',
            'permalink' => 'permalink_originale',
            'method_type' => 'get',
            'controller' => $filename,
            'created_at' => now(),
        ]);
        $dollarPermalink = 'valore_con_dollaro_$1_e_backslash_\\1';

        $response = $this->post('http://localhost/admin/api_generator/save-api-custom', $this->baseApiPayload([
            'id' => $apiId,
            'nama' => 'Phpunit Test Dollar',
            'tabel' => 'tabella_originale',
            'permalink' => $dollarPermalink,
            'method_type' => 'get',
        ]));

        $response->assertStatus(302);
        $contents = file_get_contents(base_path('app/Http/Controllers/' . $filename));
        $this->assertGeneratedFileSafelyEmbeds($contents, 'permalink', $dollarPermalink);
    }

    // ---------------------------------------------------------------
    // Regressione null-safety
    // ---------------------------------------------------------------

    public function test_getdeleteapi_su_id_inesistente_non_va_in_crash(): void
    {
        $this->actingAsSuperadmin();

        $response = $this->get('http://localhost/admin/api_generator/delete-api/999999');

        $response->assertStatus(200);
        $response->assertJson(['status' => 0]);
    }

    public function test_creazione_api_con_nome_file_collidente_ma_senza_riga_corrispondente_non_va_in_crash(): void
    {
        $this->actingAsSuperadmin();
        $table = 'mg_phpunit_test_' . uniqid();
        $this->seedApiModule($table);
        // File orfano: esiste su disco ma nessuna riga cms_apicustom lo referenzia -
        // stesso nome che produrrebbe il permalink sotto.
        $this->writeFixtureControllerFile('ApiPhpunitTestOrphanController', 'x', 'x', 'get');

        $response = $this->post('http://localhost/admin/api_generator/save-api-custom', $this->baseApiPayload([
            'nama' => 'Phpunit Test Orphan',
            'tabel' => $table,
            'permalink' => 'phpunit_test_orphan',
            'method_type' => 'get',
        ]));

        $response->assertStatus(302);
        $response->assertSessionHas('message', trans('crudbooster.api_controller_not_found'));
        $this->assertDatabaseMissing('cms_apicustom', ['permalink' => 'phpunit_test_orphan']);
    }

    // ---------------------------------------------------------------
    // Regressione dello swap redirectBack() -> redirect(referer)
    // (non tocca redirectBack() stessa, ancora exit()-based e usata anche
    // da 2 Blade view Qlik - vedi docs/refactoring/063)
    // ---------------------------------------------------------------

    public function test_modifica_api_con_permalink_duplicato_viene_rifiutata(): void
    {
        $this->actingAsSuperadmin();
        DB::table('cms_apicustom')->insert([
            'nama' => 'Phpunit Test Duplicate Existing', 'tabel' => 'x', 'permalink' => 'phpunit_test_duplicate',
            'method_type' => 'get', 'controller' => 'PlaceholderA.php', 'created_at' => now(),
        ]);
        $apiId = DB::table('cms_apicustom')->insertGetId([
            'nama' => 'Phpunit Test Duplicate Editing', 'tabel' => 'x', 'permalink' => 'phpunit_test_duplicate_altro',
            'method_type' => 'get', 'controller' => 'PlaceholderB.php', 'created_at' => now(),
        ]);

        $response = $this->post('http://localhost/admin/api_generator/save-api-custom', $this->baseApiPayload([
            'id' => $apiId,
            'nama' => 'Phpunit Test Duplicate Editing',
            'tabel' => 'x',
            'permalink' => 'phpunit_test_duplicate',
            'method_type' => 'get',
        ]));

        $response->assertStatus(302);
        $response->assertSessionHas('message', trans('crudbooster.api_permalink_already_exists'));
        $this->assertDatabaseHas('cms_apicustom', ['id' => $apiId, 'permalink' => 'phpunit_test_duplicate_altro']);
    }

    public function test_modifica_api_con_id_senza_controller_associato_viene_rifiutata(): void
    {
        $this->actingAsSuperadmin();
        $apiId = DB::table('cms_apicustom')->insertGetId([
            'nama' => 'Phpunit Test No Controller', 'tabel' => 'x', 'permalink' => 'phpunit_test_no_controller',
            'method_type' => 'get', 'controller' => null, 'created_at' => now(),
        ]);

        $response = $this->post('http://localhost/admin/api_generator/save-api-custom', $this->baseApiPayload([
            'id' => $apiId,
            'nama' => 'Phpunit Test No Controller',
            'tabel' => 'x',
            'permalink' => 'phpunit_test_no_controller',
            'method_type' => 'get',
        ]));

        $response->assertStatus(302);
        $response->assertSessionHas('message', trans('crudbooster.api_controller_not_found'));
    }

    // ---------------------------------------------------------------
    // Caratterizzazione degli endpoint piu' semplici (cms_apikey)
    // ---------------------------------------------------------------

    public function test_getgeneratescreetkey_crea_una_riga_cms_apikey_attiva(): void
    {
        $this->actingAsSuperadmin();

        $response = $this->get('http://localhost/admin/api_generator/generate-screet-key');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertNotEmpty($data['key']);
        $this->assertDatabaseHas('cms_apikey', ['id' => $data['id'], 'screetkey' => $data['key'], 'status' => 'active', 'hit' => 0]);
    }

    public function test_getstatusapikey_aggiorna_lo_stato_di_una_api_key(): void
    {
        $this->actingAsSuperadmin();
        $keyId = DB::table('cms_apikey')->insertGetId(['screetkey' => 'x', 'status' => 'active', 'hit' => 0, 'created_at' => now()]);

        $response = $this->get("http://localhost/admin/api_generator/status-apikey?id={$keyId}&status=0");

        $response->assertStatus(302);
        $this->assertDatabaseHas('cms_apikey', ['id' => $keyId, 'status' => 'non active']);
    }

    /**
     * Regressione del fix su CRUDBooster::valid() (stesso condiviso con
     * Settings, vedi docs/refactoring/064): senza id/status la validazione
     * fallisce - prima un exit() qui avrebbe reso questo scenario non
     * testabile via HTTP simulato.
     */
    public function test_getstatusapikey_senza_id_o_status_viene_rifiutato(): void
    {
        $this->actingAsSuperadmin();

        $response = $this->get('http://localhost/admin/api_generator/status-apikey');

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'warning');
    }

    public function test_getdeleteapikey_cancella_la_riga(): void
    {
        $this->actingAsSuperadmin();
        $keyId = DB::table('cms_apikey')->insertGetId(['screetkey' => 'x', 'status' => 'active', 'hit' => 0, 'created_at' => now()]);

        $response = $this->get("http://localhost/admin/api_generator/delete-api-key?id={$keyId}");

        $response->assertStatus(200);
        $response->assertJson(['status' => 1]);
        $this->assertDatabaseMissing('cms_apikey', ['id' => $keyId]);
    }

    public function test_getdeleteapikey_su_id_inesistente_ritorna_status_zero(): void
    {
        $this->actingAsSuperadmin();

        $response = $this->get('http://localhost/admin/api_generator/delete-api-key?id=999999');

        $response->assertStatus(200);
        $response->assertJson(['status' => 0]);
    }

    public function test_getscreetkey_mostra_le_api_key_esistenti(): void
    {
        $this->actingAsSuperadmin();
        DB::table('cms_apikey')->insert(['screetkey' => 'chiave-phpunit-test-da-trovare', 'status' => 'active', 'hit' => 0, 'created_at' => now()]);

        $response = $this->get('http://localhost/admin/api_generator/screet-key');

        $response->assertStatus(200);
        $response->assertSee('chiave-phpunit-test-da-trovare');
    }

    // ---------------------------------------------------------------
    // Controlli di accesso GIA' presenti (getIndex/getGenerator/getEditApi)
    // ---------------------------------------------------------------

    public function test_getindex_nega_accesso_a_non_superadmin(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: ['api_generator']);

        $response = $this->get('http://localhost/admin/api_generator');

        $response->assertStatus(302);
        $response->assertSessionHas('message', trans('crudbooster.denied_access'));
    }

    public function test_getgenerator_nega_accesso_a_non_superadmin(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: ['api_generator']);

        $response = $this->get('http://localhost/admin/api_generator/generator');

        $response->assertStatus(302);
        $response->assertSessionHas('message', trans('crudbooster.denied_access'));
    }

    public function test_geteditapi_nega_accesso_a_non_superadmin(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: ['api_generator']);
        $apiId = DB::table('cms_apicustom')->insertGetId([
            'nama' => 'Phpunit Test Edit Access', 'tabel' => 'x', 'permalink' => 'phpunit_test_edit_access',
            'method_type' => 'get', 'controller' => 'x.php', 'created_at' => now(),
        ]);

        $response = $this->get("http://localhost/admin/api_generator/edit-api/{$apiId}");

        $response->assertStatus(302);
        $response->assertSessionHas('message', trans('crudbooster.denied_access'));
    }

    // ---------------------------------------------------------------
    // Caratterizzazione del punto A (deliberatamente non corretto)
    // ---------------------------------------------------------------

    /**
     * CARATTERIZZAZIONE di un gap noto, non un fix (punto A, deciso con
     * l'utente di rimandare - vedi docs/refactoring/065): a differenza di
     * getIndex()/getGenerator()/getEditApi(), postSaveApiCustom() non ha
     * ALCUN controllo di privilegio proprio - CBBackend verifica solo "sei
     * loggato", non il modulo/privilegio. Un utente Standard senza alcun
     * permesso assegnato puo' comunque creare API generate. Questo test
     * fissa il comportamento ATTUALE, cosi' da accorgersi (test che inizia
     * a fallire) il giorno in cui verra' introdotto un controllo qui.
     */
    public function test_caratterizzazione_postsaveapicustom_e_raggiungibile_da_utente_senza_alcun_permesso(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: false, visibleModulePaths: []);
        $table = 'mg_phpunit_test_' . uniqid();
        $this->seedApiModule($table);

        $response = $this->post('http://localhost/admin/api_generator/save-api-custom', $this->baseApiPayload([
            'nama' => 'Phpunit Test No Privilege',
            'tabel' => $table,
            'permalink' => 'phpunit_test_no_privilege',
            'method_type' => 'get',
        ]));

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'success');
        $row = DB::table('cms_apicustom')->where('permalink', 'phpunit_test_no_privilege')->first();
        $this->assertNotNull($row, "Caratterizzazione: un utente senza alcun permesso puo' comunque creare una API generata (punto A, non corretto).");
        $this->fixtureFiles[] = base_path('app/Http/Controllers/' . $row->controller);
    }
}
