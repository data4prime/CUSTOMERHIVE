<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test di caratterizzazione per ApiController::execute_api() — il metodo
 * che gestisce davvero una chiamata a una API generata dal modulo API
 * Generator (letta da cms_apicustom PER PERMALINK a ogni richiesta:
 * parametri/risposte/azione/where NON sono mai "cotti" nel controller
 * generato, solo il permalink lo è).
 *
 * A differenza di ApiCustomCrudTest.php (che copre postSaveApiCustom() e
 * la generazione/scrittura del file), qui si registra in setUp() una rotta
 * ad-hoc che istanzia ApiController DIRETTAMENTE, bypassando del tutto
 * CRUDBooster::generateAPI()/postSaveApiCustom()/il routing dinamico via
 * cms_moduls — necessario perché quella catena, per una tabella di
 * sistema, produce oggi un file non caricabile (vedi "Rischi e note" in
 * docs/refactoring/065-api-generator-rce-e-test.md) e comunque non
 * aggiungerebbe copertura sulla logica qui testata, che è interamente
 * guidata dal DB a runtime.
 *
 * Questi test NON passano dal middleware CBAuthAPI/CRUDBooster::authAPI()
 * (token+timestamp+user agent) — la rotta ad-hoc non lo applica
 * deliberatamente, per isolare la logica di execute_api() da quel layer
 * (separato, non ancora testato — vedi backlog in docs/refactoring/README.md).
 * L'unica autenticazione qui in gioco è quella INTERNA di execute_api()
 * stesso: l'header 'X-user' con l'email di un cms_users attivo
 * (ApiController::login()).
 */
class ApiExecuteTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    protected function setUp(): void
    {
        parent::setUp();

        Route::any('phpunit-test-execute-api/{permalink}', function (string $permalink) {
            // Stesso pattern del controller generato da CRUDBooster::
            // generateAPI(): una sottoclasse che dichiara $controller (non
            // presente sulla classe base - impostarla dall'esterno senza
            // dichiararla creerebbe una proprietà dinamica, deprecata in
            // PHP 8.2+) con un controller "figlio" reale (necessario:
            // execute_api() lo usa per cbInit()/ModuleHelper::can_view()
            // sul ramo 'detail' anche per un attore superadmin).
            $controller = new class extends \App\Http\Controllers\System\ApiController {
                public $controller = null;
            };
            $controller->permalink = $permalink;
            $controller->controller = new \App\Http\Controllers\System\SettingsController();

            return $controller->execute_api();
        })->middleware('web');
    }

    private function seedSuperadminUser(): string
    {
        $tenantId = $this->seedTenant();
        $privilegeId = $this->seedPrivilege(isSuperadmin: true);
        $user = $this->seedUser(['tenant' => $tenantId, 'id_cms_privileges' => $privilegeId]);

        return $user['email'];
    }

    private function seedApiCustomRow(array $overrides = []): void
    {
        DB::table('cms_apicustom')->insert(array_merge([
            'nama' => 'Phpunit Test Execute',
            'tabel' => 'cms_settings',
            'aksi' => 'list',
            'permalink' => 'phpunit_test_execute_default',
            'method_type' => 'get',
            'parameters' => serialize([]),
            'responses' => serialize([]),
            'sql_where' => null,
            'created_at' => now(),
        ], $overrides));
    }

    public function test_execute_api_list_rispetta_i_parametri_e_le_risposte_configurate(): void
    {
        $email = $this->seedSuperadminUser();
        DB::table('cms_settings')->insert([
            ['name' => 's1', 'label' => 'S1', 'content' => 'valore-uno', 'group_setting' => 'GruppoA', 'created_at' => now()],
            ['name' => 's2', 'label' => 'S2', 'content' => 'valore-due', 'group_setting' => 'GruppoB', 'created_at' => now()],
        ]);
        $this->seedApiCustomRow([
            'permalink' => 'phpunit_test_execute_list',
            'aksi' => 'list',
            // "group_setting" filtra i risultati (parametro), solo
            // "name"/"content" vengono esposti nella risposta ("id" e'
            // configurato used=0: deve sparire dall'output).
            'parameters' => serialize([
                ['name' => 'group_setting', 'type' => 'text', 'config' => '', 'required' => '0', 'used' => '1'],
            ]),
            'responses' => serialize([
                ['name' => 'name', 'type' => 'text', 'subquery' => '', 'used' => '1'],
                ['name' => 'content', 'type' => 'text', 'subquery' => '', 'used' => '1'],
                ['name' => 'id', 'type' => 'text', 'subquery' => '', 'used' => '0'],
            ]),
        ]);

        $response = $this->withHeaders(['X-user' => $email])
            ->get('http://localhost/phpunit-test-execute-api/phpunit_test_execute_list?group_setting=GruppoA');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertSame(1, $data['api_status']);
        $this->assertCount(1, $data['data']);
        $this->assertSame('valore-uno', $data['data'][0]['content']);
        // Solo i campi "used" nelle risposte configurate - "id" non c'e'.
        $this->assertSame(['name', 'content'], array_keys($data['data'][0]));
    }

    public function test_execute_api_detail_rispetta_le_risposte_configurate(): void
    {
        $email = $this->seedSuperadminUser();
        $settingId = DB::table('cms_settings')->insertGetId([
            'name' => 'dettaglio_test', 'label' => 'Dettaglio', 'content' => 'valore-dettaglio',
            'group_setting' => 'GruppoDettaglio', 'created_at' => now(),
        ]);
        $this->seedApiCustomRow([
            'permalink' => 'phpunit_test_execute_detail',
            'aksi' => 'detail',
            'parameters' => serialize([
                ['name' => 'id', 'type' => 'text', 'config' => '', 'required' => '1', 'used' => '1'],
            ]),
            'responses' => serialize([
                ['name' => 'content', 'type' => 'text', 'subquery' => '', 'used' => '1'],
            ]),
        ]);

        $response = $this->withHeaders(['X-user' => $email])
            ->get("http://localhost/phpunit-test-execute-api/phpunit_test_execute_detail?id={$settingId}");

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertSame(1, $data['api_status']);
        // A differenza di 'list' (righe annidate sotto "data"), 'detail'
        // fa il merge dei campi della riga direttamente nel livello
        // superiore della response (array_merge($result, (array) $rows)).
        $this->assertSame('valore-dettaglio', $data['content']);
        // Solo "content" e' configurato come risposta - le altre colonne
        // di cms_settings (name/label/group_setting/id) non compaiono.
        $this->assertArrayNotHasKey('name', $data);
        $this->assertArrayNotHasKey('label', $data);
        $this->assertArrayNotHasKey('group_setting', $data);
        $this->assertArrayNotHasKey('id', $data);
    }

    public function test_execute_api_senza_header_x_user_viene_rifiutato(): void
    {
        $this->seedApiCustomRow(['permalink' => 'phpunit_test_execute_nouser']);

        $response = $this->get('http://localhost/phpunit-test-execute-api/phpunit_test_execute_nouser');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertSame(0, $data['api_status']);
        $this->assertStringContainsString('X-User', $data['api_message']);
    }

    public function test_execute_api_rifiuta_il_metodo_http_non_configurato(): void
    {
        $email = $this->seedSuperadminUser();
        $this->seedApiCustomRow(['permalink' => 'phpunit_test_execute_method', 'method_type' => 'post']);

        // La rotta ad-hoc accetta qualunque verbo (Route::any) apposta per
        // poter esercitare questo scenario: method_type configurato 'post',
        // richiesta mandata GET.
        $response = $this->withHeaders(['X-user' => $email])
            ->get('http://localhost/phpunit-test-execute-api/phpunit_test_execute_method');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertSame(0, $data['api_status']);
        $this->assertSame('The requested method is not allowed!', $data['api_message']);
    }

    /**
     * Regressione di un bug null-safety trovato scrivendo questo test:
     * $row_api veniva letto (->aksi/->tabel, poi ->method_type) PRIMA del
     * controllo "riga esistente" più avanti nello stesso metodo - un
     * permalink senza corrispondenza in cms_apicustom crashava con 500
     * invece del messaggio d'errore già previsto per questo caso. Corretto
     * spostando il controllo subito dopo la lettura di $row_api.
     */
    public function test_execute_api_su_permalink_inesistente_non_va_in_crash(): void
    {
        $email = $this->seedSuperadminUser();

        $response = $this->withHeaders(['X-user' => $email])
            ->get('http://localhost/phpunit-test-execute-api/phpunit_test_execute_permalink_inesistente');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertSame(0, $data['api_status']);
        $this->assertSame(
            'Sorry this API endpoint is no longer available or has been changed. Please make sure endpoint is correct.',
            $data['api_message']
        );
    }
}
