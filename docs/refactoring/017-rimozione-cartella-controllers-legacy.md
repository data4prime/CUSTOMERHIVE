# 017 - Rimossa la cartella packages/.../controllers/ (alias consolidati in un bootstrap unico)

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Architettura / CRUDBooster
- **File/aree di codice coinvolte**:
  - `app/Support/legacy_crudbooster_aliases.php` (nuovo)
  - `composer.json` (`autoload.files`)
  - `packages/crocodicstudio/crudbooster/src/controllers/` (rimossa interamente)

## Contesto

Dopo [012](012-controller-motore-shim-class-alias.md)-[015](015-cbcontroller-shim-class-alias.md),
la cartella `packages/crocodicstudio/crudbooster/src/controllers/` conteneva
solo 5 file-shim, ciascuno con una singola chiamata `class_alias()` verso
la classe reale ormai in `App\Http\Controllers\System`. L'utente ha chiesto
se si potesse eliminare del tutto quella cartella.

Distinzione chiarita con l'utente prima di agire: **due obiettivi diversi**.
1. "La cartella non esiste più" (layout dei file) — ottenibile subito,
   spostando il *meccanismo* degli alias fuori da quella cartella.
2. "Il vecchio FQCN non serve più a nessuno" (compatibilità eliminabile
   davvero) — richiede che *ogni* cliente attivo sia stato aggiornato con
   [016](016-comando-migrazione-extends-legacy-clienti.md), percorso
   ancora da fare, indipendente da questo intervento.

Questo intervento realizza solo il punto 1: gli alias restano, ma non
hanno più bisogno di vivere in file dentro quella cartella.

## Situazione prima

5 file in `packages/crocodicstudio/crudbooster/src/controllers/`
(`Controller.php`, `CBController.php`, `ApiController.php`,
`ExportData.php`, `ImportData.php`), ciascuno un one-liner
`class_alias(...)`, caricati solo quando il vecchio namespace veniva
autoloadato via PSR-4 (mapping `crocodicstudio\crudbooster\` →
`packages/crocodicstudio/crudbooster/src` in `composer.json`).

## Situazione dopo

- Le 5 chiamate `class_alias()` consolidate in un unico file,
  `app/Support/legacy_crudbooster_aliases.php` (nessun namespace, nessuna
  classe — solo side-effect all'inclusione).
- Registrato in `composer.json` → `"autoload": {"files": [...]}`: viene
  incluso **eagerly, ad ogni request/comando artisan**, prima che qualunque
  altro codice giri — quindi il vecchio FQCN è già un alias valido nel
  momento in cui qualunque controller (di sistema o custom cliente) lo
  referenzia, senza passare dall'autoloader PSR-4 per quel namespace.
- `packages/crocodicstudio/crudbooster/src/controllers/` cancellata
  interamente (cartella ora inesistente).
- `composer dump-autoload` eseguito (necessario: le entry `files` sono
  compilate staticamente in `vendor/composer/autoload_static.php`).
- Il mapping PSR-4 `crocodicstudio\crudbooster\` → `packages/.../src`
  resta in `composer.json`, invariato: serve ancora per il resto del
  pacchetto (`helpers/`, `middlewares/`, ecc.), solo il sub-namespace
  `controllers` non ha più bisogno che l'autoloader ci trovi nulla.

## Motivazione

Nessun file "morto" da mantenere solo per ospitare una riga di alias;
un unico punto centrale (leggibile, commentato, con il motivo e il link a
quando andrà rimosso) invece di 5 file sparsi. Comportamento identico
all'esterno: il vecchio FQCN resta risolvibile esattamente come prima,
per chiunque lo estenda.

## Test

Eseguiti in sequenza, prima e dopo la cancellazione, dentro il container
Docker:
- Prima di cancellare: verificato che tutti e 5 gli alias risolvano già
  tramite il nuovo bootstrap (`class_exists()` su ognuno dei 5 FQCN
  legacy → vero), **con i vecchi shim ancora presenti** — nessun conflitto
  tra le due fonti di alias (composer carica i `files` una sola volta per
  processo, prima di tutto il resto).
- Cancellati i 5 file e la cartella, `composer dump-autoload`, poi
  rieseguita la stessa batteria:
  - I 5 `class_exists()` sul vecchio FQCN → tutti veri.
  - `get_parent_class(new crocodicstudio\crudbooster\controllers\CBController())`
    → `App\Http\Controllers\System\Controller` (corretto).
  - `get_parent_class(new App\Http\Controllers\System\AdminGroupsController())`
    → `App\Http\Controllers\System\CBController` (uno dei ~56 controller
    di sistema che estendono `CBController` per FQCN legacy).
  - `new crocodicstudio\crudbooster\controllers\ExportData(...)` →
    `App\Http\Controllers\System\ExportData`.
  - `php artisan route:list`: 486 rotte, invariato.
  - `curl` senza sessione su `/admin`, `/admin/groups`, `/admin/logs`:
    tutti 302, nessun 500.

## Rischi e note

- **Non cambia nulla riguardo alla necessità di migrare i clienti**: gli
  alias restano necessari finché non tutti i clienti attivi sono passati
  per [016](016-comando-migrazione-extends-legacy-clienti.md). Questo
  intervento ha solo spostato *dove* vive il meccanismo di compatibilità,
  non ha eliminato la compatibilità stessa.
- `app/Support/legacy_crudbooster_aliases.php` va rimosso (insieme alla
  entry in `composer.json`) solo quando quella migrazione sarà completa
  per ogni cliente — a quel punto, e solo a quel punto, il vecchio FQCN
  può davvero smettere di esistere.
- Chiunque cloni il repo o tiri un `git pull` dopo questo commit deve
  eseguire `composer install`/`dump-autoload` (normale per una modifica a
  `composer.json`, non specifico di questo intervento).

## Rollback

`git revert` del commit, seguito da `composer dump-autoload` — ripristina
i 5 file-shim e la cartella, comportamento identico a prima
dell'intervento.
