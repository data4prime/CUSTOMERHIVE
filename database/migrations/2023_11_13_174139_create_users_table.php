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
        DB::statement("
            CREATE VIEW users AS
SELECT
  `cms_users`.`email` AS `userid`,
  `cms_users`.`name`  AS `NAME`
FROM `cms_users`
WHERE ((NOT((`cms_users`.`status` LIKE '%Inactive%')))
        OR (`cms_users`.`status` IS NULL))
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS nome_vista');
    }
};
