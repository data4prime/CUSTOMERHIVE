<?php 

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class QlikSett extends Seeder
{
    public function run()
    {

        $data = [
            //QLIK CONFIGURATION
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'confname',
                'label' => 'Configuration Name',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'content' => '',
                'content_input_type' => 'text',
                'dataenum' => null,
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'type',
                'label' => 'Type',
                'content' => '',
                'content_input_type' => 'select',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'dataenum' => 'On-Premise,SAAS',
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'qrsurl',
                'label' => 'QRS Url',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'content' => '',
                'content_input_type' => 'text',
                'dataenum' => null,
                'helper' => '',
            ],

            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'endpoint',
                'label' => 'Endpoint',
                'content' => '',
                'content_input_type' => 'text',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'dataenum' => null,
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'QRSCertfile',
                'label' => 'QRSCertfile',
                'content' => '',
                'content_input_type' => 'upload_file',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'dataenum' => null,
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'QRSCertkeyfile',
                'label' => 'QRSCertkeyfile',
                'content' => '',
                'content_input_type' => 'upload_file',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'dataenum' => null,
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'QRSCertkeyfilePassword',
                'label' => 'QRSCertkeyfilePassword',
                'content' => '',
                'content_input_type' => 'text',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'dataenum' => null,
                'helper' => null,
            ],



            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'url',
                'label' => 'Url',
                'content' => '',
                'content_input_type' => 'text',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'dataenum' => null,
                'helper' => null,
            ],


            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'keyid',
                'label' => 'Key ID',
                'content' => '',
                'content_input_type' => 'text',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'dataenum' => null,
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'issuer',
                'label' => 'Issuer',
                'content' => '',
                'content_input_type' => 'text',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'dataenum' => null,
                'helper' => null,
            ],

            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'web_int_id',
                'label' => 'Web integration ID',
                'content' => '',
                'content_input_type' => 'text',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'dataenum' => null,
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'private_key',
                'label' => 'Private Key',
                'content' => '',
                'content_input_type' => 'upload_file',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'dataenum' => null,
                'helper' => null,
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'debug',
                'label' => 'Debug',
                'content' => '',
                'content_input_type' => 'select',
                'group_setting' => trans('crudbooster.qlik_conf'),
                'dataenum' => 'Active, Inactive',
                'helper' => null,
            ]
        ];

        foreach ($data as $row) {
            $count = DB::table('cms_settings')->where('name', $row['name'])->count();
            if ($count) {
                if ($count > 1) {
                    $newsId = DB::table('cms_settings')->where('name', $row['name'])->orderby('id', 'asc')->take(1)->first();
                    DB::table('cms_settings')->where('name', $row['name'])->where('id', '!=', $newsId->id)->delete();
                }
                continue;
            }
            DB::table('cms_settings')->insert($row);
        }
    }
}
