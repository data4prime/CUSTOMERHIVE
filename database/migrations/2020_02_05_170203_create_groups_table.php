<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('groups', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->string('description')->nullable();
        $table->unsignedInteger('created_by')->nullable();
        $table->dateTime('created_at')->nullable();
        $table->unsignedInteger('modified_by')->nullable();
        $table->dateTime('modified_at')->nullable();
        $table->unsignedInteger('deleted_by')->nullable();
        $table->dateTime('deleted_at')->nullable();
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::dropIfExists('groups');
    }
}
