<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsGroupTenantToTableQlikItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qlik_items', function (Blueprint $table) {
          $table->unsignedInteger('tenant')->after('qlik_data_last_update');
          $table->unsignedInteger('group')->after('qlik_data_last_update');
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
          /*$table->dropColumn('tenant');
          $table->dropColumn('group');*/
          $table->dropColumn(['tenant', 'group']);
        });
    }
}
