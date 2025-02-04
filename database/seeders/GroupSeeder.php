<?php 

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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