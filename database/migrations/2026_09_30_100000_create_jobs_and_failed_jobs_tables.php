<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabelle della coda Laravel (driver "database"): servono alla connessione
 * "qlik_sync" (sincronizzazione app/item Qlik). Additiva, con guardia
 * hasTable per non fallire dove le tabelle fossero gia' state create a mano.
 */
class CreateJobsAndFailedJobsTables extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (! Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('jobs');
    }
}
