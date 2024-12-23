<?php

use Illuminate\Database\Seeder;



class CBSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Please wait updating the data...');


                
        $this->call('GroupSeeder');
        $this->call('TenantSeeder');
        $this->call('Cms_statistics');
        $this->call('Cms_menus');
        $this->call('Cms_usersSeeder');


        $this->call('Cms_menusGroups');
        $this->call('Cms_menusTenants');
        $this->call('Cms_groupTenants');

        $this->call('Cms_usersGroups');

        $this->call('Cms_modulsSeeder');
        $this->call('Cms_privilegesSeeder');
        $this->call('Cms_privileges_rolesSeeder');

        $this->call('Cms_settingsSeeder');
        $this->call('CmsEmailTemplates');
        $this->call('Cms_menusPrivileges');


        $this->call('ModuleHelpersSeeder');



        //$this->call('QlikSett');




        $this->command->info('Updating the data completed !');
    }
}

class CmsEmailTemplates extends Seeder
{
    public function run()
    {

        $check = DB::table('cms_email_templates')->where('slug', 'forgot_password_backend')->first();

        if ($check) {
            DB::table('cms_email_templates')->insert([
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'Email Template Forgot Password Backend',
                'slug' => 'forgot_password_backend',
                'content' => '<p>Hi,</p><p>Someone requested forgot password, here is your new password : </p><p>[password]</p><p><br></p><p>--</p><p>Regards,</p><p>Admin</p>',
                'description' => '[password]',
                'from_name' => 'System',
                'from_email' => 'system@crudbooster.com',
                'cc_email' => null,
            ]);
        }

    }
}

class Cms_settingsSeeder extends Seeder
{
    public function run()
    {

        $data = [

            //LOGIN REGISTER STYLE
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'login_background_color',
                'label' => 'Login Background Color',
                'content' => null,
                'content_input_type' => 'text',
                'group_setting' => trans('crudbooster.login_register_style'),
                'dataenum' => null,
                'helper' => 'Input hexacode',
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'login_font_color',
                'label' => 'Login Font Color',
                'content' => null,
                'content_input_type' => 'text',
                'group_setting' => trans('crudbooster.login_register_style'),
                'dataenum' => null,
                'helper' => 'Input hexacode',
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'login_background_image',
                'label' => 'Login Background Image',
                'content' => null,
                'content_input_type' => 'upload_image',
                'group_setting' => trans('crudbooster.login_register_style'),
                'dataenum' => null,
                'helper' => null,
            ],

            //EMAIL SETTING
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'email_sender',
                'label' => 'Email Sender',
                'content' => 'support@crudbooster.com',
                'content_input_type' => 'text',
                'group_setting' => trans('crudbooster.email_setting'),
                'dataenum' => null,
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'smtp_driver',
                'label' => 'Mail Driver',
                'content' => 'mail',
                'content_input_type' => 'select',
                'group_setting' => trans('crudbooster.email_setting'),
                'dataenum' => 'smtp,mail,sendmail',
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'smtp_host',
                'label' => 'SMTP Host',
                'content' => '',
                'content_input_type' => 'text',
                'group_setting' => trans('crudbooster.email_setting'),
                'dataenum' => null,
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'smtp_port',
                'label' => 'SMTP Port',
                'content' => '25',
                'content_input_type' => 'text',
                'group_setting' => trans('crudbooster.email_setting'),
                'dataenum' => null,
                'helper' => 'default 25',
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'smtp_username',
                'label' => 'SMTP Username',
                'content' => '',
                'content_input_type' => 'text',
                'group_setting' => trans('crudbooster.email_setting'),
                'dataenum' => null,
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'smtp_password',
                'label' => 'SMTP Password',
                'content' => '',
                'content_input_type' => 'text',
                'group_setting' => trans('crudbooster.email_setting'),
                'dataenum' => null,
                'helper' => null,
            ],

