<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsWidthHeigthToMenuTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cms_menus', function (Blueprint $table) {
            $table->string('frame_width')->after('new_tab')->default('100%')->nullable();
            $table->string('frame_height')->after('frame_width')->default('100%')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cms_menus', function (Blueprint $table) {
            $table->dropColumn('frame_width');
            $table->dropColumn('frame_height');
        });
    }
}
