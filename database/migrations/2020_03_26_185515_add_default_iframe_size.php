<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDefaultIframeSize extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qlik_items', function (Blueprint $table) {
            $table->string('frame_width')->default('100%')->change();
            $table->string('frame_height')->default('100%')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qlik_items', function (Blueprint $table) {
          $table->string('frame_width')->nullable()->change();
          $table->string('frame_height')->nullable()->change();
        });
    }
}
