# 018 - Pulizia commands/, middlewares/, validations/

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Architettura / CRUDBooster
- **File/aree di codice coinvolte**:
  - `app/Console/Commands/Mailqueues.php` (nuovo)
  - `app/Providers/AppServiceProvider.php` (aggiunte le 2 regole di validazione)
  - `packages/crocodicstudio/crudbooster/src/CRUDBoosterServiceProvider.php`
  - `app/Console/Kernel.php`
  - `packages/crocodicstudio/crudbooster/src/commands/` (rimossa interamente)
  - `packages/crocodicstudio/crudbooster/src/validations/` (rimossa interamente)
  - `packages/crocodicstudio/crudbooster/src/middlewares/CBBackend__.php` (rimosso)

## Contesto

Analisi di `packages/crocodicstudio/crudbooster/src/` (vedi discussione in
chat) per pianificare l'uscita dalle parti rimanenti del pacchetto
vendorizzato, dopo le classi motore ([012](012-controller-motore-shim-class-alias.md)-[017](017-rimozione-cartella-controllers-legacy.md)).
Deciso con l'utente, dopo aver verificato l'uso reale di ciascun pezzo:
- I 3 comandi "installer" storici di CRUDBooster (`crudbooster:install`,
  `crudbooster:update`, `crudbooster:version`): **zero riferimenti** in
  tutto il repo (codice o docs/) oltre alla propria definizione, non
  schedulati, non corrispondenti al reale processo di aggiornamento
  cliente (vedi [[project-client-update-process]]) — eliminati.
- `Mailqueues` (comando `mailqueues`): diverso dagli altri 3 — non
  schedulato in `app/Console/Kernel.php`, ma `CRUDBooster::sendEmail()`
  inserisce ancora righe in `cms_email_queues` in una delle modalità di
  invio email, quindi potenzialmente ancora attivo via cron di sistema
  esterno (non verificabile da qui). Spostato come comando standard
  invece di eliminato.
- `CBBackend__.php`: già segnalato come codice morto in
  [006](006-controller-sistema-app-http-controllers-system.md) (nessun
  riferimento in tutto il repo, riverificato) — eliminato.
- `validations/validation.php`: **non morto** — registra due regole
  Laravel (`alpha_spaces`, `alpha_num_spaces`) usate realmente nei form di
  `AdminCmsUsersController` (campo "Name") e `EmailTemplatesController`
  (campo nome template), e referenziate anche nelle view del module
  generator (quindi potenzialmente usate anche in moduli custom cliente).
  Spostato, non eliminato.

## Situazione prima

- `commands/`: `CrudboosterInstallationCommand.php`,
  `CrudboosterUpdateCommand.php`, `CrudboosterVersionCommand.php`,
  `Mailqueues.php` — tutti in stile Laravel pre-`$signature`
  (`protected $name = '...'`), registrati in
  `CRUDBoosterServiceProvider::register()` con un mix di
  `$this->commands([...])` e singleton custom (`registerCrudboosterCommand()`).
- `middlewares/`: `CBBackend.php`, `CBAuthAPI.php` (vivi, applicati per
  FQCN letterale in `routes.php`) + `CBBackend__.php` (doppio underscore,
  morto).
- `validations/validation.php`: incluso con
  `require __DIR__.'/validations/validation.php';` in
  `CRUDBoosterServiceProvider::boot()`.

## Situazione dopo

- **`Mailqueues`** spostato in `App\Console\Commands\Mailqueues`,
  modernizzato nello stile già usato dagli altri comandi dell'app
  (`protected $signature = 'mailqueues';` invece di `$name`, import
  espliciti `Illuminate\Support\Facades\{Cache,DB}` invece di alias bare,
  rimosso `use Request;` mai usato nel corpo). **Nome del comando
  invariato** (`mailqueues`) — qualunque cron di sistema esterno che lo
  invochi continua a funzionare senza modifiche. Logica di `handle()`
  copiata identica, bug preesistenti non toccati (variabili
  `$limit_an_hour` e `$queue` non definite — comportamento preesistente,
  fuori scope per uno spostamento puro).
