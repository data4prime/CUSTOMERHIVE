<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //$this->call(UsersTableSeeder::class);


        $this->call(CBSeeder::class);


        //$this->command->info('Qlik settings...');
        //$this->call(Qlik_Sett::class);


        $mod = DB::table('cms_moduls')->where('name', 'Qlik Configuration')->first();

        if (!$mod) {
            $mod = [
                    'created_at' => date('Y-m-d H:i:s'),
                    'name' => 'Qlik Configuration',
                    'icon' => 'fa fa-cog',
                    'path' => 'qlik_confs',
                    'table_name' => 'qlik_confs',
                    'controller' => 'QlikConfController',
                    'is_protected' => 1,
                    'is_active' => 1,
                    ];
            DB::table('cms_moduls')->insert($mod);  
        }

        $mod = DB::table('cms_moduls')->where('name', 'Qlik Apps')->first();

        if (!$mod) {
            $mod = [
                    'created_at' => date('Y-m-d H:i:s'),
                    'name' => 'Qlik Apps',
                    'icon' => 'fa fa-cog',
                    'path' => 'qlik_apps',
                    'table_name' => 'qlik_apps',
                    'controller' => 'QlikAppController',
                    'is_protected' => 1,
                    'is_active' => 1,
                ];
            DB::table('cms_moduls')->insert($mod);
        }
 
 
        $mod = DB::table('cms_moduls')->where('name', 'Dashboard Layouts')->first();

        if (!$mod) {
            $mod = [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'Dashboard Layouts',
                'icon' => 'fa fa-cog',
                'path' => 'dashboard_layouts',
                'table_name' => 'dashboard_layouts',
                'controller' => 'DashboardLayoutController',
                'is_protected' => 1,
                'is_active' => 1,
            ];
            DB::table('cms_moduls')->insert($mod); 
        }



        $mod = DB::table('cms_moduls')->where('name', 'Chat AI')->first();

        if (!$mod) {


        $mod =  [
                        'created_at' => date('Y-m-d H:i:s'),
                        'name' => 'Chat AI',
                        'icon' => '',
                        'path' => 'chat_ai',
                        'table_name' => 'chatai_confs',
                        'controller' => 'AdminChatAIController',
                        'is_protected' => 0,
                        'is_active' => 1,
                ];
            DB::table('cms_moduls')->insert($mod);
        }

    }
}
