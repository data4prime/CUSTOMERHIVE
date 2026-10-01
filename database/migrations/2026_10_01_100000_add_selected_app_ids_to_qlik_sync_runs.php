<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Import selettivo delle app Qlik: elenco (JSON) degli id Qlik scelti per il
 * run. NULL = tutte le app (comportamento storico).
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('qlik_sync_runs', 'selected_app_ids')) {
            Schema::table('qlik_sync_runs', function (Blueprint $table) {
                $table->text('selected_app_ids')->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('qlik_sync_runs', 'selected_app_ids')) {
            Schema::table('qlik_sync_runs', function (Blueprint $table) {
                $table->dropColumn('selected_app_ids');
            });
        }
    }
};
