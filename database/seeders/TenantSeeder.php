<?php 
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class TenantSeeder extends Seeder
{
    public function run()
    {

        if (DB::table('tenants')->count() == 0) {
            DB::table('tenants')->insert([
                'name' => 'Tenant 1',
                'description' => 'Descrizione tenant 1',
                'logo' => '',
                'favicon' => '',
                'domain_name' => '',
                'login_background_color' => '#ffffff',
                'login_background_image' => 'background.jpg',
                'login_font_color' => '#000000',
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

?>