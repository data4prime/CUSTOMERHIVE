<?php

namespace Tests\Concerns;

use App\Helpers\MfaHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Helper condiviso per simulare POST /admin/login nei test.
 *
 * Due dettagli non ovvi, entrambi necessari per riprodurre fedelmente il
 * comportamento reale:
 * - va passato come URL assoluto (non con un header 'Host' separato): con
 *   il routing dinamico di CRUDBooster (registra le route ad ogni
 *   richiesta), un semplice header Host senza URL assoluto produce un 404
 *   invece di raggiungere il controller.
 * - AdminController::postLogin() legge il dominio da $_SERVER['HTTP_HOST']
 *   DIRETTAMENTE (non da Request::getHost()) — il client di test di
 *   Laravel non sincronizza quella superglobale con l'URL simulato, va
 *   quindi impostata a mano. In produzione (richiesta HTTP reale via
 *   Apache) non serve, $_SERVER è sempre popolato correttamente: è solo un
 *   problema di testabilità di questo pezzo di codice, da tenere presente
 *   per il futuro refactoring dell'auth.
 */
trait LogsInAdmin
{
    /**
     * Simula un dispositivo gia' fidato (stesso meccanismo di
     * MfaHelper::issueTrustedDeviceCookie(), vedi docs/refactoring/097-*)
     * per l'utente indicato, cosi' un postLoginFrom() successivo salta lo
     * step-up email OTP - questi test verificano il comportamento del
     * login "di base" (sessione/guard popolati), non il gate MFA in se',
     * che ha una copertura manuale separata (vedi docs/refactoring/097-*
     * e 098-*).
     */
    protected function trustDeviceFor(array $user): void
    {
        $selector = Str::random(16);
        $validator = Str::random(32);

        DB::table('mfa_trusted_devices')->insert([
            'user_id' => $user['id'],
            'selector' => $selector,
            'validator_hash' => hash('sha256', $validator),
            'user_agent' => 'phpunit',
            'trusted_until' => now()->addDays(MfaHelper::TRUSTED_DEVICE_DAYS),
            'last_used_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withCookie(MfaHelper::TRUSTED_DEVICE_COOKIE, $selector.':'.$validator);
    }

    protected function postLoginFrom(?string $host, array $data)
    {
        $host = $host ?: parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';

        $previousHost = $_SERVER['HTTP_HOST'] ?? null;
        $_SERVER['HTTP_HOST'] = $host;

        try {
            return $this->post("http://{$host}/admin/login", $data);
        } finally {
            if ($previousHost === null) {
                unset($_SERVER['HTTP_HOST']);
            } else {
                $_SERVER['HTTP_HOST'] = $previousHost;
            }
        }
    }
}
