<?php

namespace App\Helpers;

/**
 * Logica pura del layout del form a schede, blocchi e campi (docs/refactoring/197,
 * riorganizzato in schede -> blocchi -> campi dall'intervento 219).
 *
 * Il layout e' un array scritto nel controller del modulo, in un blocco
 * separato da quello dei campi:
 *
 *   # START FORM LAYOUT DO NOT REMOVE THIS LINE
 *   $this->form_layout = ['v' => 2, 'tabs' => [
 *       ['id' => 't1', 'title' => 'Dati', 'blocks' => [
 *           ['id' => 'b1', 'title' => '...', 'x' => 0, 'y' => 0, 'w' => 8, 'h' => 5,
 *            'fields' => [['name' => 'nome', 'w' => 6], ['name' => 'created_at', 'w' => 6, 'sys' => 1]]],
 *       ]],
 *   ]];
 *   # END FORM LAYOUT DO NOT REMOVE THIS LINE
 *
 * Le schede stanno in alto (navigazione orizzontale), i blocchi sono su una
 * griglia a 12 colonne dentro la scheda, i campi dentro i blocchi.
 *
 * Il vecchio formato v1 (['v' => 1, 'blocks' => [...]], con blocchi di tipo
 * 'tabs') resta leggibile: normalize() lo converte al volo, senza riscrivere
 * i controller gia' generati.
 *
 * Senza questo blocco il form e' disegnato come sempre (elenco piatto).
 */
class ModuleGeneratorLayout
{
    const START = '# START FORM LAYOUT DO NOT REMOVE THIS LINE';
    const END = '# END FORM LAYOUT DO NOT REMOVE THIS LINE';

