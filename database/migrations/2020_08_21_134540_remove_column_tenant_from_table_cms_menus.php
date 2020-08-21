<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveColumnTenantFromTableCmsMenus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cms_menus', function (Blueprint $table) {
            $table->dropColumn('tenant');
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
          $table->unsignedInteger('tenant')->after('icon');
        });
    }
}
