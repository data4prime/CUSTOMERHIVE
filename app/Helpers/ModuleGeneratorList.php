<?php

namespace App\Helpers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Logica pura del passo "Lista" del module generator (wizard v2).
 *
 * Nessun accesso a request/DB/filesystem: lavora su stringhe e array, cosi'
 * si puo' verificare senza il browser. Il formato scritto nel controller del
 * modulo e' quello di sempre ($this->col[] = [...]); le chiavi nuove sono
 * tutte opzionali (format, badge, col_width, calc) e ignorate dal codice
 * che non le conosce. Vedi docs/refactoring/194.
 */
class ModuleGeneratorList
{
    /** Formati selezionabili dal wizard (stringa vuota = valore grezzo). */
    const FORMATS = ['', 'date_short', 'date_long', 'datetime_short', 'money', 'money_eur', 'money_plain', 'badge', 'trunc', 'image', 'download'];

    /** Join automatico delle colonne di sistema (nome colonna => "tabella,colonna da mostrare"). */
    const SYSTEM_JOINS = [
        'created_by' => 'cms_users,name', 'updated_by' => 'cms_users,name', 'deleted_by' => 'cms_users,name',
        'tenant' => 'tenants,name', 'group' => 'groups,name',
    ];

    /** Larghezze predefinite in px; 'auto' = nessuna chiave. */
    const WIDTHS = ['narrow' => 80, 'medium' => 140, 'wide' => 240];

