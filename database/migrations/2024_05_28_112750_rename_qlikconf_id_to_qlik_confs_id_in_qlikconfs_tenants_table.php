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
        Schema::table('qlikconfs_tenants', function (Blueprint $table) {
            //
		$table->renameColumn('qlikconf_id', 'qlik_confs_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qlikconfs_tenants', function (Blueprint $table) {
            //
        });
    }
};
