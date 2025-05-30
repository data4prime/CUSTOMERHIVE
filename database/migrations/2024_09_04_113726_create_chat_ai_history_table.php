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
        Schema::create('chat_ai_history', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('chat_ai_id')->nullable();

            //longtext 
            $table->text('messages')->nullable();

            $table->unsignedInteger('tenant')->nullable();




        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chat_ai_history');
    }
};
