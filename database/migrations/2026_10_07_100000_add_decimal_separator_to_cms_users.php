<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preferenze utente (profilo > Preferenze): separatore decimale con cui
 * l'utente vuole vedere numeri, importi e percentuali. ',' (italiano, con il
 * punto come separatore delle migliaia) e' il default; '.' = formato inglese.
 * Vedi App\Helpers\NumberFormat.
 */
class AddDecimalSeparatorToCmsUsers extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('cms_users', 'decimal_separator')) {
            return;
        }
        Schema::table('cms_users', function (Blueprint $table) {
            $table->string('decimal_separator', 1)->default(',');
        });
    }

    public function down()
    {
        if (Schema::hasColumn('cms_users', 'decimal_separator')) {
            Schema::table('cms_users', function (Blueprint $table) {
                $table->dropColumn('decimal_separator');
            });
        }
    }
}
