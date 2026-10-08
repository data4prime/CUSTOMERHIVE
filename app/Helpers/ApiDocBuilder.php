<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * Documentazione automatica di un endpoint API (campo Descrizione /
 * export Postman): per ogni parametro attivo dice se e' obbligatorio, il
 * formato atteso e, per le select del modulo, i valori ammessi.
 *
 * Le informazioni sulle select vengono dal blocco FORM del controller del
 * modulo (stesso blocco che legge il module generator). Il testo generato sta
 * in un <div data-ch-autodoc> dentro la Descrizione: rigenerarlo non tocca il
 * testo scritto a mano fuori da quel blocco.
 *
 * Per le select su altra tabella (datatable) NON si elencano i record: sono
 * dati variabili (e per tenant), la Descrizione e' visibile anche nella
 * documentazione pubblica. Si indica solo da dove vengono i valori.
 */
class ApiDocBuilder
{
    private const BLOCK_RE = '/<div[^>]*data-ch-autodoc[^>]*>.*?<\/div>/is';

    /** @var array<string, array> cache voci FORM per tabella */
    private static $formCache = [];

    /** Blocco HTML della documentazione ('' se non c'e' nulla da dire). */
    public static function build(string $table, string $aksi, array $parameters): string
    {
        $html = '';
        $items = '';
        foreach (self::notes($table, $parameters) as $name => $note) {
            $items .= '<li><code>' . e($name) . '</code> &mdash; ' . $note . '</li>';
        }
        if ($items !== '') {
            $html .= '<p><strong>' . e(trans('crudbooster.api_autodoc_title')) . '</strong></p><ul>' . $items . '</ul>';
        }
        if ($aksi === 'list') {
            $html .= '<p>' . e(trans('crudbooster.api_autodoc_list_extra')) . '</p>';
        }

        return $html === '' ? '' : '<div data-ch-autodoc="1">' . $html . '</div>';
    }

    /**
     * Nota (HTML) per ogni parametro attivo, indicizzata per nome.
     *
     * @return array<string, string>
     */
    public static function notes(string $table, array $parameters): array
    {
        $form = self::formEntries($table);
        $out = [];
        foreach ($parameters as $p) {
            if (empty($p['used']) || empty($p['name'])) {
                continue;
            }
            $out[$p['name']] = self::paramNote($p, $form[$p['name']] ?? null);
        }

        return $out;
    }

    /** Sostituisce (o aggiunge in coda) il blocco automatico nella Descrizione. */
    public static function merge(?string $existing, string $block): string
    {
        $existing = (string) $existing;
        if (preg_match(self::BLOCK_RE, $existing)) {
            return trim(preg_replace_callback(self::BLOCK_RE, fn () => $block, $existing, 1));
        }

        return $block === '' ? $existing : $existing . $block;
    }

    /** True se il blocco automatico presente e' equivalente a $block (spazi ignorati). */
    public static function sameBlock(?string $existing, string $block): bool
    {
        $current = preg_match(self::BLOCK_RE, (string) $existing, $m) ? $m[0] : '';
        $norm = fn ($s) => preg_replace('/\s+/', '', $s);

        return $norm($current) === $norm($block);
    }

    /** HTML -> testo semplice (Postman mostra la descrizione come markdown). */
    public static function toText(?string $html): string
    {
        $t = preg_replace('#<code[^>]*>(.*?)</code>#is', '`$1`', (string) $html);
        $t = preg_replace('#<li[^>]*>#i', '- ', $t);
        $t = preg_replace('#</(p|li|tr|h[1-6]|div|ul|ol)>|<br\s*/?>#i', "\n", $t);
        $t = html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = preg_replace("/[ \t]+\n/", "\n", $t);

        return trim(preg_replace("/\n{3,}/", "\n\n", $t));
    }

    private static function paramNote(array $p, ?array $entry): string
    {
        $parts = [e(trans(!empty($p['required']) ? 'crudbooster.api_autodoc_required' : 'crudbooster.api_autodoc_optional'))];

        $type = (string) ($p['type'] ?? '');
        $formType = (string) ($entry['type'] ?? '');

        if (!empty($entry['dataenum'])) {
            $values = [];
            $enum = is_array($entry['dataenum']) ? $entry['dataenum'] : explode(';', (string) $entry['dataenum']);
            foreach ($enum as $item) {
                $item = trim((string) $item);
                if ($item === '') {
                    continue;
                }
                if (strpos($item, '|') !== false) {
                    [$value, $label] = explode('|', $item, 2);
                    $values[] = '<code>' . e($value) . '</code> (' . e($label) . ')';
                } else {
                    $values[] = '<code>' . e($item) . '</code>';
                }
            }
            if ($values) {
                $parts[] = e(trans('crudbooster.api_autodoc_values')) . ': ' . implode(', ', $values);
            }
        } elseif (!empty($entry['datatable'])) {
            $t = explode(',', (string) $entry['datatable']);
            $parts[] = e(trans('crudbooster.api_autodoc_ref', ['table' => $t[0] ?? '', 'column' => $t[1] ?? '']));
        } elseif (!empty($entry['dataquery'])) {
            $parts[] = e(trans('crudbooster.api_autodoc_query'));
        } elseif ($type === 'date') {
            $parts[] = e(trans('crudbooster.api_autodoc_date', ['format' => 'Y-m-d']));
        } elseif (strpos($type, 'date_format:') === 0) {
            $parts[] = e(trans('crudbooster.api_autodoc_date', ['format' => substr($type, 12)]));
        } elseif ($type === 'email') {
            $parts[] = e(trans('crudbooster.api_autodoc_email'));
        } elseif (in_array($type, ['image', 'file'], true) || in_array($formType, ['upload', 'filemanager'], true)) {
            $parts[] = e(trans('crudbooster.api_autodoc_file'));
        } elseif (in_array($type, ['integer', 'numeric'], true)) {
            $parts[] = e(trans('crudbooster.api_autodoc_number'));
        }

        return implode('. ', $parts);
    }

    /** Voci del blocco FORM del controller del modulo, per nome campo. */
    private static function formEntries(string $table): array
    {
        if (isset(self::$formCache[$table])) {
            return self::$formCache[$table];
        }
        $byName = [];
        try {
            $module = DB::table('cms_moduls')->where('table_name', $table)->whereNull('deleted_at')->first();
            $path = $module && $module->controller ? app_path('Http/Controllers/' . $module->controller . '.php') : null;
            if ($path && file_exists($path)) {
                foreach (ModuleGeneratorList::readBlock((string) file_get_contents($path), 'FORM') as $entry) {
                    if (!empty($entry['name'])) {
                        $byName[$entry['name']] = $entry;
                    }
                }
            }
        } catch (\Throwable $e) {
            // controller non leggibile: si documentano solo i tipi dei parametri
            $byName = [];
        }

        return self::$formCache[$table] = $byName;
    }
}
