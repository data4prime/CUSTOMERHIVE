<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dispositivi che hanno gia' superato lo step-up email OTP, per non
 * richiederlo ad ogni login (stile GitHub device verification). Riguarda
 * solo il ramo email OTP: non bypassa mai un TOTP attivo (vedi Fase 2 del
 * piano MFA, docs/refactoring/095-*).
 *
 * Pattern selector/validator (come un "remember me" sicuro): 'selector' in
 * chiaro per la lookup veloce via indice, 'validator_hash' hashato per il
 * confronto vero e proprio, cosi' un dump del DB non basta a impersonare un
 * dispositivo fidato senza anche il cookie originale.
 *
 * trusted_until e' scorrevole: rinnovato ad ogni uso valido (30 giorni
 * dall'ultimo utilizzo, non fissi dalla creazione) - vedi la logica di
 * verifica nella Fase 2, non in questa migration.
 */
class CreateMfaTrustedDevicesTable extends Migration
{
    public function up()
    {
        Schema::create('mfa_trusted_devices', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->index();
            $table->string('selector')->unique();
            $table->string('validator_hash');
            $table->string('user_agent')->nullable();
            $table->timestamp('trusted_until');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mfa_trusted_devices');
    }
}
