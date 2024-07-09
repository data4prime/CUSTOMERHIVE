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
        Schema::create('qlik_mashups', function (Blueprint $table) {
            //
                        $table->id();
        $table->string('mashupname');
        $table->string('conf');
        $table->string('appid');

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
        Schema::create('qlik_mashups', function (Blueprint $table) {
            //
        });
    }
};
