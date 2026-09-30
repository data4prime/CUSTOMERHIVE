<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 0 del piano "dashboard a griglia libera", vedi
 * docs/piano-dashboard-griglia-libera.md e docs/refactoring/108-*.
 * Additiva e behavior-preserving: default 'legacy_areas' per tutte le
 * righe esistenti/nuove, nessuna dashboard cambia comportamento finche'
 * non viene convertita esplicitamente (vedi 109-*).
 */
class AddLayoutModeToCmsStatistics extends Migration
{
    public function up()
    {
        Schema::table('cms_statistics', function (Blueprint $table) {
            $table->string('layout_mode', 20)->default('legacy_areas')->after('layout');
        });
    }

    public function down()
    {
        Schema::table('cms_statistics', function (Blueprint $table) {
            $table->dropColumn('layout_mode');
        });
    }
}
