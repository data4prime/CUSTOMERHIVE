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
            // Drop the existing foreign key constraint
            $table->dropForeign(['user_id']);

            // Add the new foreign key constraint with ON DELETE CASCADE
            $table->foreign('user_id')
                  ->references('id')
                  ->on('cms_users')
                  ->onDelete('cascade');
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
