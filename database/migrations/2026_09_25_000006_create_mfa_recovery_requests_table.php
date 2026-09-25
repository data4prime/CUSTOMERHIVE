<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 3 del piano MFA (recovery "ho perso il dispositivo e i backup
 * codes"), vedi docs/refactoring/098-*. effective_at e' quando la
 * disattivazione diventa effettiva (requested_at + 30 minuti, decisione
 * utente) - applicata in modo lazy (nessun cron), vedi MfaHelper::
 * applyDueRecovery()/applyDueRecoveryForUser().
 */
class CreateMfaRecoveryRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('mfa_recovery_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->index();
            $table->string('selector')->unique();
            $table->string('validator_hash');
            $table->timestamp('requested_at');
            $table->timestamp('effective_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mfa_recovery_requests');
    }
}
