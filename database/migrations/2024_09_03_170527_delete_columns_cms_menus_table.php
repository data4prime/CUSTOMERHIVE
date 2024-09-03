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
        Schema::table('cms_menus', function (Blueprint $table) {
            //drop method, url, auth, token columns
            $table->dropColumn('method');
            $table->dropColumn('url');
            $table->dropColumn('auth');
            $table->dropColumn('token');

            //add new column boolean by deafult false, called 'primary'
            //$table->boolean('primary')->default(false);

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
            //
        });
    }
};
