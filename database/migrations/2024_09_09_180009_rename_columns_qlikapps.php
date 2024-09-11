<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
            if (Schema::hasColumn('qlikapps_groups', 'qlik_mashups_id')) {
                Schema::table('qlikapps_groups', function (Blueprint $table) {
                    $table->renameColumn('qlik_mashups_id', 'qlik_apps_id');
                });
            }

            if (Schema::hasColumn('qlikapps_tenants', 'qlik_mashups_id')) {
                Schema::table('qlikapps_tenants', function (Blueprint $table) {
                    $table->renameColumn('qlik_mashups_id', 'qlik_apps_id');
                });
            }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
