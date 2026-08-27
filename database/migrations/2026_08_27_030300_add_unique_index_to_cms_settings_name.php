<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Rende unico cms_settings.name.
 *
 * "name" e' la chiave logica di un setting: la usano CRUDBooster::getSetting(),
 * la cache ("setting_".$name), l'UPDATE di SettingsController::postSaveSetting
 * (che scrive WHERE name = ..., non per id) e la dedup dei seeder (che tiene
 * l'id piu' basso e cancella il resto). Non c'era pero' nessun vincolo a DB ne'
 * un controllo applicativo: hook_before_add derivava il nome da
 * str_slug($label) e inseriva. Due righe con lo stesso nome - anche in gruppi
 * diversi - si sovrascrivevano a vicenda al salvataggio e una veniva cancellata
 * silenziosamente al primo db:seed.
 *
 * Il controllo applicativo e' stato aggiunto in hook_before_add; questo indice
 * e' la rete di sicurezza per tutto il resto (import, SQL a mano, seeder).
 *
 * ATTENZIONE - questa migration NON cancella dati da sola.
 * Se trova nomi duplicati si ferma con un errore, elencandoli nel log e
 * nell'output del comando, e NON viene registrata come eseguita: resta
 * pendente. Sta a chi fa il deploy guardare quelle righe e decidere quale
 * tenere, poi rilanciare `php artisan migrate`. Una deduplica automatica
 * "tengo l'id piu' basso" cancellerebbe dati di produzione senza che nessuno
 * li abbia visti, e su questa tabella il contenuto della riga scartata puo'
 * essere quello buono (i seeder ordinano per id, non per significato).
 *
 * Se invece non ci sono duplicati, l'indice viene creato subito senza
 * richiedere alcun intervento.
 */
class AddUniqueIndexToCmsSettingsName extends Migration
{
    public function up()
    {
        // Idempotenza: su un ambiente dove l'indice esiste gia' non si fa nulla.
        if ($this->uniqueIndexExists()) {
            Log::info('Migration add-unique-index-cms-settings-name: indice gia\' presente, niente da fare.');

            return;
        }

        $duplicates = DB::table('cms_settings')
            ->select('name', DB::raw('COUNT(*) AS occorrenze'))
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('name')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $this->reportAndStop($duplicates);
        }

        Schema::table('cms_settings', function (Blueprint $table) {
            $table->unique('name', 'cms_settings_name_unique');
        });

        Log::info('Migration add-unique-index-cms-settings-name: indice cms_settings_name_unique creato.');
    }

    public function down()
    {
        if (! $this->uniqueIndexExists()) {
            return;
        }

        Schema::table('cms_settings', function (Blueprint $table) {
            $table->dropUnique('cms_settings_name_unique');
        });
    }

    private function uniqueIndexExists(): bool
    {
        return DB::table('information_schema.statistics')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', 'cms_settings')
            ->where('index_name', 'cms_settings_name_unique')
            ->exists();
    }

    /**
     * Scrive nel log tutte le righe coinvolte (non solo i nomi: servono id,
     * gruppo e contenuto per capire quale tenere) e interrompe la migration.
     */
    private function reportAndStop($duplicates)
    {
        $lines = [];

        foreach ($duplicates as $dup) {
            $rows = DB::table('cms_settings')
                ->where('name', $dup->name)
                ->orderBy('id')
                ->get(['id', 'group_setting', 'name', 'content_input_type', 'content']);

            $lines[] = "  name \"{$dup->name}\" -> {$dup->occorrenze} righe:";

            foreach ($rows as $row) {
                $content = $row->content === null ? 'NULL' : '"' . mb_strimwidth((string) $row->content, 0, 60, '...') . '"';
                $lines[] = "    id={$row->id}  gruppo=\"{$row->group_setting}\"  tipo={$row->content_input_type}  content={$content}";
            }
        }

        $detail = implode(PHP_EOL, $lines);

        Log::warning(
            'Migration add-unique-index-cms-settings-name INTERROTTA: cms_settings ha nomi duplicati.' . PHP_EOL .
            $detail . PHP_EOL .
            'Decidere quale riga tenere per ogni nome, eliminare le altre, poi rilanciare php artisan migrate.'
        );

        throw new RuntimeException(
            'cms_settings ha ' . $duplicates->count() . ' nome(i) duplicato(i): l\'indice unique non e\' stato creato ' .
            'e questa migration risulta ancora pendente.' . PHP_EOL . PHP_EOL .
            $detail . PHP_EOL . PHP_EOL .
            'Controllare le righe elencate (dettaglio anche in storage/logs), tenere quella corretta per ogni nome, ' .
            'eliminare le altre e rilanciare php artisan migrate. Nessun dato e\' stato modificato.'
        );
    }
}
