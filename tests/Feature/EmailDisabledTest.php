<?php

namespace Tests\Feature;

use App\Helpers\CRUDBooster;
use App\Helpers\MfaHelper;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Flag "Email attiva" (cms_settings.email_enabled, vedi
 * docs/refactoring/220-*): a 'no' l'app si comporta come se l'email non ci
 * fosse - nessun invio/accodamento, nessun codice email OTP, cambio
 * email/password dal profilo con la sola password, forgot password con
 * messaggio esplicito.
 */
class EmailDisabledTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    private $previousServerValues = [];

    private const FAKE_SERVER_VALUES = [
        'REQUEST_URI' => '/admin/users/profile',
        'HTTP_HOST' => 'localhost',
        'REMOTE_ADDR' => '127.0.0.1',
        'HTTP_USER_AGENT' => 'phpunit',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAdminModules();

        foreach (self::FAKE_SERVER_VALUES as $key => $value) {
            $this->previousServerValues[$key] = $_SERVER[$key] ?? null;
            $_SERVER[$key] = $value;
        }

        if (! DB::table('cms_email_templates')->where('slug', 'forgot_password_backend')->exists()) {
            DB::table('cms_email_templates')->insert([
                'name' => 'forgot_password_backend',
                'slug' => 'forgot_password_backend',
                'subject' => 'forgot',
                'content' => '[reset_url]',
                'created_at' => now(),
            ]);
        }

        $this->setEmailEnabled(null);
    }

    protected function tearDown(): void
    {
        foreach (['email_enabled', 'smtp_driver', 'smtp_host'] as $name) {
            Cache::forget('setting_' . $name);
        }

        foreach ($this->previousServerValues as $key => $previousValue) {
            if ($previousValue === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $previousValue;
            }
        }

        parent::tearDown();
    }

    /** null = riga assente (installazione non ancora migrata). */
    private function setEmailEnabled(?string $value): void
    {
        DB::table('cms_settings')->where('name', 'email_enabled')->delete();

        if ($value !== null) {
            DB::table('cms_settings')->insert([
                'name' => 'email_enabled',
                'label' => 'Email Enabled',
                'content' => $value,
                'content_input_type' => 'radio',
                'group_setting' => 'Email Setting',
                'dataenum' => 'yes,no',
                'created_at' => now(),
            ]);
        }

        Cache::forget('setting_email_enabled');
    }

    private function smtpConfigurato(): void
    {
        Cache::forever('setting_smtp_driver', 'smtp');
        Cache::forever('setting_smtp_host', 'smtp.test');
    }

    public function test_il_flag_e_attivo_se_assente_vuoto_o_yes_e_spento_solo_con_no(): void
    {
        $this->setEmailEnabled(null);
        $this->assertTrue(CRUDBooster::isEmailEnabled());

        $this->setEmailEnabled('');
        $this->assertTrue(CRUDBooster::isEmailEnabled());

        $this->setEmailEnabled('yes');
        $this->assertTrue(CRUDBooster::isEmailEnabled());

        $this->setEmailEnabled('no');
        $this->assertFalse(CRUDBooster::isEmailEnabled());
    }

    public function test_la_migration_crea_il_setting_con_default_yes(): void
    {
        // RefreshDatabase esegue le migration: la riga e' quella creata da
        // AddEmailEnabledSetting (setUp l'ha poi cancellata, qui la ricreiamo
        // rieseguendo up() - e' idempotente).
        $migration = require_once base_path('database/migrations/2026_10_05_100000_add_email_enabled_setting.php');
        $migration = is_object($migration) ? $migration : new \AddEmailEnabledSetting();
        $migration->up();
        $migration->up();

        $this->assertSame(1, DB::table('cms_settings')->where('name', 'email_enabled')->count());
        $this->assertDatabaseHas('cms_settings', ['name' => 'email_enabled', 'content' => 'yes']);
    }

    public function test_con_il_flag_spento_send_email_non_invia_ne_accoda(): void
    {
        Mail::fake();
        $this->setEmailEnabled('no');

        $this->assertFalse(CRUDBooster::sendEmail(['to' => 'a@example.com', 'data' => [], 'template' => 'forgot_password_backend']));
        $this->assertFalse(CRUDBooster::sendEmail([
            'to' => 'a@example.com',
            'data' => [],
            'template' => 'forgot_password_backend',
            'send_at' => now()->addHour()->toDateTimeString(),
        ]));

        Mail::assertNothingSent();
        $this->assertSame(0, DB::table('cms_email_queues')->count());
    }

    public function test_con_il_flag_spento_lo_smtp_conta_come_non_configurato(): void
    {
        $this->smtpConfigurato();

        $this->setEmailEnabled('yes');
        $this->assertTrue(MfaHelper::isSmtpConfigured());

        $this->setEmailEnabled('no');
        $this->assertFalse(MfaHelper::isSmtpConfigured());
    }

    public function test_con_il_flag_spento_il_cambio_email_e_password_usa_la_sola_password(): void
    {
        $this->smtpConfigurato();
        $user = User::find($this->seedUser()['id']);

        $this->setEmailEnabled('yes');
        $this->assertSame('email', MfaHelper::emailChangeMode($user));

        $this->setEmailEnabled('no');
        $this->assertSame('none', MfaHelper::emailChangeMode($user));
    }

    public function test_con_il_flag_spento_non_viene_generato_il_codice_email_otp(): void
    {
        Mail::fake();
        $this->setEmailEnabled('no');
        $user = User::find($this->seedUser()['id']);

        $this->assertFalse(MfaHelper::sendEmailOtp($user));

        $this->assertSame(0, DB::table('mfa_email_otp_codes')->where('user_id', $user->id)->count());
        Mail::assertNothingSent();
    }

    public function test_con_il_flag_spento_password_dimenticata_mostra_il_messaggio_e_non_invia(): void
    {
        Mail::fake();
        $this->setEmailEnabled('no');
        $user = $this->seedUser(['email' => 'dimenticata@example.com']);

        $this->post('http://localhost/admin/forgot', ['email' => $user['email']])
            ->assertSessionHas('message', trans('crudbooster.email_disabled_notice'));

        $this->assertDatabaseMissing('password_resets', ['email' => 'dimenticata@example.com']);
        Mail::assertNothingSent();
    }

    public function test_con_il_flag_spento_il_reset_da_admin_risponde_409(): void
    {
        Mail::fake();
        $this->actingAsSuperadmin();
        $target = $this->seedUser(['email' => 'destinatario@example.com']);
        $this->setEmailEnabled('no');

        $this->postJson('http://localhost/admin/users/user-reset-password/' . $target['id'])
            ->assertStatus(409)
            ->assertJson(['ok' => false]);

        $this->assertDatabaseMissing('password_resets', ['email' => 'destinatario@example.com']);
        Mail::assertNothingSent();
    }
}
