<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\InstanceCommandSupport;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Nuovo link per impostare la password di un utente esistente e attivo (il
 * pulsante "Reinvia link" del Control Plane, che poi spedisce l'email). Il
 * broker sostituisce i token precedenti dello stesso utente, quindi il link
 * vecchio smette di funzionare. TTL: quello standard dei reset (60 minuti,
 * config/auth.php), il flusso "password dimenticata" non viene toccato.
 * Solo da riga di comando: non e' esposto via HTTP. Vedi docs/refactoring/299-*.
 */
class InstancePasswordLink extends Command
{
    use InstanceCommandSupport;

    protected $signature = 'instance:password-link
                            {--admin-email= : Email dell\'utente (obbligatoria)}';

    protected $description = 'Genera un nuovo link per impostare la password di un utente esistente (ultima riga di stdout: LINK=<url>)';

    public function handle(): int
    {
        $email = trim((string) $this->option('admin-email'));
        if ($email === '') {
            $this->err('ERRORE: --admin-email obbligatoria.');

            return self::EXIT_ERROR;
        }

        try {
            $user = User::where('email', $email)->first();
            if (! $user) {
                $this->err('ERRORE: nessun utente con questa email.');

                return self::EXIT_USER_NOT_FOUND;
            }
            if ($user->status !== 'Active') {
                $this->err('ERRORE: l\'utente non e\' attivo.');

                return self::EXIT_USER_INACTIVE;
            }

            $this->printLink($this->createPasswordLink($user));

            return self::EXIT_OK;
        } catch (\Throwable $e) {
            $this->err('ERRORE: ' . $this->safeError($e));

            return self::EXIT_ERROR;
        }
    }
}
