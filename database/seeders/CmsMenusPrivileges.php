<?php 

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class Cms_menusPrivileges extends Seeder
{
    public function run()
    {

        $menus = DB::table('cms_menus')->get();
        $privileges = DB::table('cms_privileges')->get();

        foreach ($menus as $menu) {
            foreach ($privileges as $privilege) {
                DB::table('cms_menus_privileges')->insert([

                    'id_cms_menus' => $menu->id,
                    'id_cms_privileges' => $privilege->id

                ]);
            }
        }
    }
}