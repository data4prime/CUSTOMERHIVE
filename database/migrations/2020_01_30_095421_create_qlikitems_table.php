<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQlikitemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('qlik_items', function (Blueprint $table) {
        $table->increments('id');
        $table->string('title')->nullable();
        $table->string('subtitle')->nullable();
        $table->string('description')->nullable();
        $table->string('url')->nullable();
        $table->string('preview')->nullable();
        $table->dateTime('last_update')->nullable();
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
      Schema::dropIfExists('qlik_items');
    }
}
