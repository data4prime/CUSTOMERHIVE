<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bonifica dei dati di cms_settings, in tre parti indipendenti.
 *
 * 1. URL assoluti nei content di tipo upload. SettingsController::postSaveSetting
 *    salvava "schema://host/storage/uploads/..." costruito da HTTP_HOST, quindi
 *    congelato sull'host visto durante l'upload. La migration
 *    2026_08_27_020000 aveva gia' ripulito tutte le colonne testuali del DB, ma
 *    copriva il path scritto da CRUDBooster::uploadFile(), non questo secondo
 *    percorso, che ha continuato a produrre URL assoluti fino ad ora: qui si
 *    normalizza di nuovo, mirato su cms_settings.
 *
 * 2. content_input_type "upload_document": era offerto dal form di Settings ma
 *    setting.blade.php non lo renderizzava, quindi il campo non compariva a
 *    schermo e il valore veniva azzerato ad ogni salvataggio del gruppo. Il tipo
 *    equivalente effettivamente gestito e' "upload_file".
 *
 * 3. smtp_password era di tipo "text", quindi renderizzato come input di testo
 *    con la password in chiaro nel sorgente della pagina. Passa a "password".
 *
 * Tutte le operazioni sono idempotenti: rilanciarle non cambia nulla.
 */
class NormalizeCmsSettingsContentAndTypes extends Migration
{
    public function up()
    {
        // --- 1. URL assoluto -> path relativo alla public root
        $absolute = DB::table('cms_settings')
            ->whereRaw("content REGEXP '^https?://[^/]+/storage/'")
            ->get(['id', 'name']);

        if ($absolute->isNotEmpty()) {
            // SUBSTRING da /storage/ in poi: LOCATE trova la prima occorrenza e
            // la authority di un URL non puo' contenere "/storage/", quindi il
            // taglio e' sempre nel punto giusto.
            $affected = DB::affectingStatement(
                "UPDATE cms_settings " .
                "SET content = SUBSTRING(content, LOCATE('/storage/', content)) " .
                "WHERE content REGEXP '^https?://[^/]+/storage/'"
            );

            foreach ($absolute as $row) {
                Cache::forget('setting_' . $row->name);
            }

            Log::info("Migration normalize-cms-settings: {$affected} content da URL assoluto a path relativo.");
        }

        // --- 2. upload_document -> upload_file
        $renamedType = DB::table('cms_settings')
            ->where('content_input_type', 'upload_document')
            ->update(['content_input_type' => 'upload_file']);

        if ($renamedType > 0) {
            Log::info("Migration normalize-cms-settings: {$renamedType} righe da upload_document a upload_file.");
        }

        // --- 3. smtp_password come campo password
        DB::table('cms_settings')
            ->where('name', 'smtp_password')
            ->where('content_input_type', 'text')
            ->update(['content_input_type' => 'password']);
    }

    public function down()
    {
        // Solo il punto 3 e' sensatamente reversibile. I punti 1 e 2 non lo sono:
        // l'host originale degli URL non e' piu' recuperabile, e riportare il
        // tipo a upload_document rimetterebbe quelle righe in uno stato in cui
        // il loro valore si cancella da solo.
        DB::table('cms_settings')
            ->where('name', 'smtp_password')
            ->where('content_input_type', 'password')
            ->update(['content_input_type' => 'text']);
    }
}
