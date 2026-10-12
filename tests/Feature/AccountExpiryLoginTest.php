<?php

namespace Tests\Feature;

use App\Helpers\UserHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\LogsInAdmin;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Scadenza account applicata anche al login (docs/refactoring/293-*), non
 * solo dal cron user:check-expiry. Stessa regola del comando, inclusa la
 * protezione dei superadmin.
 */
class AccountExpiryLoginTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;
    use LogsInAdmin;

    private const PASSWORD = 'password-corretta-123';

    private function loginAs(array $user, string $host)
    {
        $this->trustDeviceFor($user);

        return $this->postLoginFrom($host, ['email' => $user['email'], 'password' => self::PASSWORD]);
    }

    private function row(array $user): object
    {
        return DB::table('cms_users')->where('id', $user['id'])->first();
    }

    public function test_utente_con_account_scaduto_ancora_attivo_non_entra(): void
    {
        $tenant = $this->seedTenant('tenant-scaduto');
        $user = $this->seedUser(['tenant' => $tenant, 'data_scadenza' => now()->subDays(2)->toDateString()]);

        $response = $this->loginAs($user, 'tenant-scaduto.thecustomerhive.com');

        $response->assertStatus(302);
        $response->assertSessionMissing('admin_id');
        $this->assertGuest();
    }

    public function test_utente_con_scadenza_futura_o_assente_entra(): void
    {
        $tenant = $this->seedTenant('tenant-valido');
        $future = $this->seedUser(['tenant' => $tenant, 'data_scadenza' => now()->addDays(10)->toDateString()]);

        $this->loginAs($future, 'tenant-valido.thecustomerhive.com')
            ->assertSessionHas('admin_id', $future['id']);
    }

    public function test_utente_senza_data_di_scadenza_entra(): void
    {
        $tenant = $this->seedTenant('tenant-senza-data');
        $user = $this->seedUser(['tenant' => $tenant, 'data_scadenza' => null]);

        $this->loginAs($user, 'tenant-senza-data.thecustomerhive.com')
            ->assertSessionHas('admin_id', $user['id']);
    }

    public function test_lultimo_superadmin_scaduto_puo_ancora_entrare(): void
    {
        DB::table('cms_users')->update(['status' => 'Inactive']);
        $tenant = $this->seedTenant('tenant-super');
        $super = $this->seedUser([
            'tenant' => $tenant,
            'id_cms_privileges' => $this->seedPrivilege(isSuperadmin: true),
            'data_scadenza' => now()->subDays(2)->toDateString(),
        ]);

        $this->loginAs($super, 'tenant-super.thecustomerhive.com')
            ->assertSessionHas('admin_id', $super['id']);
    }

    public function test_un_superadmin_scaduto_non_entra_se_ne_esiste_uno_valido(): void
    {
        DB::table('cms_users')->update(['status' => 'Inactive']);
        $tenant = $this->seedTenant('tenant-super-due');
        $privilege = $this->seedPrivilege(isSuperadmin: true);
        $expired = $this->seedUser([
            'tenant' => $tenant, 'id_cms_privileges' => $privilege,
            'data_scadenza' => now()->subDays(2)->toDateString(),
        ]);
        $this->seedUser(['tenant' => $tenant, 'id_cms_privileges' => $privilege, 'data_scadenza' => null]);

        $response = $this->loginAs($expired, 'tenant-super-due.thecustomerhive.com');

        $response->assertSessionMissing('admin_id');
    }

    public function test_is_account_expired_sui_casi_limite(): void
    {
        $tenant = $this->seedTenant();

        $past = $this->seedUser(['tenant' => $tenant, 'data_scadenza' => now()->subDay()->toDateString()]);
        $today = $this->seedUser(['tenant' => $tenant, 'data_scadenza' => now()->toDateString()]);
        $tomorrow = $this->seedUser(['tenant' => $tenant, 'data_scadenza' => now()->addDay()->toDateString()]);
        $none = $this->seedUser(['tenant' => $tenant, 'data_scadenza' => null]);

        $this->assertTrue(UserHelper::isAccountExpired($this->row($past)));
        // Stessa regola del cron: alla mezzanotte del giorno di scadenza e' gia' scaduto.
        $this->assertTrue(UserHelper::isAccountExpired($this->row($today)));
        $this->assertFalse(UserHelper::isAccountExpired($this->row($tomorrow)));
        $this->assertFalse(UserHelper::isAccountExpired($this->row($none)));
    }

    public function test_expired_active_user_ids_coincide_con_is_account_expired(): void
    {
        DB::table('cms_users')->update(['status' => 'Inactive']);
        $tenant = $this->seedTenant();
        $superPrivilege = $this->seedPrivilege(isSuperadmin: true);

        $users = [
            $this->seedUser(['tenant' => $tenant, 'data_scadenza' => now()->subDays(3)->toDateString()]),
            $this->seedUser(['tenant' => $tenant, 'data_scadenza' => now()->addDays(3)->toDateString()]),
            $this->seedUser(['tenant' => $tenant, 'id_cms_privileges' => $superPrivilege, 'data_scadenza' => now()->subDays(3)->toDateString()]),
            $this->seedUser(['tenant' => $tenant, 'id_cms_privileges' => $superPrivilege, 'data_scadenza' => now()->addDays(30)->toDateString()]),
        ];

        $byLogin = collect($users)
            ->filter(fn ($user) => UserHelper::isAccountExpired($this->row($user)))
            ->pluck('id')->sort()->values()->all();

        $byCron = UserHelper::expiredActiveUserIds();
        sort($byCron);

        $this->assertSame($byLogin, $byCron);
        $this->assertCount(2, $byCron);
    }
}
