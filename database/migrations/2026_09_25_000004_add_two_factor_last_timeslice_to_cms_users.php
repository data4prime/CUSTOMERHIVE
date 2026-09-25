<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 1 del piano MFA (enrollment TOTP), vedi docs/refactoring/096-*.
 * Necessaria per l'anti-replay: PragmaRX\Google2FA\Google2FA::verifyKeyNewer()
 * accetta l'ultimo timeslice accettato e rifiuta un codice gia' usato in
 * quella o in una finestra precedente. Nullable: chi non ha ancora
 * confermato il TOTP non ha nessun timeslice da confrontare.
 */
class AddTwoFactorLastTimesliceToCmsUsers extends Migration
{
    public function up()
    {
        Schema::table('cms_users', function (Blueprint $table) {
            $table->unsignedInteger('two_factor_last_timeslice')->nullable()->after('two_factor_confirmed_at');
        });
    }

    public function down()
    {
        Schema::table('cms_users', function (Blueprint $table) {
            $table->dropColumn('two_factor_last_timeslice');
        });
    }
}
