<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use CRUDBooster;
use crocodicstudio\crudbooster\helpers\UserHelper;
use crocodicstudio\crudbooster\helpers\TenantHelper;


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

        $users_tenants = [];

        foreach ($users as $user) {

            $user->data_scadenza = date('d/m/Y', strtotime($user->data_scadenza));
            $tenant = UserHelper::tenant($user->id);
            $users_tenants[$tenant][] = $user;

            CRUDBooster::sendEmail(['to' => $user->email, 'data' => $user, 'template' => 'notifica_scadenza_utente_user']);
        
        }

        foreach ($users_tenants as $tenant => $users) {
            $tenant_admins = TenantHelper::getTenantAdmins($tenant);

            foreach ($tenant_admins as $admin) {

                $template = CRUDBooster::first('cms_email_templates', ['slug' => 'notifica_scadenza_utente_tenant']);

                $utenti_html = self::build_utenti_scadenza_tenantadmin($users_tenants[$tenant]);

                //replace |CUSTOMFUNCTION|TenantHelper::build_utenti_scadenza_tenantadmin|CUSTOMFUNCTIONEND| 
                $template->content = str_replace(
                    '|CUSTOMFUNCTION|TenantHelper::build_utenti_scadenza_tenantadmin|CUSTOMFUNCTIONEND|',
                    $utenti_html,
                    $template->content
                );

                //save the template with DB::update function
                DB::table('cms_email_templates')
                    ->where('id', $template->id)
                    ->update(['content' => $template->content]);


                CRUDBooster::sendEmail(['to' => $admin->email, 'data' => $users, 'template' => 'notifica_scadenza_utente_tenant']);

                 $template = CRUDBooster::first('cms_email_templates', ['slug' => 'notifica_scadenza_utente_tenant']);
                //restore the template  
                $template->content = str_replace(
                    $utenti_html,
                    '|CUSTOMFUNCTION|TenantHelper::build_utenti_scadenza_tenantadmin|CUSTOMFUNCTIONEND|',
                    $template->content
                );

                DB::table('cms_email_templates')
                    ->where('id', $template->id)
                    ->update(['content' => $template->content]);

            }


            CRUDBooster::sendEmail(['to' => $tenant->email, 'data' => $users, 'template' => 'notifica_scadenza_utente_tenant']);
        }

        return Command::SUCCESS;
    }

    public static function build_utenti_scadenza_tenantadmin($users) {

        //build table with following columns: name, email, data_scadenza 
        $table = "<table>";
        $table .= "<tr><th>Nome</th><th>Email</th><th>Data scadenza</th></tr>";
        foreach ($users as $user) {
            $table .= "<tr>";
            $table .= "<td>".$user->name."</td>";
            $table .= "<td>".$user->email."</td>";
            $table .= "<td>".$user->data_scadenza."</td>";
            $table .= "</tr>";
        }
        $table .= "</table>";

        return $table;

    }




}
