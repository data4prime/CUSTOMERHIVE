<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableModuleTenants extends Migration
{
    /**
     * Run the migrations. Untested
     *
     * @return void
     */
    public function up()
    {
      Schema::create('module_tenants', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('module_id');
        $table->unsignedInteger('tenant_id');
        $table->unique(['module_id', 'tenant_id'], 'module_tenant_unique');
        $table->foreign('module_id','module_fk')->references('id')->on('cms_moduls');
        $table->foreign('tenant_id','tenant_fk')->references('id')->on('tenants');
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::dropIfExists('module_tenants');
    }
}
