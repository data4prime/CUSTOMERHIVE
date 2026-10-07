<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Aggiunge il setting "email_enabled" (yes/no) al gruppo Email Setting.
 * Default 'yes': nessun cambio di comportamento per le installazioni esistenti.
 *
 * group_setting e' salvato tradotto, quindi si riusa quello della riga
 * smtp_driver gia' presente invece di indovinare la lingua.
 */
class AddEmailEnabledSetting extends Migration
{
    public function up()
    {
        if (DB::table('cms_settings')->where('name', 'email_enabled')->exists()) {
            return;
        }

        $group = DB::table('cms_settings')->where('name', 'smtp_driver')->value('group_setting') ?: 'Email Setting';

        DB::table('cms_settings')->insert([
            'created_at' => date('Y-m-d H:i:s'),
            'name' => 'email_enabled',
            'label' => 'Email Enabled',
            'content' => 'yes',
            'content_input_type' => 'radio',
            'group_setting' => $group,
            'dataenum' => 'yes,no',
            'helper' => null,
        ]);

        Cache::forget('setting_email_enabled');
    }

    public function down()
    {
        DB::table('cms_settings')->where('name', 'email_enabled')->delete();
        Cache::forget('setting_email_enabled');
    }
}
