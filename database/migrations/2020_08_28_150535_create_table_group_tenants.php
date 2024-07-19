<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableGroupTenants extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('group_tenants', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('group_id');
        $table->unsignedInteger('tenant_id');
        $table->unique(['group_id', 'tenant_id'], 'unique');
        $table->foreign('group_id','group_fk')->references('id')->on('groups');
        $table->foreign('tenant_id','group_tenant_fk')->references('id')->on('tenants');
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::dropIfExists('group_tenants');
    }
}
