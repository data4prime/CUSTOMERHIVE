# 108 - Dashboard a griglia libera, Fase 0: modello dati e registry dataset

- **Data**: 2026-09-28
- **Stato**: Completato
- **Area**: Statistic Builder / Dashboard
- **File/aree di codice coinvolte**:
  - `database/migrations/2026_09_28_090000_add_layout_mode_to_cms_statistics.php`
  - `database/migrations/2026_09_28_090001_add_grid_position_to_cms_statistic_components.php`
  - `app/Dashboards/DashboardDatasetRegistry.php` (nuovo)

## Contesto

Prima fase del piano [`docs/piano-dashboard-griglia-libera.md`](../piano-dashboard-griglia-libera.md):
sostituire l'attuale editor di dashboard ad aree fisse con una griglia
libera drag & resize. Questa fase pone solo le fondamenta dati, senza
alcun cambiamento visibile.

## Situazione prima

- `cms_statistics` non distingueva alcuna "modalità" di dashboard.
- `cms_statistic_components` non aveva colonne di posizione libera: la
  posizione/dimensione di un widget veniva solo da `area_name` +
  `sorting`, risolti contro il layout HTML in `dashboard_layouts`.
- Nessun meccanismo per query "guidate": l'unico modo di interrogare dati
  in un widget era SQL libera in `config->sql`.

## Situazione dopo

- `cms_statistics.layout_mode` (stringa, default `'legacy_areas'`): tutte
  le righe esistenti restano `legacy_areas` dopo la migration. (Le
  dashboard **nuove** create da qui in poi partono invece direttamente
  in `'grid'` — decisione presa in questa stessa fase del piano ma
  implementata solo in un secondo momento, vedi
  [114](114-dashboard-griglia-fix-accesso-ui.md).)
- `cms_statistic_components.pos_x/pos_y/width/height` (interi nullable,
  unità di griglia a 12 colonne): non toccano `area_name`/`sorting`, che
  restano per il renderer legacy.
- `App\Dashboards\DashboardDatasetRegistry`: registry statico con due
  dataset di esempio (`admin_users` su `cms_users`, `system_activity` su
  `cms_logs` — tabelle generiche presenti in ogni installazione, non
  business-specifiche di un cliente), ciascuno con metriche/dimensioni/
  filtri esplicitamente whitelistati. Espone `execute()` (query
  parametrica via query builder di Laravel, mai stringa concatenata) e
  `optionsForFrontend()` (per popolare le select del builder guidato).

## Motivazione

- Additivo puro: nessuna riga esistente cambia significato, nessuna vista
  esistente legge le nuove colonne.
- Dataset "curati a codice" (non introspezione dinamica dello schema)
  per decisione esplicita dell'utente: un nuovo dataset è una modifica di
  codice deliberata e revisionata, non un'esposizione automatica di
  tabelle potenzialmente sensibili.

## Test

- Migration eseguita su DB dev (`php artisan migrate --path=...`),
  nessun errore.
- Nessun test automatico dedicato: `DashboardDatasetRegistry::execute()`
  è stato verificato manualmente in Fase 2/3 (vedi 110/111) tramite
  l'anteprima del query builder guidato in browser.

## Rischi e note

- I due dataset di esempio sono generici (utenti/log di sistema) proprio
  per non dipendere da tabelle business specifiche di un singolo cliente:
  vanno estesi con dataset reali quando si passa dalla demo a un uso
  concreto per cliente.

## Rollback

- `php artisan migrate:rollback` sulle due migration (drop colonne
  additive, nessuna perdita di dati esistenti).
- Rimuovere `app/Dashboards/DashboardDatasetRegistry.php` (nessun altro
  file lo referenzia se non quelli introdotti dalle fasi successive).
