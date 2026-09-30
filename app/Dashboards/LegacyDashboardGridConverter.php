<?php

namespace App\Dashboards;

use Illuminate\Support\Facades\DB;

/**
 * Fase 1 del piano "dashboard a griglia libera" (vedi
 * docs/piano-dashboard-griglia-libera.md, docs/refactoring/109-*).
 *
 * Conversione automatica euristica di una dashboard 'legacy_areas' verso
 * 'grid': legge la larghezza col-sm-N di ciascuna area dal layout HTML
 * gia' risolto dal controller (StatisticBuilderController::
 * resolveDashboardCodeLayout(), stesso HTML che il renderer legacy usa
 * gia' oggi), poi assegna pos_x/pos_y/width/height a ogni widget
 * esistente con un bin-packing a 12 colonne, nell'ordine in cui
 * comparivano (area, poi sorting).
 *
 * Non distruttiva: 'area_name'/'sorting'/il layout HTML non vengono
 * toccati ne' cancellati - servono ancora a chi resta su 'legacy_areas' e
 * come riferimento se la conversione va rifatta a mano.
 */
class LegacyDashboardGridConverter
{
    private const GRID_COLUMNS = 12;
    private const FALLBACK_HEIGHT = 3;

    private const DEFAULT_HEIGHTS = [
        'smallbox' => 2,
        'table' => 5,
        'chartline' => 5,
        'chartbar' => 5,
        'chartarea' => 5,
        'chartline_v2' => 5,
        'chartbar_v2' => 5,
        'chartarea_v2' => 5,
        'qlikwidget' => 5,
        'panelarea' => 4,
        'panelcustom' => 4,
        'modulewidget' => 4,
    ];

    public function convert(int $idCmsStatistics, string $codeLayoutHtml): void
    {
        $areaWidths = $this->parseAreaWidths($codeLayoutHtml);

        $components = DB::table('cms_statistic_components')
            ->where('id_cms_statistics', $idCmsStatistics)
            ->get()
            ->sortBy(function ($component) {
                preg_match('/(\d+)$/', (string) $component->area_name, $matches);
                $areaNumber = (int) ($matches[1] ?? 0);

                return sprintf('%05d-%05d', $areaNumber, (int) $component->sorting);
            })
            ->values();

        DB::transaction(function () use ($components, $areaWidths, $idCmsStatistics) {
            $cursorX = 0;
            $cursorY = 0;
            $rowHeight = 0;

            foreach ($components as $component) {
                $width = min($areaWidths[$component->area_name] ?? self::GRID_COLUMNS, self::GRID_COLUMNS);
                $height = self::DEFAULT_HEIGHTS[$component->component_name] ?? self::FALLBACK_HEIGHT;

                if ($cursorX + $width > self::GRID_COLUMNS) {
                    $cursorX = 0;
                    $cursorY += $rowHeight;
                    $rowHeight = 0;
                }

                DB::table('cms_statistic_components')->where('id', $component->id)->update([
                    'pos_x' => $cursorX,
                    'pos_y' => $cursorY,
                    'width' => $width,
                    'height' => $height,
                ]);

                $cursorX += $width;
                $rowHeight = max($rowHeight, $height);
            }

            DB::table('cms_statistics')->where('id', $idCmsStatistics)->update([
                'layout_mode' => 'grid',
            ]);
        });
    }

    /** @return array<string, int> area_name => larghezza in colonne (su 12) */
    private function parseAreaWidths(string $html): array
    {
        $widths = [];
        preg_match_all('/<div\b[^>]*>/i', $html, $divTags);

        foreach ($divTags[0] as $tag) {
            if (!preg_match('/id=["\']?(area\d+)["\']?/i', $tag, $idMatch)) {
                continue;
            }

            $areaName = $idMatch[1];
            $widths[$areaName] = preg_match('/col-sm-(\d+)/i', $tag, $colMatch)
                ? (int) $colMatch[1]
                : self::GRID_COLUMNS;
        }

        return $widths;
    }
}
