<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Svuota il setting "email_sender" dove vale ancora il default upstream di
 * CRUDBooster (support@crudbooster.com).
 *
 * Non si sostituisce con un indirizzo di CustomerHive: il mittente e' una scelta
 * di ogni cliente, che lo imposta dal pannello Settings. Lasciare
 * support@crudbooster.com significherebbe pero' tenere come mittente un dominio
 * di terzi, quindi il valore va azzerato: cosi' il campo si presenta vuoto e
 * chiede di essere compilato, invece di sembrare gia' configurato.
 *
 * L'UPDATE e' condizionato al valore esatto del default upstream: se qualcuno ha
 * gia' impostato un mittente suo, non viene toccato.
 *
 * Nota su cosa usa davvero questo setting: nel percorso di invio immediato
 * (CRUDBooster::sendEmail) "email_sender" non viene mai letto - conta solo
 * cms_email_templates.from_email, e se e' vuoto Laravel usa
 * config('mail.from.address'). "email_sender" entra in gioco solo nel percorso
 * accodato (sendEmail con 'send_at' -> cms_email_queues -> sendEmailQueue), che
 * nessuna chiamata di questa applicazione usa. Azzerarlo quindi non cambia il
 * comportamento attuale degli invii.
 */
class UpdateDefaultEmailSender extends Migration
{
    private const UPSTREAM_DEFAULT = 'support@crudbooster.com';

    public function up()
    {
        $updated = DB::table('cms_settings')
            ->where('name', 'email_sender')
            ->where('content', self::UPSTREAM_DEFAULT)
            ->update(['content' => '']);

        if ($updated > 0) {
            // getSetting() cacha con Cache::forever.
            Cache::forget('setting_email_sender');
            Log::info('Migration update-default-email-sender: email_sender svuotato (era il default upstream ' . self::UPSTREAM_DEFAULT . ').');
        }
    }

    public function down()
    {
        // Intenzionalmente irreversibile. Rimettere il default upstream a
        // partire da un valore vuoto non e' distinguibile dal caso di chi ha
        // svuotato il campo di proposito dal pannello: si rischierebbe di
        // scrivere support@crudbooster.com in installazioni che non l'hanno mai
        // avuto. Se serve tornare indietro, si reimposta dal pannello Settings.
    }
}
