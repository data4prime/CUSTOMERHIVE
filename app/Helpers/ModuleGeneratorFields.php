<?php

namespace App\Helpers;

/**
 * Logica pura del passo "Campi" del module generator (wizard v2).
 *
 * Nessun accesso a request/DB/filesystem: lavora su array e stringhe. Il
 * formato scritto nel blocco FORM del controller e' quello di sempre
 * ($this->form[] = [...]). Il passo non modifica, rinomina o elimina colonne
 * esistenti: puo' solo aggiungerne di nuove. Vedi docs/refactoring/196.
 */
class ModuleGeneratorFields
{
    /** Tipi che non corrispondono a una colonna della tabella. */
    const NO_COLUMN = ['header', 'child', 'custom', 'googlemaps', 'group_items_datamodal', 'group_members_datamodal',
        'group_tenant_datamodal', 'item_access_datamodal', 'item_tenant_datamodal', 'tenant_group_datamodal', 'user_groups_datamodal'];

    const CHOICE = ['select', 'select2', 'radio', 'checkbox'];

    const MODAL = ['datamodal', 'group_items_datamodal', 'group_members_datamodal', 'group_tenant_datamodal',
        'item_access_datamodal', 'item_tenant_datamodal', 'tenant_group_datamodal', 'user_groups_datamodal'];

    /** Chiavi delle opzioni dei tipi: vengono rigenerate dal passo. */
    const OPTION_KEYS = ['dataenum', 'datatable', 'datatable_where', 'dataquery', 'datamodal_table', 'datamodal_columns',
        'datamodal_size', 'datamodal_where', 'filemanager_type', 'latitude', 'longitude', 'html', 'shape', 'size',
        'currency', 'decimals'];

    /** Chiavi generiche che si conservano anche se cambia il tipo. */
    const GENERIC_KEYS = ['help', 'style', 'readonly', 'disabled', 'placeholder', 'value'];

    /** Chiavi sempre gestite dal passo (non vengono ricopiate dalla voce esistente). */
    const MANAGED = ['label', 'name', 'type', 'validation', 'width', 'required'];

    const DEFAULT_WIDTH = 'col-sm-10';

    /* ------------------------------------------------------------------ */
    /*  Tipi e colonne                                                      */
    /* ------------------------------------------------------------------ */

    /** Tipi che si salvano come file caricato (stessa validazione: dimensione massima in MB). */
    public static function isFileType(string $type): bool
    {
        return $type === 'upload' || $type === 'image';
    }

    public static function hasColumn(string $type): bool
    {
        return !in_array($type, self::NO_COLUMN, true);
    }

    /**
     * Colonna creata per un campo nuovo: [tipo di colonna, dimensione di default].
     * Tipi di colonna: text (VARCHAR) | number (INT) con dimensione scelta, plaintext (TEXT),
     * longtext (LONGTEXT), decimal ("precisione,scala"), date, datetime, time.
     * I tipi non elencati usano VARCHAR(255); vedi anche dbTypeFor() per le scelte da tabella.
     */
    const SQL_BY_TYPE = [
        'textarea' => ['plaintext', ''], 'multitext' => ['plaintext', ''],
        'ckeditor' => ['longtext', ''], 'tinymce' => ['longtext', ''], 'wysiwyg' => ['longtext', ''], 'json' => ['longtext', ''],
        'number' => ['number', '11'], 'money' => ['decimal', '12,2'], 'percent' => ['decimal', '5,2'],
        'date' => ['date', ''], 'datetime' => ['datetime', ''], 'time' => ['time', ''],
        'datamodal' => ['number', '11'], 'color' => ['text', '20'],
    ];

    /** Tipi di campo la cui colonna e' un id numerico quando i valori vengono da un'altra tabella. */
    const TABLE_CHOICE = ['select', 'select2'];

    /** Tipo di colonna del generatore per un tipo di campo (vedi SQL_BY_TYPE). */
    public static function dbTypeFor(string $type, array $opts = []): string
    {
        return self::sqlSpec($type, $opts)[0];
    }

