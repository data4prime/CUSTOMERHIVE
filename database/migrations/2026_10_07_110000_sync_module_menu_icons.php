<?php

use App\Helpers\MenuHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Le voci di menu di tipo Module usano l'icona del modulo (module generator):
 * allinea quelle gia' esistenti. Da qui in poi la sincronizzazione e' fatta da
 * MenuHelper::sync_module_icon() a ogni modifica del modulo.
 */
class SyncModuleMenuIcons extends Migration
{
    public function up()
    {
        foreach (DB::table('cms_moduls')->whereNull('deleted_at')->pluck('id') as $id) {
            MenuHelper::sync_module_icon($id);
        }
    }

    public function down()
    {
        // niente da annullare: le icone precedenti non vengono conservate
    }
}
