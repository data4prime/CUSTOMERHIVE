<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProxyColumnsToQlikItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qlik_items', function (Blueprint $table) {
            $table->dateTime('proxy_enabled_at')->after('frame_height')->nullable();
            $table->string('proxy_token')->after('frame_height')->nullable();
            $table->renameColumn('last_update','qlik_data_last_update');
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
            $table->dropColumn('proxy_enabled_at');
            $table->dropColumn('proxy_token');
            $table->renameColumn('qlik_data_last_update','last_update');
        });
    }
}