            //APPLICATION SETTING
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'appname',
                'label' => 'Application Name',
                'group_setting' => trans('crudbooster.application_setting'),
                'content' => 'CustomerHive',
                'content_input_type' => 'text',
                'dataenum' => null,
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'default_paper_size',
                'label' => 'Default Paper Print Size',
                'group_setting' => trans('crudbooster.application_setting'),
                'content' => 'Legal',
                'content_input_type' => 'text',
                'dataenum' => null,
                'helper' => 'Paper size, ex : A4, Legal, etc',
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'logo',
                'label' => 'Logo',
                'content' => '',
                'content_input_type' => 'upload_image',
                'group_setting' => trans('crudbooster.application_setting'),
                'dataenum' => null,
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'favicon',
                'label' => 'Favicon',
                'content' => '',
                'content_input_type' => 'upload_image',
                'group_setting' => trans('crudbooster.application_setting'),
                'dataenum' => null,
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'api_debug_mode',
                'label' => 'API Debug Mode',
                'content' => 'true',
                'content_input_type' => 'select',
                'group_setting' => trans('crudbooster.application_setting'),
                'dataenum' => 'true,false',
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'google_api_key',
                'label' => 'Google API Key',
                'content' => '',
                'content_input_type' => 'text',
                'group_setting' => trans('crudbooster.application_setting'),
                'dataenum' => null,
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'google_fcm_key',
                'label' => 'Google FCM Key',
                'content' => '',
                'content_input_type' => 'text',
                'group_setting' => trans('crudbooster.application_setting'),
                'dataenum' => null,
                'helper' => null,
            ],


        ];

        foreach ($data as $row) {
            $count = DB::table('cms_settings')->where('name', $row['name'])->count();
            if ($count) {
                if ($count > 1) {
                    $newsId = DB::table('cms_settings')->where('name', $row['name'])->orderby('id', 'asc')->take(1)->first();
                    DB::table('cms_settings')->where('name', $row['name'])->where('id', '!=', $newsId->id)->delete();
                }
                continue;
            }
            DB::table('cms_settings')->insert($row);
        }
            
    }
}

class Cms_privileges_rolesSeeder extends Seeder
{
    public function run()
    {

        if (DB::table('cms_privileges_roles')->count() == 0) {
            $modules = DB::table('cms_moduls')->get();
            $i = 1;
            foreach ($modules as $module) {

                $is_visible = 1;
                $is_create = 1;
                $is_read = 1;
                $is_edit = 1;
                $is_delete = 1;

                switch ($module->table_name) {
                    case 'cms_logs':
                        $is_create = 0;
                        $is_edit = 0;
                        break;
                    case 'cms_privileges_roles':
                        $is_visible = 0;
                        break;
                    case 'cms_apicustom':
                        $is_visible = 0;
                        break;
                    case 'cms_notifications':
                        $is_create = $is_read = $is_edit = $is_delete = 0;
                        break;
                }

                DB::table('cms_privileges_roles')->insert([
                    'created_at' => date('Y-m-d H:i:s'),
                    'is_visible' => $is_visible,
                    'is_create' => $is_create,
                    'is_edit' => $is_edit,
                    'is_delete' => $is_delete,
                    'is_read' => $is_read,
                    'id_cms_privileges' => 1,
                    'id_cms_moduls' => $module->id,
                ]);
                $i++;
            }
        }
    }
}

class Cms_privilegesSeeder extends Seeder
{
    public function run()
    {

        if (DB::table('cms_privileges')->where('name', 'Super Administrator')->count() == 0) {
            DB::table('cms_privileges')->insert([
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'Super Administrator',
                'is_superadmin' => 1,
                'theme_color' => 'skin-red',
            ]);
        }

        if (DB::table('cms_privileges')->where('name', 'Tenant Administrator')->count() == 0) {
            DB::table('cms_privileges')->insert([
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'Tenant Administrator',
                'is_tenantadmin' => 1,
                'theme_color' => 'skin-blue',
            ]);
        }

        if (DB::table('cms_privileges')->where('name', 'Basic')->count() == 0) {
            DB::table('cms_privileges')->insert([
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'Basic',
                'theme_color' => 'skin-grey',
            ]);
        }


    }
}

