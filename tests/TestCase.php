<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Mail;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Rete di sicurezza: nessun test deve mai poter spedire una email
        // vera. L'SMTP si configura solo dai Settings (App\Helpers\MailSettings,
        // non esiste config/mail.php): senza questo, un test che salva
        // impostazioni SMTP reali e poi chiama sendEmail() spedirebbe davvero.
        Mail::fake();
    }
}
