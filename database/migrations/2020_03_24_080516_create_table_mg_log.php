<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableMgLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('mg_log', function (Blueprint $table) {
        $table->increments('id');
        $table->string('category')->nullable();
        $table->text('description')->nullable();
        $table->string('type')->nullable();
        $table->string('ip')->nullable();
        $table->string('url')->nullable();
        $table->string('useragent')->nullable();
        $table->unsignedInteger('created_by')->nullable();
        $table->dateTime('created_at');
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::dropIfExists('mg_log');
    }
}
