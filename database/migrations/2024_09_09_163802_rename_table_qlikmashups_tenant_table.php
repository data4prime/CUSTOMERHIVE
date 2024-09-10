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
        Schema::table('qlikmashups_tenants', function (Blueprint $table) {
            //
            if (Schema::hasTable('qlikmashups_tenants')) {
                Schema::rename('qlikmashups_tenants', 'qlikapps_tenants');
            }
 


//----------------------------------------------

            if (Schema::hasTable('qlikmashups_groups')) {
                Schema::rename('qlikmashups_groups', 'qlikapps_groups');
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
        Schema::table('qlikmashups_tenants', function (Blueprint $table) {
            //
        });
    }
};
