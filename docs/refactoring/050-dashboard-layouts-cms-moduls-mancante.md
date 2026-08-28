# 050 - Modulo "Dashboard Layouts" senza riga in cms_moduls (404)

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Bug fix / dati
- **File/aree di codice coinvolte**:
  - `database/seeders/CmsModulsSeeder.php`
  - Dati: tabelle `cms_moduls` e `module_helpers` (dev)

## Contesto

Segnalato dall'utente: `/admin/dashboard_layouts` ritornava 404. Stesso
identico problema già risolto in [009](009-module-helpers-cms-moduls-mancante.md)
per "Module Helpers": il routing dei moduli "di sistema" dipende
interamente da una riga in `cms_moduls` (vedi
[006](006-controller-sistema-app-http-controllers-system.md)) — senza
quella riga, nessuna rotta viene mai generata, a prescindere dal fatto
che controller, tabella e persino il link nel menu esistano già.

Verificato che esistono davvero tutti i pezzi tranne la riga in
`cms_moduls`:
- `App\Http\Controllers\System\DashboardLayoutController` presente
  (`$this->table = 'dashboard_layouts'`).
- Tabella `dashboard_layouts` presente (migration
  `2024_06_13_143719_create_dashboard_layouts_table.php`).
- Link reale (non commentato) nella sidebar, sotto "Statistic Builder"
  (`resources/views/crudbooster/sidebar.blade.php:288`,
  `url("admin/dashboard_layouts")`).
- **Nessuna riga** in `cms_moduls` con `path` o `controller` relativi a
  questo modulo, e `CmsModulsSeeder.php` non lo includeva — quindi il
  problema non è solo nel DB di sviluppo ma anche nel seeder, come per
  009: qualunque installazione pulita avrebbe lo stesso 404.

## Situazione prima

`CmsModulsSeeder.php` non aveva nessuna voce per
`DashboardLayoutController`.

## Situazione dopo

Aggiunta la riga mancante, stesso schema delle altre voci del seeder:
`name` = `trans('crudbooster.Dashboard_Layouts')` (chiave già presente
in `it`/`en`, usata anche dalla sidebar), `path` = `dashboard_layouts`,
`table_name` = `dashboard_layouts`, `controller` =
`DashboardLayoutController`, `is_protected` = 1 (come gli altri moduli
di configurazione: Menu Management, Email Templates, ecc.).

Seeder rieseguito sul DB di sviluppo (idempotente: salta i nomi già
presenti, ha inserito solo la riga nuova, `id=18`).

**Verifica aggiuntiva** (richiesta dall'utente dopo il fix): il link
"Helper" nell'header admin per questo modulo dipende dalla tabella
`module_helpers` (`database/seeders/ModuleHelperSeeder.php`, che cerca
ogni modulo per nome in `cms_moduls`). Quel seeder ha **già** una voce
per `"Dashboard Layouts"` (riga 33, URL della pagina "statistic-builder"
della knowledge base), ma non aveva mai trovato nulla perché
`cms_moduls` non aveva questa riga prima di questo intervento.
Rieseguito `php artisan db:seed --class='\ModuleHelperSeeder'`
(idempotente, classe senza namespace: va invocata con il backslash
iniziale) — ha creato la riga mancante (`module_helpers.id=16`,
`id_cms_moduls=18`), quindi ora anche il link "Helper" per questo
modulo funziona.

## Motivazione

Senza la riga nel seeder, il gap si sarebbe ripresentato su ogni nuova
installazione pulita, non solo in questo ambiente — stessa motivazione
di [009](009-module-helpers-cms-moduls-mancante.md).

## Test

- `php -l` sul seeder modificato: nessun errore.
- `php artisan db:seed --class=Cms_modulsSeeder`: riga inserita
  (verificato con query diretta, `id=18`,
  `controller=DashboardLayoutController`).
- `php artisan route:list`: ora mostra tutte le rotte generate per il
  modulo (index/add/edit/delete/ecc.); totale rotte 513 (da 490 prima
  dell'intervento — 23 nuove rotte per questo singolo modulo,
  coerente con gli altri moduli CRUD generici).
- `curl` senza sessione su `/admin/dashboard_layouts` e
  `/admin/dashboard_layouts/add`: 302 (redirect a login), non più 404.

**Non verificato visivamente in browser** — lasciato al giro di test
manuale dell'utente.

## Rischi e note

- Stesso pattern esatto di [009](009-module-helpers-cms-moduls-mancante.md):
  se altri moduli nel menu risultassero 404 in futuro, controllare
  prima di tutto se hanno una riga in `cms_moduls`.

## Rollback

`git revert` del commit per il seeder. Per rimuovere il dato inserito:
`DELETE FROM cms_moduls WHERE controller='DashboardLayoutController'`
(da fare solo se il modulo non è mai stato usato nel frattempo).