    /** Colonne di sistema mostrabili in sola lettura (non fanno parte di $this->form). */
    const SYSTEM_FIELDS = ['id', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'];

    /** Campi gestiti dal sistema in un blocco a parte: sempre mantenuti, mai posizionabili. */
    const ALWAYS_NAMES = ['tenant', 'group', 'primary_group'];

    /** Larghezze di un campo dentro il blocco, in dodicesimi. */
    const WIDTHS = [12, 6, 4, 3];

    /* ------------------------------------------------------------------ */
    /*  Lettura                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * Layout nel formato corrente (schede -> blocchi). Il formato v1 viene
     * convertito: i blocchi semplici finiscono in un'unica scheda, ogni scheda
     * di un vecchio blocco "schede" diventa un blocco a se' impilato in fondo.
     */
    public static function normalize($layout): array
    {
        if (!is_array($layout)) {
            return ['v' => 2, 'tabs' => []];
        }
        if (isset($layout['tabs']) && is_array($layout['tabs'])) {
            $tabs = [];
            foreach ($layout['tabs'] as $t) {
                if (is_array($t)) {
                    $t['blocks'] = array_values(array_filter((array) ($t['blocks'] ?? []), 'is_array'));
                    $tabs[] = $t;
                }
            }

            return ['v' => 2, 'tabs' => $tabs];
        }
        if (empty($layout['blocks']) || !is_array($layout['blocks'])) {
            return ['v' => 2, 'tabs' => []];
        }

        $blocks = [];
        $bottom = 0;
        foreach ($layout['blocks'] as $b) {
            if (is_array($b)) {
                $bottom = max($bottom, (int) ($b['y'] ?? 0) + max(1, (int) ($b['h'] ?? 1)));
            }
        }
        foreach ($layout['blocks'] as $b) {
            if (!is_array($b)) {
                continue;
            }
            if (($b['kind'] ?? 'block') === 'tabs') {
                foreach ((array) ($b['tabs'] ?? []) as $t) {
                    $h = max(1, (int) ($b['h'] ?? 3));
                    $blocks[] = ['title' => (string) ($t['title'] ?? ''), 'x' => 0, 'y' => $bottom, 'w' => 12, 'h' => $h, 'fields' => array_values((array) ($t['fields'] ?? []))];
                    $bottom += $h;
                }
            } else {
                $blocks[] = ['title' => (string) ($b['title'] ?? ''), 'x' => (int) ($b['x'] ?? 0), 'y' => (int) ($b['y'] ?? 0), 'w' => (int) ($b['w'] ?? 12), 'h' => (int) ($b['h'] ?? 3), 'fields' => array_values((array) ($b['fields'] ?? []))];
            }
        }
        foreach ($blocks as $i => &$blk) {
            $blk['id'] = 'b' . ($i + 1);
        }
        unset($blk);

        return ['v' => 2, 'tabs' => $blocks ? [['id' => 't1', 'title' => '', 'blocks' => $blocks]] : []];
    }

    public static function isActive($layout): bool
    {
        return (bool) self::normalize($layout)['tabs'];
    }

    /** Schede del layout, ciascuna con i propri blocchi. */
    public static function tabs($layout): array
    {
        return self::normalize($layout)['tabs'];
    }

    /**
     * Posizione della scheda "Sistema" gia' presente nel layout (titolo "Sistema"
     * o "System", senza distinzione di maiuscole), o null. Tenant e gruppo non
     * posizionati vanno li' (intervento 238) invece che in un riquadro in fondo.
     */
    public static function systemTabIndex(array $tabs): ?int
    {
        foreach (array_values($tabs) as $i => $t) {
            $title = mb_strtolower(trim((string) ($t['title'] ?? '')));
            if ($title === 'sistema' || $title === 'system') {
                return $i;
            }
        }

        return null;
    }

    /** Tutti i blocchi di tutte le schede, nell'ordine del layout. */
    public static function allBlocks($layout): array
    {
        $out = [];
        foreach (self::tabs($layout) as $t) {
            foreach ((array) ($t['blocks'] ?? []) as $b) {
                $out[] = $b;
            }
        }

        return $out;
    }

    /** Nomi dei campi del form posizionati nel layout (esclusi gli elementi di sistema in sola lettura). */
    public static function placedNames($layout): array
    {
        $names = [];
        foreach (self::allBlocks($layout) as $b) {
            foreach ((array) ($b['fields'] ?? []) as $it) {
                if (empty($it['sys']) && isset($it['name'])) {
                    $names[] = (string) $it['name'];
                }
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * Blocchi di UNA scheda ordinati per posizione (y, poi x), ciascuno con
     * 'row_start' e 'row_span' per la griglia CSS: le righe sono i valori
     * distinti di y; un blocco alto occupa tutte le righe che iniziano dentro
     * la sua altezza.
     */
    public static function sortedBlocks(array $blocks): array
    {
        $blocks = array_values($blocks);
        usort($blocks, function ($a, $b) {
            return [(int) ($a['y'] ?? 0), (int) ($a['x'] ?? 0)] <=> [(int) ($b['y'] ?? 0), (int) ($b['x'] ?? 0)];
        });
        $ys = [];
        foreach ($blocks as $b) {
            $ys[(int) ($b['y'] ?? 0)] = true;
        }
        $ys = array_keys($ys);
        sort($ys);
        foreach ($blocks as &$b) {
            $y = (int) ($b['y'] ?? 0);
            $h = max(1, (int) ($b['h'] ?? 1));
            $start = array_search($y, $ys, true) + 1;
            $span = 0;
            foreach ($ys as $v) {
                if ($v >= $y && $v < $y + $h) {
                    $span++;
                }
            }
            $b['row_start'] = $start;
            $b['row_span'] = max(1, $span);
            $b['x'] = max(0, min(11, (int) ($b['x'] ?? 0)));
            $b['w'] = max(1, min(12 - $b['x'], (int) ($b['w'] ?? 12)));
        }
        unset($b);

        return $blocks;
    }

    /**
     * Toglie da $form i campi non posizionati (non disegnati e non salvati,
     * come per hide_form). Restano sempre: i campi posizionati, tenant/group/
     * primary_group (blocco di sistema), i campi di tipo hidden e il campo che
     * collega un sotto-modulo al padre.
     */
    public static function filterForm(array $form, $layout, ?string $parentField = null): array
    {
        if (!self::isActive($layout)) {
            return $form;
        }
        $keep = array_merge(self::placedNames($layout), self::ALWAYS_NAMES);
        if ($parentField) {
            $keep[] = $parentField;
        }
        foreach ($form as $i => $f) {
            $name = (string) ($f['name'] ?? '');
            $type = $f['type'] ?? 'text';
            if ($type === 'hidden' || in_array($name, $keep, true)) {
                continue;
            }
            unset($form[$i]);
        }

        return $form;
    }

    /**
     * Elenco ordinato per la vista dettaglio: i campi seguono il layout
     * (schede, poi blocchi per posizione), con righe di intestazione per titoli
     * di scheda e di blocco e righe 'sys' per le colonne di sistema. In coda: i
     * campi non posizionati rimasti (tenant/group, hidden).
     */
    public static function detailOrder(array $forms, $layout): array
    {
        if (!self::isActive($layout)) {
            return $forms;
        }
        $by = [];
        foreach ($forms as $f) {
            if (isset($f['name'])) {
                $by[$f['name']] = $f;
            }
        }
        $out = [];
        $used = [];
        $emit = function (array $items) use (&$out, &$used, $by) {
            foreach ($items as $it) {
                $name = (string) ($it['name'] ?? '');
                if (!empty($it['sys'])) {
                    if (in_array($name, self::SYSTEM_FIELDS, true)) {
                        $out[] = ['__sys' => $name];
                    }
                } elseif (isset($by[$name])) {
                    $out[] = $by[$name];
                    $used[$name] = true;
                }
            }
        };
        foreach (self::tabs($layout) as $tab) {
            if (!empty($tab['title'])) {
                $out[] = ['__heading' => (string) $tab['title']];
            }
            foreach (self::sortedBlocks((array) ($tab['blocks'] ?? [])) as $b) {
                if (!empty($b['title'])) {
                    $out[] = ['__heading' => (string) $b['title']];
                }
                $emit((array) ($b['fields'] ?? []));
            }
        }
        foreach ($forms as $f) {
            if (isset($f['name']) && empty($used[$f['name']])) {
                $out[] = $f;
            }
        }

        return $out;
    }

    /** Valore da mostrare per una colonna di sistema in sola lettura (vuoto se il record non esiste ancora). */
    public static function systemValue($row, string $name): string
    {
        if (!is_object($row) || !isset($row->{$name}) || $row->{$name} === '') {
            return '';
        }
        $v = $row->{$name};
        if (substr($name, -3) === '_at') {
            try {
                return \Illuminate\Support\Carbon::parse($v)->format('d/m/Y H:i');
            } catch (\Throwable $e) {
                return (string) $v;
            }
        }
        if (substr($name, -3) === '_by') {
            try {
                $n = \Illuminate\Support\Facades\DB::table('cms_users')->where('id', $v)->value('name');

                return $n ? (string) $n : (string) $v;
            } catch (\Throwable $e) {
                return (string) $v;
            }
        }

        return (string) $v;
    }

    /* ------------------------------------------------------------------ */
    /*  Costruzione e modifica                                              */
    /* ------------------------------------------------------------------ */

    /** Layout iniziale: una scheda con un solo blocco a tutta larghezza con tutti i campi, uno per riga. */
    public static function defaultLayout(array $names, string $title): array
    {
        $fields = [];
        foreach ($names as $n) {
            $fields[] = ['name' => $n, 'w' => 12];
        }

        return ['v' => 2, 'tabs' => [['id' => 't1', 'title' => '', 'blocks' => [
            ['id' => 'b1', 'title' => $title, 'x' => 0, 'y' => 0, 'w' => 12, 'h' => max(3, 2 + count($names)), 'fields' => $fields],
        ]]]];
    }

    /** Aggiunge in coda al primo blocco i campi indicati; se manca crea un blocco (e una scheda). */
    public static function appendFields(array $layout, array $names, string $newTitle): array
    {
        if (!$names) {
            return $layout;
        }
        $layout = self::normalize($layout);
        if (!$layout['tabs']) {
            $layout['tabs'][] = ['id' => 't1', 'title' => '', 'blocks' => []];
        }
        $ti = 0;
        foreach ($layout['tabs'] as $i => $t) {
            if (!empty($t['blocks'])) {
                $ti = $i;
                break;
            }
        }
        if (empty($layout['tabs'][$ti]['blocks'])) {
            $layout['tabs'][$ti]['blocks'][] = ['id' => self::nextBlockId($layout), 'title' => $newTitle, 'x' => 0, 'y' => 0, 'w' => 12, 'h' => 3, 'fields' => []];
        }
        $blocks = &$layout['tabs'][$ti]['blocks'];
        foreach ($names as $n) {
            $blocks[0]['fields'][] = ['name' => $n, 'w' => 12];
        }
        $blocks[0]['h'] = max((int) ($blocks[0]['h'] ?? 3), 2 + count($blocks[0]['fields']));
        unset($blocks);

        return $layout;
    }

    /** Toglie dal layout i campi indicati (non piu' nel modulo). */
    public static function dropFields(array $layout, array $names): array
    {
        $drop = array_flip($names);
        $layout = self::normalize($layout);
        foreach ($layout['tabs'] as &$t) {
            foreach ($t['blocks'] as &$b) {
                $b['fields'] = array_values(array_filter((array) ($b['fields'] ?? []), function ($it) use ($drop) {
                    return !empty($it['sys']) || !isset($drop[$it['name'] ?? '']);
                }));
            }
            unset($b);
        }
        unset($t);

        return $layout;
    }

    private static function nextBlockId(array $layout): string
    {
        $n = 1;
        $ids = [];
        foreach (self::allBlocks($layout) as $b) {
            $ids[$b['id'] ?? ''] = true;
        }
        while (isset($ids['b' . $n])) {
            $n++;
        }

        return 'b' . $n;
    }

    /**
     * Valida il layout inviato dall'editor e lo restituisce pulito (id
     * rigenerati, posizioni limitate alla griglia). Accetta anche il vecchio
     * formato a blocchi (import di export precedenti). Lancia
     * InvalidArgumentException con una chiave di traduzione ("mg_lay_err_...",
     * opzionalmente "chiave|parametro").
     *
     * @param array $fields campi posizionabili: nome => ['label' => ..., 'required' => bool]
     */
    public static function build(array $payload, array $fields): array
    {
        $isLegacy = !isset($payload['tabs']) && isset($payload['blocks']);
        if (!$isLegacy && (!isset($payload['tabs']) || !is_array($payload['tabs']))) {
            throw new \InvalidArgumentException('mg_lay_err_structure');
        }
        $source = self::normalize($payload)['tabs'];
        if (count($source) > 12 || ($isLegacy && !is_array($payload['blocks'])) || ($isLegacy && count($payload['blocks']) > 60)) {
            throw new \InvalidArgumentException('mg_lay_err_structure');
        }
        if (!$source && !$fields) {
            return ['v' => 2, 'tabs' => []];
        }

        $seen = [];
        $tabs = [];
        $tn = 0;
        $bn = 0;

        $cleanItems = function ($items) use (&$seen, $fields) {
            if (!is_array($items) || count($items) > 200) {
                throw new \InvalidArgumentException('mg_lay_err_structure');
            }
            $out = [];
            foreach ($items as $it) {
                $name = is_array($it) ? (string) ($it['name'] ?? '') : '';
                $sys = is_array($it) && !empty($it['sys']);
                if ($sys ? !in_array($name, self::SYSTEM_FIELDS, true) : !isset($fields[$name])) {
                    throw new \InvalidArgumentException('mg_lay_err_field|' . $name);
                }
                $key = ($sys ? 's:' : 'f:') . $name;
                if (isset($seen[$key])) {
                    throw new \InvalidArgumentException('mg_lay_err_dup|' . $name);
                }
                $seen[$key] = true;
                $w = (int) ($it['w'] ?? 12);
                $item = ['name' => $name, 'w' => in_array($w, self::WIDTHS, true) ? $w : 12];
                if ($sys) {
                    $item['sys'] = 1;
                }
                $out[] = $item;
            }

            return $out;
        };

        foreach ($source as $t) {
            if (!is_array($t) || !isset($t['blocks']) || !is_array($t['blocks']) || count($t['blocks']) > 60) {
                throw new \InvalidArgumentException('mg_lay_err_structure');
            }
            $blocks = [];
            foreach ($t['blocks'] as $b) {
                if (!is_array($b)) {
                    throw new \InvalidArgumentException('mg_lay_err_structure');
                }
                $x = max(0, min(11, (int) ($b['x'] ?? 0)));
                $blocks[] = [
                    'id' => 'b' . (++$bn),
                    'title' => mb_substr(trim((string) ($b['title'] ?? '')), 0, 120),
                    'x' => $x, 'y' => max(0, min(500, (int) ($b['y'] ?? 0))),
                    'w' => max(1, min(12 - $x, (int) ($b['w'] ?? 12))), 'h' => max(1, min(100, (int) ($b['h'] ?? 3))),
                    'fields' => $cleanItems($b['fields'] ?? []),
                ];
            }
            $tabs[] = ['id' => 't' . (++$tn), 'title' => mb_substr(trim((string) ($t['title'] ?? '')), 0, 60), 'blocks' => $blocks];
        }

        $placed = [];
        foreach ($seen as $k => $_) {
            if (strpos($k, 'f:') === 0) {
                $placed[substr($k, 2)] = true;
            }
        }
        foreach ($fields as $name => $f) {
            if (!empty($f['required']) && empty($placed[$name])) {
                throw new \InvalidArgumentException('mg_lay_err_required|' . ($f['label'] ?? $name));
            }
        }
        if ($fields && !$placed) {
            throw new \InvalidArgumentException('mg_lay_err_empty');
        }

        return ['v' => 2, 'tabs' => $tabs];
    }

    /* ------------------------------------------------------------------ */
    /*  Scrittura nel sorgente del controller                               */
    /* ------------------------------------------------------------------ */

    /** Sostituisce il blocco FORM LAYOUT; se manca lo inserisce subito dopo il blocco FORM. */
    public static function replaceLayoutBlock(string $contents, array $layout): string
    {
        $body = "\t\t\t" . '$this->form_layout = ' . min_var_export($layout) . ';';
        if (strpos($contents, self::START) !== false && strpos($contents, self::END) !== false) {
            $raw = explode(self::START, $contents);
            $rraw = explode(self::END, $raw[1]);

            return trim($raw[0]) . "\n\n\t\t\t" . self::START . "\n" . $body . "\n\t\t\t" . self::END . "\n\n\t\t\t" . trim($rraw[1]);
        }
        $endForm = '# END FORM DO NOT REMOVE THIS LINE';
        $pos = strpos($contents, $endForm);
        if ($pos === false) {
            throw new \RuntimeException('Blocco FORM non trovato nel controller.');
        }
        $after = $pos + strlen($endForm);

        return substr($contents, 0, $after) . "\n\n\t\t\t" . self::START . "\n" . $body . "\n\t\t\t" . self::END . substr($contents, $after);
    }
}
