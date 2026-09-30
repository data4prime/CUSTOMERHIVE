<?php

namespace App\Dashboards;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Trasforma il "valore" che StatisticBuilderController passa a un widget
 * grafico (chartline_v2/chartbar_v2) in categorie + serie pronte per
 * ApexCharts (docs/refactoring/156-*: estratto da chartline_v2.blade.php,
 * dove viveva copiato, per riusarlo identico nel Grafico a barre).
 *
 * Il valore ricevuto puo' essere:
 * - un array di {name,color,rows}, una entry per linea/serie - quando
 *   config->lines non e' vuoto (docs/refactoring/152-*);
 * - un array piatto di righe {label,value} - una sola serie da Query
 *   guidata;
 * - una stringa SQL grezza - una sola serie, eseguita qui (SQL libera).
 *
 * L'asse X e' l'unione di tutte le label distinte tra le serie; una serie
 * senza valore per una label riceve 0 (stesso algoritmo del widget legacy
 * per gestire serie con un numero diverso di punti).
 */
class ChartSeriesBuilder
{
    /** Palette di default per le serie senza colore proprio. */
    public const PALETTE = ['#3B5BDB', '#0EA5E9', '#16A34A', '#F59E0B', '#EF4444', '#8B5CF6'];

    /**
     * @param mixed $value valore passato da renderComponentPayload()
     * @param object|null $config config del widget (serve nome/colore della serie unica)
     * @return array{series: array<int, array{name: string, color: string, data: array}>, categories: array<int, string>, error: string|null}
     */
    public static function build($value, $config): array
    {
        $singleName = ($config->name ?? '') ?: 'Serie';
        $singleColor = $config->color ?? null;

        if (is_array($value) && isset($value[0]['rows'])) {
            $lines = $value;
        } elseif (is_array($value)) {
            $lines = [['name' => $singleName, 'color' => $singleColor, 'rows' => $value]];
        } else {
            $rows = [];
            try {
                $sql = $value;
                foreach (Session::all() as $k => $val) {
                    if (gettype($val) == gettype($sql)) {
                        $sql = str_replace('[' . $k . ']', $val, $sql);
                    }
                }
                foreach (DB::select($sql) as $r) {
                    $rows[] = ['label' => $r->label ?? '', 'value' => $r->value ?? 0];
                }
            } catch (\Exception $e) {
                return ['series' => [], 'categories' => [], 'error' => $e->getMessage()];
            }
            $lines = [['name' => $singleName, 'color' => $singleColor, 'rows' => $rows]];
        }

        $categories = [];
        foreach ($lines as $line) {
            foreach ($line['rows'] as $row) {
                $categories[] = $row['label'];
            }
        }
        $categories = array_values(array_unique($categories));

        $series = [];
        foreach ($lines as $i => $line) {
            $byLabel = [];
            foreach ($line['rows'] as $row) {
                $byLabel[$row['label']] = $row['value'];
            }
            $series[] = [
                'name' => ($line['name'] ?? '') !== '' ? $line['name'] : ('Serie ' . ($i + 1)),
                'color' => !empty($line['color']) ? $line['color'] : self::PALETTE[$i % count(self::PALETTE)],
                'data' => array_map(fn ($label) => $byLabel[$label] ?? 0, $categories),
            ];
        }

        return ['series' => $series, 'categories' => $categories, 'error' => null];
    }
}
