<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        /*
                $users = DB::table('cms_users')->('data_scadenza', '!=', '0000-00-00')->('data_scadenza', '!=', '')->whereNotNull('data_scadenza')->where('data_scadenza', '<', now())->get();

        foreach ($users as $user) {
            DB::table('cms_users')->where('id', $user->id)->update(['status' => 'Inactive']);
        }*/
        $users = DB::table('cms_users')
            ->where('data_scadenza', '!=', '0000-00-00')
            ->where('data_scadenza', '!=', '')
            ->whereNotNull('data_scadenza')
            ->where('data_scadenza', '<', now())
            ->get();

        foreach ($users as $user) {
            DB::table('cms_users')
                ->where('id', $user->id)
                ->update(['status' => 'Inactive']);
        }
    }
}
