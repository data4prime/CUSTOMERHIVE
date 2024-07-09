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
        Schema::create('qlikmashups_tenants', function (Blueprint $table) {
            //
            $table->increments('id');
            $table->unsignedBigInteger('qlik_mashups_id');
            $table->unsignedInteger('tenant_id');
            $table->unique(['qlik_mashups_id', 'tenant_id'], 'unique');
            $table->foreign('qlik_mashups_id')->references('id')->on('qlik_mashups')->onDelete('cascade');
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
        Schema::table('qlikmashups_tenants', function (Blueprint $table) {
            //
        });
    }
};
