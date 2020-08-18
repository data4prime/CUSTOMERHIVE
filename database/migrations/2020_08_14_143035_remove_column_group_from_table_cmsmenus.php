<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveColumnGroupFromTableCmsmenus extends Migration
{
    /**
     * Run the migrations. Untested, possibile perdita di dati
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cms_menus', function (Blueprint $table) {
            $table->dropColumn('group');
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
            $table->unsignedInteger('group')->after('icon')->default(1);
        });
    }
}
