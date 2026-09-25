<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2 del piano MFA (login con step-up email), vedi
 * docs/refactoring/097-*. Codici a 6 cifre monouso, scadenza 10 minuti
 * (decisione utente), inviati solo a chi non ha il TOTP attivo.
 */
class CreateMfaEmailOtpCodesTable extends Migration
{
    public function up()
    {
        Schema::create('mfa_email_otp_codes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->index();
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mfa_email_otp_codes');
    }
}
