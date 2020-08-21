<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MenuTenants extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('menu_tenants', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('menu_id');
        $table->unsignedInteger('tenant_id');
        $table->unique(['menu_id', 'tenant_id'], 'unique');
        $table->foreign('menu_id','fk_menu_tenants_1')->references('id')->on('cms_menus')->onDelete('cascade');
        $table->foreign('tenant_id','fk_menu_tenants_2')->references('id')->on('tenants')->onDelete('cascade');
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::dropIfExists('menu_tenants');
    }
}
