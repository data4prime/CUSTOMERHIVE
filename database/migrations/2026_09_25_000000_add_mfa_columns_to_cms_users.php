<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 0 del piano MFA (TOTP + email OTP), vedi docs/refactoring/095-*.
 * Entrambe le colonne sono nullable: zero impatto su chi non attiva l'MFA.
 * two_factor_secret e' cifrato con Crypt (APP_KEY), non hashato: il server
 * deve poterlo rileggere per ricalcolare il codice TOTP atteso.
 */
class AddMfaColumnsToCmsUsers extends Migration
{
    public function up()
    {
        Schema::table('cms_users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_secret');
        });
    }

    public function down()
    {
        Schema::table('cms_users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_confirmed_at']);
        });
    }
}
