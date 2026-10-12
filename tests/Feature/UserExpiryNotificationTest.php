<?php

namespace Tests\Feature;

use App\Jobs\SendTemplateEmailJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Comando user:expiry-notification (vedi docs/refactoring/290-*): quali
 * utenti vengono avvisati, a chi vanno le mail per tenant/superadmin, cosa
 * finisce nel payload dei job. Niente SMTP: i job sono finti (Queue::fake).
 */
class UserExpiryNotificationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    private const PLACEHOLDER = '|CUSTOMFUNCTION|TenantHelper::build_utenti_scadenza_tenantadmin|CUSTOMFUNCTIONEND|';

    private int $tenantA;

    private int $tenantB;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        // Privilegio 1 = superadmin, 2 = tenant admin (id fissi usati dal comando).
        DB::table('cms_privileges')->updateOrInsert(['id' => 1], [
            'name' => 'Superadmin', 'is_superadmin' => 1, 'is_tenantadmin' => 0, 'theme_color' => 'blue',
        ]);
        DB::table('cms_privileges')->updateOrInsert(['id' => 2], [
            'name' => 'Tenantadmin', 'is_superadmin' => 0, 'is_tenantadmin' => 1, 'theme_color' => 'blue',
        ]);

        foreach (['notifica_scadenza_utente_user', 'notifica_scadenza_utente_tenantadmin'] as $slug) {
            DB::table('cms_email_templates')->where('slug', $slug)->delete();
            DB::table('cms_email_templates')->insert([
                'name' => $slug,
                'slug' => $slug,
                'subject' => $slug,
                'content' => '[name] '.self::PLACEHOLDER,
                'created_at' => now(),
            ]);
        }

        $this->tenantA = $this->seedTenant();
        $this->tenantB = $this->seedTenant();
    }

    protected function tearDown(): void
    {
        Cache::forget('setting_email_enabled');

        parent::tearDown();
    }

    private function expiringUser(int $days, array $overrides = []): array
    {
        return $this->seedUser(array_merge([
            'tenant' => $this->tenantA,
            'id_cms_privileges' => $this->seedPrivilege(),
            'data_scadenza' => now()->addDays($days)->toDateString(),
        ], $overrides));
    }

    private function tenantAdmin(int $tenant, array $overrides = []): array
    {
        return $this->seedUser(array_merge(['tenant' => $tenant, 'id_cms_privileges' => 2], $overrides));
    }

    private function superadmin(): array
    {
        return $this->seedUser(['id_cms_privileges' => 1]);
    }

    private function runCommand(array $options = []): void
    {
        $this->artisan('user:expiry-notification', $options)->assertExitCode(0);
    }

    /** @return array<int,array> config dei job accodati per il template. */
    private function queued(string $template): array
    {
        return Queue::pushed(SendTemplateEmailJob::class)
            ->map(fn ($job) => $job->config)
            ->filter(fn ($config) => $config['template'] === $template)
            ->values()
            ->all();
    }

    private function recipients(string $template): array
    {
        $to = array_column($this->queued($template), 'to');
        sort($to);

        return $to;
    }

    public function test_avvisa_solo_gli_utenti_con_scadenza_a_30_7_e_1_giorni(): void
    {
        $inWindow = [30, 7, 1];
        $outOfWindow = [-1, 0, 2, 6, 8, 29, 31];

        $expected = [];
        foreach ($inWindow as $days) {
            $expected[] = $this->expiringUser($days)['email'];
        }
        foreach ($outOfWindow as $days) {
            $this->expiringUser($days);
        }

        $this->runCommand();

        $actual = $this->recipients('notifica_scadenza_utente_user');
        sort($expected);

        $this->assertSame($expected, $actual);
    }

    public function test_non_avvisa_gli_utenti_non_attivi(): void
    {
        $this->expiringUser(7, ['status' => 'Inactive']);

        $this->runCommand();

        $this->assertSame([], $this->queued('notifica_scadenza_utente_user'));
    }

    public function test_con_email_disattivata_non_accoda_nulla(): void
    {
        DB::table('cms_settings')->updateOrInsert(['name' => 'email_enabled'], [
            'label' => 'Email Enabled', 'content' => 'no', 'content_input_type' => 'radio',
            'group_setting' => 'Email Setting', 'dataenum' => 'yes,no', 'created_at' => now(),
        ]);
        Cache::forget('setting_email_enabled');

        $this->expiringUser(7);
        $this->tenantAdmin($this->tenantA);
        $this->superadmin();

        $this->runCommand();

        Queue::assertNothingPushed();
    }

    public function test_i_tenant_admin_ricevono_solo_gli_utenti_del_proprio_tenant(): void
    {
        $adminA = $this->tenantAdmin($this->tenantA);
        $adminB = $this->tenantAdmin($this->tenantB);
        $this->expiringUser(7, ['name' => 'Utente Del Tenant A']);
        $this->expiringUser(7, ['name' => 'Utente Del Tenant B', 'tenant' => $this->tenantB]);

        $this->runCommand();

        $byRecipient = [];
        foreach ($this->queued('notifica_scadenza_utente_tenantadmin') as $config) {
            $byRecipient[$config['to']] = $config['content_replace'][self::PLACEHOLDER];
        }

        $this->assertStringContainsString('Utente Del Tenant A', $byRecipient[$adminA['email']]);
        $this->assertStringNotContainsString('Utente Del Tenant B', $byRecipient[$adminA['email']]);
        $this->assertStringContainsString('Utente Del Tenant B', $byRecipient[$adminB['email']]);
        $this->assertStringNotContainsString('Utente Del Tenant A', $byRecipient[$adminB['email']]);
    }

    public function test_ai_tenant_admin_non_si_mostrano_superadmin_e_tenant_admin_in_scadenza(): void
    {
        $admin = $this->tenantAdmin($this->tenantA);
        $this->tenantAdmin($this->tenantA, [
            'name' => 'Altro Tenant Admin In Scadenza',
            'data_scadenza' => now()->addDays(7)->toDateString(),
        ]);
        $this->expiringUser(7, ['name' => 'Utente Standard In Scadenza']);

        $this->runCommand();

        $html = null;
        foreach ($this->queued('notifica_scadenza_utente_tenantadmin') as $config) {
            if ($config['to'] === $admin['email']) {
                $html = $config['content_replace'][self::PLACEHOLDER];
            }
        }

        $this->assertNotNull($html);
        $this->assertStringContainsString('Utente Standard In Scadenza', $html);
        $this->assertStringNotContainsString('Altro Tenant Admin In Scadenza', $html);
    }

    public function test_i_superadmin_ricevono_lelenco_di_tutti_i_tenant(): void
    {
        $super = $this->superadmin();
        $this->expiringUser(7, ['name' => 'Utente Del Tenant A']);
        $this->expiringUser(30, ['name' => 'Utente Del Tenant B', 'tenant' => $this->tenantB]);

        $this->runCommand();

        $html = null;
        foreach ($this->queued('notifica_scadenza_utente_tenantadmin') as $config) {
            if ($config['to'] === $super['email']) {
                $html = $config['content_replace'][self::PLACEHOLDER];
            }
        }

        $this->assertNotNull($html);
        $this->assertStringContainsString('Utente Del Tenant A', $html);
        $this->assertStringContainsString('Utente Del Tenant B', $html);
    }

    public function test_senza_utenti_in_scadenza_non_scrive_a_nessuno(): void
    {
        $this->superadmin();
        $this->tenantAdmin($this->tenantA);
        $this->expiringUser(15);

        $this->runCommand();

        Queue::assertNothingPushed();
    }

    public function test_il_payload_dei_job_non_contiene_credenziali(): void
    {
        $this->expiringUser(7, ['password' => 'hash-segreto']);

        $this->runCommand();

        $configs = $this->queued('notifica_scadenza_utente_user');
        $this->assertCount(1, $configs);

        foreach (array_keys($configs[0]['data']) as $field) {
            $this->assertDoesNotMatchRegularExpression('/password|token|secret|mfa|otp/i', $field);
        }
        $this->assertArrayHasKey('name', $configs[0]['data']);
        $this->assertArrayHasKey('giorni_mancanti', $configs[0]['data']);
        $this->assertSame(now()->addDays(7)->format('d/m/Y'), $configs[0]['data']['data_scadenza']);
    }

    public function test_rilanciare_il_comando_lo_stesso_giorno_non_duplica_le_mail(): void
    {
        $this->expiringUser(7);
        $this->superadmin();

        $this->runCommand();
        $first = Queue::pushed(SendTemplateEmailJob::class)->count();
        $this->assertGreaterThan(0, $first);

        $this->runCommand();
        $this->assertSame($first, Queue::pushed(SendTemplateEmailJob::class)->count());

        $this->runCommand(['--force' => true]);
        $this->assertSame($first * 2, Queue::pushed(SendTemplateEmailJob::class)->count());
    }

    public function test_i_job_vanno_sulla_coda_mail_della_connessione_configurata(): void
    {
        config(['queue.mail_connection' => 'mail']);
        $this->expiringUser(7);

        $this->runCommand();

        Queue::assertPushedOn('mail', SendTemplateEmailJob::class, function ($job) {
            return $job->connection === 'mail';
        });
    }
}
