<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnIstenantadminToTableCmsPrivileges extends Migration
{
    /**
     * Run the migrations. Untested
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cms_privileges', function (Blueprint $table) {
            $table->boolean('is_superadmin')->default(0)->change();
            $table->boolean('is_tenantadmin')->after('is_superadmin')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cms_privileges', function (Blueprint $table) {
            $table->boolean('is_superadmin')->nullable()->default(NULL)->change();
            $table->dropColumn('is_tenantadmin');
        });
    }
}
