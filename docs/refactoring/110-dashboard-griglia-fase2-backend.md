# 110 - Dashboard a griglia libera, Fase 2: backend per l'editor a griglia

- **Data**: 2026-09-28
- **Stato**: Completato
- **Area**: Statistic Builder / Dashboard
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/StatisticBuilderController.php`

## Contesto

Terza fase del piano [`docs/piano-dashboard-griglia-libera.md`](../piano-dashboard-griglia-libera.md):
gli endpoint necessari all'editor a griglia (Fase 3) e al query builder
guidato, additivi rispetto a quelli legacy.

## Situazione prima

- `getViewComponent()` risolveva mashup/config/rendering di un singolo
  widget inline, senza un punto riusabile per farlo in bulk.
- `getBuilder()` renderizzava sempre la vista legacy ad aree fisse.
- `postAddComponent()`/`postUpdateAreaComponent()` conoscevano solo
  `area_name`/`sorting`.
- Nessun endpoint per popolare/eseguire il query builder guidato.

## Situazione dopo (additivo, il ramo legacy di ogni metodo resta invariato)

- `renderComponentPayload()`: corpo di `getViewComponent()` estratto
  cosi' com'era + un'aggiunta — se `config->mode === 'builder'`, risolve
  `[sql]` eseguendo il dataset scelto via `DashboardDatasetRegistry`
  invece di un `DB::select()` letterale.
- `getListComponentsGrid($id)`: fetch bulk di tutti i widget di una
  dashboard con HTML già pronto (una chiamata invece degli N+1
  round-trip di `list-component`+N×`view-component`).
- `getBuilder($id)`: branch su `layout_mode` — `'grid'` apre
  `builder_grid.blade.php`, altrimenti comportamento identico a prima.
- `postConvertToGrid($id)` (vedi 109), `postUpdateComponentPosition()`
  (autosave x/y/w/h, analogo a `postUpdateAreaComponent()`),
  `postAddComponent()` esteso per accettare `pos_x/pos_y/width/height`
  oltre ad area/sorting, `getDatasetOptions()`/`postDatasetPreview()` per
  il pannello "Query guidata".
- `renderDashboardShow()` (vedi anche 113): usata da `getDashboard()`/
  `getShow()`, branch legacy/griglia.

## Motivazione

Un'unica chiamata bulk invece di N+1 è un miglioramento di performance
indipendente dalla griglia in sé (beneficia anche la Fase 5, sola
lettura). Riuso del corpo di `getViewComponent()` invece di duplicarlo:
stesso comportamento legacy garantito byte-per-byte.

## Test

- `php -l` su tutto il controller dopo ogni modifica.
- `php artisan route:list | grep statistic_builder`: confermate le nuove
  rotte (`list-components-grid`, `update-component-position`,
  `dataset-options`, `dataset-preview`, `convert-to-grid`) generate
  automaticamente dalla convenzione `get`/`post` + nome metodo di
  `CRUDBooster::routeController()`, nessuna rotta legacy rimossa/alterata.
- End-to-end in browser (vedi 111): tutti gli endpoint testati con
  richieste reali (fetch/AJAX) e verifica diretta a DB.

## Rischi e note

- `getEditComponent()` (usato sia dal builder legacy sia dalla sidebar
  del builder a griglia) non è stato toccato: stesso comportamento,
  stesso perimetro superadmin-only.

## Rollback

Rimuovere i metodi aggiunti e ripristinare `getViewComponent()`/
`getBuilder()`/`postAddComponent()` alla versione precedente (git diff di
questo commit).
