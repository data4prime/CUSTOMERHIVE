<?php 

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class Cms_menusGroups extends Seeder
{
    public function run()
    {

        $menus = DB::table('cms_menus')->get();
        $groups = DB::table('groups')->get();
        foreach ($menus as $menu) {
            foreach ($groups as $group) {
                DB::table('menu_groups')->insert([
                    'menu_id' => $menu->id,
                    'group_id' => $group->id,
                ]);
            }
        }
    }
}