    /** Dimensione di default della colonna: '' se il tipo non ne ha, "p,s" per i decimali. */
    public static function defaultSize(string $type, array $opts = []): string
    {
        return self::sqlSpec($type, $opts)[1];
    }

    private static function sqlSpec(string $type, array $opts): array
    {
        if (in_array($type, self::TABLE_CHOICE, true) && ($opts['source'] ?? '') === 'table') {
            return ['number', '11'];
        }
        // importo/percentuale con cifre decimali scelte: la colonna nuova ha la scala giusta
        if (($type === 'money' || $type === 'percent') && isset($opts['decimals']) && $opts['decimals'] !== '' && is_numeric($opts['decimals'])) {
            $d = max(0, min(6, (int) $opts['decimals']));

            return ['decimal', ($type === 'money' ? max(12, $d + 8) : max(5, $d + 3)) . ',' . $d];
        }

        return self::SQL_BY_TYPE[$type] ?? ['text', '255'];
    }

    /** Tabelle di sistema utili come sorgente di scelte/collegamenti (le altre tabelle di sistema si nascondono). */
    const SELECTABLE_SYSTEM = ['cms_users', 'tenants', 'groups'];

    /** Tabelle di infrastruttura: prefissi e nomi che non hanno senso come sorgente di scelte. */
    const HIDDEN_PREFIXES = ['cms_', 'mfa_', 'qlik', 'chat_ai', 'chatai_', 'dashboard_', 'module_', 'menu_', 'items_', 'sync_'];
    const HIDDEN_NAMES = ['migrations', 'failed_jobs', 'jobs', 'job_batches', 'password_resets', 'personal_access_tokens', 'license',
        'log', 'sessions', 'cache', 'cache_locks', 'users', 'users_groups', 'group_tenants', 'tenants_allowed', 'attributes'];

    /**
     * Tabelle da proporre nelle select "Quale tabella?": quelle dei moduli e le tabelle utente,
     * piu' cms_users, tenants e groups. Restano fuori tabelle di sistema, di servizio (log, code,
     * licenza, MFA, Qlik...) e di collegamento. $keep = tabelle gia' in uso, da non togliere.
     */
    public static function selectableTables(array $all, array $keep = []): array
    {
        $out = [];
        foreach ($all as $t) {
            $t = (string) $t;
            $hidden = false;
            if (!in_array($t, self::SELECTABLE_SYSTEM, true)) {
                if (in_array($t, self::HIDDEN_NAMES, true)) {
                    $hidden = true;
                } else {
                    foreach (self::HIDDEN_PREFIXES as $p) {
                        if (strpos($t, $p) === 0) {
                            $hidden = true;
                            break;
                        }
                    }
                }
            }
            if (!$hidden || in_array($t, $keep, true)) {
                $out[] = $t;
            }
        }

        return $out;
    }

    /** Tipo di campo suggerito per una colonna che non e' ancora nel form. */
    public static function guessType(string $dbType): string
    {
        return ['text' => 'text', 'number' => 'number', 'boolean' => 'radio'][$dbType] ?? 'text';
    }

    /* ------------------------------------------------------------------ */
    /*  Validazione: da stringa Laravel a scelte guidate e ritorno          */
    /* ------------------------------------------------------------------ */

    public static function emptyRules(): array
    {
        return ['min' => '', 'max' => '', 'unique' => false, 'unique_raw' => '', 'alpha' => '', 'datePolicy' => '',
            'fileKind' => 'any', 'maxMb' => '', 'extra' => []];
    }

