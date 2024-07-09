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
            $table->id();
$table->string('confname');
        $table->string('type');
        $table->string('qrsurl');
        $table->string('endpoint');
        $table->string('QRSCertfile');
        $table->string('QRSCertkeyfile');
        $table->string('QRSCertkeyfilePassword');
        $table->string('url');
        $table->string('keyid');
        $table->string('issuer');
        $table->integer('web_int_id');
        $table->text('private_key');
        $table->boolean('debug');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('qlik_confs');
    }
};
