<?php

namespace Tests\Unit\Services;

use App\Exceptions\AuthException;
use App\Services\ConnectorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Test di caratterizzazione per ConnectorService. Fissano il comportamento
 * ATTUALE della classe (dopo il fix del bug access-token/401 e il
 * refactoring di validateLicense() - vedi docs/refactoring/005-*.md),
 * non solo per prevenire regressioni ma anche per documentare a colpo
 * d'occhio delle scelte non ovvie (es. il fallback sul dominio).
 *
 * ConnectorService legge/scrive storage/app/license.json con path/Storage
 * disk misti (non tramite Storage::fake()), quindi qui si manipola
 * direttamente il file reale, salvando e ripristinando il contenuto
 * originale in setUp()/tearDown() per non perdere una licenza gia'
 * attivata in locale.
 */
class ConnectorServiceTest extends TestCase
{
    use RefreshDatabase;

    private const AUTH_URL = 'http://license.thecustomerhive.com/api/api-license/license-server/auth/login';
    private const LICENSE_URL = 'http://license.thecustomerhive.com/api/api-license/license-server/license';

    private string $licenseFilePath;
    private ?string $originalLicenseFileContents = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->licenseFilePath = storage_path('app/license.json');

        if (file_exists($this->licenseFilePath)) {
            $this->originalLicenseFileContents = file_get_contents($this->licenseFilePath);
            unlink($this->licenseFilePath);
        }
    }

    protected function tearDown(): void
    {
        if ($this->originalLicenseFileContents !== null) {
            file_put_contents($this->licenseFilePath, $this->originalLicenseFileContents);
        } elseif (file_exists($this->licenseFilePath)) {
            unlink($this->licenseFilePath);
        }

        parent::tearDown();
    }

    private function writeLicenseFile(array $license): void
    {
        file_put_contents($this->licenseFilePath, json_encode($license));
    }

    private function fakeSuccessfulAuth(string $token = 'fresh-access-token'): void
    {
        Http::fake([
            self::AUTH_URL => Http::response([
                'success' => true,
                'data' => ['access_token' => $token],
            ], 200),
        ]);
    }

    // --- getAccessToken() (via costruttore) -------------------------------

    public function test_usa_il_token_in_cache_senza_chiamare_auth_login(): void
    {
        Cache::put('license-connector:access-token-my-key', 'cached-token', now()->addMinutes(60));

        Http::fake([
            self::LICENSE_URL => Http::response(['data' => ['id' => 1, 'status' => 'active']], 200),
        ]);

        $service = new ConnectorService('my-key');
        $service->writeLicense(['license_key' => 'my-key']);

        Http::assertNotSent(fn ($request) => $request->url() === self::AUTH_URL);
        Http::assertSent(fn ($request) => $request->url() === self::LICENSE_URL
            && $request->hasHeader('Authorization', 'Bearer cached-token'));
    }

    public function test_senza_token_in_cache_chiama_auth_login_e_lo_mette_in_cache(): void
    {
        $this->fakeSuccessfulAuth('fresh-access-token');

        new ConnectorService('my-key');

        Http::assertSent(fn ($request) => $request->url() === self::AUTH_URL
            && $request['license_key'] === 'my-key'
            && $request['ls_domain'] === env('APP_DOMAIN'));

        $this->assertEquals('fresh-access-token', Cache::get('license-connector:access-token-my-key'));
    }

    public function test_auth_login_con_risposta_di_fallimento_lancia_AuthException(): void
    {
        Http::fake([
            self::AUTH_URL => Http::response([
                'success' => false,
                'message' => 'chiave non valida',
            ], 401),
        ]);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('chiave non valida');

        new ConnectorService('chiave-non-valida');
    }

    public function test_login_irraggiungibile_non_va_in_crash_ma_disattiva_il_token(): void
    {
        Http::fake([
            self::AUTH_URL => function () {
                throw new ConnectionException('Connection refused');
            },
        ]);

        // Non deve lanciare - prima del refactoring qui si aveva un Error
        // fatale ("Call to a member function json() on null").
        $service = new ConnectorService('my-key');

        // Senza token, writeLicense() non deve nemmeno provare la chiamata.
        $result = $service->writeLicense(['license_key' => 'my-key']);

        $this->assertFalse($result);
        Http::assertNotSent(fn ($request) => $request->url() === self::LICENSE_URL);
    }

    // --- writeLicense() -----------------------------------------------------

    public function test_writeLicense_scrive_il_file_locale_quando_il_server_risponde_con_un_id(): void
    {
        Http::fake([
            self::AUTH_URL => Http::response(['success' => true, 'data' => ['access_token' => 'tok']], 200),
            self::LICENSE_URL => Http::response([
                'data' => ['id' => 42, 'status' => 'active', 'license_key' => 'my-key'],
            ], 200),
        ]);

        $service = new ConnectorService('my-key');
        $result = $service->writeLicense(['license_key' => 'my-key']);

        $this->assertIsArray($result);
        $this->assertEquals(42, $result['id']);
        $this->assertFileExists($this->licenseFilePath);
        $this->assertEquals(42, json_decode(file_get_contents($this->licenseFilePath), true)['id']);
    }

    public function test_writeLicense_ritorna_false_se_la_risposta_non_ha_un_id(): void
    {
        Http::fake([
            self::AUTH_URL => Http::response(['success' => true, 'data' => ['access_token' => 'tok']], 200),
            self::LICENSE_URL => Http::response(['data' => null], 200),
        ]);

        $service = new ConnectorService('my-key');
        $result = $service->writeLicense(['license_key' => 'my-key']);

        $this->assertFalse($result);
        $this->assertFileDoesNotExist($this->licenseFilePath);
    }

    public function test_writeLicense_ritorna_false_se_la_richiesta_fallisce(): void
    {
        Http::fake([
            self::AUTH_URL => Http::response(['success' => true, 'data' => ['access_token' => 'tok']], 200),
            self::LICENSE_URL => function () {
                throw new ConnectionException('timeout');
            },
        ]);

        $service = new ConnectorService('my-key');
        $result = $service->writeLicense(['license_key' => 'my-key']);

        $this->assertFalse($result);
    }

    // --- getLicense() / getLicenseFromFile() (protetto, testato via getLicense) --

    public function test_getLicense_legge_il_file_locale_se_presente(): void
    {
        $this->writeLicenseFile(['id' => 1, 'status' => 'active', 'license_key' => 'my-key']);
        $this->fakeSuccessfulAuth();

        $service = new ConnectorService('my-key');

        $this->assertEquals(['id' => 1, 'status' => 'active', 'license_key' => 'my-key'], $service->getLicense());
    }

    public function test_getLicense_ritorna_false_se_il_file_contiene_json_non_valido(): void
    {
        file_put_contents($this->licenseFilePath, 'non e\' json valido');
        $this->fakeSuccessfulAuth();

        $service = new ConnectorService('my-key');

        $this->assertFalse($service->getLicense());
    }

    public function test_getLicense_se_il_file_manca_prova_a_riscriverlo_dal_server(): void
    {
        Http::fake([
            self::AUTH_URL => Http::response(['success' => true, 'data' => ['access_token' => 'tok']], 200),
            self::LICENSE_URL => Http::response([
                'data' => ['id' => 7, 'status' => 'active', 'license_key' => 'my-key'],
            ], 200),
        ]);

        $service = new ConnectorService('my-key');
        $license = $service->getLicense();

        $this->assertIsArray($license);
        $this->assertEquals(7, $license['id']);
    }

    public function test_getLicense_ritorna_false_se_il_file_manca_e_il_server_non_lo_ricrea(): void
    {
        Http::fake([
            self::AUTH_URL => Http::response(['success' => true, 'data' => ['access_token' => 'tok']], 200),
            self::LICENSE_URL => Http::response(['data' => null], 200),
        ]);

        $service = new ConnectorService('my-key');

        $this->assertFalse($service->getLicense());
    }

    // --- validateLicense() ---------------------------------------------------

    private function activeLicense(array $overrides = []): array
    {
        return array_merge([
            'status' => 'active',
            'path' => env('APP_PATH'),
            'domain' => env('APP_DOMAIN'),
            'tenants_number' => 5,
            'clients_number' => 5,
        ], $overrides);
    }

    public function test_validateLicense_true_se_licenza_attiva_e_path_dominio_combaciano(): void
    {
        $this->writeLicenseFile($this->activeLicense());
        $this->fakeSuccessfulAuth();

        $service = new ConnectorService('my-key');

        $this->assertTrue($service->validateLicense());
    }

    public function test_validateLicense_false_se_status_non_active(): void
    {
        $this->writeLicenseFile($this->activeLicense(['status' => 'expired']));
        $this->fakeSuccessfulAuth();

        $service = new ConnectorService('my-key');

        $this->assertFalse($service->validateLicense());
    }

    public function test_validateLicense_false_se_tenants_number_insufficiente(): void
    {
        $this->writeLicenseFile($this->activeLicense(['tenants_number' => 2]));
        $this->fakeSuccessfulAuth();

        $service = new ConnectorService('my-key');

        $this->assertFalse($service->validateLicense(['tenants_number' => 3]));
    }

    public function test_validateLicense_false_se_clients_number_insufficiente(): void
    {
        $this->writeLicenseFile($this->activeLicense(['clients_number' => 2]));
        $this->fakeSuccessfulAuth();

        $service = new ConnectorService('my-key');

        $this->assertFalse($service->validateLicense(['clients_number' => 3]));
    }

    public function test_validateLicense_false_se_path_esplicito_non_combacia(): void
    {
        $this->writeLicenseFile($this->activeLicense());
        $this->fakeSuccessfulAuth();

        $service = new ConnectorService('my-key');

        $this->assertFalse($service->validateLicense(['path' => 'un-path-diverso']));
    }

    public function test_validateLicense_false_se_nessun_dominio_combacia(): void
    {
        $this->writeLicenseFile($this->activeLicense(['domain' => 'dominio-completamente-diverso']));
        $this->fakeSuccessfulAuth();

        $service = new ConnectorService('my-key');

        $this->assertFalse($service->validateLicense());
    }

    /**
     * Pinna il comportamento "quirk" documentato in
     * ConnectorService::licenseMatchesDomain(): un $data['domain'] esplicito
     * che non combacia NON fa fallire subito la validazione - si ricade sul
     * confronto con env('APP_DOMAIN') (che qui, senza punto, resta intero).
     */
    public function test_validateLicense_domain_esplicito_che_non_combacia_ricade_su_env_app_domain(): void
    {
        $this->writeLicenseFile($this->activeLicense(['domain' => env('APP_DOMAIN')]));
        $this->fakeSuccessfulAuth();

        $service = new ConnectorService('my-key');

        $this->assertTrue($service->validateLicense(['domain' => 'valore-esplicito-diverso-ma-ignorato']));
    }

    public function test_validateLicense_false_e_ripulisce_la_riga_license_se_manca_il_file(): void
    {
        DB::table('license')->insert(['license_key' => 'my-key']);
        Http::fake([
            self::AUTH_URL => Http::response(['success' => true, 'data' => ['access_token' => 'tok']], 200),
            self::LICENSE_URL => Http::response(['data' => null], 200),
        ]);

        $service = new ConnectorService('my-key');

        $this->assertFalse($service->validateLicense());
        $this->assertDatabaseMissing('license', ['license_key' => 'my-key']);
    }
}
