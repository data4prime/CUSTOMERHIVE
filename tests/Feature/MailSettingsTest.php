<?php

namespace Tests\Feature;

use App\Helpers\MailSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * L'unico SMTP e' quello dei Settings (docs/refactoring/294-*):
 * MailSettings costruisce il mailer, config/mail.php non esiste.
 */
class MailSettingsTest extends TestCase
{
    use RefreshDatabase;

    private const SETTINGS = [
        'smtp_driver', 'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password',
        'tls_ssl', 'email_sender', 'appname',
    ];

    protected function tearDown(): void
    {
        foreach (self::SETTINGS as $name) {
            Cache::forget('setting_'.$name);
        }

        parent::tearDown();
    }

    private function setting(string $name, $value): void
    {
        Cache::forever('setting_'.$name, $value);
    }

    private function mailer(string $name): array
    {
        return config('mail.mailers.'.$name);
    }

    public function test_config_mail_php_non_esiste_piu(): void
    {
        $this->assertFileDoesNotExist(base_path('config/mail.php'));
    }

    public function test_costruisce_il_mailer_dai_settings_salvati(): void
    {
        $this->setting('smtp_driver', 'smtp');
        $this->setting('smtp_host', 'smtp.cliente.test');
        $this->setting('smtp_port', '587');
        $this->setting('smtp_username', 'invii@cliente.test');
        $this->setting('smtp_password', 'segreto');
        $this->setting('appname', 'Cliente SRL');

        $name = MailSettings::fromSettings();

        $this->assertSame($name, config('mail.default'));
        $mailer = $this->mailer($name);
        $this->assertSame('smtp', $mailer['transport']);
        $this->assertSame('smtp.cliente.test', $mailer['host']);
        $this->assertSame(587, $mailer['port']);
        $this->assertSame('invii@cliente.test', $mailer['username']);
        $this->assertSame('segreto', $mailer['password']);
        $this->assertNull($mailer['scheme']);
        $this->assertSame('Cliente SRL', config('mail.from.name'));
    }

    public function test_il_mittente_e_email_sender_poi_lusername_se_e_un_indirizzo(): void
    {
        $this->setting('smtp_driver', 'smtp');
        $this->setting('smtp_host', 'smtp.cliente.test');
        $this->setting('smtp_username', 'invii@cliente.test');

        $this->setting('email_sender', 'noreply@cliente.test');
        MailSettings::fromSettings();
        $this->assertSame('noreply@cliente.test', config('mail.from.address'));

        $this->setting('email_sender', 'non-un-indirizzo');
        MailSettings::fromSettings();
        $this->assertSame('invii@cliente.test', config('mail.from.address'));

        $this->setting('smtp_username', 'utente-senza-dominio');
        MailSettings::fromSettings();
        $this->assertNull(config('mail.from.address'));
    }

    public function test_tls_ssl_diventa_lo_scheme_dello_smtp(): void
    {
        $this->setting('smtp_driver', 'smtp');
        $this->setting('smtp_host', 'smtp.cliente.test');

        $this->setting('tls_ssl', 'ssl');
        $this->assertSame('smtps', $this->mailer(MailSettings::fromSettings())['scheme']);

        $this->setting('tls_ssl', 'TLS');
        $this->assertSame('smtp', $this->mailer(MailSettings::fromSettings())['scheme']);

        $this->setting('tls_ssl', '');
        $this->assertNull($this->mailer(MailSettings::fromSettings())['scheme']);
    }

    public function test_un_cambio_di_settings_produce_un_mailer_diverso(): void
    {
        $this->setting('smtp_driver', 'smtp');
        $this->setting('smtp_host', 'smtp.uno.test');
        $first = MailSettings::fromSettings();

        $this->setting('smtp_host', 'smtp.due.test');
        $second = MailSettings::fromSettings();

        $this->assertNotSame($first, $second);
        $this->assertSame('smtp.due.test', $this->mailer($second)['host']);
    }

    public function test_senza_settings_il_mailer_non_ha_un_trasporto_valido(): void
    {
        // Nessun SMTP configurato: nessun valore di ripiego (niente host o
        // credenziali nascosti in un file di config).
        $mailer = $this->mailer(MailSettings::fromSettings());

        $this->assertSame('', $mailer['transport']);
        $this->assertSame('', $mailer['host']);
        $this->assertNull($mailer['username']);
        $this->assertNull($mailer['password']);
    }

    public function test_configure_usa_i_valori_dati_senza_leggere_i_settings(): void
    {
        $this->setting('smtp_host', 'smtp.salvato.test');

        $name = MailSettings::configure([
            'driver' => 'smtp', 'host' => 'smtp.dal-form.test', 'port' => 465,
            'encryption' => 'SSL', 'from_email' => 'prova@cliente.test', 'from_name' => 'Prova',
        ]);

        $mailer = $this->mailer($name);
        $this->assertSame('smtp.dal-form.test', $mailer['host']);
        $this->assertSame(465, $mailer['port']);
        $this->assertSame('smtps', $mailer['scheme']);
        $this->assertSame('prova@cliente.test', config('mail.from.address'));
    }
}
