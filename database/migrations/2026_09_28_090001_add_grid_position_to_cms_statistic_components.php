<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 0 del piano "dashboard a griglia libera", vedi
 * docs/piano-dashboard-griglia-libera.md e docs/refactoring/108-*.
 * Colonne nullable, additive: 'area_name'/'sorting' (usate dal renderer
 * legacy) non vengono toccate ne' rimosse - restano il riferimento per la
 * conversione automatica (109-*) e per le dashboard ancora
 * 'legacy_areas'. Unita' di misura: colonne di una griglia a 12, come gia'
 * oggi le classi Bootstrap col-sm-N nel layout HTML legacy.
 */
class AddGridPositionToCmsStatisticComponents extends Migration
{
    public function up()
    {
        Schema::table('cms_statistic_components', function (Blueprint $table) {
            $table->unsignedSmallInteger('pos_x')->nullable()->after('sorting');
            $table->unsignedSmallInteger('pos_y')->nullable()->after('pos_x');
            $table->unsignedSmallInteger('width')->nullable()->after('pos_y');
            $table->unsignedSmallInteger('height')->nullable()->after('width');
        });
    }

    public function down()
    {
        Schema::table('cms_statistic_components', function (Blueprint $table) {
            $table->dropColumn(['pos_x', 'pos_y', 'width', 'height']);
        });
    }
}
