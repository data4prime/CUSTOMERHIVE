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
        Schema::table('chatai_confs', function (Blueprint $table) {
            //

            //edit column is_active to string type
            $table->string('is_active')->default('Non Attivo')->nullable()->change();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('chatai_confs', function (Blueprint $table) {
            //
        });
    }
};
