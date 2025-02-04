<?php 

namespace Database\Seeders;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
class Cms_statistics extends Seeder
{
    public function run()
    {

        DB::table('cms_statistics')->insert([
            'created_at' => date('Y-m-d H:i:s'),
            'name' => 'Dashboard',
            'slug' => 'dashboard',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => null,
        ]);
    }
}
?>