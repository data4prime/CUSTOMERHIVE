<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Rimuove definitivamente il gruppo "Qlik Configuration" da cms_settings.
 *
 * Quelle righe (confname, type, url, endpoint, keyid, issuer, web_int_id,
 * private_key, qrsurl, QRSCert*, debug) duplicavano la tabella `qlik_confs`,
 * che e' la configurazione Qlik realmente usata (QlikHelper::getConfFromItem()).
 * Nessuno dei loro nomi viene letto da CRUDBooster::getSetting(): le due
 * occorrenze di getSetting('type') in AdminQlikItemsController e
 * AdminChatAIController sono commentate.
 *
 * La migration 2024_07_16_111255_delete_qlik_setting le cancellava gia', ma il
 * seeder QlikSett (chiamato da DatabaseSeeder subito dopo Cms_settingsSeeder) le
 * reinseriva: girando dopo le migration, vinceva lui. Per questo il gruppo era
 * ancora presente a DB nonostante quella migration risultasse "Ran".
 * Contestualmente il seeder e' stato eliminato, quindi questa cancellazione e'
 * definitiva e non torna piu' indietro al prossimo db:seed.
 */
class RemoveQlikConfigurationSettings extends Migration
{
    /**
     * group_setting e' salvato tradotto (i seeder scrivevano
     * trans('crudbooster.qlik_conf')), quindi vanno coperte tutte le lingue in
     * cui quella chiave e' definita, non solo l'inglese.
     */
    private const GROUPS = [
        'Qlik Configuration',
        'Configurazione Qlik',
    ];

    public function up()
    {
        $rows = DB::table('cms_settings')->whereIn('group_setting', self::GROUPS)->get();

        foreach ($rows as $row) {
            // getSetting() cacha con Cache::forever: senza questo, un eventuale
            // valore resterebbe leggibile dalla cache dopo la cancellazione.
            Cache::forget('setting_' . $row->name);
        }

        $deleted = DB::table('cms_settings')->whereIn('group_setting', self::GROUPS)->delete();

        if ($deleted > 0) {
            Log::info("Migration remove-qlik-configuration-settings: rimosse {$deleted} righe da cms_settings.");
        }
    }

    public function down()
    {
        // Intenzionalmente irreversibile: sono dati morti, ricrearli
        // rimetterebbe in piedi esattamente il duplicato che si vuole eliminare.
        // Per tornare indietro serve un backup del DB.
    }
}
