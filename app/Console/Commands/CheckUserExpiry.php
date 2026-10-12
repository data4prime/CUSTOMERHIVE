<?php

namespace App\Console\Commands;

use App\Helpers\UserHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckUserExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:check-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check user expiry dates';

    /**
     * Disattiva gli utenti attivi con data_scadenza passata.
     *
     * data_scadenza e' una colonna date confrontata con now(): alla
     * mezzanotte del giorno di scadenza l'utente risulta gia' scaduto (vedi
     * docs/refactoring/292-* e 293-*). '0000-00-00' (data zero legacy) e NULL
     * non sono scadenze. Se tutti i superadmin sono scaduti restano attivi
     * (UserHelper::expiredActiveUserIds()).
     *
     * @return int
     */
    public function handle()
    {
        $ids = UserHelper::expiredActiveUserIds();

        if (empty($ids)) {
            $this->comment('Nessun utente scaduto da disattivare.');

            return Command::SUCCESS;
        }

        DB::table('cms_users')
            ->whereIn('id', $ids)
            ->update(['status' => 'Inactive', 'updated_at' => now()]);

        Log::info('user:check-expiry: utenti disattivati per scadenza: '.implode(',', $ids));
        $this->comment('Utenti disattivati per scadenza: '.count($ids));

        return Command::SUCCESS;
    }
}
