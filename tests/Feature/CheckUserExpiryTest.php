<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Comando user:check-expiry (vedi docs/refactoring/292-*): disattiva gli
 * utenti attivi con data_scadenza passata.
 */
class CheckUserExpiryTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    private function user(?string $dataScadenza, string $status = 'Active'): int
    {
        return $this->seedUser(['data_scadenza' => $dataScadenza, 'status' => $status])['id'];
    }

    private function userStatus(int $id): string
    {
        return DB::table('cms_users')->where('id', $id)->value('status');
    }

    public function test_disattiva_chi_e_scaduto_da_giorni(): void
    {
        $id = $this->user(now()->subDays(3)->toDateString());

        $this->artisan('user:check-expiry')->assertExitCode(0);

        $this->assertSame('Inactive', $this->userStatus($id));
    }

    public function test_non_tocca_chi_scade_nei_giorni_successivi(): void
    {
        $tomorrow = $this->user(now()->addDay()->toDateString());
        $later = $this->user(now()->addDays(30)->toDateString());

        $this->artisan('user:check-expiry')->assertExitCode(0);

        $this->assertSame('Active', $this->userStatus($tomorrow));
        $this->assertSame('Active', $this->userStatus($later));
    }

    public function test_il_giorno_di_scadenza_lutente_risulta_gia_scaduto(): void
    {
        // Comportamento attuale (confronto date < now()): alla mezzanotte del
        // giorno di scadenza l'utente viene disattivato. Se si decide di
        // renderlo inclusivo, questo test va aggiornato.
        $id = $this->user(now()->toDateString());

        $this->artisan('user:check-expiry')->assertExitCode(0);

        $this->assertSame('Inactive', $this->userStatus($id));
    }

    public function test_senza_data_di_scadenza_resta_attivo(): void
    {
        $id = $this->user(null);

        $this->artisan('user:check-expiry')->assertExitCode(0);

        $this->assertSame('Active', $this->userStatus($id));
    }

    public function test_la_data_zero_legacy_non_conta_come_scadenza(): void
    {
        $id = $this->user(null);

        try {
            DB::statement("UPDATE cms_users SET data_scadenza = '0000-00-00' WHERE id = ?", [$id]);
        } catch (\Throwable $e) {
            $this->markTestSkipped('MySQL non accetta date zero (NO_ZERO_DATE): niente dati legacy da testare.');
        }

        $this->artisan('user:check-expiry')->assertExitCode(0);

        $this->assertSame('Active', $this->userStatus($id));
    }

    public function test_non_riscrive_gli_utenti_gia_disattivati(): void
    {
        $id = $this->user(now()->subDays(3)->toDateString(), 'Inactive');
        $before = DB::table('cms_users')->where('id', $id)->value('updated_at');

        $this->artisan('user:check-expiry')->assertExitCode(0);

        $this->assertSame('Inactive', $this->userStatus($id));
        $this->assertSame($before, DB::table('cms_users')->where('id', $id)->value('updated_at'));
    }

    public function test_aggiorna_solo_gli_utenti_scaduti_e_riporta_il_conteggio(): void
    {
        $expiredA = $this->user(now()->subDays(1)->toDateString());
        $expiredB = $this->user(now()->subDays(40)->toDateString());
        $valid = $this->user(now()->addDays(5)->toDateString());

        $this->artisan('user:check-expiry')
            ->expectsOutput('Utenti disattivati per scadenza: 2')
            ->assertExitCode(0);

        $this->assertSame('Inactive', $this->userStatus($expiredA));
        $this->assertSame('Inactive', $this->userStatus($expiredB));
        $this->assertSame('Active', $this->userStatus($valid));
    }

    private function superadmin(?string $dataScadenza): int
    {
        return $this->seedUser([
            'id_cms_privileges' => $this->seedPrivilege(isSuperadmin: true),
            'data_scadenza' => $dataScadenza,
        ])['id'];
    }

    public function test_se_tutti_i_superadmin_sono_scaduti_restano_attivi(): void
    {
        DB::table('cms_users')->update(['status' => 'Inactive']);
        $superA = $this->superadmin(now()->subDays(5)->toDateString());
        $superB = $this->superadmin(now()->subDays(1)->toDateString());
        $standard = $this->user(now()->subDays(5)->toDateString());

        $this->artisan('user:check-expiry')->assertExitCode(0);

        $this->assertSame('Active', $this->userStatus($superA));
        $this->assertSame('Active', $this->userStatus($superB));
        $this->assertSame('Inactive', $this->userStatus($standard));
    }

    public function test_i_superadmin_scaduti_si_disattivano_se_ne_esiste_uno_valido(): void
    {
        DB::table('cms_users')->update(['status' => 'Inactive']);
        $expired = $this->superadmin(now()->subDays(5)->toDateString());
        $valid = $this->superadmin(now()->addDays(60)->toDateString());

        $this->artisan('user:check-expiry')->assertExitCode(0);

        $this->assertSame('Inactive', $this->userStatus($expired));
        $this->assertSame('Active', $this->userStatus($valid));
    }

    public function test_un_superadmin_senza_scadenza_conta_come_valido(): void
    {
        DB::table('cms_users')->update(['status' => 'Inactive']);
        $expired = $this->superadmin(now()->subDays(5)->toDateString());
        $this->superadmin(null);

        $this->artisan('user:check-expiry')->assertExitCode(0);

        $this->assertSame('Inactive', $this->userStatus($expired));
    }

    public function test_senza_utenti_scaduti_lo_dice_e_non_fa_nulla(): void
    {
        $id = $this->user(now()->addDays(5)->toDateString());

        $this->artisan('user:check-expiry')
            ->expectsOutput('Nessun utente scaduto da disattivare.')
            ->assertExitCode(0);

        $this->assertSame('Active', $this->userStatus($id));
    }
}