- Registrato in `app/Console/Kernel.php` → `$commands`, come gli altri
  comandi dell'app.
- I 3 comandi installer e il vecchio `Mailqueues.php` cancellati;
  `commands/` rimossa interamente (cartella vuota).
- `CRUDBoosterServiceProvider.php`: rimossi i 2 `use` per
  `CrudboosterInstallationCommand`/`CrudboosterUpdateCommand`, il blocco
  `$this->commands([commands\Mailqueues::class])` + le 3 righe
  `$this->commands(...)` per installer/updater/version, e il metodo
  privato `registerCrudboosterCommand()`.
- Le 2 chiamate `Validator::extend()` di `validation.php` spostate in
  `App\Providers\AppServiceProvider::boot()` (punto standard Laravel per
  questo tipo di registrazione), corpo delle closure invariato.
  `validations/validation.php` cancellato, `validations/` rimossa
  (cartella vuota). Rimossa la riga `require __DIR__.'/validations/validation.php';`
  da `CRUDBoosterServiceProvider::boot()`.
- `CBBackend__.php` cancellato.

## Motivazione

Riduce ulteriormente la superficie del pacchetto vendorizzato spostando
ciò che ha ancora un uso reale nella struttura standard Laravel
(`app/Console/Commands`, `AppServiceProvider`), ed eliminando quello che
non ne ha nessuno — senza toccare nulla che il processo di aggiornamento
cliente o un cron esterno potrebbero ancora usare.

## Test

- `php -l` su tutti i file toccati/nuovi: nessun errore.
- `composer dump-autoload` (necessario per l'entry `files` già presente
  da [017](017-rimozione-cartella-controllers-legacy.md), non per questo
  intervento specifico, ma rieseguito per sicurezza).
- `php artisan list`: `mailqueues` presente, `crudbooster:install`,
  `crudbooster:update`, `crudbooster:version` **spariti**,
  `crudbooster:migrate-legacy-extends` ([016](016-comando-migrazione-extends-legacy-clienti.md))
  invariato.
- `php artisan route:list`: 486 rotte, invariato.
- `curl` senza sessione su `/admin`, `/admin/groups`, `/admin/users`,
  `/admin/logs`: tutti 302, nessun 500.
- Verifica funzionale delle regole di validazione: bootstrap completo
  dell'app via script diretto, `Validator::make(['name' => 'Mario Rossi'],
  ['name' => 'alpha_spaces'])` → passa; `['name' => 'Mario123']` → fallisce
  correttamente; `alpha_num_spaces` su `'Room 123'` → passa. Confermato che
  la registrazione in `AppServiceProvider` funziona in modo identico alla
  precedente in `validations/validation.php`.
- **Non verificato**: esecuzione reale di `php artisan mailqueues` con
  dati veri in `cms_email_queues` (comando con bug preesistenti,
  invariati rispetto a prima — non nello scope di questo intervento).

## Rischi e note

- **Da confermare con l'utente**: se `crudbooster:install`/`:update`/`:version`
  vengono ancora digitati a mano su qualche server cliente — se sì,
  andrebbero ripristinati (sono stati eliminati, non solo spostati,
  quindi il rollback per questi 3 è `git revert`, non uno spostamento).
- **Da verificare sul crontab reale dei server clienti**: se
  `php artisan mailqueues` è ancora invocato da un cron esterno (non è
  schedulato da Laravel in questo repo) — se sì, il nome del comando resta
  identico quindi nessuna modifica necessaria lato cron.
- `alpha_spaces`/`alpha_num_spaces` sono referenziate anche nelle view del
  module generator (`api_generator.blade.php`,
  `module_generator/step4.blade.php`) — non toccate, restano stringhe
  suggerite/digitabili nell'interfaccia, invariato.

## Rollback

`git revert` del commit. Per i 3 comandi installer eliminati (non
spostati), il revert li ripristina identici a prima; per `Mailqueues` e
`validation.php` (spostati), il revert ripristina sia la vecchia
posizione sia annulla le modifiche nei file nuovi.
