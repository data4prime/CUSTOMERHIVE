<?php 

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class QlikConf extends Seeder
{
    public function run()
    {

 $mod = [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'Qlik Configuration',
                'icon' => 'fa fa-cog',
                'path' => 'qlik_confs',
                'table_name' => 'qlik_confs',
                'controller' => 'QlikConfController',
                'is_protected' => 1,
                'is_active' => 1,
            ];
        DB::table('cms_moduls')->insert($mod);   
    }
}