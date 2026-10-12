<?php

namespace App\Helpers;

/**
 * Unica fonte della configurazione di invio email: i Settings dell'interfaccia
 * (Email Setting). config/mail.php non esiste piu': il mailer viene costruito
 * qui, a ogni invio, dai valori salvati (o, per il test email, da quelli del
 * form). Vedi docs/refactoring/294-*.
 */
class MailSettings
{
    /**
     * Configura il mailer di default dai Settings salvati e ne ritorna il nome.
     *
     * Mittente: setting email_sender, se non valido l'username SMTP quando e'
     * un indirizzo email; un template con from_email lo sovrascrive comunque.
     */
    public static function fromSettings(): string
    {
        $username = trim((string) CRUDBooster::getSetting('smtp_username'));
        $sender = trim((string) CRUDBooster::getSetting('email_sender'));

        if (! filter_var($sender, FILTER_VALIDATE_EMAIL)) {
            $sender = filter_var($username, FILTER_VALIDATE_EMAIL) ? $username : '';
        }

        return self::configure([
            'driver' => CRUDBooster::getSetting('smtp_driver'),
            'host' => CRUDBooster::getSetting('smtp_host'),
            'port' => CRUDBooster::getSetting('smtp_port'),
            'username' => $username,
            'password' => CRUDBooster::getSetting('smtp_password'),
            'encryption' => CRUDBooster::getSetting('tls_ssl'),
            'from_email' => $sender,
            'from_name' => CRUDBooster::getSetting('appname') ?: 'CustomerHive',
        ]);
    }

    /**
     * Imposta mail.default / mail.mailers.<nome> / mail.from con i valori
     * dati e ritorna il nome del mailer. Il nome dipende dai valori: un
     * cambio di impostazioni produce un mailer nuovo invece di riusare
     * quello gia' risolto e messo in cache dal MailManager.
     *
     * @param array $values driver, host, port, username, password,
     *   encryption ('SSL' => smtps, 'TLS' => smtp, altro => dalla porta),
     *   from_email, from_name
     */
    public static function configure(array $values): string
    {
        $encryption = strtoupper(trim((string) ($values['encryption'] ?? '')));
        $scheme = $encryption === 'SSL' ? 'smtps' : ($encryption === 'TLS' ? 'smtp' : null);

        $port = trim((string) ($values['port'] ?? ''));
        $username = trim((string) ($values['username'] ?? ''));
        $password = (string) ($values['password'] ?? '');

        $mailer = [
            'transport' => trim((string) ($values['driver'] ?? '')),
            'host' => trim((string) ($values['host'] ?? '')),
            'port' => $port !== '' ? (int) $port : null,
            'username' => $username !== '' ? $username : null,
            'password' => $password !== '' ? $password : null,
            'scheme' => $scheme,
            'path' => '/usr/sbin/sendmail -bs',
        ];

        $name = 'ch_'.substr(md5(json_encode($mailer)), 0, 12);

        $fromEmail = trim((string) ($values['from_email'] ?? ''));

        config([
            'mail.default' => $name,
            'mail.mailers.'.$name => $mailer,
            'mail.from' => [
                'address' => $fromEmail !== '' ? $fromEmail : null,
                'name' => $values['from_name'] ?? null,
            ],
        ]);

        return $name;
    }
}
