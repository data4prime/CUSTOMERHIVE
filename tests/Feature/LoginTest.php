<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test di caratterizzazione per AdminController::postLogin() (CRUDBooster).
 * Fissano il comportamento ATTUALE del login, non modificano il controller.
 *
 * Nota: il controllo di licenza (LicenseHelper::canLicenseLogin()) è oggi
 * bypassato incondizionatamente (tag LICENSE-CHECK-DISABLED-DEV) — questi
 * test dipendono da quel bypass per non fare chiamate di rete verso il
 * license server esterno. Quando il bypass verrà rimosso, questi test
 * andranno rivisti (vedi docs/pre-push-checklist.md).
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    private const PASSWORD = 'password-corretta-123';

    /**
     * POST /admin/login simulando l'arrivo da un dominio specifico.
     *
     * Due dettagli non ovvi, entrambi necessari per riprodurre fedelmente
     * il comportamento reale:
     * - va passato come URL assoluto (non con un header 'Host' separato):
     *   con questo routing dinamico (CRUDBooster registra le route ad ogni
     *   richiesta), un semplice header Host senza URL assoluto produce un
     *   404 invece di raggiungere il controller.
     * - AdminController::postLogin() legge il dominio da $_SERVER['HTTP_HOST']
     *   DIRETTAMENTE (non da Request::getHost()) — il client di test di
     *   Laravel non sincronizza quella superglobale con l'URL simulato, va
     *   quindi impostata a mano. In produzione (richiesta HTTP reale via
     *   Apache) non serve, $_SERVER è sempre popolato correttamente: è solo
     *   un problema di testabilità di questo pezzo di codice, da tenere
     *   presente per il futuro refactoring dell'auth.
     */
    private function postLoginFrom(?string $host, array $data)
    {
        $host = $host ?: parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';

        $previousHost = $_SERVER['HTTP_HOST'] ?? null;
        $_SERVER['HTTP_HOST'] = $host;

        try {
            return $this->post("http://{$host}/admin/login", $data);
        } finally {
            if ($previousHost === null) {
                unset($_SERVER['HTTP_HOST']);
            } else {
                $_SERVER['HTTP_HOST'] = $previousHost;
            }
        }
    }

    public function test_login_con_credenziali_corrette_riesce(): void
    {
        $tenantId = $this->seedTenant('tenant-uno');
        $user = $this->seedUser(['tenant' => $tenantId, 'email' => 'ok@example.com']);

        $response = $this->postLoginFrom('tenant-uno.thecustomerhive.com', [
            'email' => 'ok@example.com',
            'password' => self::PASSWORD,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('admin_id', $user['id']);
        $response->assertSessionHas('admin_is_superadmin', 0);
        // Guard Laravel nativo (config/auth.php: 'web' -> App\User, gia'
        // mappato su cms_users), popolato in aggiunta alla sessione legacy
        // sopra - vedi la nota nel controller e docs/refactoring/README.md.
        $this->assertTrue(Auth::check());
        $this->assertEquals($user['id'], Auth::id());
    }

    public function test_login_con_password_sbagliata_fallisce(): void
    {
        $tenantId = $this->seedTenant('tenant-due');
        $this->seedUser(['tenant' => $tenantId, 'email' => 'pwderrata@example.com']);

        $response = $this->postLoginFrom('tenant-due.thecustomerhive.com', [
            'email' => 'pwderrata@example.com',
            'password' => 'password-sbagliata',
        ]);

        $response->assertStatus(302);
        $response->assertSessionMissing('admin_id');
        $this->assertGuest();
    }

    public function test_login_con_email_inesistente_fallisce(): void
    {
        $response = $this->postLoginFrom(null, [
            'email' => 'non-esiste@example.com',
            'password' => self::PASSWORD,
        ]);

        $response->assertStatus(302);
        $response->assertSessionMissing('admin_id');
        // postLogin() non usa il sistema standard di validation error di
        // Laravel: fa redirect()->back()->with(['message' => ...]) a mano.
        $response->assertSessionHas('message');
    }

    public function test_login_utente_non_active_fallisce(): void
    {
        $tenantId = $this->seedTenant('tenant-tre');
        $this->seedUser([
            'tenant' => $tenantId,
            'email' => 'sospeso@example.com',
            'status' => 'Suspend',
        ]);

        $response = $this->postLoginFrom('tenant-tre.thecustomerhive.com', [
            'email' => 'sospeso@example.com',
            'password' => self::PASSWORD,
        ]);

        $response->assertStatus(302);
        $response->assertSessionMissing('admin_id');
    }

    public function test_login_da_dominio_di_un_altro_tenant_fallisce(): void
    {
        $tenantId = $this->seedTenant('tenant-proprio');
        $this->seedTenant('tenant-altrui');
        $this->seedUser(['tenant' => $tenantId, 'email' => 'mismatch@example.com']);

        $response = $this->postLoginFrom('tenant-altrui.thecustomerhive.com', [
            'email' => 'mismatch@example.com',
            'password' => self::PASSWORD,
        ]);

        $response->assertStatus(302);
        $response->assertSessionMissing('admin_id');
    }

    public function test_superadmin_bypassa_il_controllo_tenant(): void
    {
        $tenantId = $this->seedTenant('tenant-super-proprio');
        $this->seedTenant('tenant-super-altrui');
        $superadminPrivilegeId = $this->seedPrivilege(isSuperadmin: true);
        $user = $this->seedUser([
            'tenant' => $tenantId,
            'id_cms_privileges' => $superadminPrivilegeId,
            'email' => 'super@example.com',
        ]);

        $response = $this->postLoginFrom('tenant-super-altrui.thecustomerhive.com', [
            'email' => 'super@example.com',
            'password' => self::PASSWORD,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('admin_id', $user['id']);
        $response->assertSessionHas('admin_is_superadmin', 1);
        $this->assertTrue(Auth::check());
        $this->assertEquals($user['id'], Auth::id());
    }

    /**
     * Test end-to-end: la sessione creata dal login basta davvero a passare
     * il gate del middleware CBBackend su una richiesta successiva (non
     * solo a popolare le chiavi giuste in isolamento). Vedi anche
     * tests/Feature/CBBackendTest.php per il middleware testato da solo.
     */
    public function test_dopo_il_login_si_accede_a_una_pagina_protetta(): void
    {
        $tenantId = $this->seedTenant('tenant-e2e');
        $this->seedUser(['tenant' => $tenantId, 'email' => 'e2e@example.com']);

        $loginResponse = $this->postLoginFrom('tenant-e2e.thecustomerhive.com', [
            'email' => 'e2e@example.com',
            'password' => self::PASSWORD,
        ]);
        $loginResponse->assertSessionHas('admin_id');

        $protectedResponse = $this->get('http://tenant-e2e.thecustomerhive.com/admin');

        $protectedResponse->assertStatus(200);
        $this->assertTrue(Auth::check());
    }
}
