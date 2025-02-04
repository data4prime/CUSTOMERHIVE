<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModuleHelperSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $modules = [
            ["Module" => "Users Management", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/gestione-degli-account-utente"],
            ["Module" => "Tenants", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/tenants"],
            ["Module" => "Statistic Builder", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/statistic-builder"],
            ["Module" => "Settings", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/settings"],
            ["Module" => "Qlik Items", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/qlik-items"],
            ["Module" => "Qlik Configuration", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/qlik"],
            ["Module" => "Qlik Apps", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/qlik-apps"],
            ["Module" => "Privileges Roles", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/ruoli-e-autorizzazioni"],
            ["Module" => "Privileges", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/ruoli-e-autorizzazioni"],
            ["Module" => "Notifications", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/navbar"],
            ["Module" => "Module Helpers", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/module-helper"],
            ["Module" => "Module Generator", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/module-generator"],
            ["Module" => "Menu Management", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/menu-management"],
            ["Module" => "", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/log-user-access"],
            ["Module" => "Groups", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/gruppi"],
            ["Module" => "Email Templates", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/email-templates"],
            ["Module" => "Dashboard Layouts", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/statistic-builder"],
            ["Module" => "Chat AI", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/chat-ai"],
            ["Module" => "API Generator", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/api-generator"]
        ];


        foreach($modules as $mod) {
            $m = DB::table('cms_moduls')
                      ->where('name', $mod["Module"])
                      ->first();

            if ($m) {
                $check = DB::table('module_helpers')
                      ->where('id_cms_moduls', $m->id)
                      ->where('url', $mod["Url"])
                      ->first();

                if (!$check) {
                    DB::table('module_helpers')->insert([
                        'url' => $mod["Url"],
                        'id_cms_moduls' =>  $m->id
                    ]);
                }
            }
        }


    }
}