    /**
     * Scompone la stringa di validazione in regole note + "extra" (tutto cio'
     * che l'interfaccia non gestisce, conservato cosi' com'e').
     * Restituisce ['required' => bool, 'rules' => [...]].
     */
    public static function parseValidation(string $validation, string $type): array
    {
        $rules = self::emptyRules();
        $required = false;
        foreach (array_filter(explode('|', $validation), 'strlen') as $tok) {
            if ($tok === 'required') {
                $required = true;
            } elseif (preg_match('/^min:(-?\d+(\.\d+)?)$/', $tok, $m) && $rules['min'] === '' && !self::isFileType($type)) {
                $rules['min'] = $m[1];
            } elseif (preg_match('/^max:(-?\d+(\.\d+)?)$/', $tok, $m) && !self::isFileType($type) && $rules['max'] === '') {
                $rules['max'] = $m[1];
            } elseif (preg_match('/^max:(\d+)$/', $tok, $m) && self::isFileType($type) && $rules['maxMb'] === '' && (int) $m[1] > 0 && (int) $m[1] % 1024 === 0) {
                $rules['maxMb'] = (string) ((int) $m[1] / 1024);
            } elseif (strpos($tok, 'unique:') === 0 && !$rules['unique']) {
                $rules['unique'] = true;
                $rules['unique_raw'] = $tok;
            } elseif ($tok === 'alpha_spaces' && $rules['alpha'] === '') {
                $rules['alpha'] = 'letters';
            } elseif ($tok === 'alpha_num' && $rules['alpha'] === '') {
                $rules['alpha'] = 'alnum';
            } elseif ($tok === 'after_or_equal:today' && $rules['datePolicy'] === '') {
                $rules['datePolicy'] = 'nopast';
            } elseif ($tok === 'before_or_equal:today' && $rules['datePolicy'] === '') {
                $rules['datePolicy'] = 'nofuture';
            } elseif ($tok === 'image' && $rules['fileKind'] === 'any') {
                $rules['fileKind'] = 'image';
            } elseif ($tok === 'mimes:pdf,doc,docx,xls,xlsx' && $rules['fileKind'] === 'any') {
                $rules['fileKind'] = 'doc';
            } else {
                $rules['extra'][] = $tok;
            }
        }

        return ['required' => $required, 'rules' => $rules];
    }

    /** Controlli di tipo che si aggiungono solo ai campi NUOVI. */
    public static function impliedRule(string $type): string
    {
        return [
            'text' => 'string', 'textarea' => 'string', 'email' => 'email', 'password' => 'string',
            'number' => 'integer', 'money' => 'numeric', 'percent' => 'numeric',
            'date' => 'date', 'datetime' => 'date',
        ][$type] ?? '';
    }

    public static function normalizeRules($rules): array
    {
        $r = array_merge(self::emptyRules(), is_array($rules) ? $rules : []);
        $r['extra'] = array_values(array_filter(array_map('strval', (array) $r['extra']), 'strlen'));
        foreach (['min', 'max', 'maxMb'] as $k) {
            $r[$k] = $r[$k] === null ? '' : (string) $r[$k];
            if ($r[$k] !== '' && !preg_match('/^-?\d+(\.\d+)?$/', $r[$k])) {
                throw new \InvalidArgumentException('mg_fld_err_rules');
            }
        }
        $r['unique'] = !empty($r['unique']);
        $r['alpha'] = in_array($r['alpha'], ['letters', 'alnum'], true) ? $r['alpha'] : '';
        $r['datePolicy'] = in_array($r['datePolicy'], ['nopast', 'nofuture'], true) ? $r['datePolicy'] : '';
        $r['fileKind'] = in_array($r['fileKind'], ['image', 'doc'], true) ? $r['fileKind'] : 'any';
        $r['unique_raw'] = is_string($r['unique_raw']) && strpos($r['unique_raw'], 'unique:') === 0 && preg_match('/^unique:[A-Za-z0-9_.,]+$/', $r['unique_raw']) ? $r['unique_raw'] : '';
        foreach ($r['extra'] as $tok) {
            // gli "extra" sono token Laravel gia' presenti nel file: niente separatori ne' spazi
            if (preg_match('/[\s]|\|/', $tok)) {
                throw new \InvalidArgumentException('mg_fld_err_rules');
            }
        }

        return $r;
    }

