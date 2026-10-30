<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\InstanceCommandSupport;
use App\Models\User;
use Database\Seeders\InstanceBaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Primo avvio di un'istanza di produzione, lanciato UNA volta
 * dall'orchestratore con "docker compose exec -T app php artisan
 * instance:bootstrap ..." quando l'app e' healthy (vedi docs/refactoring/299-*).
 * Solo da riga di comando: non e' esposto via HTTP.
 *
 * Istanza "gia' inizializzata" = esiste almeno un utente in cms_users OPPURE
 * almeno una riga in license. Tutte le scritture stanno in UNA transazione
 * (solo DML, nessun commit implicito di MySQL): se il comando non arriva in
 * fondo non resta scritto niente, quindi il segnale non puo' risultare vero
 * a meta'. Un lock MySQL (GET_LOCK) evita due bootstrap in parallelo.
 *
 * Password e chiave di licenza arrivano solo come argomenti del comando (non
 * da variabili d'ambiente); la password dell'admin e' casuale, hashata, mai
 * stampata ne' loggata; il token del link non e' scritto in nessun log.
 */
class InstanceBootstrap extends Command
{
    use InstanceCommandSupport;

    protected $signature = 'instance:bootstrap
                            {--admin-email= : Email del superadmin (obbligatoria)}
                            {--license-key= : Chiave di licenza, UUID (obbligatoria)}';

    protected $description = 'Primo avvio di un\'istanza nuova: dati di base, superadmin, licenza e link per impostare la password (ultima riga di stdout: LINK=<url>)';

    private const LOCK_NAME = 'customerhive_instance_bootstrap';

    public function handle(): int
    {
        $email = trim((string) $this->option('admin-email'));
        $licenseKey = trim((string) $this->option('license-key'));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $this->err('ERRORE: --admin-email obbligatoria e deve essere un indirizzo email valido.');

            return self::EXIT_ERROR;
        }
        if (! Str::isUuid($licenseKey)) {
            $this->err('ERRORE: --license-key obbligatoria e deve essere un UUID valido.');

            return self::EXIT_ERROR;
        }

        try {
            $locked = (int) (DB::selectOne('SELECT GET_LOCK(?, 0) AS l', [self::LOCK_NAME])->l ?? 0) === 1;
            if (! $locked) {
                $this->err('ERRORE: un altro instance:bootstrap e\' in corso.');

                return self::EXIT_ERROR;
            }

            try {
                return $this->bootstrap($email, $licenseKey);
            } finally {
                DB::select('SELECT RELEASE_LOCK(?)', [self::LOCK_NAME]);
            }
        } catch (\Throwable $e) {
            // Nessun report() all'handler (laravel.log): le eccezioni SQL
            // contengono i parametri. Il messaggio su stderr e' ripulito.
            $this->err('ERRORE: bootstrap non completato, nessuna modifica salvata. ' . $this->safeError($e));

            return self::EXIT_ERROR;
        }
    }

    private function bootstrap(string $email, string $licenseKey): int
    {
        $url = DB::transaction(function () use ($email, $licenseKey) {
            if (DB::table('cms_users')->count() > 0 || DB::table('license')->count() > 0) {
                return null;
            }

            $this->err('Dati di base (privilegi, menu, moduli, impostazioni, template email)...');
            $seeder = new InstanceBaseSeeder();
            $seeder->run();

            $this->err('Superadmin...');
            $privilegeId = DB::table('cms_privileges')->where('is_superadmin', 1)->orderBy('id')->value('id');
            $tenantId = DB::table('tenants')->orderBy('id')->value('id');
            $groupId = DB::table('groups')->orderBy('id')->value('id');
            if (! $privilegeId || ! $tenantId || ! $groupId) {
                throw new \RuntimeException('dati di base incompleti (privilegio superadmin, tenant o gruppo mancanti)');
            }

            // Stessi campi dell'utente di Cms_usersSeeder, ma password casuale
            // lunga: l'admin la imposta dal link.
            DB::table('cms_users')->insert([
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'Super Admin',
                'email' => $email,
                'password' => Hash::make(Str::random(64)),
                'id_cms_privileges' => $privilegeId,
                'status' => 'Active',
                'primary_group' => $groupId,
                'tenant' => $tenantId,
            ]);
            $seeder->linkUsersToGroups();

            // Solo la chiave: niente chiamata al license server (al primo login
            // l'app scarica il resto da sola) e niente license.json.
            $this->err('Licenza...');
            DB::table('license')->insert(['license_key' => $licenseKey]);

            $this->err('Link per impostare la password...');

            return $this->createPasswordLink(User::where('email', $email)->firstOrFail());
        });

        if ($url === null) {
            $this->err('ISTANZA GIA\' INIZIALIZZATA (esistono utenti in cms_users o una riga in license): nessuna modifica.');

            return self::EXIT_ALREADY_INITIALIZED;
        }

        $this->printLink($url);

        return self::EXIT_OK;
    }
}
