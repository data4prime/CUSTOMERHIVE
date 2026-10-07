<?php

use App\Helpers\MenuHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Il module generator creava la voce di menu come type "Route" (path =
 * "<Controller>GetIndex"). Ora la crea come "Module" (path = path del modulo +
 * "?m=ID"), come Menu Management: serve per l'icona legata al modulo. Converte
 * le voci create in passato: solo quelle che puntano esattamente alla route
 * index di un modulo (stessa pagina di destinazione).
 */
class ConvertWizardRouteMenusToModule extends Migration
{
    public function up()
    {
        $prefix = config('app.module_generator_prefix');
        foreach (DB::table('cms_moduls')->whereNull('deleted_at')->get(['id', 'controller', 'path', 'table_name']) as $module) {
            if (!$module->controller || !$module->path || strpos((string) $module->table_name, (string) $prefix) !== 0) {
                continue;
            }
            $menus = DB::table('cms_menus')->where('type', 'Route')->where('path', $module->controller . 'GetIndex')->get(['id']);
            foreach ($menus as $menu) {
                DB::table('cms_menus')->where('id', $menu->id)->update([
                    'type' => 'Module',
                    'path' => $module->path . '?m=' . $menu->id,
                ]);
            }
            if ($menus->count()) {
                MenuHelper::sync_module_icon($module->id);
            }
        }
    }

    public function down()
    {
        // le voci restano di tipo Module (stessa destinazione)
    }
}
