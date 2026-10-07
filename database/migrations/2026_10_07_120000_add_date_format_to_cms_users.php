<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preferenza utente (profilo > Preferenze): formato con cui vedere le date in
 * lista e dettaglio. Default 'd/m/Y' (italiano). Vedi App\Helpers\DateFormat.
 */
class AddDateFormatToCmsUsers extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('cms_users', 'date_format')) {
            return;
        }
        Schema::table('cms_users', function (Blueprint $table) {
            $table->string('date_format', 10)->default('d/m/Y');
        });
    }

    public function down()
    {
        if (Schema::hasColumn('cms_users', 'date_format')) {
            Schema::table('cms_users', function (Blueprint $table) {
                $table->dropColumn('date_format');
            });
        }
    }
}
