<?php 

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class Cms_menusTenants extends Seeder
{
    public function run()
    {

        $menus = DB::table('cms_menus')->get();
        $groups = DB::table('groups')->get();
        foreach ($menus as $menu) {
            foreach ($groups as $group) {
                DB::table('menu_tenants')->insert([
                    'menu_id' => $menu->id,
                    'tenant_id' => $group->id,
                ]);
            }
        }
    }
}