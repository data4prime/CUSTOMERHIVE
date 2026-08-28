# 026 - Helpers (4/N): QlikHelper e ModuleHelper spostati in App\Helpers

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Architettura / CRUDBooster
- **File/aree di codice coinvolte**:
  - `app/Helpers/{QlikHelper,ModuleHelper}.php` (nuovi)
  - `packages/crocodicstudio/crudbooster/src/helpers/{QlikHelper,ModuleHelper}.php` (eliminati)
  - `packages/crocodicstudio/crudbooster/src/CRUDBoosterServiceProvider.php`
  - `packages/crocodicstudio/crudbooster/src/helpers/CRUDBooster.php`
  - `app/Helpers/TenantHelper.php`
  - 7 controller in `app/Http/Controllers/System/`
  - `resources/views/{mashup,mashup_objects}.blade.php`
  - `routes/web.php`

## Contesto

Continua [023](023-helpers-nnhelper-moduleHelperhelper-myhelper.md)-[025](025-helpers-chatai-license-menu.md).
`QlikHelper` (19 KB) e `ModuleHelper` (30 KB) sono entrambi tra gli 8
helper aliasati globalmente via `AliasLoader`, e `ModuleHelper` importa
già esplicitamente `QlikHelper` (`use crocodicstudio\crudbooster\helpers\QlikHelper;`)
— spostati insieme per coerenza.

Alcuni file esterni importano `QlikHelper` **due volte con alias diversi**
nello stesso file: un `use QlikHelper;` semplice (che importa l'alias
globale registrato via `AliasLoader`, non il FQCN) e un
`use crocodicstudio\crudbooster\helpers\QlikHelper as HelpersQlikHelper;`
(per riferirsi esplicitamente alla classe reale sotto un nome diverso,
evitando collisione). Solo il secondo tipo di riga andava aggiornato — il
primo (`use QlikHelper;`) continua a funzionare invariato, risolvendo
tramite l'alias.

## Situazione prima

I 2 file in `packages/.../helpers/`. `QlikHelper` conteneva anche 3 metodi
(`getTicketFromConf`, `getTicket`, `dataForTicketConf`, ~250 righe)
interamente commentati (`/* ... */`, mai eseguiti). Riferimenti esterni:
`QlikHelper` in 10 file (7 controller/route con `use` semplice o con
alias, 2 view Blade), `ModuleHelper` in 3 file esterni +
`app/Helpers/TenantHelper.php` (già aggiornato in un lotto precedente per
puntare provvisoriamente al vecchio namespace).

## Situazione dopo

- I 2 file spostati in `App\Helpers\{QlikHelper,ModuleHelper}`. Aggiunti
  `use crocodicstudio\crudbooster\helpers\{CRUDBooster,UserHelper};` a
  entrambi (bare, non ancora spostati); `ModuleHelper` ha anche
  `use App\Helpers\QlikHelper;` (aggiornato dal vecchio namespace).
- **Rimossi i 3 metodi interamente commentati** in `QlikHelper` durante
  la ricopiatura manuale (656 → 404 righe) — verificato con un confronto
  dei nomi dei metodi pubblici tra vecchio e nuovo file: gli 11 metodi
  reali sono identici, mancano solo i 3 mai eseguiti. Nessun impatto
  comportamentale (erano commenti, non codice).
- `ModuleHelper`: nessuna riga rimossa, solo import aggiunti (958 → 960
  righe), confermato con lo stesso confronto dei metodi pubblici
  (identici).
- `AliasLoader`: `QlikHelper`/`ModuleHelper` ripuntati a `App\Helpers\...`.
- Tutti i riferimenti esterni noti aggiornati (compresi i doppi import
  con alias nei 3 file che ne avevano due).
- `CRUDBooster.php`: aggiunto `use App\Helpers\{QlikHelper,ModuleHelper};`.

## Motivazione

Prosegue lo svuotamento di `helpers/`, uno dei lotti più grandi per
dimensione dei file e numero di punti da aggiornare finora.

## Test

- `php -l` su tutti i file PHP toccati: nessun errore (stessa
  deprecation preesistente in `CRUDBooster.php`, riga spostata da 1532 a
  1534 per le 2 righe di `use` aggiunte).
- Compilazione isolata (via `BladeCompiler::compileString()` + `php -l`
  sul PHP risultante) di `mashup.blade.php`/`mashup_objects.blade.php`:
  entrambe senza errori.
- Grep di conferma: zero riferimenti al vecchio namespace per
  `QlikHelper`/`ModuleHelper` in tutto il repo.
- `composer dump-autoload`; `class_exists()` vero per entrambi i nuovi
  FQCN.
- `php artisan route:list`: 486 rotte, invariato.
- `curl` senza sessione su `/admin`, `/admin/groups`, `/admin/logs`: 302,
  nessun 500.

## Rischi e note

- Restano da spostare: `UserHelper` (molto richiamato, ma non il più
  grosso), `Helper.php` (funzioni globali), `CRUDBooster.php` per ultimo
  — vedi [`README.md`](README.md#roadmap-uscita-da-crudbooster-packages).

## Rollback

`git revert` del commit — ripristina i 2 file nel pacchetto (inclusi i 3
metodi commentati rimossi) e tutti i riferimenti, nessun impatto su
codice esterno.
