<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabella richiesta dal broker nativo Illuminate\Auth\Passwords (config
 * auth.php: 'passwords.users.table' => 'password_resets'), mai creata finora
 * perche' AdminController::postForgot() generava/mandava una password invece
 * di usare un link di reset con token. Vedi la migration successiva e
 * docs/refactoring/072-forgot-password-link-invece-di-password-in-chiaro.md.
 */
class CreatePasswordResetsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('password_resets')) {
            return;
        }

        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('password_resets');
    }
}