class Cms_modulsSeeder extends Seeder
{
    public function run()
    {

        /* 
            1 = Public
            2 = Setting        
        */

        $data = [
            [

                'created_at' => date('Y-m-d H:i:s'),
                'name' => trans('crudbooster.Notifications'),
                'icon' => 'fa fa-cog',
                'path' => 'notifications',
                'table_name' => 'cms_notifications',
                'controller' => 'NotificationsController',
                'is_protected' => 1,
                'is_active' => 1,
            ],
            [

                'created_at' => date('Y-m-d H:i:s'),
                'name' => trans('crudbooster.Privileges'),
                'icon' => 'fa fa-cog',
                'path' => 'privileges',
                'table_name' => 'cms_privileges',
                'controller' => 'PrivilegesController',
                'is_protected' => 1,
                'is_active' => 1,
            ],
            [

                'created_at' => date('Y-m-d H:i:s'),
                'name' => trans('crudbooster.Privileges_Roles'),
                'icon' => 'fa fa-cog',
                'path' => 'privileges_roles',
                'table_name' => 'cms_privileges_roles',
                'controller' => 'PrivilegesRolesController',
                'is_protected' => 1,
                'is_active' => 1,
            ],
            [

                'created_at' => date('Y-m-d H:i:s'),
                'name' => trans('crudbooster.Users_Management'),
                'icon' => 'fa fa-users',
                'path' => 'users',
                'table_name' => 'cms_users',
                'controller' => 'AdminCmsUsersController',
                'is_protected' => 0,
                'is_active' => 1,
            ],
            [

                'created_at' => date('Y-m-d H:i:s'),
                'name' => trans('crudbooster.settings'),
                'icon' => 'fa fa-cog',
                'path' => 'settings',
                'table_name' => 'cms_settings',
                'controller' => 'SettingsController',
                'is_protected' => 1,
                'is_active' => 1,
            ],
            [

                'created_at' => date('Y-m-d H:i:s'),
                'name' => trans('crudbooster.Module_Generator'),
                'icon' => 'fa fa-database',
                'path' => 'module_generator',
                'table_name' => 'cms_moduls',
                'controller' => 'ModulsController',
                'is_protected' => 1,
                'is_active' => 1,
            ],
            [

                'created_at' => date('Y-m-d H:i:s'),
                'name' => trans('crudbooster.Menu_Management'),
                'icon' => 'fa fa-bars',
                'path' => 'menu_management',
                'table_name' => 'cms_menus',
                'controller' => 'MenusController',
                'is_protected' => 1,
                'is_active' => 1,
            ],
            [

                'created_at' => date('Y-m-d H:i:s'),
                'name' => trans('crudbooster.Email_Templates'),
                'icon' => 'fa fa-envelope-o',
                'path' => 'email_templates',
                'table_name' => 'cms_email_templates',
                'controller' => 'EmailTemplatesController',
                'is_protected' => 1,
                'is_active' => 1,
            ],
            [

                'created_at' => date('Y-m-d H:i:s'),
                'name' => trans('crudbooster.Statistic_Builder'),
                'icon' => 'fa fa-dashboard',
                'path' => 'statistic_builder',
                'table_name' => 'cms_statistics',
                'controller' => 'StatisticBuilderController',
                'is_protected' => 1,
                'is_active' => 1,
            ],
            [

                'created_at' => date('Y-m-d H:i:s'),
                'name' => trans('crudbooster.API_Generator'),
                'icon' => 'fa fa-cloud-download',
                'path' => 'api_generator',
                'table_name' => '',
                'controller' => 'ApiCustomController',
                'is_protected' => 1,
                'is_active' => 1,
            ],
            [

                'created_at' => date('Y-m-d H:i:s'),
                'name' => trans('crudbooster.Log_User_Access'),
                'icon' => 'fa fa-flag-o',
                'path' => 'logs',
                'table_name' => 'cms_logs',
                'controller' => 'LogsController',
                'is_protected' => 1,
                'is_active' => 1,
            ],

            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'Qlik Items',
                'icon' => 'fa fa-cog',
                'path' => 'qlik_items',
                'table_name' => 'qlik_items',
                'controller' => 'AdminQlikItemsController',
                'is_protected' => 0,
                'is_active' => 1,
            ],

            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'Groups',
                'icon' => 'fa fa-users',
                'path' => 'groups',
                'table_name' => 'groups',
                'controller' => 'AdminGroupsController',
                'is_protected' => 0,
                'is_active' => 1,
            ],

            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'Tenants',
                'icon' => 'fa fa-industry',
                'path' => 'tenants',
                'table_name' => 'tenants',
                'controller' => 'AdminTenantsController',
                'is_protected' => 0,
                'is_active' => 1,
            ],

        ];

        foreach ($data as $k => $d) {
            if (DB::table('cms_moduls')->where('name', $d['name'])->count()) {
                unset($data[$k]);
            }
        }

        DB::table('cms_moduls')->insert($data);
    }
}

class Cms_usersSeeder extends Seeder
{
    public function run()
    {

        if (DB::table('cms_users')->count() == 0) {
            $tenant = DB::table('tenants')->first();
            $group = DB::table('groups')->first();
            $password = \Hash::make('123456');
            $email = $this->command->ask('Please enter your email');
            $cms_users = DB::table('cms_users')->insert([
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'Super Admin',
                'email' => $email,
                'password' => $password,
                'id_cms_privileges' => 1,
                'status' => 'Active',
                'primary_group' => $group->id,
                'tenant' => $tenant->id,
            ]);
        }
    }
}

