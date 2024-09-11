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

        if (Schema::hasTable('qlik_mashups')) {
                Schema::rename('qlik_mashups', 'qlik_apps');
        }




        Schema::table('qlik_apps', function (Blueprint $table) {
            
            if (Schema::hasColumn('qlik_apps', 'mashupname')) {
                $table->renameColumn('mashupname', 'appname');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qlik_apps', function (Blueprint $table) {
            //
        });
    }
};
