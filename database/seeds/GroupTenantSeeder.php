<?php

use Illuminate\Database\Seeder;

class GroupTenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //get data from Groups
        $groups = DB::table('groups')
                      ->whereNotNull('tenant')
                      ->whereNull('deleted_at')
                      ->get();

        foreach ($groups as $group) {
          //save tenant for each group
          DB::table('group_tenants')->insert([
              'group_id' => $group->id,
              'tenant_id' => $group->tenant
          ]);
        }
    }
}
