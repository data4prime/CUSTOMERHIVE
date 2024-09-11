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
        Schema::table('cms_settings', function (Blueprint $table) {
            //

            $rows = DB::table('cms_settings')->where('group_setting', 'Qlik Configuration')->get();
            foreach ($rows as $row) {
                DB::table('cms_settings')->where('id', $row->id)->delete();
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
        Schema::table('cms_settings', function (Blueprint $table) {
            //
        });
    }
};
