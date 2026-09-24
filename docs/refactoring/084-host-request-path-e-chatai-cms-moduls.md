# 084 - `$_SERVER['HTTP_HOST']`/`REQUEST_URI` residui e riga ChatAI mancante in cms_moduls

- **Data**: 2026-09-24
- **Stato**: Completato
- **Area**: Bug fix / robustezza / dati
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/AdminController.php` (`getLicensescreen()`, `getLogin()`, `getForgot()`, `getResetPassword()`)
  - `app/Helpers/CRUDBooster.php` (`isEditPage()`, `isAddPage()`, `isProfilePage()`)
  - `database/seeders/CmsModulsSeeder.php`
  - Dati: tabella `cms_moduls` (dev)

## Contesto

Due voci di backlog riprese insieme su richiesta dell'utente (punti 4 e 5
dell'elenco bug noti in `docs/refactoring/README.md`).

**Punto 4**: [081](081-fix-robustezza-helper-e-login.md) aveva già
sostituito `$_SERVER['HTTP_HOST']` con `Request::getHost()` in
`postLogin()`, segnalando esplicitamente nelle note che lo stesso pattern
restava in `getLogin()`/`getForgot()`/`getResetPassword()`/
`getLicensescreen()` e in `CRUDBooster::isEditPage()`/`isAddPage()`/
`isProfilePage()`.

**Punto 5**: emerso durante [076](076-guard-licenza-e-login-moduli-qlik-chatai.md)
verificando il modulo ChatAI con licenza attiva: `chat_ai/access/1` e
`chat_ai/tenant/1` arrivavano al controller ma davano 500, per un motivo
distinto dal fix di quell'intervento. Stesso identico pattern già visto in
[009](009-module-helpers-cms-moduls-mancante.md) e
[050](050-dashboard-layouts-cms-moduls-mancante.md): un controller "di
sistema" senza riga in `cms_moduls` non ha nessuna rotta auto-generata
(vedi `routes/crudbooster.php`, il blocco che itera
`cms_moduls.controller` per i controller sotto `App\Http\Controllers\System`),
a prescindere dal fatto che controller, tabella e licenza siano già a posto.

## Situazione prima

**Punto 4**: `getLicensescreen()`, `getLogin()`, `getForgot()`,
`getResetPassword()` leggevano il tenant dal sottodominio con
`isset($_SERVER) && isset($_SERVER['HTTP_HOST']) ? explode('.', $_SERVER['HTTP_HOST']) : []`
— codice non testabile senza forzare la superglobale (stesso problema già
risolto per `postLogin()` in 081). `CRUDBooster::isEditPage()`/
`isAddPage()`/`isProfilePage()` costruivano l'URL corrente con
`"http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"` per poi testarlo con
una regex/`str_contains` che guarda solo il *path* — l'host concatenato
non serviva mai al confronto, era pura dipendenza inutile dalla
superglobale. `isAddPage()`/`isProfilePage()` sono usate attivamente da
`AdminCmsUsersController::cbInit()` per la policy password (074): campo
password `required` in aggiunta, `nullable` in modifica/profilo.
`isEditPage()` risulta senza chiamanti nel grafo (probabile residuo morto,
non toccato oltre al fix del pattern).

**Punto 5**: `CmsModulsSeeder.php` non aveva nessuna voce per
`AdminChatAIController` (verificato anche a DB: nessuna riga con
`controller='AdminChatAIController'`). Controller, tabella
(`chatai_confs`) e link nella sidebar (`resources/views/crudbooster/sidebar.blade.php:187`,
dietro `LicenseHelper::isActiveChatAI()`) esistevano già.

## Situazione dopo

**Punto 4**: le 4 chiamate in `AdminController.php` ora usano
`Request::getHost()` (stesso pattern già adottato in `postLogin()`,
facade già importata nel file). Le 3 funzioni in `CRUDBooster.php` ora
usano `request()->getRequestUri()` invece di concatenare
host+request-uri da `$_SERVER`: il pattern testato dipende solo dal path,
quindi togliere l'host dal confronto non cambia il risultato (nessun
comportamento visibile modificato).

**Punto 5**: aggiunta la riga mancante in `CmsModulsSeeder.php`, stesso
schema di 009/050: `name` = `'Chat AI'` (letterale, coerente con la
sidebar e con `ModuleHelperSeeder.php` che cerca il modulo per questo
nome — non `trans()`, come già per `'Qlik Items'`/`'Qlik Configuration'`),
`path` = `chat_ai`, `table_name` = `chatai_confs`, `controller` =
`AdminChatAIController`, `is_protected` = 1 (come Module Helpers/Dashboard
Layouts/Menu Management — un modulo di configurazione "di sistema", non
generato da interfaccia). Seeder rieseguito sul DB di sviluppo
(idempotente: inserita solo la riga nuova).

## Motivazione

Punto 4: robustezza/testabilità, nessun rischio comportamentale reale
(host non usato nel confronto path-only; per il tenant da sottodominio,
stesso ragionamento già validato in 081 — `getHost()` non include la
porta, irrilevante per i domini reali dei clienti). Punto 5: senza la riga
nel seeder il 500 si sarebbe ripresentato su ogni installazione pulita con
ChatAI in licenza, non solo nell'ambiente locale — stessa motivazione di
009/050.

## Test

Manuali sull'ambiente Docker locale:

- `php -l` su `AdminController.php`, `CRUDBooster.php`,
  `CmsModulsSeeder.php`: nessun errore.
- `grep` (`tokensave_search literal`) su `$_SERVER['HTTP_HOST']` in `app/`:
  restano solo i commenti che documentano il fix, nessun uso residuo nelle
  4 funzioni + 3 metodi.
- `php artisan db:seed --class=Cms_modulsSeeder`: idempotente, ha inserito
  solo la riga `AdminChatAIController`.
- `php artisan route:list | grep chat_ai`: ora presenti tutte le rotte
  CRUD auto-generate (`admin/chat_ai` → `AdminChatAIControllerGetIndex`,
  add/edit/delete/detail/ecc.), prima assenti.
- `curl` senza sessione su `admin/chat_ai` → 302 (redirect login), non più
  404/500.

Non eseguito: suite automatica (solo su richiesta), verifica visiva in
browser del flusso ChatAI con licenza attiva.

## Rischi e note

- Il fix del punto 5 riguarda solo l'esistenza delle rotte CRUD generiche
  del modulo (index/add/edit/ecc.); non è stato verificato che le pagine
  `chat_ai/access/{id}`/`chat_ai/tenant/{id}` (route custom di
  `routes/web.php`, già esistenti) funzionino end-to-end con licenza
  ChatAI attiva — serve un giro di test manuale con licenza reale.
- Su ogni cliente già in produzione con ChatAI in licenza, la riga
  `cms_moduls` andrà inserita manualmente (o rieseguendo il seeder) in
  fase di aggiornamento, altrimenti resterebbe con lo stesso 500 finché
  non viene applicata.
- `CRUDBooster::isEditPage()` resta senza chiamanti individuati nel
  grafo — non rimossa in questo intervento (fuori scope, fix minimo sul
  pattern `$_SERVER`).

## Rollback

Per il punto 4: `git revert` del commit, nessuna migration né dato
coinvolto. Per il punto 5: `git revert` del commit sul seeder, più
`DELETE FROM cms_moduls WHERE controller='AdminChatAIController'` solo se
il modulo non è mai stato usato nel frattempo (stessa nota di 009/050).
