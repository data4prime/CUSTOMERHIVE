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
            $table->string('qlik_login')->nullable();
            $table->string('user_directory')->nullable();
            $table->string('idp_qlik')->nullable();
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('cms_users');

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
