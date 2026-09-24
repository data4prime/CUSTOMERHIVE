# 086 - Nuovo binario `api2` con Laravel Sanctum, additivo rispetto ad `api/`

> **Superato in parte da [088](088-api-tokens-modello-pat.md)**, stesso
> giorno: il concetto di utente "client API" dedicato (`is_api_client`,
> descritto sotto) è stato rimosso subito dopo per un problema di
> conteggio licenza — sostituito da un modello Personal Access Token
> (token generabile per qualunque utente esistente). La base tecnica
> descritta qui (Sanctum, route `api2`, modulo `ApiTokensController`)
> resta valida; solo "chi può avere un token" è cambiato. Documento
> lasciato invariato per la cronologia.

- **Data**: 2026-09-24
- **Stato**: Completato
- **Area**: Sicurezza / API
- **File/aree di codice coinvolte**:
  - `composer.json`/`composer.lock` (nuovo pacchetto `laravel/sanctum`)
  - `config/auth.php`, `config/sanctum.php` (nuovo)
  - `app/User.php` (trait `HasApiTokens`)
  - `database/migrations/` (tabella `personal_access_tokens` di Sanctum, colonna `is_api_client` su `cms_users`)
  - `app/Http/Controllers/System/AdminController.php` (`postLogin()`)
  - `routes/crudbooster.php` (nuovo route-group `api2`)
  - `app/Http/Controllers/System/ApiTokensController.php` (nuovo)
  - `app/Http/Controllers/System/AdminCmsUsersController.php` (flag "API client")
  - `resources/views/api_tokens/*` (nuove)
  - `resources/views/crudbooster/sidebar.blade.php` (voce di menu, annidata sotto "API Generator")
  - `resources/lang/{en,it}/crudbooster.php`
  - `database/seeders/CmsModulsSeeder.php`

## Contesto

