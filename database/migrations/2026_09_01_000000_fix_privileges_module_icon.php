<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Il modulo 'privileges' (cms_moduls) era stato seedato con la stessa icona
 * generica "fa fa-cog" di Notifications e Privileges_Roles - la sidebar
 * (sidebar.blade.php) usa invece "fa fa-key" per la voce "Roles" che punta
 * proprio a questo modulo, causando un'icona diversa tra la testata della
 * pagina /admin/privileges (letta da cms_moduls.icon via
 * CRUDBooster::getCurrentModule()) e la sidebar.
 *
 * L'UPDATE e' condizionato al valore esatto del vecchio default: se
 * qualcuno ha gia' personalizzato l'icona da altrove, non viene toccata.
 */
class FixPrivilegesModuleIcon extends Migration
{
    private const OLD_ICON = 'fa fa-cog';
    private const NEW_ICON = 'fa fa-key';

    public function up()
    {
        DB::table('cms_moduls')
            ->where('path', 'privileges')
            ->where('icon', self::OLD_ICON)
            ->update(['icon' => self::NEW_ICON]);
    }

    public function down()
    {
        DB::table('cms_moduls')
            ->where('path', 'privileges')
            ->where('icon', self::NEW_ICON)
            ->update(['icon' => self::OLD_ICON]);
    }
}