    /** Colonne di sistema create da Blueprint::defaults(). */
    const SYSTEM_COLUMNS = ['id', 'group', 'tenant', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'];

    /** Chiavi di una colonna esistente che il passo conserva senza modificarle. */
    const PRESERVED_KEYS = ['join_where', 'join_id', 'nl2br', 'color', 'callback', 'style', 'callback_php', 'query'];

    const DEFAULT_BADGE_COLOR = '#6c757d';

    /** Join da usare per una colonna di sistema senza join esplicito; null per le altre. */
    public static function systemJoin(string $column): ?string
    {
        return self::SYSTEM_JOINS[$column] ?? null;
    }

    /* ------------------------------------------------------------------ */
    /*  Lettura dei blocchi del controller                                  */
    /* ------------------------------------------------------------------ */

    /**
     * Valuta un blocco "# START <NOME> ... # END <NOME> ..." del controller
     * (stesso meccanismo di getStep3/getStep4: i blocchi sono scritti dal
     * wizard stesso con var_export) e restituisce l'array risultante.
     *
     * @param string $marker 'COLUMNS' | 'FORM'
     */
    public static function readBlock(string $contents, string $marker): array
    {
        $start = "# START {$marker} DO NOT REMOVE THIS LINE";
        $end = "# END {$marker} DO NOT REMOVE THIS LINE";
        if (strpos($contents, $start) === false || strpos($contents, $end) === false) {
            return [];
        }
        $unit = extract_unit($contents, $start, $end);
        $unit = str_replace('$this->', '$cb_', $unit);
        eval($unit);

        if ($marker === 'COLUMNS') {
            return $cb_col ?? [];
        }
        if ($marker === 'FORM LAYOUT') {
            return $cb_form_layout ?? [];
        }

        return $cb_form ?? [];
    }

    /**
     * Legge il valore di una proprieta' del blocco CONFIGURATION (es. limit,
     * orderby). Restituisce null se assente o non interpretabile.
     */
    public static function readConfigValue(string $contents, string $key)
    {
        $start = '# START CONFIGURATION DO NOT REMOVE THIS LINE';
        $end = '# END CONFIGURATION DO NOT REMOVE THIS LINE';
        if (strpos($contents, $start) === false || strpos($contents, $end) === false) {
            return null;
        }
        $inner = extract_unit($contents, $start, $end);
        if (!preg_match('/^[ \t]*\$this->' . preg_quote($key, '/') . '[ \t]*=(.*);[ \t]*$/m', $inner, $m)) {
            return null;
        }
        try {
            return eval('return ' . trim($m[1]) . ';');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** orderby come stringa "colonna,dir;colonna2,dir" (accetta anche l'array del vecchio formato). */
    public static function orderbyToString($value): string
    {
        if (is_array($value)) {
            $parts = [];
            foreach ($value as $k => $dir) {
                $parts[] = $k . ',' . $dir;
            }

            return implode(';', $parts);
        }

        return is_string($value) ? $value : '';
    }

    /* ------------------------------------------------------------------ */
    /*  Righe iniziali dell'editor                                          */
    /* ------------------------------------------------------------------ */

    /**
     * Costruisce le righe mostrate dall'editor: prima le colonne gia' nel
     * modulo (nell'ordine salvato), poi le altre colonne della tabella
     * (spente). Le colonne nascoste (visible=false) non sono righe: restano
     * nel file e vengono conservate da buildColumns().
     *
     * @param callable $typeOf function(string $column): string  tipo DB della colonna
     */
    public static function describeRows(array $tableColumns, array $existing, array $form, callable $typeOf): array
    {
        $rows = [];
        $used = [];
        $formByName = [];
        foreach ($form as $f) {
            if (!empty($f['name'])) {
                $formByName[$f['name']] = $f;
            }
        }

        foreach ($existing as $i => $col) {
            if (isset($col['visible']) && $col['visible'] === false) {
                continue;
            }
            $name = (string) ($col['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $row = self::baseRow($i, $name, (string) ($col['label'] ?? ''), true);

            // il marcatore 'calc' => 'php' (scritto dal wizard) va controllato per
            // primo: una colonna calcolata PHP e' agganciata a una colonna vera
            if (($col['calc'] ?? null) === 'php') {
                $row['kind'] = 'calc';
                $row['calc'] = ['lang' => 'php', 'expr' => (string) ($col['callback_php'] ?? ''), 'alias' => ''];
            } elseif (in_array($name, $tableColumns, true)) {
                $row['kind'] = 'col';
                $row['type'] = $typeOf($name);
                $row['system'] = in_array($name, self::SYSTEM_COLUMNS, true);
                $used[$name] = true;
            } elseif (self::parseSqlCalc($name) !== null) {
                $calc = self::parseSqlCalc($name);
                $row['kind'] = 'calc';
                $row['calc'] = ['lang' => 'sql', 'expr' => $calc['expr'], 'alias' => $calc['alias']];
            } else {
                $row['kind'] = 'legacy';
            }

            if ($row['kind'] === 'col' || $row['kind'] === 'legacy') {
                $row = self::applyFormatFromCol($row, $col);
                if (!empty($col['join'])) {
                    $p = explode(',', $col['join']);
                    $row['join'] = ['table' => $p[0] ?? '', 'column' => $p[1] ?? ''];
                }
                $row['advanced'] = !empty($col['callback_php']) || !empty($col['query']) || count(explode(',', (string) ($col['join'] ?? ''))) > 2;
            }

            $row['width'] = self::widthFromCol($col);
            $rows[] = $row;
        }

        foreach ($tableColumns as $name) {
            if (isset($used[$name])) {
                continue;
            }
            $row = self::baseRow(null, $name, ModuleHelper::sql_name_decode($name), false);
            $row['kind'] = 'col';
            $row['type'] = $typeOf($name);
            $row['system'] = in_array($name, self::SYSTEM_COLUMNS, true);
            if (isset($formByName[$name]['datatable']) && $formByName[$name]['datatable'] !== '') {
                $p = explode(',', $formByName[$name]['datatable']);
                if (count($p) >= 2) {
                    $row['join'] = ['table' => $p[0], 'column' => $p[1]];
                    $row['join_suggested'] = true;
                }
            }
            // created_by/tenant/group...: si propone da soli il collegamento alla tabella giusta
            if ($row['join'] === null && ($sj = self::systemJoin($name)) !== null) {
                $p = explode(',', $sj);
                $row['join'] = ['table' => $p[0], 'column' => $p[1]];
                $row['join_suggested'] = true;
            }
            $rows[] = $row;
        }

        foreach ($rows as &$r) {
            // valuta e decimali di partenza = quelli del campo importo del form, se ce l'ha
            $ff = $formByName[$r['name']] ?? null;
            if ($ff && ($ff['type'] ?? '') === 'money' && $r['format'] !== 'money') {
                $r['currency'] = array_key_exists('currency', $ff) ? (string) $ff['currency'] : $r['currency'];
                $r['decimals'] = isset($ff['decimals']) ? self::decimalsOf($ff['decimals']) : $r['decimals'];
            }
            $r['enum'] = null;
            if (isset($formByName[$r['name']]['dataenum']) && $formByName[$r['name']]['dataenum'] !== '') {
                $r['enum'] = array_values(array_filter(array_map('trim', explode(';', $formByName[$r['name']]['dataenum'])), 'strlen'));
            }
        }
        unset($r);

        return $rows;
    }

    private static function baseRow($src, string $name, string $label, bool $show): array
    {
        return [
            'src' => $src, 'kind' => 'col', 'name' => $name, 'label' => $label, 'show' => $show,
            'type' => 'varchar', 'system' => false, 'format' => '', 'trunc' => 60, 'width' => 'auto',
            'join' => null, 'join_suggested' => false, 'advanced' => false, 'enum' => null, 'currency' => 'EUR', 'decimals' => 2,
            'badge' => ['colors' => new \stdClass(), 'default' => self::DEFAULT_BADGE_COLOR],
            'calc' => null,
        ];
    }

    private static function applyFormatFromCol(array $row, array $col): array
    {
        if (!empty($col['image'])) {
            $row['format'] = 'image';
        } elseif (!empty($col['download'])) {
            $row['format'] = 'download';
        } elseif (!empty($col['str_limit'])) {
            $row['format'] = 'trunc';
            $row['trunc'] = (int) $col['str_limit'];
        } elseif (!empty($col['format']) && in_array($col['format'], self::FORMATS, true)) {
            $row['format'] = $col['format'];
            if ($col['format'] === 'money_eur') {
                // vecchio formato fisso in euro: nell'editor e' un "importo" con valuta EUR e 2 decimali
                $row['format'] = 'money';
            } elseif ($col['format'] === 'money') {
                $row['currency'] = (string) ($col['currency'] ?? '');
                $row['decimals'] = self::decimalsOf($col['decimals'] ?? 2);
            }
            if ($col['format'] === 'badge' && isset($col['badge']) && is_array($col['badge'])) {
                $colors = [];
                foreach (($col['badge']['colors'] ?? []) as $k => $v) {
                    if (self::isHex($v)) {
                        $colors[(string) $k] = $v;
                    }
                }
                $row['badge'] = [
                    'colors' => $colors ?: new \stdClass(),
                    'default' => self::isHex($col['badge']['default'] ?? null) ? $col['badge']['default'] : self::DEFAULT_BADGE_COLOR,
                ];
            }
        }

        return $row;
    }

    private static function widthFromCol(array $col): string
    {
        if (!isset($col['col_width']) || (int) $col['col_width'] <= 0) {
            return 'auto';
        }
        $px = (int) $col['col_width'];
        $preset = array_search($px, self::WIDTHS, true);

        return $preset !== false ? $preset : (string) $px;
    }

    /** name del tipo "(espressione) as alias" scritto dal wizard (o "espr as alias"). */
    public static function parseSqlCalc(string $name): ?array
    {
        if (!preg_match('/^(.*)\s+as\s+([A-Za-z0-9_]+)$/s', $name, $m)) {
            return null;
        }
        $expr = trim($m[1]);
        if ($expr === '') {
            return null;
        }
        if (substr($expr, 0, 1) === '(' && substr($expr, -1) === ')') {
            $expr = trim(substr($expr, 1, -1));
        }

        return ['expr' => $expr, 'alias' => $m[2]];
    }

    /* ------------------------------------------------------------------ */
    /*  Costruzione delle colonne da salvare                                */
    /* ------------------------------------------------------------------ */

    /**
     * Valida le righe inviate dall'editor e produce le voci $this->col[].
     * Lancia InvalidArgumentException con una chiave di traduzione
     * ("mg_list_err_...", opzionalmente "chiave|parametro") in caso di errore.
     *
     * @param array $rows         righe in ordine di visualizzazione
     * @param array $existing     voci attuali del blocco COLUMNS
     * @param array $tableColumns colonne reali della tabella del modulo
     * @param array $knownTables  nomi delle tabelle esistenti (per i join)
     */
    public static function buildColumns(array $rows, array $existing, array $tableColumns, array $knownTables): array
    {
        $cols = [];
        $shownNames = [];
        $aliases = [];
        $needHelpers = [];

        foreach ($rows as $row) {
            if (!is_array($row) || empty($row['show'])) {
                continue;
            }
            $kind = $row['kind'] ?? 'col';
            $name = (string) ($row['name'] ?? '');
            $label = trim((string) ($row['label'] ?? ''));

            $base = [];
            if (isset($row['src']) && $row['src'] !== null && isset($existing[(int) $row['src']])) {
                $cand = $existing[(int) $row['src']];
                if (($cand['name'] ?? null) === $name) {
                    $base = $cand;
                }
            }

            if ($kind === 'calc') {
                $calc = is_array($row['calc'] ?? null) ? $row['calc'] : [];
                $lang = ($calc['lang'] ?? 'sql') === 'php' ? 'php' : 'sql';
                $expr = trim((string) ($calc['expr'] ?? ''));
                if ($label === '') {
                    throw new \InvalidArgumentException('mg_list_err_label');
                }
                if ($expr === '' || mb_strlen($expr) > 1000) {
                    throw new \InvalidArgumentException('mg_list_err_expr|' . $label);
                }
                $entry = ['label' => self::cut($label)];
                if ($lang === 'sql') {
                    if (preg_match('/;|--|\/\*/', $expr)) {
                        throw new \InvalidArgumentException('mg_list_err_sql_chars|' . $label);
                    }
                    // il codice di getIndex cerca il PRIMO " as " minuscolo per
                    // separare l'alias: nell'espressione si usa sempre AS maiuscolo
                    $expr = preg_replace('/\s+as\s+/i', ' AS ', $expr);
                    $alias = preg_match('/^[A-Za-z0-9_]+$/', (string) ($calc['alias'] ?? '')) ? $calc['alias'] : 'calc_' . (Str::slug($label, '_') ?: 'col');
                    $n = 2;
                    $orig = $alias;
                    while (isset($aliases[$alias])) {
                        $alias = $orig . '_' . $n++;
                    }
                    $aliases[$alias] = true;
                    $entry['name'] = '(' . $expr . ') as ' . $alias;
                } else {
                    preg_match_all('/\[([A-Za-z0-9_]+)\]/', $expr, $mm);
                    $tokens = array_values(array_unique($mm[1]));
                    foreach ($tokens as $t) {
                        if (!in_array($t, $tableColumns, true)) {
                            throw new \InvalidArgumentException('mg_list_err_php_field|' . $t);
                        }
                    }
                    $anchor = $tokens[0] ?? (in_array('id', $tableColumns, true) ? 'id' : ($tableColumns[0] ?? ''));
                    if ($anchor === '') {
                        throw new \InvalidArgumentException('mg_list_err_expr|' . $label);
                    }
                    $entry['name'] = $anchor;
                    $entry['callback_php'] = $expr;
                    $entry['calc'] = 'php';
                    foreach ($tokens as $t) {
                        $needHelpers[$t] = true;
                    }
                }
                $entry = array_merge($entry, self::widthEntry($row));
                $cols[] = $entry;
                continue;
            }

            // colonna normale o "legacy" (nome non e' una colonna semplice)
            if ($kind === 'col') {
                if (!in_array($name, $tableColumns, true)) {
                    throw new \InvalidArgumentException('mg_list_err_column|' . $name);
                }
            } elseif (empty($base)) {
                throw new \InvalidArgumentException('mg_list_err_column|' . $name);
            }
            if ($label === '') {
                $label = ModuleHelper::sql_name_decode($name);
            }

            $entry = ['label' => self::cut($label), 'name' => $name];

            // join
            $join = null;
            if ($kind === 'legacy') {
                $join = $base['join'] ?? null;
            } elseif (is_array($row['join'] ?? null) && ($row['join']['table'] ?? '') !== '' && ($row['join']['column'] ?? '') !== '') {
                $jt = (string) $row['join']['table'];
                $jc = (string) $row['join']['column'];
                if (!preg_match('/^[A-Za-z0-9_]+(\.[A-Za-z0-9_]+)?$/', $jt) || !preg_match('/^[A-Za-z0-9_]+$/', $jc) || !in_array($jt, $knownTables, true)) {
                    throw new \InvalidArgumentException('mg_list_err_join|' . $label);
                }
                $join = $jt . ',' . $jc;
                // join a 2 passaggi (tabella,colonna,tabella2,colonna2): invariato se i primi due coincidono
                if (!empty($base['join']) && strpos($base['join'], $join . ',') === 0) {
                    $join = $base['join'];
                }
            }
            if ($join) {
                $entry['join'] = $join;
            }

            // formato
            $format = (string) ($row['format'] ?? '');
            if (!in_array($format, self::FORMATS, true)) {
                throw new \InvalidArgumentException('mg_list_err_format|' . $label);
            }
            if ($kind === 'col') {
                switch ($format) {
                    case 'image':
                        $entry['image'] = true;
                        break;
                    case 'download':
                        $entry['download'] = true;
                        break;
                    case 'trunc':
                        $n = (int) ($row['trunc'] ?? 0);
                        $entry['str_limit'] = max(1, min(1000, $n ?: 60));
                        break;
                    case 'badge':
                        $entry['format'] = 'badge';
                        $entry['badge'] = self::cleanBadge($row['badge'] ?? []);
                        break;
                    case 'money':
                        $currency = strtoupper(trim((string) ($row['currency'] ?? '')));
                        if ($currency !== '' && !isset(NumberFormat::CURRENCIES[$currency])) {
                            throw new \InvalidArgumentException('mg_list_err_currency|' . $label);
                        }
                        $entry['format'] = 'money';
                        $entry['currency'] = $currency;
                        $entry['decimals'] = self::decimalsOf($row['decimals'] ?? 2);
                        break;
                    case '':
                        break;
                    default:
                        $entry['format'] = $format;
                }
            } else {
                foreach (['image', 'download', 'str_limit', 'format', 'badge', 'currency', 'decimals'] as $k) {
                    if (isset($base[$k])) {
                        $entry[$k] = $base[$k];
                    }
                }
            }

            $entry = array_merge($entry, self::widthEntry($row));

            foreach (self::PRESERVED_KEYS as $k) {
                if (isset($base[$k]) && !isset($entry[$k])) {
                    $entry[$k] = $base[$k];
                }
            }

            $shownNames[$name] = true;
            $cols[] = $entry;
        }

        // colonne nascoste del file attuale (visible=false): servono ai
        // callback_php che citano [campo]; non si perdono al salvataggio
        foreach ($existing as $col) {
            if (isset($col['visible']) && $col['visible'] === false && !empty($col['name']) && !isset($shownNames[$col['name']])) {
                $cols[] = $col;
                $shownNames[$col['name']] = true;
            }
        }
        // campi citati da una colonna calcolata PHP e non presenti in lista
        foreach (array_keys($needHelpers) as $t) {
            if (!isset($shownNames[$t])) {
                $cols[] = ['label' => $t, 'name' => $t, 'visible' => false];
                $shownNames[$t] = true;
            }
        }

        return $cols;
    }

    private static function widthEntry(array $row): array
    {
        $w = (string) ($row['width'] ?? 'auto');
        if (isset(self::WIDTHS[$w])) {
            return ['col_width' => self::WIDTHS[$w]];
        }
        if (ctype_digit($w) && (int) $w > 0 && (int) $w <= 2000) {
            return ['col_width' => (int) $w];
        }

        return [];
    }

    private static function cleanBadge($badge): array
    {
        $colors = [];
        if (is_array($badge['colors'] ?? null)) {
            foreach ($badge['colors'] as $k => $v) {
                if (is_scalar($k) && mb_strlen((string) $k) <= 100 && self::isHex($v)) {
                    $colors[(string) $k] = strtolower($v);
                }
            }
        }

        return [
            'colors' => $colors,
            'default' => self::isHex($badge['default'] ?? null) ? strtolower($badge['default']) : self::DEFAULT_BADGE_COLOR,
        ];
    }

    private static function cut(string $s): string
    {
        return mb_substr($s, 0, 255);
    }

    public static function isHex($v): bool
    {
        return is_string($v) && preg_match('/^#[0-9a-fA-F]{6}$/', $v) === 1;
    }

    /* ------------------------------------------------------------------ */
    /*  Scrittura dei blocchi nel sorgente del controller                   */
    /* ------------------------------------------------------------------ */

    /** Sostituisce il blocco COLUMNS (stessa struttura scritta da postStep3 di sempre). */
    public static function replaceColumnsBlock(string $contents, array $cols): string
    {
        $start = '# START COLUMNS DO NOT REMOVE THIS LINE';
        $end = '# END COLUMNS DO NOT REMOVE THIS LINE';
        $raw = explode($start, $contents);
        if (count($raw) < 2) {
            throw new \RuntimeException('Blocco COLUMNS non trovato nel controller.');
        }
        $rraw = explode($end, $raw[1]);
        if (count($rraw) < 2) {
            throw new \RuntimeException('Blocco COLUMNS non chiuso nel controller.');
        }

        $lines = [];
        foreach ($cols as $col) {
            $lines[] = "\t\t\t" . '$this->col[] = ' . min_var_export($col) . ';';
        }

        $out = trim($raw[0]) . "\n\n";
        $out .= "\t\t\t{$start}\n";
        $out .= "\t\t\t" . '$this->col = [];' . "\n";
        $out .= implode("\n", $lines) . "\n";
        $out .= "\t\t\t{$end}\n\n";
        $out .= "\t\t\t" . trim($rraw[1]);

        return $out;
    }

    /**
     * Aggiorna (o aggiunge) solo le proprieta' indicate nel blocco
     * CONFIGURATION, lasciando intatte le altre. Valori sempre come literal
     * var_export (mai interpolati).
     */
    public static function mergeConfig(string $contents, array $values): string
    {
        $start = '# START CONFIGURATION DO NOT REMOVE THIS LINE';
        $end = '# END CONFIGURATION DO NOT REMOVE THIS LINE';
        $raw = explode($start, $contents);
        if (count($raw) < 2) {
            throw new \RuntimeException('Blocco CONFIGURATION non trovato nel controller.');
        }
        $rraw = explode($end, $raw[1]);
        if (count($rraw) < 2) {
            throw new \RuntimeException('Blocco CONFIGURATION non chiuso nel controller.');
        }
        $inner = $rraw[0];

        foreach ($values as $key => $val) {
            if (!preg_match('/^[a-z_]+$/', $key)) {
                continue;
            }
            $line = "\t\t\t" . '$this->' . $key . ' = ' . var_export((string) $val, true) . ';';
            $pattern = '/^[ \t]*\$this->' . preg_quote($key, '/') . '[ \t]*=.*;[ \t]*$/m';
            if (preg_match($pattern, $inner)) {
                $inner = preg_replace_callback($pattern, function () use ($line) {
                    return $line;
                }, $inner, 1);
            } else {
                $inner = rtrim($inner) . "\n" . $line . "\n\t\t\t";
            }
        }

        return $raw[0] . $start . $inner . $end . $rraw[1];
    }

    /* ------------------------------------------------------------------ */
    /*  Formattazione in lista (usata da CBController::getIndex)            */
    /* ------------------------------------------------------------------ */

    /**
     * Applica a $value il formato richiesto dalla colonna ($col['format']).
     * In caso di dato non interpretabile restituisce il valore invariato.
     */
    public static function formatValue($value, array $col)
    {
        $format = $col['format'] ?? null;
        if (!$format || $value === null || $value === '') {
            return $value;
        }

        try {
            switch ($format) {
                case 'date_short':
                    return Carbon::parse($value)->format('d/m/Y');
                case 'date_long':
                    return Carbon::parse($value)->translatedFormat('j F Y');
                case 'datetime_short':
                    return Carbon::parse($value)->format('d/m/Y H:i');
                case 'money_eur':
                    return is_numeric($value) ? NumberFormat::money($value, 2, 'EUR') : $value;
                case 'money':
                    return is_numeric($value) ? NumberFormat::money($value, self::decimalsOf($col['decimals'] ?? 2), (string) ($col['currency'] ?? '')) : $value;
                case 'money_plain':
                    return is_numeric($value) ? number_format((float) $value, 2, '.', '') : $value;
                case 'badge':
                    $colors = $col['badge']['colors'] ?? [];
                    $bg = $colors[(string) $value] ?? ($col['badge']['default'] ?? self::DEFAULT_BADGE_COLOR);
                    if (!self::isHex($bg)) {
                        $bg = self::DEFAULT_BADGE_COLOR;
                    }

                    return '<span class="badge" style="background:' . $bg . ';color:' . self::textColor($bg) . '">' . e($value) . '</span>';
            }
        } catch (\Throwable $e) {
            return $value;
        }

        return $value;
    }

    /** Cifre decimali valide (0-6) da un valore qualunque. */
    public static function decimalsOf($value): int
    {
        return max(0, min(6, (int) $value));
    }

    /**
     * Valore di un campo numero/importo/percentuale del form mostrato in lista
     * col separatore decimale dell'utente. $form = voce del campo (type,
     * decimals, currency). Un valore non numerico resta com'e'.
     */
    public static function formatNumericField($value, array $form)
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return $value;
        }
        $type = $form['type'] ?? '';
        $hasDecimals = isset($form['decimals']) && $form['decimals'] !== '';
        if ($type === 'money') {
            $dec = $hasDecimals ? self::decimalsOf($form['decimals']) : (floor((float) $value) == (float) $value ? 0 : 2);
            $currency = array_key_exists('currency', $form) ? (string) $form['currency'] : '';

            return NumberFormat::money($value, $dec, $currency);
        }
        if ($type === 'percent' && $hasDecimals) {
            return NumberFormat::format($value, self::decimalsOf($form['decimals']));
        }

        return NumberFormat::formatNatural($value);
    }

    public static function textColor(string $hex): string
    {
        $n = hexdec(substr($hex, 1));
        $l = 0.299 * ($n >> 16) + 0.587 * (($n >> 8) & 255) + 0.114 * ($n & 255);

        return $l > 150 ? '#000' : '#fff';
    }
}
