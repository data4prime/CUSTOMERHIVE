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
        Schema::create('qlikmashups_groups', function (Blueprint $table) {
            //
            $table->increments('id');
            $table->unsignedBigInteger('qlik_apps_id');
            $table->unsignedInteger('group_id');
            $table->unique(['qlik_apps_id', 'group_id'], 'unique');
            $table->foreign('qlik_apps_id')->references('id')->on('qlik_apps')->onDelete('cascade');
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qlikmashups_groups', function (Blueprint $table) {
            //
        });
    }
};
