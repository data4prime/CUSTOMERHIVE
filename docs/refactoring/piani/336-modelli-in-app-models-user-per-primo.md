# Piano 336 — Modelli in `app/Models/`, a un modello per batch (batch B1: `User`)

Stato: **approvato ed eseguito (doc 336)**. Branch: `refactor/models`.
Autore: `/architect`, 2026-10-30. Destinatario: l'implementatore (non vede la
conversazione: questo file è autosufficiente). Origine: la persona ha chiesto di portare i
modelli nella cartella `App\Models\`, "un po' alla volta", **partendo da `User`**, dopo il piano
334 (Form Request del profilo, doc 335). **Sostituisce l'ADR A11** ("restare in `App\`").

## Obiettivo

Spostare i modelli Eloquent da `App\` a `App\Models\` (standard Laravel), un modello per batch,
**senza rompere** i controller custom dei clienti, Sanctum e i dati già salvati. Questo piano
dettaglia il **batch B1 (`User`)**; i batch successivi sono solo elencati in fondo e si
pianificano uno alla volta.

## Fatti verificati (2026-10-30)

- `App\User` (`app/User.php`) estende `Authenticatable`, usa `Notifiable` e `HasApiTokens`
  (Sanctum), tabella `cms_users`.
- Riferimenti al nome `App\User` **nel codice**: `config/auth.php:78` e `config/services.php:33`
  (`App\User::class`); `use App\User;` in `GenerateApiToken`, `InstanceCommandSupport`,
  `InstanceBootstrap`, `InstancePasswordLink`, `MfaHelper`, `UserHelper`, `AdminTenantsController`,
  `ApiTokensController`, `LogsController`, `QlikSyncService`; uso diretto `\App\User::find/where`
  in `QlikHelper` (168, 217), `AdminCmsUsersController` (3 punti), `AdminController` (6 punti),
  `QlikConfController:642`; un commento in `QlikConnectionTester:45` e uno in `AdminController:464`.
  Nei test: `tests/Concerns/SeedsCmsData.php` (2), `Api2SanctumAuthTest` (2), `CBBackendTest`,
  `UserProfileSectionsTest`. **Nessun riferimento** in viste, `routes/`, `database/`, `bootstrap/`,
  `phpstan.neon`, baseline, `composer.json` (ricerca a testo fisso, 2026-10-30).
- **Colonne polimorfiche**: nel DB locale l'unica tabella con `*able_type` è
  `personal_access_tokens.tokenable_type` (1 riga, valore `App\User`). Nessuna tabella
  `notifications` di Laravel (esiste solo `cms_notifications`, non polimorfica). Le altre colonne
  `*_type` (`method_type`, `item_type`, `record_type`, `content_input_type`) non c'entrano.
- Gli alias di compatibilità esistono già: `app/Support/legacy_crudbooster_aliases.php` è caricato
  da `composer.json` (`autoload.files`, riga 40), quindi **aggiungere un alias lì non richiede di
  cambiare `composer.json`**.
- Il processo di update dei clienti lancia `php artisan migrate` (confermato dalla persona,
  2026-10-30). Si assume anche che rigeneri l'autoload di Composer (già necessario per ogni classe
  nuova, es. `app/Actions/`, `app/Http/Requests/`).
- `Relation::morphMap` non è usata nel repo; con `class_alias` un `tokenable_type = 'App\User'`
  continua a risolversi (Eloquent istanzia la classe per nome, e l'alias punta alla stessa classe).

## Perimetro del batch B1

Dentro:
1. `app/User.php` → `app/Models/User.php` (stesso contenuto, solo `namespace App\Models;` e
   gli `use` necessari: `Tenant`, `Group`, `Role` restano in `App\`, quindi `use App\Tenant;` ecc.
   o nome qualificato; i loro spostamenti sono batch successivi).
2. Alias `class_alias(\App\Models\User::class, 'App\User')` in `legacy_crudbooster_aliases.php`
   (sezione nuova "Modelli spostati in App\Models").
3. Migration `database/migrations/2026_10_30_xxxxxx_update_tokenable_type_for_user_model.php`:
   `UPDATE personal_access_tokens SET tokenable_type = 'App\Models\User' WHERE tokenable_type =
   'App\User'`; `down()` inverso. Idempotente (se non ci sono righe non fa nulla).
4. Aggiornare i riferimenti del **repo** al nuovo nome (config + i file elencati sopra + test),
   così l'alias serve solo al codice dei clienti.

Fuori (non toccare): metodi e relazioni del modello (nessun cambio di firma o comportamento),
`UsersGroup` e gli altri modelli (batch successivi), la rinomina al singolare, `Clienti`/`Ordini`/
`Righe`/`DynamicTable` (piano 317: non si toccano), `CBController`/`CRUDBooster::`.

## Pattern e perché

**Spostamento con alias di compatibilità** (stesso meccanismo dei namespace `crudbooster`, ADR A1):
il vecchio nome continua a funzionare per i controller custom dei clienti (`use App\User;`,
`instanceof \App\User`, `extends \App\User`), mentre il codice del repo usa il nome nuovo.
**Migration** per i dati già scritti: con l'alias i vecchi token funzionerebbero comunque, ma la
migration evita di lasciare due nomi nel DB (un token nuovo salva `App\Models\User`, perché
`getMorphClass()` restituisce la classe reale).

## Passi

1. **Test di caratterizzazione prima** (vedi sotto): eseguirli sul codice attuale; devono essere verdi.
2. Creare `app/Models/User.php` (contenuto identico, nuovo namespace) e rimuovere `app/User.php`.
3. Aggiungere l'alias.
4. Aggiornare `config/auth.php` e `config/services.php` (`App\Models\User::class`).
5. Aggiornare i `use`/riferimenti nel codice del repo (elenco sopra). Non toccare i commenti storici
   nei documenti `docs/refactoring/` (sono storia).
6. Scrivere la migration e provarla (`up` e `down`) sul DB di test.
7. `php -l`, Pint `--test` sui soli file nuovi/toccati, Larastan (perimetro `app/Helpers` e
   `Controllers/System`) anche da cache vuota (`phpstan clear-result-cache`).
8. Dopo l'esecuzione: ADR **A17** che sostituisce A11 per i modelli (A11 resta, marcata
   `(sostituita da A17)` solo per la parte sullo spostamento), `mappa.md`, `pattern-nel-repo.md`
   (riga "Spostamento modello con alias e migration"), nota in `CLAUDE.md` sull'alias.

## Test di caratterizzazione (per rischio decrescente)

Esistono già test che esercitano `User` indirettamente (`Api2SanctumAuthTest`, `ApiTokens*Test`,
`CBBackendTest`, `UserProfileSectionsTest`, `UsersCrudTest`, `UserModelLookupsTest`, i test di
login/MFA): sono la regressione. Da aggiungere (nuovo file `tests/Feature/UserModelMoveTest.php`):

1. **Token esistente prima della migration**: riga in `personal_access_tokens` con
   `tokenable_type = 'App\User'` e un token valido → una chiamata API autenticata Sanctum riesce
   (prova che l'alias basta anche senza migration).
2. **Token nuovo**: generare un token da `User` → `tokenable_type` salvato = `App\Models\User`.
3. **Alias**: `class_exists('App\User')`, `new \App\User instanceof \App\Models\User`,
   `\App\User::class` e `\App\Models\User::class` risolvono alla stessa classe reale.
4. **Migration**: con una riga `App\User` e una già `App\Models\User`, `up` le porta entrambe a
   `App\Models\User`; `down` riporta a `App\User`; con tabella vuota non fa nulla.
5. **Guard `web`**: `Auth::guard('web')->user()` (via `config/auth.php`) è un `App\Models\User`.

Regola (lezione 2026-10-29): ogni negativo ha il suo positivo con lo stesso setup. Regola
(lezione 2026-10-30): i test si scrivono **ed eseguono sul codice vecchio** prima di spostare
nulla (quelli sull'alias e sulla migration saranno rossi finché non esistono: dichiararlo nel
nome/docblock come "rosso fino al passo N" e pulire il docblock al passo N).

## Vincoli

- Behavior-preserving. Nessun metodo/relazione del modello cambia; nessun testo UI nuovo.
- Il vecchio nome `App\User` deve continuare a funzionare finché **ogni** cliente non è migrato.
- Nessun commit/push, nessuna suite completa in autonomia (la lancia la persona con `/refactor-verify`).
- Se un test fa DDL o `TRUNCATE`, tearDown che ripulisce (lezione doc 324).
- Non rinominare file o classi oltre allo spostamento (niente singolarizzazione in questo batch).

## Impatto sui clienti

Nessuno atteso, **a condizione che** il processo di update: (a) lanci `php artisan migrate`
(confermato), (b) rigeneri l'autoload di Composer. Codice custom che usa `App\User` continua a
funzionare tramite l'alias. Un cliente che riceve codice nuovo ma **non** ha ancora la migration
funziona comunque (vedi sopra); un cliente con token esistenti li mantiene.

**Cambia comportamento visibile? No.**

## Rischio

Basso-medio. Punti di attenzione: (1) un `use App\User;` dimenticato funziona comunque grazie
all'alias, quindi l'errore è silenzioso ma innocuo; (2) `class_alias` carica `App\Models\User`
a ogni richiesta (autoload `files`): costo trascurabile; (3) con `composer dump-autoload
--classmap-authoritative` in produzione serve il dump dopo l'update (già richiesto per le classi
nuove); (4) la baseline di Larastan non cita `App\User`, ma va rilanciata da cache vuota.

## Rollback

Rimettere `app/User.php` dal diff, togliere l'alias, ripristinare `config/auth.php` e
`config/services.php`, e lanciare `php artisan migrate:rollback --step=1` (il `down()` riporta
`tokenable_type` a `App\User`). Nessun altro dato toccato.

## Decisioni aperte (per la persona)

- Nessuna al momento: l'ordine (User per primo), la migration e l'alias sono già stati decisi.

## Prossimi batch (non in questo piano, uno alla volta)

B2 modelli poco esposti (Qlik, ChatAI: `QlikConf`, `QlikItem`, `QlikSyncRun*`, `ChatAIConf`, pivot
`ChatAI*Allowed`); B3 `Tenant`, `Group`, `Role`, `UsersGroup`, pivot di autorizzazione; B4 `Menu*`,
`Modules`, `Log`, `AccessLog` e restanti. Ciascuno con alias e test di caratterizzazione propri.
La singolarizzazione dei nomi (`UsersGroup` → `UserGroup`, …) è una decisione separata.
