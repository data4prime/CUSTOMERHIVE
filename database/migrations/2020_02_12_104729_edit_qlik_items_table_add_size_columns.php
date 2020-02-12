<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditQlikItemsTableAddSizeColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qlik_items', function (Blueprint $table) {
            $table->string('frame_width')->after('url')->nullable();
            $table->string('frame_height')->after('frame_width')->nullable();
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
            $table->dropColumn('frame_width');
            $table->dropColumn('frame_height');
        });
    }
}
