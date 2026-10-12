<?php

namespace Tests\Feature;

use App\Helpers\TenantHelper;
use App\Jobs\SendDelayedEmailJob;
use App\Jobs\SendTemplateEmailJob;
use CRUDBooster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Email accodate (vedi docs/refactoring/289-* e 290-*): invio ritardato con
 * send_at, SendTemplateEmailJob, sostituzione di blocchi nel template senza
 * toccare il DB, tabella degli utenti in scadenza, getTenantAdmins().
 */
class QueuedEmailTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    private const PLACEHOLDER = '|CUSTOMFUNCTION|TenantHelper::build_utenti_scadenza_tenantadmin|CUSTOMFUNCTIONEND|';

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('cms_email_templates')->where('slug', 'coda_test')->delete();
        DB::table('cms_email_templates')->insert([
            'name' => 'coda_test',
            'slug' => 'coda_test',
            'subject' => 'Ciao [name]',
            'content' => 'Elenco: '.self::PLACEHOLDER.' per [name]',
            'created_at' => now(),
        ]);
    }

    private function utente(string $name, string $email, string $data = '01/01/2030'): object
    {
        return (object) ['name' => $name, 'email' => $email, 'data_scadenza' => $data];
    }

    public function test_send_email_con_send_at_accoda_un_job_con_delay_e_non_invia(): void
    {
        Queue::fake();
        Mail::shouldReceive('send')->never();

        $sendAt = now()->addHours(3)->toDateTimeString();

        $this->assertTrue(CRUDBooster::sendEmail([
            'to' => 'dest@example.com',
            'data' => ['name' => 'Mario'],
            'template' => 'coda_test',
            'send_at' => $sendAt,
        ]));

        Queue::assertPushedOn('mail', SendDelayedEmailJob::class, function ($job) use ($sendAt) {
            return $job->payload['to'] === 'dest@example.com'
                && $job->payload['subject'] === 'Ciao Mario'
                && str_contains($job->payload['content'], 'per Mario')
                && $job->delay->toDateTimeString() === $sendAt;
        });
    }

    public function test_send_email_applica_content_replace_senza_modificare_il_template(): void
    {
        $captured = null;
        Mail::shouldReceive('send')->once()->withArgs(function ($view, $data) use (&$captured) {
            $captured = [$view, $data];

            return true;
        });

        CRUDBooster::sendEmail([
            'to' => 'dest@example.com',
            'data' => ['name' => 'Mario'],
            'template' => 'coda_test',
            'content_replace' => [self::PLACEHOLDER => '<table>TABELLA</table>'],
        ]);

        $this->assertSame('crudbooster::emails.blank', $captured[0]);
        $this->assertSame('Elenco: <table>TABELLA</table> per Mario', $captured[1]['content']);
        $this->assertSame(
            'Elenco: '.self::PLACEHOLDER.' per [name]',
            DB::table('cms_email_templates')->where('slug', 'coda_test')->value('content')
        );
    }

    public function test_send_template_email_job_invia_tramite_send_email(): void
    {
        $captured = null;
        Mail::shouldReceive('send')->once()->withArgs(function ($view, $data) use (&$captured) {
            $captured = $data;

            return true;
        });

        (new SendTemplateEmailJob([
            'to' => 'dest@example.com',
            'data' => ['name' => 'Luisa'],
            'template' => 'coda_test',
            'content_replace' => [self::PLACEHOLDER => 'X'],
        ]))->handle();

        $this->assertSame('Elenco: X per Luisa', $captured['content']);
    }

    public function test_enqueue_usa_la_connessione_configurata_e_la_coda_mail(): void
    {
        Queue::fake();

        config(['queue.mail_connection' => 'sync']);
        SendTemplateEmailJob::enqueue(['to' => 'a@example.com', 'data' => [], 'template' => 'coda_test']);
        Queue::assertPushedOn('mail', SendTemplateEmailJob::class, fn ($job) => $job->connection === 'sync');

        config(['queue.mail_connection' => 'mail']);
        SendTemplateEmailJob::enqueue(['to' => 'b@example.com', 'data' => [], 'template' => 'coda_test']);
        Queue::assertPushedOn('mail', SendTemplateEmailJob::class, fn ($job) => $job->connection === 'mail');
    }

    public function test_tabella_utenti_in_scadenza_righe_colori_ed_escape(): void
    {
        $html = TenantHelper::build_utenti_scadenza_tenantadmin([
            $this->utente('Mario Rossi', 'mario@example.com', '15/10/2026'),
            $this->utente('<script>alert(1)</script>', 'x@example.com'),
            $this->utente('Terzo', 'terzo@example.com'),
        ]);

        // intestazione + 3 righe
        $this->assertSame(4, substr_count($html, '<tr'));
        $this->assertStringContainsString('Mario Rossi', $html);
        $this->assertStringContainsString('15/10/2026', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertSame(2, substr_count($html, 'background-color: #f9f9f9'));
        $this->assertSame(1, substr_count($html, 'background-color: #ffffff'));
        $this->assertStringContainsString('Nome', $html);
        $this->assertStringContainsString('Data scadenza', $html);
    }

    public function test_tabella_utenti_in_scadenza_senza_utenti_ha_solo_lintestazione(): void
    {
        $html = TenantHelper::build_utenti_scadenza_tenantadmin([]);

        $this->assertSame(1, substr_count($html, '<tr'));
        $this->assertStringEndsWith('</table>', $html);
    }

    public function test_get_tenant_admins_ritorna_solo_gli_admin_attivi_del_tenant(): void
    {
        DB::table('cms_privileges')->updateOrInsert(['id' => 2], [
            'name' => 'Tenantadmin', 'is_superadmin' => 0, 'is_tenantadmin' => 1, 'theme_color' => 'blue',
        ]);
        $tenant = $this->seedTenant();
        $other = $this->seedTenant();

        $active = $this->seedUser(['tenant' => $tenant, 'id_cms_privileges' => 2]);
        $this->seedUser(['tenant' => $tenant, 'id_cms_privileges' => 2, 'status' => 'Inactive']);
        $this->seedUser(['tenant' => $other, 'id_cms_privileges' => 2]);
        $this->seedUser(['tenant' => $tenant, 'id_cms_privileges' => $this->seedPrivilege()]);

        $admins = TenantHelper::getTenantAdmins($tenant);

        $this->assertCount(1, $admins);
        $this->assertSame($active['email'], $admins[0]->email);
    }

    public function test_get_tenant_admins_e_vuoto_se_il_ruolo_2_non_e_tenantadmin(): void
    {
        DB::table('cms_privileges')->updateOrInsert(['id' => 2], [
            'name' => 'Altro', 'is_superadmin' => 0, 'is_tenantadmin' => 0, 'theme_color' => 'blue',
        ]);
        $tenant = $this->seedTenant();
        $this->seedUser(['tenant' => $tenant, 'id_cms_privileges' => 2]);

        $this->assertSame([], TenantHelper::getTenantAdmins($tenant));
    }
}
