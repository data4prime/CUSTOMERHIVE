<?php

namespace App\Console\Commands\Concerns;

use App\Helpers\CRUDBooster;
use App\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Password;

/**
 * Parti comuni di instance:bootstrap e instance:password-link, i due comandi
 * che il Control Plane lancia con "docker compose exec" (vedi
 * docs/refactoring/299-*).
 *
 * Contratto di uscita: l'ULTIMA riga di stdout e' esattamente "LINK=<url>";
 * tutto il resto (avanzamento, errori) va su stderr. Il token non viene mai
 * scritto in nessun log: ne' laravel.log ne' cms_logs (niente
 * CRUDBooster::insertLog), e nei messaggi d'errore non finiscono mai le query
 * con i loro parametri.
 */
trait InstanceCommandSupport
{
    /** Tutto e' andato bene. */
    protected const EXIT_OK = 0;

    /** Errore generico (input non valido, DB, eccezione imprevista). */
    protected const EXIT_ERROR = 1;

    /** instance:bootstrap: l'istanza e' gia' inizializzata, nulla e' stato modificato. */
    protected const EXIT_ALREADY_INITIALIZED = 2;

    /** instance:password-link: nessun utente con quell'email. */
    protected const EXIT_USER_NOT_FOUND = 3;

    /** instance:password-link: l'utente esiste ma non e' attivo. */
    protected const EXIT_USER_INACTIVE = 4;

    /** Messaggio di avanzamento/errore: sempre su stderr, mai su stdout. */
    protected function err(string $message): void
    {
        fwrite(STDERR, $message . PHP_EOL);
    }

    /**
     * Crea il token col broker nativo (tabella password_resets, TTL di
     * config/auth.php, il broker sostituisce i token precedenti dello stesso
     * utente) SENZA inviare email ne' passare dalla Notification di default,
     * e costruisce l'URL come AdminController::postForgot().
     * Da CLI url() parte da APP_URL (SetRequestForConsole).
     */
    protected function createPasswordLink(User $user): string
    {
        $token = Password::broker()->createToken($user);

        return CRUDBooster::adminPath('reset-password/' . $token) . '?email=' . urlencode($user->email);
    }

    /** L'unica riga su stdout, l'ultima. */
    protected function printLink(string $url): void
    {
        $this->line('LINK=' . $url);
    }

    /**
     * Descrizione di un'eccezione senza dati sensibili: QueryException
     * include nel messaggio l'SQL con i parametri (chiave di licenza, hash
     * della password, hash del token), quindi si usa solo il messaggio del driver.
     */
    protected function safeError(\Throwable $e): string
    {
        if ($e instanceof QueryException) {
            $driver = $e->errorInfo[2] ?? 'errore SQL';

            return get_class($e) . ': ' . $driver;
        }

        return get_class($e) . ': ' . $e->getMessage();
    }
}
