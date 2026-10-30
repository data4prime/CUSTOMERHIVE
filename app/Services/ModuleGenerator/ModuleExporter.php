<?php

namespace App\Services\ModuleGenerator;

use App\Helpers\ModuleGeneratorLayout;
use CRUDBooster;
use Illuminate\Support\Facades\DB;
use ModuleHelper;

/**
 * Esporta la definizione di un modulo custom (non i suoi dati) in un JSON
 * portabile: riga cms_moduls, struttura tabella, e i blocchi scritti dal wizard
 * nel controller generato (config/col/form/layout). Legge col/form/config
 * istanziando il controller generato e chiamando cbInit() invece di riparsare
 * il sorgente PHP con eval() (stesso risultato, senza eval() su testo estratto).
 * Spostato da ModulsController::getExport() (piano 335); il formato del JSON
 * (format_version 1) non cambia: i file gia' esportati restano importabili.
 */
class ModuleExporter
{
    /**
     * @return array{filename: string, json: string}
     *
     * @throws ModuleTransferException
     */
    public function export($id): array
    {
        $row = DB::table('cms_moduls')->where('id', $id)->whereNull('deleted_at')->first();

        if (!$row || $row->is_protected) {
            throw new ModuleTransferException(trans('crudbooster.module_export_not_exportable'), 'warning');
        }

        $controller_class = 'App\\Http\\Controllers\\' . $row->controller;

        if (!class_exists($controller_class)) {
            throw new ModuleTransferException(trans('crudbooster.module_export_controller_missing'), 'danger');
        }

        $instance = new $controller_class();
        $instance->cbInit();

        $config = ['table' => $row->table_name];
        foreach ([
            'title_field', 'limit', 'orderby', 'global_privilege',
            'button_table_action', 'button_bulk_action', 'button_action_style',
            'button_add', 'button_edit', 'button_delete', 'button_detail',
            'button_filter', 'button_import', 'button_export',
        ] as $key) {
            $config[$key] = $instance->$key;
        }

        $export = [
            'format_version' => 1,
            'exported_at' => now(),
            'module' => [
                'name' => $row->name,
                'icon' => $row->icon,
            ],
            'table' => [
                'name' => $row->table_name,
                'columns' => $this->exportTableColumns($row->table_name),
            ],
            'config' => $config,
            'col' => $instance->col,
            'form' => $instance->form,
        ];
        // layout a blocchi del form (docs/refactoring/197): chiave opzionale, assente nei moduli senza layout
        if (ModuleGeneratorLayout::isActive($instance->form_layout)) {
            $export['form_layout'] = $instance->form_layout;
        }

        $filename = ModuleHelper::sql_name_encode($row->table_name) . '_module_export.json';

        return [
            'filename' => $filename,
            'json' => json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];
    }

    // Colonne della tabella per l'export, con gli stessi tipi del generatore
    // (text/number/boolean + plaintext/longtext/decimal/date/datetime/time, vedi
    // ModuleGeneratorFields::SQL_BY_TYPE). getTableStructure() li riduce a
    // text/number/boolean, quindi non va bene per ricreare la tabella fedelmente.
    // I file esportati prima di questa modifica hanno solo i tre tipi base: l'import
    // li gestisce ancora.
    private function exportTableColumns($table_name)
    {
        $reserved = config('app.reserved_column_names');
        $out = [];

        foreach (CRUDBooster::getTableStructure($table_name, 'verbose') as $c) {
            if (in_array($c['COLUMN_NAME'], $reserved)) {
                continue;
            }

            $entry = ['name' => $c['COLUMN_NAME']];
            switch ($c['DATA_TYPE']) {
                case 'int':
                    $entry['type'] = 'number';
                    $entry['size'] = preg_match('/^int\((\d+)\)/', $c['COLUMN_TYPE'], $m) ? (int) $m[1] : null;
                    break;
                case 'tinyint':
                    $entry['type'] = 'boolean';
                    $entry['size'] = 1;
                    break;
                case 'text':
                case 'mediumtext':
                    $entry['type'] = 'plaintext';
                    break;
                case 'longtext':
                    $entry['type'] = 'longtext';
                    break;
                case 'decimal':
                    $entry['type'] = 'decimal';
                    $entry['size'] = $c['NUMERIC_PRECISION'] . ',' . $c['NUMERIC_SCALE'];
                    break;
                case 'date':
                    $entry['type'] = 'date';
                    break;
                case 'datetime':
                case 'timestamp':
                    $entry['type'] = 'datetime';
                    break;
                case 'time':
                    $entry['type'] = 'time';
                    break;
                case 'varchar':
                    $entry['type'] = 'text';
                    $entry['size'] = $c['CHARACTER_MAXIMUM_LENGTH'];
                    break;
                default:
                    $entry['type'] = 'text';
                    break;
            }
            $out[] = $entry;
        }

        return $out;
    }
}
