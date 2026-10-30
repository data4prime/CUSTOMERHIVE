<?php

namespace App\Services\ModuleGenerator;

use App\Helpers\ModuleGeneratorLayout;
use CRUDBooster;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ModuleHelper;

/**
 * Importa un modulo da un JSON creato da ModuleExporter: crea la tabella se
 * manca, genera il controller (stessa CRUDBooster::generateController() usata
 * da postStep1()) e vi scrive i blocchi col/form/config con la stessa tecnica
 * gia' in uso in postStep3()/postStep4()/postStep5() (var_export/min_var_export,
 * mai interpolazione grezza - vedi docs/refactoring/068). Non crea ne' menu ne'
 * righe cms_privileges_roles: deciso con l'utente, l'assegnazione a menu/ruoli
 * resta manuale in Privileges dopo l'import (vedi docs/refactoring/071).
 * Non e' transazionale (come prima): un errore a meta' lascia stato parziale.
 * Spostato da ModulsController::postImport() (piano 335).
 */
class ModuleImporter
{
    /**
     * @return string nome del modulo importato
     *
     * @throws ModuleTransferException
     */
    public function import(string $contents): string
    {
        $data = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data) || empty($data['module']['name']) || empty($data['table']['name'])) {
            throw new ModuleTransferException(trans('crudbooster.module_import_invalid_json'), 'danger');
        }

        $name = $data['module']['name'];
        $icon = $data['module']['icon'] ?? 'bi bi-box';
        $table_name = ModuleHelper::sql_name_encode($data['table']['name']);

        if (DB::table('cms_moduls')->where('name', $name)->whereNull('deleted_at')->count()) {
            throw new ModuleTransferException(trans('crudbooster.module_import_name_exists', ['name' => $name]), 'warning');
        }

        // stesso divieto di save_table() sulle tabelle riservate del framework
        if (substr($table_name, 0, strlen(config('app.reserved_tables_prefix'))) === config('app.reserved_tables_prefix')) {
            throw new ModuleTransferException(trans('crudbooster.module_import_reserved_table'), 'danger');
        }

        if (DB::table('cms_moduls')->where('table_name', $table_name)->whereNull('deleted_at')->count()) {
            throw new ModuleTransferException(trans('crudbooster.module_import_table_used', ['table' => $table_name]), 'warning');
        }

        if (!Schema::hasTable($table_name)) {
            $columns = $data['table']['columns'] ?? [];

            if (empty($columns)) {
                throw new ModuleTransferException(trans('crudbooster.module_import_no_columns'), 'danger');
            }

            $this->createImportedTable($table_name, $columns);
        }

        $controller = CRUDBooster::generateController($table_name, $name);

        $id = DB::table('cms_moduls')->max('id') + 1;
        DB::table('cms_moduls')->insert([
            'id' => $id,
            'name' => $name,
            'icon' => $icon,
            'path' => $table_name,
            'table_name' => $table_name,
            'controller' => $controller,
            'is_protected' => 0,
            'is_active' => 1,
            'created_at' => now(),
        ]);

        $this->writeImportedColumns($controller, $data['col'] ?? []);
        $this->writeImportedForm($controller, $data['form'] ?? []);
        $this->writeImportedLayout($controller, $data['form_layout'] ?? null, $data['form'] ?? []);
        $this->writeImportedConfig($controller, $table_name, $data['config'] ?? []);

        return $name;
    }

    // Crea la tabella per un modulo importato: stesse colonne "di cornice"
    // (id/group/tenant/created_at/created_by/updated_at/updated_by/deleted_at/
    // deleted_by) aggiunte da ModulsController::save_table() quando crea una
    // tabella nuova dal wizard. Duplicato intenzionalmente invece di
    // richiamare save_table() (pensata per leggere da $_POST del wizard, non
    // da un array gia' pronto) per non rischiare di alterare il comportamento
    // del wizard esistente.
    private function createImportedTable($table_name, array $columns)
    {
        Schema::create($table_name, function (Blueprint $table) use ($columns) {
            foreach ($columns as $col) {
                if (empty($col['name'])) {
                    continue;
                }

                $columnname = ModuleHelper::sql_name_encode($col['name']);
                $size = $col['size'] ?? null;

                switch ($col['type'] ?? 'text') {
                    case 'number':
                        $table->integer($columnname)->length($size ?: 11)->nullable();
                        break;
                    case 'boolean':
                        $table->boolean($columnname)->nullable();
                        break;
                    case 'plaintext':
                        $table->text($columnname)->nullable();
                        break;
                    case 'longtext':
                        $table->longText($columnname)->nullable();
                        break;
                    case 'decimal':
                        $ps = array_pad(explode(',', (string) $size), 2, null);
                        $table->decimal($columnname, (int) ($ps[0] ?: 12), (int) ($ps[1] ?: 2))->nullable();
                        break;
                    case 'date':
                        $table->date($columnname)->nullable();
                        break;
                    case 'datetime':
                        $table->dateTime($columnname)->nullable();
                        break;
                    case 'time':
                        $table->time($columnname)->nullable();
                        break;
                    default:
                        $table->string($columnname, $size ?: 255)->nullable();
                        break;
                }
            }

            $table->increments('id');
            $table->unsignedInteger('group')->nullable();
            $table->unsignedInteger('tenant')->nullable();
            $table->dateTime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->unsignedInteger('created_by')->nullable();
            $table->dateTime('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->unsignedInteger('updated_by')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
        });
    }

    // Sostituisce il contenuto tra due marcatori "# START ... # END" nel
    // controller generato, stesso schema usato da postStep3()/postStep4()/
    // postStep5() per riscrivere i rispettivi blocchi.
    private function replaceControllerBlock($controller, $start_marker, $end_marker, $body)
    {
        $path = CRUDBooster::customControllersPath() . '/' . $controller . '.php';
        $raw = explode($start_marker, file_get_contents($path));
        $rraw = explode($end_marker, $raw[1]);

        $file_controller = trim($raw[0]) . "\n\n";
        $file_controller .= "\t\t\t{$start_marker}\n";
        $file_controller .= $body . "\n";
        $file_controller .= "\t\t\t{$end_marker}\n\n";
        $file_controller .= "\t\t\t" . trim($rraw[1]);

        file_put_contents($path, $file_controller);
    }

    // Scrive il blocco colonne ($this->col[]) da un array importato. Stessa
    // whitelist di chiavi che postStep3() sa scrivere - non e' un confine di
    // sicurezza (min_var_export() esclude gia' qualunque interpolazione
    // grezza, stesso fix di docs/refactoring/068), solo coerenza con cio' che
    // il wizard stesso genera.
    private function writeImportedColumns($controller, array $columns)
    {
        // Stesse chiavi che il passo Lista del wizard (ModuleGeneratorList) puo' scrivere.
        $allowed_keys = [
            'label', 'name', 'join', 'join_where', 'join_id', 'image', 'download', 'width', 'col_width',
            'str_limit', 'format', 'badge', 'currency', 'decimals', 'calc', 'nl2br', 'color', 'style',
            'callback', 'callback_php', 'query', 'visible',
        ];
        $script_cols = ["\t\t\t" . '$this->col = [];'];

        foreach ($columns as $col) {
            if (empty($col['name']) || !isset($col['label'])) {
                continue;
            }

            $filtered = array_intersect_key($col, array_flip($allowed_keys));
            $script_cols[] = "\t\t\t" . '$this->col[] = ' . min_var_export($filtered) . ';';
        }

        $this->replaceControllerBlock(
            $controller,
            '# START COLUMNS DO NOT REMOVE THIS LINE',
            '# END COLUMNS DO NOT REMOVE THIS LINE',
            implode("\n", $script_cols)
        );
    }

    // Scrive il blocco form ($this->form[]) da un array importato. Stessa
    // whitelist minima di postStep4() (label/name obbligatori), il resto
    // delle chiavi passa cosi' com'e' - stesso motivo di sicurezza di sopra:
    // min_var_export() e' la protezione reale, la whitelist e' solo pulizia.
    private function writeImportedForm($controller, array $fields)
    {
        $script_form = ["\t\t\t" . '$this->form = [];'];

        foreach ($fields as $field) {
            if (empty($field['label']) || empty($field['name'])) {
                continue;
            }

            $script_form[] = "\t\t\t" . '$this->form[] = ' . min_var_export($field) . ';';
        }

        $this->replaceControllerBlock(
            $controller,
            '# START FORM DO NOT REMOVE THIS LINE',
            '# END FORM DO NOT REMOVE THIS LINE',
            implode("\n", $script_form)
        );
    }

    // Scrive il blocco FORM LAYOUT di un modulo importato (se il JSON lo
    // contiene), dopo averlo ripulito con la stessa validazione del wizard:
    // nomi che esistono nel form importato, posizioni nella griglia. Un layout
    // non valido si scarta (il modulo resta con il form piatto).
    private function writeImportedLayout($controller, $layout, array $form)
    {
        if (!is_array($layout)) {
            return;
        }
        try {
            $clean = ModuleGeneratorLayout::build($layout, ModuleGeneratorLayout::placeableFields($form));
        } catch (\InvalidArgumentException $e) {
            return;
        }
        $path = CRUDBooster::customControllersPath() . '/' . $controller . '.php';
        file_put_contents($path, ModuleGeneratorLayout::replaceLayoutBlock(file_get_contents($path), $clean));
    }

    // Scrive il blocco configurazione, stessa whitelist di chiavi di
    // postStep5() (l'unica differenza: qui non c'e' un $post da cui leggere,
    // i valori arrivano gia' pronti dal JSON importato).
    private function writeImportedConfig($controller, $table_name, array $config)
    {
        $allowed_keys = [
            'title_field', 'limit', 'orderby', 'global_privilege',
            'button_table_action', 'button_bulk_action', 'button_action_style',
            'button_add', 'button_edit', 'button_delete', 'button_detail',
            'button_filter', 'button_import', 'button_export',
        ];

        $script_config = ["\t\t\t" . '$this->table = ' . var_export($table_name, true) . ';'];

        foreach ($allowed_keys as $key) {
            if (!array_key_exists($key, $config)) {
                continue;
            }

            $value = $config[$key];
            $script_config[] = "\t\t\t" . '$this->' . $key . ' = ' . var_export($value, true) . ';';
        }

        $this->replaceControllerBlock(
            $controller,
            '# START CONFIGURATION DO NOT REMOVE THIS LINE',
            '# END CONFIGURATION DO NOT REMOVE THIS LINE',
            implode("\n", $script_config)
        );
    }
}