class TenantSeeder extends Seeder
{
    public function run()
    {

        if (DB::table('tenants')->count() == 0) {
            DB::table('tenants')->insert([
                'name' => 'Tenant 1',
                'description' => 'Descrizione tenant 1',
                'logo' => '',
                'favicon' => '',
                'domain_name' => '',
                'login_background_color' => '#ffffff',
                'login_background_image' => 'background.jpg',
                'login_font_color' => '#000000',
                'created_by' => 1,
                'created_at' => now(),
                'modified_by' => 1,
                'modified_at' => now(),
                'deleted_by' => null,
                'deleted_at' => null,
            ]);
        }
    }
}

class GroupSeeder extends Seeder
{
    public function run()
    {

        if (DB::table('groups')->count() == 0) {
            DB::table('groups')->insert([
                'name' => 'IT',
                'description' => 'Information Technology',
                'created_by' => 1,
                'created_at' => now(),
                'modified_by' => 1,
                'modified_at' => now(),
                'deleted_by' => null,
                'deleted_at' => null,
            ]);
        }
    }
}

class Cms_menus extends Seeder
{
    public function run()
    {

        if (DB::table('cms_menus')->count() == 0) {

            $stat = DB::table('cms_statistics')->where('slug', 'dashboard')->first();

            DB::table('cms_menus')->insert([
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'Dashboard',
                'type' => 'Statistic',
                'path' => 'dashboard',
                'color' => 'normal',
                'icon' => 'fa fa-dashboard',
                'parent_id' => 0,
                'is_active' => 1,
                'is_dashboard' => 1,
                'id_cms_privileges' => 1,
                'sorting' => 0,
            ]);
        }
    }
}

class Cms_menusPrivileges extends Seeder
{
    public function run()
    {

        $menus = DB::table('cms_menus')->get();
        $privileges = DB::table('cms_privileges')->get();

        foreach ($menus as $menu) {
            foreach ($privileges as $privilege) {

                $check = DB::table('cms_menus_privileges')->where('id_cms_menus', $menu->id)->where('id_cms_privileges', $privilege->id)->count();

                if ($check == 0) {
                    DB::table('cms_menus_privileges')->insert([

                        'id_cms_menus' => $menu->id,
                        'id_cms_privileges' => $privilege->id

                    ]);
                }



            }
        }
    }
}

class Cms_statistics extends Seeder
{
    public function run()
    {

        $check = DB::table('cms_statistics')->where('slug', 'dashboard')->first();

        if ($check == 0) {
            DB::table('cms_statistics')->insert([

                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'Dashboard',
                'slug' => 'dashboard',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => null,
            ]);
        }


    }
}


class Cms_menusGroups extends Seeder
{
    public function run()
    {

        $menus = DB::table('cms_menus')->get();
        $groups = DB::table('groups')->get();
        foreach ($menus as $menu) {
            foreach ($groups as $group) {

                $check = DB::table('menu_groups')->where('menu_id', $menu->id)->where('group_id', $group->id)->count();

                if ($check == 0) {
                    DB::table('menu_groups')->insert([
                        'menu_id' => $menu->id,
                        'group_id' => $group->id,
                    ]);
                }



            }
        }
    }
}

class Cms_menusTenants extends Seeder
{
    public function run()
    {

        $menus = DB::table('cms_menus')->get();
        $groups = DB::table('groups')->get();
        foreach ($menus as $menu) {
            foreach ($groups as $group) {

                $check = DB::table('menu_tenants')->where('menu_id', $menu->id)->where('tenant_id', $group->id)->count();

                if ($check == 0) {
                    DB::table('menu_tenants')->insert([
                        'menu_id' => $menu->id,
                        'tenant_id' => $group->id,
                    ]);
                }



            }
        }
    }
}

class Cms_groupTenants extends Seeder
{
    public function run()
    {

        /*
        $menus = DB::table('cms_menus')->get();
        $tenants = DB::table('tenants')->get();
        foreach ($menus as $menu) {
            foreach ($tenants as $tenant) {

                $check = DB::table('group_tenants')->where('group_id', $menu->id)->where('tenant_id', $tenant->id)->count();

                if ($check == 0) {
                    DB::table('group_tenants')->insert([
                        'group_id' => $menu->id,
                        'tenant_id' => $tenant->id,
                    ]);
                }


            }
        }*/ 

        $groups  = DB::table('groups')->get();
        $tenants = DB::table('tenants')->get();
        foreach ($groups as $group) {
            foreach ($tenants as $tenant) {

                $check = DB::table('group_tenants')->where('group_id', $group->id)->where('tenant_id', $tenant->id)->count();

                if ($check == 0) {
                    DB::table('group_tenants')->insert([
                        'group_id' => $group->id,
                        'tenant_id' => $tenant->id,
                    ]);
                }


            }
        }
    }
}


