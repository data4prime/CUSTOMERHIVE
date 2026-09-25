<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backup codes monouso generati all'enrollment del TOTP (Fase 1 del piano
 * MFA), da usare al posto del codice TOTP se si perde il dispositivo. Vedi
 * docs/refactoring/095-*. code_hash e non code: sono equivalenti a una
 * password monouso, vanno trattati come tali (hash, non testo cifrabile).
 */
class CreateMfaRecoveryCodesTable extends Migration
{
    public function up()
    {
        Schema::create('mfa_recovery_codes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->index();
            $table->string('code_hash');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mfa_recovery_codes');
    }
}
