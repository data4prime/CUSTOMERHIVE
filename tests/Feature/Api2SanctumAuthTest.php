<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test del solo layer di autenticazione del binario api2 (Sanctum, vedi
 * docs/refactoring/086 e 088) - non di execute_api() (già coperto, con un
 * approccio analogo, da ApiExecuteTest.php). La rotta ad-hoc registrata qui
 * applica esattamente lo stesso middleware del gruppo 'api2' reale
 * (routes/crudbooster.php), ma punta a un endpoint fittizio: isolare il
 * layer di auth da execute_api() evita di dipendere da un bug preesistente
 * e scollegato in ApiController::execute_api() (variabile
 * $debug_mode_message non definita, riprodotto anche su api/ già esistenti
 * - vedi backlog in docs/refactoring/README.md), che renderebbe questi test
 * fragili senza aggiungere copertura sul comportamento qui in esame.
 *
 * Modello Personal Access Token (088): un token è generabile per QUALUNQUE
 * utente esistente, non serve nessun flag/categoria dedicata - il token
 * eredita i permessi dell'utente collegato, e smette di funzionare se
 * l'utente viene disattivato (vedi AppServiceProvider::boot(),
 * Sanctum::authenticateAccessTokensUsing()).
 */
class Api2SanctumAuthTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    protected function setUp(): void
    {
        parent::setUp();

        Route::group(['middleware' => ['api', 'auth:sanctum']], function () {
            Route::any('phpunit-test-api2/{permalink}', fn () => response()->json(['ok' => true]));
        });
    }

    private function seedNormalUser(array $overrides = []): \App\User
    {
        $tenantId = $this->seedTenant();
        $privilegeId = $this->seedPrivilege();
        $data = $this->seedUser(array_merge([
            'tenant' => $tenantId,
            'id_cms_privileges' => $privilegeId,
        ], $overrides));

        return \App\User::find($data['id']);
    }

    public function test_api2_senza_token_viene_rifiutato(): void
    {
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->getJson('http://localhost/phpunit-test-api2/qualcosa');

        $response->assertStatus(401);
    }

    public function test_api2_con_token_valido_di_un_utente_normale_passa(): void
    {
        $user = $this->seedNormalUser();
        $token = $user->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('http://localhost/phpunit-test-api2/qualcosa');

        $response->assertStatus(200)->assertJson(['ok' => true]);
    }

    public function test_api2_con_token_scaduto_viene_rifiutato(): void
    {
        $user = $this->seedNormalUser();
        $token = $user->createToken('test-token', ['*'], now()->subMinute())->plainTextToken;

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('http://localhost/phpunit-test-api2/qualcosa');

        $response->assertStatus(401);
    }

    public function test_api2_con_token_revocato_viene_rifiutato(): void
    {
        $user = $this->seedNormalUser();
        $newToken = $user->createToken('test-token', ['*']);
        $plainTextToken = $newToken->plainTextToken;

        $newToken->accessToken->delete();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $plainTextToken,
        ])->getJson('http://localhost/phpunit-test-api2/qualcosa');

        $response->assertStatus(401);
    }

    /**
     * Un Personal Access Token deve smettere di funzionare se l'utente a
     * cui è legato viene disattivato dopo l'emissione - vedi
     * AppServiceProvider::boot() e docs/refactoring/088.
     */
    public function test_api2_con_token_di_utente_disattivato_dopo_l_emissione_viene_rifiutato(): void
    {
        $user = $this->seedNormalUser();
        $token = $user->createToken('test-token', ['*'])->plainTextToken;

        \Illuminate\Support\Facades\DB::table('cms_users')->where('id', $user->id)->update(['status' => 'Inactive']);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('http://localhost/phpunit-test-api2/qualcosa');

        $response->assertStatus(401);
    }
}
