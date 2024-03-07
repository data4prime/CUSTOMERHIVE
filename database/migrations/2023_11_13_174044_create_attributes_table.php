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
CREATE VIEW attributes AS
SELECT
  `cms_users`.`email` AS `userid`,
  'email'             AS `TYPE`,
  `cms_users`.`email` AS `VALUE`
FROM `cms_users`
WHERE ((NOT((`cms_users`.`status` LIKE '%Inactive%')))
        OR (`cms_users`.`status` IS NULL))UNION ALL SELECT
                                                      `us`.`email`         AS `userid`,
                                                      'memberOf'           AS `TYPE`,
                                                      `gr`.`name`          AS `VALUE`
                                                    FROM ((`cms_users` `us`
                                                        LEFT JOIN `users_groups` `usgr`
                                                          ON ((`us`.`id` = `usgr`.`user_id`)))
                                                       LEFT JOIN `groups` `gr`
                                                         ON ((`usgr`.`group_id` = `gr`.`id`)))
                                                    WHERE ((NOT((`us`.`status` LIKE '%Inactive%')))
                                                            OR (`us`.`status` IS NULL))
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
