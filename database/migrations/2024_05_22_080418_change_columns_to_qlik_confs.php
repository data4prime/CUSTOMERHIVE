<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qlik_confs', function (Blueprint $table) {
            //
$table->string('confname')->nullable()->change();
        $table->string('type')->nullable()->change();
        $table->string('qrsurl')->nullable()->change();
        $table->string('endpoint')->nullable()->change();
        $table->string('QRSCertfile')->nullable()->change();
        $table->string('QRSCertkeyfile')->nullable()->change();
        $table->string('QRSCertkeyfilePassword')->nullable()->change();
        $table->string('url')->nullable()->change();
        $table->string('keyid')->nullable()->change();
        $table->string('issuer')->nullable()->change();
        $table->string('web_int_id')->nullable()->change();
        $table->string('private_key')->nullable()->change();
        $table->string('debug')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qlik_confs', function (Blueprint $table) {
            //
        });
    }
};
