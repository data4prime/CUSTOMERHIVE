<?php 

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'Qlik Configuration',
                'icon' => 'fa fa-cog',
                'path' => 'qlik_confs',
                'table_name' => 'qlik_confs',
                'controller' => 'QlikConfController',
                'is_protected' => 1,
                'is_active' => 1,
            ],
            [
                // Mancava: senza questa riga AdminModuleHelperController non
                // ha nessuna rotta (il routing dei moduli di sistema dipende
                // interamente da una riga cms_moduls), e ModuleHelperSeeder
                // (che cerca "Module Helpers" per nome per crearsi il proprio
                // link di aiuto) non trova nulla - vedi docs/refactoring/README.md.
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'Module Helpers',
                'icon' => 'fa fa-question-circle',
                'path' => 'module_helpers',
                'table_name' => 'module_helpers',
                'controller' => 'AdminModuleHelperController',
                'is_protected' => 1,
                'is_active' => 1,
            ],
            [
                // Stesso problema di "Module Helpers" sopra: il link nella
                // sidebar (resources/views/crudbooster/sidebar.blade.php,
                // sotto Statistic Builder) esiste gia', ma senza questa riga
                // DashboardLayoutController non ha nessuna rotta -> 404 su
                // /admin/dashboard_layouts.
                'created_at' => date('Y-m-d H:i:s'),
                'name' => trans('crudbooster.Dashboard_Layouts'),
                'icon' => 'fa fa-th-large',
                'path' => 'dashboard_layouts',
                'table_name' => 'dashboard_layouts',
                'controller' => 'DashboardLayoutController',
                'is_protected' => 1,
                'is_active' => 1,
            ]

        ];

        foreach ($data as $k => $d) {
            if (DB::table('cms_moduls')->where('name', $d['name'])->count()) {
                unset($data[$k]);
            }
        }

        DB::table('cms_moduls')->insert($data);
    }
}