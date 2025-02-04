<?php 

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class Cms_usersSeeder extends Seeder
{
    public function run()
    {

        if (DB::table('cms_users')->count() == 0) {
            $email = $this->command->ask('Email: ');
            $tenant = DB::table('tenants')->first();
            $group = DB::table('groups')->first();
            $password = \Hash::make('123456');
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
