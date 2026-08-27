<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * La tabella `license` esisteva gia' nei database reali (dev/staging/prod,
 * usata da ConnectorService/LicenseHelper) ma non era mai stata tracciata
 * da una migration - creata manualmente in un momento imprecisato. Questa
 * migration la rende parte dello schema versionato senza toccare i dati
 * esistenti: se la tabella c'e' gia' (dev/staging/prod) non fa nulla, se
 * non c'e' (installazioni pulite, DB di test in CI) la crea con lo stesso
 * schema verificato via `SHOW CREATE TABLE license` sull'ambiente dev.
 */
class CreateLicenseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('license')) {
            return;
        }

        Schema::create('license', function (Blueprint $table) {
            $table->id();
            $table->string('license_key')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('license');
    }
}
