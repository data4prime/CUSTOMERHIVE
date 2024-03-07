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
        Schema::create('log', function (Blueprint $table) {
            $table->id();
            $table->string('category')->nullable();
            $table->text('description')->collation('utf8mb3_general_ci');
            $table->string('type')->nullable()->collation('utf8mb3_unicode_ci');
            $table->string('ip')->nullable()->collation('utf8mb3_unicode_ci');
            $table->string('url')->nullable()->collation('utf8mb3_unicode_ci');
            $table->string('useragent')->nullable()->collation('utf8mb3_unicode_ci');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('cms_users'); // Assuming a users table exists
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
        Schema::dropIfExists('log');
    }
};
