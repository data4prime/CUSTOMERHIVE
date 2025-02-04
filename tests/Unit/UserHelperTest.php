<?php

namespace Tests\Unit;

//use PHPUnit\Framework\TestCase;
use Tests\TestCase;

use Illuminate\Support\Facades\DB;

use crocodicstudio\crudbooster\helpers\UserHelper;

use Illuminate\Foundation\Testing\RefreshDatabase;

use Cms_privilegesSeeder;
use GroupSeeder;
use TenantSeeder;
use Cms_groupTenants;
use Cms_usersGroups;


class UserHelperTest extends TestCase
{

    use RefreshDatabase;
    public function setUp(): void
    {
        parent::setUp();

        $ps = new Cms_privilegesSeeder();
        $ps->run();

        $gs = new GroupSeeder();
        $gs->run();

        $ts = new TenantSeeder();
        $ts->run();

        $tenant = DB::table('tenants')->first();
        $group = DB::table('groups')->first();
        $password = \Hash::make('123456');
        $cms_users = DB::table('cms_users')->insert(
                [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'Super Admin',
                'email' => 'superadmin@gmail.com',
                'password' => $password,
                'id_cms_privileges' => 1,
                'status' => 'Active',
                'primary_group' => $group->id,
                'tenant' => $tenant->id,
                ],
                [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'Tenant Admin',
                'email' => 'tenantadmin@gmail.com',
                'password' => $password,
                'id_cms_privileges' => 2,
                'status' => 'Active',
                'primary_group' => $group->id,
                'tenant' => $tenant->id,
                ],
                [
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'User',
                'email' => 'user@gmail.com',
                'password' => $password,
                'id_cms_privileges' => 3,
                'status' => 'Active',
                'primary_group' => $group->id,
                'tenant' => $tenant->id,
                ]
        );



        



    }
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_example()
    {
        $this->assertTrue(true);
    }

    /*public function test_new_users_count() {
        $users = UserHelper::new_users_count();
        $this->assertEquals(3, $users);

    }*/
}