class Cms_usersGroups extends Seeder
{
    public function run()
    {

        $users = DB::table('cms_users')->get();
        $groups = DB::table('groups')->get();
        foreach ($users as $user) {
            foreach ($groups as $group) {

                $check = DB::table('users_groups')->where('user_id', $user->id)->where('group_id', $group->id)->count();

                if ($check == 0) {
                    DB::table('users_groups')->insert([
                        'user_id' => $user->id,
                        'group_id' => $group->id,
                    ]);
                } 


            }
        }
    }
}

class ModuleHelpersSeeder extends Seeder
{
    public function run()
    {

        $url = "https://help.thecustomerhive.com/books/manuale-amministratore/chapter/";


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
            ["Module" => "Log User Access", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/log-user-access"],
            ["Module" => "Groups", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/gruppi"],
            ["Module" => "Email Templates", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/email-templates"],
            ["Module" => "Dashboard Layouts", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/statistic-builder"],
            ["Module" => "Chat AI", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/chat-ai"],
            ["Module" => "API Generator", "Url" => "https://help.thecustomerhive.com/books/manuale-amministratore/page/api-generator"],
        ];
        foreach ($modules as $mod) {

            $module = DB::table('cms_moduls')->where('name', $mod['Module'])->first();

            if(!$module) {
                continue;
            }


            $check = DB::table('module_helpers')->where('id_cms_moduls', $module->id)->count();


            if ($check == 0) {
                DB::table('module_helpers')->insert([
                    'id_cms_moduls' => $module->id,
                    'url' => $mod['Url'],
                ]);
            } 


        }
    }
}

/*
class QlikSett extends Seeder
{
    public function run()
    {

        $data = [
            //QLIK CONFIGURATION
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'confname',
                'label' => 'Configuration Name',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'content' => '',
                'content_input_type' => 'text',
                'dataenum' => null,
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'type',
                'label' => 'Type',
                'content' => '',
                'content_input_type' => 'select',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'dataenum' => 'On-Premise,SAAS',
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'qrsurl',
                'label' => 'QRS Url',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'content' => '',
                'content_input_type' => 'text',
                'dataenum' => null,
                'helper' => '',
            ],

            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'endpoint',
                'label' => 'Endpoint',
                'content' => '',
                'content_input_type' => 'text',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'dataenum' => null,
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'QRSCertfile',
                'label' => 'QRSCertfile',
                'content' => '',
                'content_input_type' => 'upload_file',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'dataenum' => null,
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'QRSCertkeyfile',
                'label' => 'QRSCertkeyfile',
                'content' => '',
                'content_input_type' => 'upload_file',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'dataenum' => null,
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'QRSCertkeyfilePassword',
                'label' => 'QRSCertkeyfilePassword',
                'content' => '',
                'content_input_type' => 'text',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'dataenum' => null,
                'helper' => null,
            ],



            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'url',
                'label' => 'Url',
                'content' => '',
                'content_input_type' => 'text',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'dataenum' => null,
                'helper' => null,
            ],


            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'keyid',
                'label' => 'Key ID',
                'content' => '',
                'content_input_type' => 'text',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'dataenum' => null,
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'issuer',
                'label' => 'Issuer',
                'content' => '',
                'content_input_type' => 'text',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'dataenum' => null,
                'helper' => null,
            ],

            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'web_int_id',
                'label' => 'Web integration ID',
                'content' => '',
                'content_input_type' => 'text',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'dataenum' => null,
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'private_key',
                'label' => 'Private Key',
                'content' => '',
                'content_input_type' => 'upload_file',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'dataenum' => null,
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'debug',
                'label' => 'Debug',
                'content' => '',
                'content_input_type' => 'select',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'dataenum' => 'Active, Inactive',
                'helper' => null,
            ]
        ];

        foreach ($data as $row) {
            $count = DB::table('cms_settings')->where('name', $row['name'])->count();
            if ($count) {
                if ($count > 1) {
                    $newsId = DB::table('cms_settings')->where('name', $row['name'])->orderby('id', 'asc')->take(1)->first();
                    DB::table('cms_settings')->where('name', $row['name'])->where('id', '!=', $newsId->id)->delete();
                }
                continue;
            }
            DB::table('cms_settings')->insert($row);
        }
    }
}
*/
