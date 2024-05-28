<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qlikconfs_tenants', function (Blueprint $table) {
            //
        $table->increments('id');
        $table->unsignedBigInteger('qlikconf_id');
        $table->unsignedInteger('tenant_id');
        $table->unique(['qlikconf_id', 'tenant_id'], 'unique');
        $table->foreign('qlikconf_id')->references('id')->on('qlik_confs')->onDelete('cascade');
        $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qlikconfs_tenants', function (Blueprint $table) {
             Schema::dropIfExists('qlikconfs_tenants');
        });
    }
};
