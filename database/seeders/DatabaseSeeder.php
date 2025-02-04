<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use Database\Seeders\GroupSeeder;

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
        $this->call(GroupSeeder::class);
        $this->call(TenantSeeder::class);
        $this->call(Cms_statistics::class);
        $this->call(Cms_menus::class);
        $this->call(Cms_usersSeeder::class);
        $this->call(Cms_menusGroups::class);
        $this->call(Cms_menusTenants::class);
        $this->call(Cms_groupTenants::class);
        $this->call(Cms_usersGroups::class);
        $this->call(Cms_modulsSeeder::class);
        $this->call(Cms_privilegesSeeder::class);
        $this->call(Cms_privileges_rolesSeeder::class);
        $this->call(Cms_settingsSeeder::class);
        $this->call(CmsEmailTemplates::class);
        $this->call(Cms_menusPrivileges::class);
        $this->call(QlikSett::class);
        $this->call(QlikConf::class);
        

        //$this->command->info('Qlik Configuration...');


    }
}
