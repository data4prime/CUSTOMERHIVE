<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riprogettazione pagina profilo: dopo un cambio password/email (o un reset
 * password) le ALTRE sessioni dell'utente vanno chiuse. Il login usa la
 * sessione legacy (Session::put('admin_id', ...)) con driver 'file', quindi
 * non si possono enumerare/cancellare le sessioni di un utente: si confronta
 * invece, a ogni richiesta (CBBackend), questo contatore con quello salvato
 * in sessione al login. Incrementarlo invalida tutte le sessioni che non lo
 * hanno aggiornato. Default 0 = nessuna sessione esistente viene toccata
 * finche' nessuno lo incrementa - vedi docs/refactoring/ (profilo a sezioni).
 */
class AddSessionVersionToCmsUsers extends Migration
{
    public function up()
    {
        Schema::table('cms_users', function (Blueprint $table) {
            $table->unsignedInteger('session_version')->default(0);
        });
    }

    public function down()
    {
        Schema::table('cms_users', function (Blueprint $table) {
            $table->dropColumn('session_version');
        });
    }
}
