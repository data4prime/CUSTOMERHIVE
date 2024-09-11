<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeColumnFramefullscreenTableCmsmenus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('cms_menus', function (Blueprint $table) {
        $table->dropColumn('frame_full_screen');
        $table->integer('target_layout')->default(0)->after('frame_height')->nullable(false);
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
          $table->string('frame_full_screen')->after('frame_height')->default(false);
      });
    }
}
