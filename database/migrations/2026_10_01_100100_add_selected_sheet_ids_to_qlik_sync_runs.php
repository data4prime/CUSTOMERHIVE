<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Import selettivo degli item (fogli) Qlik: elenco (JSON) degli id Qlik dei
 * fogli scelti per il run, valido per i run item di una singola app.
 * NULL = tutti i fogli (comportamento storico).
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('qlik_sync_runs', 'selected_sheet_ids')) {
            Schema::table('qlik_sync_runs', function (Blueprint $table) {
                $table->text('selected_sheet_ids')->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('qlik_sync_runs', 'selected_sheet_ids')) {
            Schema::table('qlik_sync_runs', function (Blueprint $table) {
                $table->dropColumn('selected_sheet_ids');
            });
        }
    }
};
