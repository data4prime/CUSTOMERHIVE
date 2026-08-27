<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Migrations\Migration;

/**
 * Prima di questo intervento (vedi docs/refactoring/007-*),
 * CRUDBooster::uploadFile() salvava nel DB un URL assoluto
 * (schema://host[:porta]/storage/uploads/...) invece di un path relativo
 * alla public root. Fragile ad ogni cambio di dominio/protocollo/porta -
 * causa concreta osservata: in sviluppo locale via Docker l'host visto dal
 * browser (con la porta pubblicata, es. "localhost:8080") non e' raggiungibile
 * dal container stesso, quindi qualunque controllo lato server su
 * quell'URL falliva sempre, anche a file perfettamente integro.
 *
 * Questa migration ripulisce ogni valore gia' salvato nel vecchio formato,
 * qualunque tabella/colonna lo contenga (sia campi "di sistema" come
 * tenants.logo o cms_users.photo, sia campi di moduli creati da interfaccia,
 * che da qui non possiamo enumerare in anticipo) - cerca per pattern
 * (schema://host/storage/uploads/...) invece che per nome di colonna.
 */
class ConvertAbsoluteUploadUrlsToRelativePaths extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = DB::select(
            "SELECT c.TABLE_NAME AS table_name, c.COLUMN_NAME AS column_name
             FROM INFORMATION_SCHEMA.COLUMNS c
             JOIN INFORMATION_SCHEMA.TABLES t
               ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME
             WHERE c.TABLE_SCHEMA = DATABASE()
               AND t.TABLE_TYPE = 'BASE TABLE'
               AND c.DATA_TYPE IN ('varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext')
               AND (c.GENERATION_EXPRESSION IS NULL OR c.GENERATION_EXPRESSION = '')"
        );

        foreach ($columns as $column) {
            $table = $column->table_name;
            $col = $column->column_name;

            try {
                $affected = DB::affectingStatement(
                    "UPDATE `{$table}` " .
                    "SET `{$col}` = SUBSTRING(`{$col}`, LOCATE('/storage/uploads/', `{$col}`)) " .
                    "WHERE `{$col}` REGEXP '^https?://[^/]+/storage/uploads/'"
                );

                if ($affected > 0) {
                    Log::info("Migration convert-absolute-upload-urls: sistemate {$affected} righe in {$table}.{$col} (URL assoluto -> path relativo).");
                }
            } catch (\Throwable $e) {
                // Non deve mai bloccare l'intera migration per una singola
                // colonna problematica - logga e continua con le altre.
                Log::warning("Migration convert-absolute-upload-urls: skip {$table}.{$col}: " . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Intenzionalmente irreversibile: la trasformazione toglie
        // schema+host, informazione non piu' recuperabile (e valori diversi
        // potrebbero aver avuto host/protocolli diversi nel tempo). Per
        // tornare indietro serve un backup del DB precedente a questa
        // migration, non un semplice rollback.
    }
}
