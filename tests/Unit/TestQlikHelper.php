<?php

namespace Tests\Unit;

//use PHPUnit\Framework\TestCase;

use Tests\TestCase;

use Illuminate\Support\Facades\DB;

use crocodicstudio\crudbooster\helpers\QlikHelper;

use Illuminate\Foundation\Testing\RefreshDatabase;

class TestQlikHelper extends TestCase
{

use RefreshDatabase;
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_example()
    {
        //$this->assertTrue(true);
    }

    public function setUp(): void
    {
        parent::setUp();

        $qlik_confs = DB::table('qlik_confs')->insert([
            [

                'confname' => 'D4P SAAS',
                'type' => 'SAAS',
                'qrsurl' => NULL,
                'endpoint' => 'chive',
                'QRSCertfile' => NULL,
                'QRSCertkeyfile' => NULL,
                'QRSCertkeyfilePassword' => NULL,
                'url' => 'https://data4primesaas.eu.qlikcloud.com',
                'keyid' => 'qlikvte',
                'issuer' => 'qlikvte',
                'web_int_id' => '9G9Lt4S--4o5Vj5BLq4HGEqVRpvP_Djj',
                'private_key' => 'https://staging.thecustomerhive.com/storage/uploads/1/2024-09/privatekey.pem',
                'debug' => 'Active',
                'created_at' => '2024-06-04 16:36:28',
                'updated_at' => '2024-09-19 17:07:37',
                'port' => NULL,
                'tenant_path' => '/var/www/staging.thecustomerhive.com/storage/app/public/',
                'auth' => 'JWT',
            ],
            [

                'confname' => 'D4P On-Premise JWT',
                'type' => 'On-Premise',
                'qrsurl' => 'https://qse.datasynapsi.cloud',
                'endpoint' => 'jwt',
                'QRSCertfile' => 'https://staging.thecustomerhive.com/storage/uploads/1/2024-07/server.pem',
                'QRSCertkeyfile' => 'https://staging.thecustomerhive.com/storage/uploads/1/2024-07/server_key.pem',
                'QRSCertkeyfilePassword' => NULL,
                'url' => 'https://qse.datasynapsi.cloud',
                'keyid' => NULL,
                'issuer' => NULL,
                'web_int_id' => NULL,
                'private_key' => 'https://staging.thecustomerhive.com/storage/uploads/1/2024-09/server_key.pem',
                'debug' => 'Active',
                'created_at' => '2024-07-30 19:13:45',
                'updated_at' => '2024-09-06 16:28:52',
                'port' => NULL,
                'tenant_path' => 'https://staging.thecustomerhive.com',
                'auth' => 'JWT',
            ]
        ]);

        $qlik_items = DB::table('qlik_items')->insert([
                    [
                        'id' => 1,
                        'title' => 'SAAS JWT',
                        'subtitle' => 'Example Qlik SAAS Auth JWT',
                        'url_help' => 'https://company.eu.qlikcloud.com/single/?appid=5a174d39-0d26-4871-bbe9-583252deaeb2&amp;sheet=GZGbMWW&amp;theme=sense&amp;opt=ctxmenu,currsel',
                        'url' => 'https://company.eu.qlikcloud.com/single/?appid=5a174d39-0d26-4871-bbe9-583252deaeb2&amp;sheet=GZGbMWW&amp;theme=sense&amp;opt=ctxmenu,currsel',
                        //'proxy_token' => NULL,
                        //'proxy_enabled_at' => NULL,
                        'preview' => NULL,
                        //'qlik_data_last_update' => NULL,
                        'created_by' => 1,
                        'created_at' => '2024-04-29 16:54:00',
                        'modified_by' => NULL,
                        'modified_at' => NULL,
                        'deleted_by' => NULL,
                        'deleted_at' => NULL,
                        'qlik_conf' => 1,
                    ],
                    [
                        'id' => 2,
                        'title' => 'On-Premise Ticket',
                        'subtitle' => 'Example Qlik On-Premise Auth Ticket',
                        'url_help' => NULL,
                        'url' => 'https://qse.company.cloud/ticket/single/?appid=73f8c3fb-6fd3-4ac9-9ab4-f2adcf0b3c98&sheet=JsCeVm',
                        //'proxy_token' => NULL,
                        //'proxy_enabled_at' => NULL,
                        'preview' => NULL,
                        //'qlik_data_last_update' => NULL,
                        'created_by' => 1,
                        'created_at' => '2024-07-01 15:17:23',
                        'modified_by' => NULL,
                        'modified_at' => NULL,
                        'deleted_by' => 1,
                        'deleted_at' => '2024-08-30 09:16:32',
                        'qlik_conf' => 2,
                    ],
                    
        ]);




    }

    public function test_getFromTables()
    {

        $type = QlikHelper::getTypeConf(1);
        $this->assertEquals('SAAS', $type);

        $type = QlikHelper::getTypeConf(2);
        $this->assertEquals('On-Premise', $type);

        $is_saas = QlikHelper::confIsSAAS(1);
        $this->assertEquals(true, $is_saas);

        $is_saas = QlikHelper::confIsSAAS(2);
        $this->assertEquals(false, $is_saas);

        $conf = QlikHelper::getConfFromItem(1);
        $this->assertEquals(1, $conf->id);

        $conf = QlikHelper::getConfFromItem(2);
        $this->assertEquals(2, $conf->id);
        



    }
}
