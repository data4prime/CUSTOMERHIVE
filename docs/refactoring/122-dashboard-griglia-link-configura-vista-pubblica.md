# 122 - Dashboard a griglia libera: link "Clicca qui per configurare" nella vista pubblica

- **Data**: 2026-09-28
- **Stato**: Completato e verificato in browser
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/StatisticBuilderController.php`
  - `resources/views/crudbooster/statistic_builder/components/_empty_widget_state.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/smallbox.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/table.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/chartline_v2.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/chartbar_v2.blade.php`

## Contesto

Richiesto dall'utente su una dashboard reale
(`/admin/statistic_builder/show/test2?m=9`): il placeholder "widget non
configurato" (120) mostrava lì "Seleziona per collegare i dati" - un
invito che non ha senso sulla vista di sola lettura (non c'è nessuna
sidebar da aprire selezionando).

## Situazione prima

Il partial `_empty_widget_state.blade.php` mostrava sempre lo stesso
sottotitolo statico, identico in builder e vista pubblica.

## Situazione dopo

- `renderComponentPayload()` accetta un parametro opzionale `$editUrl`
  (default `null`), passato alla vista `layout` del widget.
- `renderDashboardShow()` lo valorizza con l'URL del builder di
  *quella* dashboard, **solo per il superadmin** (`getBuilder()` è già
  riservato al superadmin: per gli altri resterebbe comunque un
  "accesso negato", quindi niente link per loro). Il builder stesso
  (`getListComponentsGrid()`) continua a non passarlo: lì il
  sottotitolo resta invariato ("Seleziona...").
- `_empty_widget_state.blade.php`: se `$link` è valorizzato, il
  sottotitolo diventa il link "Clicca qui per configurare" verso quel
  URL; altrimenti resta il testo statico di prima.

## Motivazione

Messaggio coerente con il contesto: nel builder "seleziona" è l'azione
giusta (apre la sidebar), nella vista pubblica non esiste alcuna
selezione - un link diretto al builder è l'azione giusta lì.

## Test

Verificato in browser sulla dashboard reale `test2`: tutti e 3 i widget
non configurati mostrano il link, `href` verificato puntare
esattamente a `/admin/statistic_builder/builder/7` (la dashboard
corretta); il builder della stessa dashboard mostra ancora il testo
statico "Seleziona per collegare i dati" (nessuna regressione lì).

## Rischi e note

Nessuna nota aggiuntiva.

## Rollback

`git diff` di questi file per tornare al sottotitolo statico ovunque.
