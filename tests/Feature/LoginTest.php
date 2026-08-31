<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\LogsInAdmin;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test di caratterizzazione per AdminController::postLogin() (CRUDBooster).
 * Fissano il comportamento ATTUALE del login, non modificano il controller.
 *
 * Nota: il controllo di licenza (LicenseHelper::canLicenseLogin()) ritorna
 * sempre true in ambiente 'testing' (vedi LicenseHelper::canLicenseLogin())
 * per non dipendere da un license server esterno raggiungibile — questi
 * test non coprono il flusso di licenza, solo login/logout.
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;
    use LogsInAdmin;

    private const PASSWORD = 'password-corretta-123';

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