    public static function buildValidation(bool $required, array $rules, string $type, bool $isNew, string $table, string $name): string
    {
        $p = [];
        if ($required) {
            $p[] = 'required';
        }
        $implied = self::impliedRule($type);
        if ($isNew && $implied !== '' && !in_array($implied, $rules['extra'], true)) {
            $p[] = $implied;
        }
        if ($rules['alpha'] === 'letters') {
            $p[] = 'alpha_spaces';
        } elseif ($rules['alpha'] === 'alnum') {
            $p[] = 'alpha_num';
        }
        if (self::isFileType($type)) {
            if ($type === 'image' || $rules['fileKind'] === 'image') {
                $p[] = 'image';
            } elseif ($rules['fileKind'] === 'doc') {
                $p[] = 'mimes:pdf,doc,docx,xls,xlsx';
            }
            if ($rules['maxMb'] !== '') {
                $p[] = 'max:' . ((int) $rules['maxMb'] * 1024);
            }
        } else {
            if ($rules['min'] !== '') {
                $p[] = 'min:' . $rules['min'];
            }
            if ($rules['max'] !== '') {
                $p[] = 'max:' . $rules['max'];
            }
        }
        if ($rules['unique']) {
            $p[] = $rules['unique_raw'] !== '' ? $rules['unique_raw'] : 'unique:' . $table . ',' . $name;
        }
        if ($rules['datePolicy'] === 'nopast') {
            $p[] = 'after_or_equal:today';
        } elseif ($rules['datePolicy'] === 'nofuture') {
            $p[] = 'before_or_equal:today';
        }

        return implode('|', array_merge($p, $rules['extra']));
    }

    /* ------------------------------------------------------------------ */
    /*  Opzioni dei tipi: da chiavi della voce a scelte guidate e ritorno   */
    /* ------------------------------------------------------------------ */

    public static function emptyOpts(): array
    {
        return ['source' => 'enum', 'enum' => [''], 'table' => '', 'column' => '', 'where' => '', 'query' => '',
            'mtable' => '', 'mcols' => [], 'msize' => 'large', 'mwhere' => '', 'ftype' => 'file', 'lat' => '', 'lng' => '', 'html' => '',
            'shape' => 'circle', 'isize' => '96', 'currency' => 'EUR', 'decimals' => '2'];
    }

    public static function parseOpts(array $entry): array
    {
        $o = self::emptyOpts();
        if (isset($entry['dataenum']) && $entry['dataenum'] !== '') {
            $o['source'] = 'enum';
            $o['enum'] = is_array($entry['dataenum']) ? array_values(array_map('strval', $entry['dataenum'])) : explode(';', (string) $entry['dataenum']);
        } elseif (!empty($entry['datatable'])) {
            $p = explode(',', (string) $entry['datatable']);
            $o['source'] = 'table';
            $o['table'] = $p[0] ?? '';
            $o['column'] = $p[1] ?? '';
            $o['where'] = (string) ($entry['datatable_where'] ?? '');
        } elseif (!empty($entry['dataquery'])) {
            $o['source'] = 'query';
            $o['query'] = (string) $entry['dataquery'];
        }
        $o['mtable'] = (string) ($entry['datamodal_table'] ?? '');
        $o['mcols'] = isset($entry['datamodal_columns']) && $entry['datamodal_columns'] !== '' ? array_map('trim', explode(',', (string) $entry['datamodal_columns'])) : [];
        $o['msize'] = ($entry['datamodal_size'] ?? 'large') === 'small' ? 'small' : 'large';
        $o['mwhere'] = (string) ($entry['datamodal_where'] ?? '');
        $o['ftype'] = ($entry['filemanager_type'] ?? 'file') === 'image' ? 'image' : 'file';
        $o['lat'] = (string) ($entry['latitude'] ?? '');
        $o['lng'] = (string) ($entry['longitude'] ?? '');
        $o['html'] = (string) ($entry['html'] ?? '');
        $o['shape'] = ($entry['shape'] ?? 'circle') === 'square' ? 'square' : 'circle';
        $o['isize'] = (string) max(48, min(240, (int) ($entry['size'] ?? 96)));
        // importo: senza chiavi nel file vale il comportamento di sempre (euro, nessun decimale);
        // percentuale: senza 'decimals' = numero "naturale" (vuoto)
        $o['currency'] = array_key_exists('currency', $entry) ? (string) $entry['currency'] : (array_key_exists('prefix', $entry) ? '' : 'EUR');
        $isPercent = self::type($entry) === 'percent';
        $o['decimals'] = isset($entry['decimals']) && $entry['decimals'] !== '' ? (string) max(0, min(6, (int) $entry['decimals'])) : ($isPercent ? '' : '0');

        return $o;
    }

