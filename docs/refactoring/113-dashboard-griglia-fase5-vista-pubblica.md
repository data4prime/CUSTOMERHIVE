# 113 - Dashboard a griglia libera, Fase 5: vista pubblica in sola lettura

- **Data**: 2026-09-28
- **Stato**: Completato
- **Area**: Statistic Builder / Dashboard
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/StatisticBuilderController.php`
    (`renderDashboardShow()`)
  - `resources/views/crudbooster/statistic_builder/show_grid.blade.php` (nuovo)

## Contesto

Ultima fase del piano [`docs/piano-dashboard-griglia-libera.md`](../piano-dashboard-griglia-libera.md):
la vista che vedono gli utenti non-superadmin (`getShow`/`getDashboard`)
per una dashboard `layout_mode = 'grid'`, di sola lettura (niente drag/
resize/palette/sidebar).

## Situazione prima

`getShow()`/`getDashboard()` renderizzavano sempre `statistic_builder.show`
(→ `index.blade.php`), con gli N+1 round-trip AJAX già presenti oggi
anche per le dashboard legacy.

## Situazione dopo

`renderDashboardShow($row, $page_title, $id_cms_statistics)` (metodo
condiviso, vedi 110): se `layout_mode = 'grid'`, recupera tutti i
componenti con **una sola query** (`renderComponentPayload()` per
ciascuno, stesso codice del builder) e passa il risultato a
`statistic_builder.show_grid` — layout in puro CSS Grid da
`pos_x/pos_y/width/height` (nessun gridstack.js: è sola lettura, niente
drag/resize). Il ramo `legacy_areas` sotto resta identico a prima.

## Motivazione

Riuso di `renderComponentPayload()` invece di duplicare la logica di
risoluzione widget; niente libreria di drag in una pagina che non ne ha
bisogno (più leggera della pagina di builder).

## Test

Verificato in browser: creata una dashboard di prova `layout_mode='grid'`
con un widget KPI in modalità "builder" (dataset `admin_users`), aperta
via `getShow($slug)` (`/admin/statistic_builder/show/{slug}`, non
`getBuilder()`) — tema admin standard, breadcrumb corretto, widget
renderizzato con il valore reale (5) nella posizione di griglia attesa,
nessun pulsante di modifica/eliminazione visibile (nascosto dal JS di
ciascun widget, che lo nasconde quando l'URL non contiene "builder" -
comportamento gia' esistente, riusato invariato). Dashboard di prova poi
eliminata.

## Rischi e note

- Permessi (`isDashboardVisibleToCurrentUser`) non modificati: stesso
  controllo di oggi, invariato.
- `getDashboard()` (percorso menu `is_dashboard=1`) non è stato testato
  direttamente in questa sessione (richiede un setup di `cms_menus` più
  articolato) — solo `getShow($slug)`, che condivide lo stesso
  `renderDashboardShow()`.

## Rollback

`git diff` di `renderDashboardShow()`/`getShow()`/`getDashboard()` per
tornare al solo ramo legacy; rimuovere `show_grid.blade.php` se creato.
