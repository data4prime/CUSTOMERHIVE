<?php

use Illuminate\Database\Seeder;

class MenuParentDefaultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //get data from Groups
        $groups = DB::table('cms_menus')
                      ->whereNull('parent_id')
                      ->update(['parent_id' => 0]);
    }
}
