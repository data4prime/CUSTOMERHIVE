<?php 

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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