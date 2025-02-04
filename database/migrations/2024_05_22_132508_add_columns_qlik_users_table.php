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
        Schema::table('qlik_users', function (Blueprint $table) {
            //
            $table->id();
            $table->unsignedBigInteger('qlik_conf_id')->nullable();
            $table->timestamps();
$table->dropForeign(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qlik_users', function (Blueprint $table) {
            //
        });
    }
};
