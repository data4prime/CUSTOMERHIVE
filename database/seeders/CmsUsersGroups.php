<?php 

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class Cms_usersGroups extends Seeder
{
    public function run()
    {

        $users = DB::table('cms_users')->get();
        $groups = DB::table('groups')->get();
        foreach ($users as $user) {
            foreach ($groups as $group) {
                DB::table('users_groups')->insert([
                    'user_id' => $user->id,
                    'group_id' => $group->id,
                ]);
            }
        }
    }
}