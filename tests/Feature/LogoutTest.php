<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\LogsInAdmin;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test di caratterizzazione per AdminController::getLogout(). Verifica, in
 * particolare, che il logout invalidi anche il guard Laravel introdotto in
 * modo additivo nella Fase 1 del refactoring auth (non solo la sessione
 * legacy) — prerequisito per poter usare Auth::guest() al posto di
 * CRUDBooster::myId() nel middleware CBBackend (Fase 3).
 */
class LogoutTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;
    use LogsInAdmin;

    private const PASSWORD = 'password-corretta-123';

    public function test_logout_invalida_sia_la_sessione_legacy_che_il_guard(): void
    {
        $tenantId = $this->seedTenant('tenant-logout');
        $this->seedUser(['tenant' => $tenantId, 'email' => 'logout@example.com']);

        $loginResponse = $this->postLoginFrom('tenant-logout.thecustomerhive.com', [
            'email' => 'logout@example.com',
            'password' => self::PASSWORD,
        ]);
        $loginResponse->assertSessionHas('admin_id');
        $this->assertTrue(Auth::check());

        $logoutResponse = $this->get('http://tenant-logout.thecustomerhive.com/admin/logout');

        $logoutResponse->assertSessionMissing('admin_id');
        $this->assertGuest();
    }
}
