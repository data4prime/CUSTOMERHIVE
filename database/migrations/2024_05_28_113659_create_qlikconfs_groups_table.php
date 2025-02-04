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
        Schema::create('qlikconfs_groups', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedBigInteger('qlik_confs_id');
        $table->unsignedInteger('group_id');
        $table->unique(['qlik_confs_id', 'group_id'], 'qlik_conf_group_unique');
        $table->foreign('qlik_confs_id')->references('id')->on('qlik_confs')->onDelete('cascade');
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
        Schema::dropIfExists('qlikconfs_groups');
    }
};
