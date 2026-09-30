# 109 - Dashboard a griglia libera, Fase 1: conversione automatica legacy → griglia

- **Data**: 2026-09-28
- **Stato**: Completato
- **Area**: Statistic Builder / Dashboard
- **File/aree di codice coinvolte**:
  - `app/Dashboards/LegacyDashboardGridConverter.php` (nuovo)
  - `app/Http/Controllers/System/StatisticBuilderController.php` (nuovo
    metodo `postConvertToGrid()`)

## Contesto

Seconda fase del piano [`docs/piano-dashboard-griglia-libera.md`](../piano-dashboard-griglia-libera.md).
Le dashboard esistenti hanno widget reali (query, nomi, layout scelti da
un umano): serve un modo per portarle alla nuova griglia senza doverle
ricreare da zero a mano.

## Situazione prima

Una dashboard `legacy_areas` non aveva alcun percorso verso `grid`: le
uniche colonne di posizione (Fase 0) restavano `NULL` per sempre.

## Situazione dopo

- `LegacyDashboardGridConverter::convert($idCmsStatistics, $codeLayoutHtml)`:
  legge la larghezza `col-sm-N` di ciascuna area dal layout HTML (stesso
  HTML che il renderer legacy usa già, risolto da
  `StatisticBuilderController::resolveDashboardCodeLayout()`, riusato
  invariato) tramite un parser dedicato a regex (non i metodi di
  `DashboardLayoutController` — quelli taggano nodi per l'editor WYSIWYG,
  scopo diverso). Assegna a ogni widget esistente (ordinati per area poi
  `sorting`) `pos_x/pos_y/width/height` con un bin-packing a 12 colonne,
  poi imposta `layout_mode = 'grid'`.
- `StatisticBuilderController::postConvertToGrid($id)`: endpoint
  superadmin-only, non distruttivo (`area_name`/`sorting`/il layout HTML
  restano intatti), loggato via `CRUDBooster::insertLog()`.

## Motivazione

Conversione **automatica euristica** (non manuale da zero): decisione
esplicita dell'utente — il mapping `col-sm-N` → colonne di griglia è
diretto (Bootstrap è già a 12 colonne), l'alternativa (ricomporre a mano)
sarebbe lavoro perso senza motivo.

## Test

Verificato manualmente in dev: clonata la dashboard reale "Dashboard
Ordini di Vendita" (10 widget, aree diverse, inclusa un'area
NULL/orfana) su una dashboard di prova, convertita via
`POST convert-to-grid/{id}`, verificate le posizioni calcolate via query
diretta al DB (packing corretto a 12 colonne, altezze per tipo di
widget) e l'apertura in `builder_grid.blade.php` (tutti i 10 widget
presenti con contenuto/config intatti). Dashboard di prova poi eliminata.

## Rischi e note

- I widget grafico legacy (`chartline`/`chartbar`/`chartarea`, Morris.js)
  convertiti a griglia mantengono dati/config ma **non disegnano il
  grafico** nella nuova pagina, che non carica Morris/Raphael (solo
  ApexCharts per i nuovi widget `_v2`, vedi 112) — gap noto, non
  risolto in questa fase: il contenitore resta vuoto finché quel widget
  non viene ricreato come `chartline_v2`/`chartbar_v2`.
- Operazione irreversibile lato UI una volta convertita (la dashboard
  apre sempre il nuovo editor), ma non distruttiva sui dati.

## Rollback

`UPDATE cms_statistics SET layout_mode='legacy_areas' WHERE id=...` (i
dati `area_name`/`sorting`/layout HTML sono ancora validi, essendo stati
lasciati intatti dalla conversione).
