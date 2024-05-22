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
        Schema::table('qlik_users', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('qlik_conf_id');
            $table->foreign('qlik_conf_id')->references('id')->on('qlik_confs');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qlik_users', function (Blueprint $table) {
            //
        });
    }
};
