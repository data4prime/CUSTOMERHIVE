<?php 

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Cms_groupTenants extends Seeder
{
    public function run()
    {

        $menus = DB::table('cms_menus')->get();
        $tenants = DB::table('tenants')->get();
        foreach ($menus as $menu) {
            foreach ($tenants as $tenant) {
                DB::table('group_tenants')->insert([
                    'group_id' => $menu->id,
                    'tenant_id' => $tenant->id,
                ]);
            }
        }
    }
}