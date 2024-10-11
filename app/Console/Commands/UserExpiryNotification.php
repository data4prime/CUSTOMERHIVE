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
            ->where('status', 'Active')
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

                $template = CRUDBooster::first('cms_email_templates', ['slug' => 'notifica_scadenza_utente_tenantadmin']);

                $users_to_pass = $users_tenants[$tenant];

                //eliminate from users_to_pass the users that is_cms_privileges == 1
                foreach ($users_to_pass as $key => $user) {
                    if ($user->id_cms_privileges == 1) {
                        unset($users_to_pass[$key]);
                    }
                    if ($user->id_cms_privileges == 2) {
                        unset($users_to_pass[$key]);
                    }
                }

                $utenti_html = self::build_utenti_scadenza_tenantadmin($users_to_pass);

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


                CRUDBooster::sendEmail(['to' => $admin->email, 'data' => $admin, 'template' => 'notifica_scadenza_utente_tenantadmin']);

                 $template = CRUDBooster::first('cms_email_templates', ['slug' => 'notifica_scadenza_utente_tenantadmin']);
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


            //CRUDBooster::sendEmail(['to' => $tenant->email, 'data' => $users, 'template' => 'notifica_scadenza_utente_tenantadmin']);
        }


        $template = CRUDBooster::first('cms_email_templates', ['slug' => 'notifica_scadenza_utente_tenantadmin']);

        $utenti_html = self::build_utenti_scadenza_tenantadmin($users);

        $template->content = str_replace(
                    '|CUSTOMFUNCTION|TenantHelper::build_utenti_scadenza_tenantadmin|CUSTOMFUNCTIONEND|',
                    $utenti_html,
                    $template->content
        );

        //save the template with DB::update function
        DB::table('cms_email_templates')
                ->where('id', $template->id)
                ->update(['content' => $template->content]);

        $superadmins = DB::table('cms_users')
            ->where('id_cms_privileges', 1)
            ->where('status', 'Active')
            ->get();

        foreach ($superadmins as $superadmin) {
            CRUDBooster::sendEmail(['to' => $superadmin->email, 'data' => $superadmin, 'template' => 'notifica_scadenza_utente_tenantadmin']);
        }

        //restore template

        $template = CRUDBooster::first('cms_email_templates', ['slug' => 'notifica_scadenza_utente_tenantadmin']);
        //restore the template
        $template->content = str_replace(
            $utenti_html,
            '|CUSTOMFUNCTION|TenantHelper::build_utenti_scadenza_tenantadmin|CUSTOMFUNCTIONEND|',
            $template->content
        );


        DB::table('cms_email_templates')
            ->where('id', $template->id)
            ->update(['content' => $template->content]);






        return Command::SUCCESS;
    }

    public static function build_utenti_scadenza_tenantadmin($users) {

        //build table with following columns: name, email, data_scadenza 
        $table = "<table style='width: 100%; border-collapse: collapse; font-family: Arial, sans-serif;'>";
        $table .= "<tr style='background-color: #f2f2f2;'>";
        $table .= "<th style='width: 35%; padding: 12px; border-bottom: 2px solid #ddd;'>Nome</th>";
        $table .= "<th style='width: 35%; padding: 12px; border-bottom: 2px solid #ddd;'>Email</th>";
        $table .= "<th style='width: 30%; padding: 12px; border-bottom: 2px solid #ddd;'>Data scadenza</th>";
        $table .= "</tr>";

        foreach ($users as $index => $user) {
            $backgroundColor = ($index % 2 == 0) ? "#f9f9f9" : "#ffffff"; // Colore alternato per le righe
            $table .= "<tr style='background-color: $backgroundColor;'>";
            $table .= "<td style='padding: 12px; border-bottom: 1px solid #ddd;'>".$user->name."</td>";
            $table .= "<td style='padding: 12px; border-bottom: 1px solid #ddd;'>".$user->email."</td>";
            $table .= "<td style='padding: 12px; border-bottom: 1px solid #ddd;'>".$user->data_scadenza."</td>";
            $table .= "</tr>";
        }

        $table .= "</table>";

        return $table;

    }




}
