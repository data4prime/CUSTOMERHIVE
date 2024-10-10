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

        $users_sql =  DB::table('cms_users')
            ->where('data_scadenza', '!=', '0000-00-00')
            ->where('data_scadenza', '!=', '')
            ->whereNotNull('data_scadenza')
            ->where(function ($query) {
                $query->where('data_scadenza', '=', now()->addDays(30)->toDateString())
                    ->orWhere('data_scadenza', '=', now()->addDays(7)->toDateString())
                    ->orWhere('data_scadenza', '=', now()->addDay()->toDateString());
            })
            ->select(
                'cms_users.*', 
                DB::raw('DATEDIFF(data_scadenza, NOW()) as giorni_mancanti')
            )->toSql();

        file_put_contents(__DIR__ . '/users.sql', $users_sql);



        $users = DB::table('cms_users')
            ->where('data_scadenza', '!=', '0000-00-00')
            ->where('data_scadenza', '!=', '')
            ->whereNotNull('data_scadenza')
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

        file_put_contents(__DIR__ . '/users.json', json_encode($users));

        foreach ($users as $user) {
            CRUDBooster::sendEmail(['to' => $user->email, 'data' => $user, 'template' => 'notifica_scadenza_utente_user']);
            

        }

        return Command::SUCCESS;
    }
}
