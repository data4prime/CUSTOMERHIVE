<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableMenuGroups extends Migration
{
    /**
     * Run the migrations. Untested
     *
     * @return void
     */
    public function up()
    {
      Schema::create('menu_groups', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('menu_id');
        $table->unsignedInteger('group_id');
        $table->unique(['menu_id', 'group_id'], 'unique');
        $table->foreign('menu_id','fk_menu_groups_1')->references('id')->on('cms_menus')->onDelete('cascade');
        $table->foreign('group_id','fk_menu_groups_2')->references('id')->on('groups')->onDelete('cascade');
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::dropIfExists('menu_groups');
    }
}
