<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use CRUDBooster;

class UserExpiryNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:expiry-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notification to users whose account is about to expire';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {



        $users = DB::table('cms_users')

            ->where(function ($query) {
                $query->where('data_scadenza', '=', now()->addDays(30)->toDateString())
                    ->orWhere('data_scadenza', '=', now()->addDays(7)->toDateString())
                    ->orWhere('data_scadenza', '=', now()->addDay()->toDateString());
            })
            ->select(
                'cms_users.*', 
                DB::raw('DATEDIFF(data_scadenza, NOW()) as giorni_mancanti')
            )
            ->get();

        foreach ($users as $user) {
            CRUDBooster::sendEmail(['to' => $user->email, 'data' => $user, 'template' => 'notifica_scadenza_utente_user']);
            

        }

        return Command::SUCCESS;
    }
}