    /**
     * Valida le opzioni inviate e restituisce le chiavi da scrivere nella voce.
     * Se le scelte coincidono con quelle gia' presenti nella voce esistente
     * ($base) si riusano le chiavi originali (conserva sintassi particolari,
     * es. datatable con piu' parti, dataenum con valore|etichetta).
     */
    public static function optionEntries(string $type, array $opts, ?array $base, array $knownTables, string $label): array
    {
        $o = array_merge(self::emptyOpts(), $opts);
        $keep = function (array $keys) use ($base) {
            $out = [];
            foreach ($keys as $k) {
                if (isset($base[$k]) && $base[$k] !== '') {
                    $out[$k] = $base[$k];
                }
            }

            return $out;
        };
        $same = $base !== null && self::type($base) === $type;
        $baseOpts = $same ? self::parseOpts($base) : null;

        if (in_array($type, self::CHOICE, true)) {
            if ($o['source'] === 'enum') {
                $items = array_values(array_filter(array_map(function ($v) {
                    return trim(str_replace([';', "\n", "\r"], ' ', (string) $v));
                }, (array) $o['enum']), 'strlen'));
                if (!$items) {
                    throw new \InvalidArgumentException('mg_fld_err_enum|' . $label);
                }
                if ($baseOpts && $baseOpts['source'] === 'enum' && array_values(array_filter(array_map('trim', $baseOpts['enum']), 'strlen')) === $items) {
                    return $keep(['dataenum']);
                }

                return ['dataenum' => implode(';', $items)];
            }
            if ($o['source'] === 'table') {
                $t = (string) $o['table'];
                $c = (string) $o['column'];
                if (!preg_match('/^[A-Za-z0-9_]+(\.[A-Za-z0-9_]+)?$/', $t) || !preg_match('/^[A-Za-z0-9_]+$/', $c) || !in_array($t, $knownTables, true)) {
                    throw new \InvalidArgumentException('mg_fld_err_table|' . $label);
                }
                $where = trim((string) $o['where']);
                if (mb_strlen($where) > 500) {
                    throw new \InvalidArgumentException('mg_fld_err_where|' . $label);
                }
                if ($baseOpts && $baseOpts['source'] === 'table' && $baseOpts['table'] === $t && $baseOpts['column'] === $c && trim($baseOpts['where']) === $where) {
                    return $keep(['datatable', 'datatable_where', 'datatable_format']);
                }
                $out = ['datatable' => $t . ',' . $c];
                if ($where !== '') {
                    $out['datatable_where'] = $where;
                }

                return $out;
            }
            $q = trim((string) $o['query']);
            if ($q === '' || mb_strlen($q) > 2000) {
                throw new \InvalidArgumentException('mg_fld_err_query|' . $label);
            }
            if ($baseOpts && $baseOpts['source'] === 'query' && trim($baseOpts['query']) === $q) {
                return $keep(['dataquery']);
            }

            return ['dataquery' => $q];
        }

        if (in_array($type, self::MODAL, true)) {
            $t = (string) $o['mtable'];
            $cols = array_values(array_filter(array_map('trim', (array) $o['mcols']), 'strlen'));
            if (!preg_match('/^[A-Za-z0-9_]+(\.[A-Za-z0-9_]+)?$/', $t) || !in_array($t, $knownTables, true) || !$cols) {
                throw new \InvalidArgumentException('mg_fld_err_modal|' . $label);
            }
            foreach ($cols as $c) {
                if (!preg_match('/^[A-Za-z0-9_]+$/', $c)) {
                    throw new \InvalidArgumentException('mg_fld_err_modal|' . $label);
                }
            }
            $out = ['datamodal_table' => $t, 'datamodal_columns' => implode(',', $cols), 'datamodal_size' => $o['msize'] === 'small' ? 'small' : 'large'];
            $where = trim((string) $o['mwhere']);
            if ($where !== '') {
                $out['datamodal_where'] = $where;
            }
            if ($baseOpts && $baseOpts['mtable'] === $t && $baseOpts['mcols'] === $cols && $baseOpts['msize'] === $out['datamodal_size'] && trim($baseOpts['mwhere']) === $where) {
                return $keep(['datamodal_table', 'datamodal_columns', 'datamodal_size', 'datamodal_where', 'datamodal_module_path', 'datamodal_columns_alias_name']);
            }

            return $out;
        }

        if ($type === 'money' || $type === 'percent') {
            $dec = trim((string) $o['decimals']);
            if ($dec !== '' && (!ctype_digit($dec) || (int) $dec > 6)) {
                throw new \InvalidArgumentException('mg_fld_err_decimals|' . $label);
            }
            $out = [];
            if ($type === 'money') {
                $currency = strtoupper(trim((string) $o['currency']));
                if ($currency !== '' && !isset(NumberFormat::CURRENCIES[$currency])) {
                    throw new \InvalidArgumentException('mg_fld_err_currency|' . $label);
                }
                $out['currency'] = $currency;
            }
            if ($dec !== '') {
                $out['decimals'] = (int) $dec;
            }
            // scelte uguali al comportamento di sempre (euro, nessun decimale) su una voce che non ha le chiavi: non si scrivono
            if ($base !== null && !isset($base['currency']) && !isset($base['decimals'])
                && ($type === 'percent' ? $dec === '' : ($out['currency'] === (array_key_exists('prefix', $base) ? '' : 'EUR') && $dec === '0'))) {
                return [];
            }

            return $out;
        }
        if ($type === 'image') {
            $out = [];
            if ($o['shape'] === 'square') {
                $out['shape'] = 'square';
            }
            $size = max(48, min(240, (int) $o['isize']));
            if ($size !== 96) {
                $out['size'] = $size;
            }

            return $out;
        }
        if ($type === 'filemanager') {
            return ['filemanager_type' => $o['ftype'] === 'image' ? 'image' : 'file'];
        }
        if ($type === 'googlemaps') {
            $out = [];
            foreach (['latitude' => 'lat', 'longitude' => 'lng'] as $k => $f) {
                if ($o[$f] !== '') {
                    if (!preg_match('/^[A-Za-z0-9_]+$/', (string) $o[$f])) {
                        throw new \InvalidArgumentException('mg_fld_err_maps|' . $label);
                    }
                    $out[$k] = (string) $o[$f];
                }
            }

            return $out;
        }
        if ($type === 'custom') {
            $h = (string) $o['html'];
            if (trim($h) === '' || mb_strlen($h) > 5000) {
                throw new \InvalidArgumentException('mg_fld_err_html|' . $label);
            }

            return ['html' => $h];
        }

        return [];
    }

