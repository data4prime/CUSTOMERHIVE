<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * qlik_items.subtitle (in UI "Descrizione") da VARCHAR(255) a TEXT: le
 * descrizioni dei fogli Qlik possono essere piu' lunghe e non vanno tagliate.
 */
return new class extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE `qlik_items` MODIFY `subtitle` TEXT NULL');
    }

    public function down()
    {
        // Torna a 255 caratteri: le descrizioni piu' lunghe vengono troncate.
        DB::statement('UPDATE `qlik_items` SET `subtitle` = LEFT(`subtitle`, 255) WHERE CHAR_LENGTH(`subtitle`) > 255');
        DB::statement('ALTER TABLE `qlik_items` MODIFY `subtitle` VARCHAR(255) NULL');
    }
};
