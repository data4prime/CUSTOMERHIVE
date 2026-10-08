<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * cms_apicustom.keterangan (in UI "Descrizione" dell'endpoint API) da
 * VARCHAR(255) a TEXT: la documentazione automatica dei parametri
 * (ApiDocBuilder) supera facilmente i 255 caratteri.
 */
return new class extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE `cms_apicustom` MODIFY `keterangan` TEXT NULL');
    }

    public function down()
    {
        // Torna a 255 caratteri: le descrizioni piu' lunghe vengono troncate.
        DB::statement('UPDATE `cms_apicustom` SET `keterangan` = LEFT(`keterangan`, 255) WHERE CHAR_LENGTH(`keterangan`) > 255');
        DB::statement('ALTER TABLE `cms_apicustom` MODIFY `keterangan` VARCHAR(255) NULL');
    }
};
