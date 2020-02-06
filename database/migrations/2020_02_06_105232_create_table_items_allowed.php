<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableItemsAllowed extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('items_allowed', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('item_id');
        $table->unsignedInteger('group_id');
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
      Schema::dropIfExists('items_allowed');
    }
}