Discusso con l'utente a partire dal backlog "Autenticazione API custom
da modernizzare" (vedi `docs/refactoring/README.md`). Lo schema attuale
(`CRUDBooster::authAPI()`, `app/Helpers/CRUDBooster.php:1471`) ha 4
problemi noti: MD5 invece di un HMAC vero, nessuna vera scadenza del
timestamp (un token catturato è rigiocabile all'infinito), confronto non
a tempo costante, autenticazione disattivata di default a meno che
`api_debug_mode` non sia esplicitamente `'false'`.

Decisione presa in conversazione (non qui): non toccare `api/` esistente
(userebbe romperebbe le integrazioni esterne dei clienti già attive con
quello schema — stesso vincolo già documentato per la migrazione
dell'auth guard, "per-cliente e asincrono"). Si costruisce invece un
secondo binario **`api2`**, che punta agli stessi controller generati
dal modulo API Generator (`execute_api()`, invariato), ma con
autenticazione **Laravel Sanctum** (Bearer token) al posto di
`CBAuthAPI`. Migrazione da `api` a `api2` **volontaria, per cliente**,
non uno switch globale.

Requisiti concordati:
- Il token è legato a un utente `cms_users` esistente (non un model
  dedicato: riusa lo scoping tenant/privilegi già presente), marcato
  esplicitamente come "client API" con un nuovo flag
  (`is_api_client`) — per evitare di generare per sbaglio un token per
  un utente dashboard normale, e per impedire che quell'utente possa
  loggarsi sull'area web (anche con password corretta).
- Chi genera/revoca i token è un modulo admin **solo superadmin**
  (azione sensibile, stesso livello di `getBuilder()` in
  `StatisticBuilderController`).
- **Scadenza scelta al momento della generazione** (non un default
  fisso): Sanctum supporta `expires_at` per singolo token via il terzo
  parametro di `createToken()`, quindi nessuna migration custom serve
  per questo — solo la UI per sceglierla.
- Rotazione: manuale (revoca + nuova generazione dalla stessa schermata),
  niente refresh-token automatico stile OAuth — coerente con il modello
  di aggiornamento "per cliente, asincrono" già in uso in questo
  progetto.

## Situazione prima

- Nessun pacchetto Sanctum installato (verificato: `composer show
  laravel/sanctum` → not found).
- `config/auth.php` non ha nessun guard `sanctum` (config ancora in
  stile CRUDBooster "vecchia", guard `api` esistente usa il driver
  `token` di Laravel, mai realmente usato/collegato a nulla).
- `app/User.php` non ha trait Sanctum.
- Nessuna colonna su `cms_users` per distinguere un utente "client API"
  da un utente dashboard normale.
- `postLogin()` (`AdminController.php:328`) autentica in base a
  email+password+status Active+corrispondenza tenant, senza alcun
  concetto di "questo utente non può fare login web".
- `routes/crudbooster.php` (righe 102-120, gruppo "ROUTER FOR OWN
  CONTROLLER FROM CB") itera `cms_moduls` e registra le rotte CRUD per
  ogni controller generato dal modulo API Generator, tutte sotto lo
  stesso gruppo con middleware `CBAuthAPI` — non c'è modo oggi di
  applicare un middleware diverso a un sottoinsieme di questi moduli
  senza toccare quel loop.

## Situazione dopo

**Fase 1 - base tecnica**:
- `laravel/sanctum` (v4.3.3) installato, config pubblicata
  (`config/sanctum.php`, invariata: `expiration` resta `null` a livello
  globale, la scadenza è sempre e solo quella del singolo token).
- Migration Sanctum (`personal_access_tokens`, con `expires_at` già
  incluso) + nuova migration additiva `is_api_client` (boolean, default
  0) su `cms_users`.
- `App\User` usa il trait `Laravel\Sanctum\HasApiTokens`.
- `config/auth.php`: nuovo guard `sanctum` (`driver => sanctum`,
  `provider => users` - stesso `App\User` di sempre).
- `AdminController::postLogin()`: aggiunta `!$users->is_api_client` alla
  condizione di successo login - un utente flaggato cade nel ramo
  "password sbagliata" (stesso messaggio di un login fallito qualunque,
  non rivela l'esistenza/natura dell'account).
- `routes/crudbooster.php`: nuovo gruppo di route, stesso scan di
  `app/Http/Controllers` usato dal gruppo `api/` esistente (stessa
  logica per leggere `$controller->permalink`, non derivato dal nome del
  file - a differenza del gruppo v1 la variabile viene azzerata a ogni
  iterazione, quindi non eredita il bug esistente di riuso della
  variabile tra un controller e il successivo), middleware `auth:sanctum`
  invece di `CBAuthAPI`, prefisso `api2` invece di `api`. **Additivo**: il
  gruppo `api/` esistente non è stato toccato in nessuna riga.

**Fase 2 - modulo admin `ApiTokensController`** (`admin/api_tokens`,
superadmin - stessa gestione privilegi di ogni altro modulo di sistema,
`global_privilege = false`, nessuna riga in `cms_privileges_roles` per
altri ruoli):
- Lista (CRUD generico ereditato, colonne custom: nome token, utente
  collegato via subquery su `cms_users`, creato il, scadenza, ultimo
  utilizzo - mai il campo `token`, che è comunque solo l'hash).
- "Genera nuovo token" (`getAdd()`/`postAddSave()` interamente
  overridati, come già fa `DashboardLayoutController`): seleziona un
  utente tra quelli marcati "client API", nome del token, **scadenza a
  scelta** (30/90/365 giorni, mai, o data personalizzata) passata come
  terzo parametro di `User::createToken()`. Il valore in chiaro del
  token è mostrato una sola volta, nella risposta diretta di
  `postAddSave()` (mai in un redirect/flash/URL - a DB resta solo
  l'hash fin da subito).
- Revoca = cancellazione riga (`getDelete()` generico ereditato: la
  tabella non ha `deleted_at`, quindi è una cancellazione vera, non
  soft-delete).
- `getEdit()`/`postEditSave()` negati esplicitamente (i token non sono
  modificabili: revoca + nuova generazione).
- Nuova riga in `cms_moduls` (stesso pattern di 009/050/084 - senza,
  nessuna rotta esiste), link in sidebar **annidato dentro il menu "API
  Generator"** (stesso `<ul class='treeview-menu'>` di Add New API/List
  API/Generate Screet Key, non una voce di primo livello a sé), tutte le
  stringhe visibili in `it`/`en`.
- `AdminCmsUsersController`: nuovo campo "Client API" nel form utenti
  (select Sì/No, visibile solo a superadmin, stesso criterio del campo
  Tenant), per marcare esplicitamente un utente come contenitore di
  token invece che come utente dashboard.

## Motivazione

Chiudere i problemi noti di `CRUDBooster::authAPI()` (MD5 invece di un
HMAC, nessuna vera scadenza del timestamp, confronto non a tempo
costante, autenticazione disattivata di default) **senza** un cambio di
contratto per i client esistenti: `api/` resta bit-per-bit identico,
`api2` è un binario a cui migrare volontariamente, un cliente alla
volta, quando aggiornato (stesso modello "per-cliente e asincrono" già
in uso in questo progetto per altre migrazioni additive, es. l'auth
guard). La scadenza scelta al momento della generazione (anziché un
default fisso) è un requisito esplicito dell'utente.

## Test

Manuali sull'ambiente Docker locale (suite automatica non eseguita, solo
su richiesta):
- `php -l` su tutti i file toccati.
- `php artisan migrate`: entrambe le migration applicate senza errori.
- `php artisan route:list`: ogni permalink registrato sotto `api/` ha
  ora il suo gemello sotto `api2/` (stessi 14 controller generati
  presenti in locale).
- End-to-end con un token reale (script/utente di prova creati e poi
  eliminati): `api2/hive` senza header → 401 JSON (con
  `Accept: application/json`; senza quell'header, redirect a login,
  comportamento standard Laravel/Handler già esistente); con token
  invalido → 401; con token valido → **stesso identico comportamento di
  `api/hive`** (vedi "Rischi e note" sotto per il perché non è un 200).
- `php artisan db:seed --class=Cms_modulsSeeder`: riga `api_tokens`
  inserita, `route:list` mostra tutte le rotte CRUD del nuovo modulo.
- Scritti (non eseguiti - regola del progetto) `tests/Feature/
  Api2SanctumAuthTest.php`: senza token → 401; token valido → 200; token
  scaduto → 401; token revocato (riga cancellata) → 401; utente
  `is_api_client` non riesce a fare login web anche con password
  corretta (`assertGuest()`, nessun `admin_id` in sessione).
- **Schermata admin verificata end-to-end** (su richiesta dell'utente,
  password del superadmin locale resettata appositamente - vedi
  memoria di sessione, non documentata qui): login reale via `curl` con
  sessione autenticata; `admin/api_tokens` (200, lista); `admin/api_tokens/add`
  (200, form con `tokenable_id`/`expiry_choice`); creato un utente
  `is_api_client` di prova e generato un token vero dal form
  (`admin/api_tokens/add-save`) → pagina di reveal con il token in
  chiaro; verificato che quel token autentica correttamente su
  `api2/hive` (stesso comportamento di `api/hive`, vedi bug scollegato
  sotto); il token compare nella lista con il nome utente risolto
  correttamente dalla colonna custom. Utente e token di prova poi
  eliminati. Voce di menu verificata renderizzata correttamente,
  annidata sotto "API Generator" (spostata lì su richiesta dell'utente,
  non più voce di primo livello).

## Rischi e note

- **Trovato un bug preesistente, scollegato da questo intervento**:
  `ApiController::execute_api()` (riga ~805) genera un `ErrorException`
  ("Undefined variable $debug_mode_message") su **qualunque** chiamata,
  riprodotto identico su `api/hive` **prima** di qualunque modifica di
  questa sessione e a prescindere da `api_debug_mode`. Sembra rompere
  ogni chiamata reale attraverso il modulo API Generator oggi in
  produzione - **da verificare con priorità alta separatamente**, non
  toccato qui (fuori scope, e servono più informazioni su quando è
  comparso). Per questo i test di questo intervento isolano il layer di
  auth con una rotta ad-hoc (stesso approccio già usato da
  `ApiExecuteTest.php`) invece di passare da `execute_api()` reale.
- Il modulo admin non ha una modalità per **creare** al volo un utente
  "client API" dalla stessa schermata: va prima creato/flaggato da
  Users Management, poi tornare qui per generare il token. Scope
  volutamente ridotto (vedi discussione con l'utente) - se in futuro
  serve più comodità, si può aggiungere.
- Rotazione: manuale (revoca + nuova generazione), nessun endpoint di
  auto-rotazione - scelta deliberata, coerente con gli aggiornamenti
  "per cliente, asincroni" già in uso.
- Non toccato il gap "autenticazione disattivata di default a meno che
  `api_debug_mode` non sia `'false'`" su `api/` - resta backlog separato,
  fuori scope per questo intervento (che introduce un binario nuovo,
  non modifica quello esistente).
- Prima del deploy su un cliente che vuole usare `api2`: verificare che
  `composer.lock` aggiornato (nuova dipendenza) sia applicato con
  `composer install` (non solo `git pull`), e lanciare le due migration.

## Rollback

Migration: `php artisan migrate:rollback` sulle due migration di questo
intervento (`create_personal_access_tokens_table`,
`add_is_api_client_to_cms_users_table`) - nessun dato applicativo perso
(tabelle nuove/colonna additiva, mai popolate se non da questo lavoro).
Codice: `git revert` dei commit; rimuovere la riga `api_tokens` da
`cms_moduls` (`DELETE FROM cms_moduls WHERE controller=
'ApiTokensController'`) solo se il modulo non è mai stato usato nel
frattempo. `composer remove laravel/sanctum` se si vuole anche togliere
la dipendenza (non necessario solo per disattivare la feature: basta
rimuovere il gruppo di route `api2` e il guard `sanctum`).