    private static function type(array $entry): string
    {
        return isset($entry['type']) && $entry['type'] !== '' ? (string) $entry['type'] : 'text';
    }

    /* ------------------------------------------------------------------ */
    /*  Righe iniziali dell'editor                                          */
    /* ------------------------------------------------------------------ */

    /**
     * @param array $structure colonne della tabella da getTableStructure() (name,type,size), senza le colonne di sistema
     * @param array $form      voci attuali del blocco FORM
     * @param array $tableColumns tutti i nomi di colonna reali (comprese quelle di sistema)
     */
    public static function describeFields(array $structure, array $form, array $tableColumns, array $yesNo = ['Yes', 'No']): array
    {
        $rows = [];
        $byName = [];
        foreach ($structure as $col) {
            $byName[$col['name']] = $col;
        }
        $seen = [];

        foreach ($form as $entry) {
            $name = (string) ($entry['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $type = self::type($entry);
            $parsed = self::parseValidation((string) ($entry['validation'] ?? ''), $type);
            $required = $parsed['required'] || !empty($entry['required']);
            $rows[] = [
                'name' => $name, 'label' => (string) ($entry['label'] ?? $name), 'type' => $type, 'required' => $required,
                'in_module' => true, 'exists' => in_array($name, $tableColumns, true), 'no_column' => !self::hasColumn($type),
                'db' => $byName[$name] ?? null, 'size' => '', 'opts' => self::parseOpts($entry), 'rules' => $parsed['rules'],
                'system' => in_array($name, (array) config('app.reserved_column_names'), true),
            ];
            $seen[$name] = true;
        }

        foreach ($structure as $col) {
            if (isset($seen[$col['name']])) {
                continue;
            }
            $type = self::guessType($col['type']);
            $opts = self::emptyOpts();
            if ($col['type'] === 'boolean') {
                $opts['enum'] = ['1|' . $yesNo[0], '0|' . $yesNo[1]];
            }
            $rows[] = [
                'name' => $col['name'], 'label' => ModuleHelper::sql_name_decode($col['name']), 'type' => $type, 'required' => false,
                'in_module' => false, 'exists' => true, 'no_column' => false, 'db' => $col, 'size' => '', 'opts' => $opts,
                'rules' => self::emptyRules(), 'system' => false,
            ];
        }

        return $rows;
    }

    /* ------------------------------------------------------------------ */
    /*  Costruzione di voci FORM e colonne nuove                            */
    /* ------------------------------------------------------------------ */

    /**
     * Valida le righe inviate e produce: le voci $this->form[] e le colonne
     * da aggiungere alla tabella. Lancia InvalidArgumentException con una
     * chiave di traduzione ("mg_fld_err_...", opzionalmente "chiave|parametro").
     *
     * @param array $rows          righe in ordine
     * @param array $existingForm  voci attuali del blocco FORM
     * @param array $tableColumns  colonne reali della tabella (tutte)
     * @param array $allowedTypes  nomi dei type_components disponibili
     * @param array $knownTables   tabelle esistenti (per le sorgenti delle scelte)
     * @param array $reserved      nomi di colonna riservati
     * @return array ['entries' => [...], 'new_columns' => [['name','type','size'], ...]]
     */
    public static function build(array $rows, array $existingForm, array $tableColumns, array $allowedTypes, array $knownTables, array $reserved, string $table): array
    {
        $baseByName = [];
        foreach ($existingForm as $e) {
            if (!empty($e['name']) && !isset($baseByName[$e['name']])) {
                $baseByName[$e['name']] = $e;
            }
        }
        $entries = [];
        $newColumns = [];
        $names = [];

        foreach ($rows as $row) {
            if (!is_array($row) || empty($row['in_module'])) {
                continue;
            }
            $name = (string) ($row['name'] ?? '');
            $label = trim((string) ($row['label'] ?? ''));
            $type = (string) ($row['type'] ?? 'text');
            $base = $baseByName[$name] ?? null;

            if ($label === '') {
                throw new \InvalidArgumentException('mg_fld_err_label');
            }
            $label = mb_substr($label, 0, 255);
            if (!in_array($type, $allowedTypes, true) && !($base !== null && self::type($base) === $type)) {
                throw new \InvalidArgumentException('mg_fld_err_type|' . $label);
            }
            $isExistingColumn = in_array($name, $tableColumns, true);
            // i nomi gia' presenti nel file o nella tabella non si rivalidano (restano com'erano)
            if ($base === null && !$isExistingColumn && !preg_match('/^[a-z][a-z0-9_]{0,63}$/', $name)) {
                throw new \InvalidArgumentException('mg_fld_err_name|' . $label);
            }
            if ($base === null && !$isExistingColumn && in_array($name, $reserved, true)) {
                throw new \InvalidArgumentException('mg_fld_err_reserved|' . $name);
            }
            if (isset($names[$name])) {
                throw new \InvalidArgumentException('mg_fld_err_duplicate|' . $name);
            }
            $names[$name] = true;

            $rules = self::normalizeRules($row['rules'] ?? []);
            $required = !empty($row['required']);
            $entry = ['label' => $label, 'name' => $name, 'type' => $type];

            // validazione: invariata (stringa originale) se le scelte coincidono
            $validation = null;
            if ($base !== null && !empty($base['validation'])) {
                $p = self::parseValidation((string) $base['validation'], $type);
                if ($p['required'] === $required && self::normalizeRules($p['rules']) == $rules) {
                    $validation = (string) $base['validation'];
                }
            }
            if ($validation === null) {
                $validation = self::buildValidation($required, $rules, $type, $base === null, $table, $name);
            }
            if ($validation !== '') {
                $entry['validation'] = $validation;
            }
            // la larghezza si imposta solo per i campi nuovi: una voce esistente
            // senza "width" resta senza (la vista usa il suo default)
            if ($base === null) {
                $entry['width'] = self::DEFAULT_WIDTH;
            } elseif (isset($base['width'])) {
                $entry['width'] = $base['width'];
            }

            $sameType = $base !== null && self::type($base) === $type;
            $opt = self::optionEntries($type, is_array($row['opts'] ?? null) ? $row['opts'] : [], $base, $knownTables, $label);
            $entry = array_merge($entry, $opt);

            if ($base !== null) {
                foreach ($base as $k => $v) {
                    if (in_array($k, self::MANAGED, true) || in_array($k, self::OPTION_KEYS, true) || isset($entry[$k])) {
                        continue;
                    }
                    if ($sameType || in_array($k, self::GENERIC_KEYS, true)) {
                        $entry[$k] = $v;
                    }
                }
                if (!empty($base['required']) && $required) {
                    $entry['required'] = $base['required'];
                }
            }
            $entries[] = $entry;

            if (self::hasColumn($type) && !$isExistingColumn) {
                $rowOpts = is_array($row['opts'] ?? null) ? $row['opts'] : [];
                $dbType = self::dbTypeFor($type, $rowOpts);
                $size = self::defaultSize($type, $rowOpts);
                // la dimensione scelta dall'utente vale solo per VARCHAR e INT
                if (in_array($dbType, ['text', 'number'], true)) {
                    $custom = (string) ($row['size'] ?? '');
                    if ($custom !== '' && ctype_digit($custom) && (int) $custom >= 1 && (int) $custom <= 4000) {
                        $size = $custom;
                    }
                }
                $newColumns[] = ['name' => $name, 'type' => $dbType, 'size' => $size];
            }
        }

        return ['entries' => $entries, 'new_columns' => $newColumns];
    }

    /** Sostituisce il blocco FORM del controller con le voci date. */
    public static function replaceFormBlock(string $contents, array $entries): string
    {
        $start = '# START FORM DO NOT REMOVE THIS LINE';
        $end = '# END FORM DO NOT REMOVE THIS LINE';
        $raw = explode($start, $contents);
        if (count($raw) < 2) {
            throw new \RuntimeException('Blocco FORM non trovato nel controller.');
        }
        $rraw = explode($end, $raw[1]);
        if (count($rraw) < 2) {
            throw new \RuntimeException('Blocco FORM non chiuso nel controller.');
        }
        $lines = [];
        foreach ($entries as $e) {
            $lines[] = "\t\t\t" . '$this->form[] = ' . min_var_export($e) . ';';
        }

        return trim($raw[0]) . "\n\n"
            . "\t\t\t{$start}\n"
            . "\t\t\t" . '$this->form = [];' . "\n"
            . implode("\n", $lines) . "\n"
            . "\t\t\t{$end}\n\n"
            . "\t\t\t" . trim($rraw[1]);
    }
}
