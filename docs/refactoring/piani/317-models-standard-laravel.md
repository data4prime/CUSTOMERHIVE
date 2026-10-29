# Piano 317 — Modelli Eloquent verso gli standard Laravel (batch 0-3)

Stato: **in attesa di approvazione**. Branch di lavoro: `refactor/models`.
Autore: `/architect`, 2026-10-29. Destinatario: `ch-senior-dev` (non vede la
conversazione: questo file è autosufficiente).

## Obiettivo

Portare i 24 modelli di `app/*.php` (namespace `App\`) a uno stile Laravel
standard **senza cambiare comportamento visibile e senza cambiare namespace**:
mass assignment e casts espliciti, relazioni Eloquent vere al posto di
`pluck()`/`find()` a mano, logica "di processo" fuori dai modelli (prima
Action del repo), local scope per i filtri ripetuti.

## Fuori perimetro (deciso con la persona)

- Spostamento in `App\Models\` e rinomina al singolare: **non ora** (rischio
  Sanctum `tokenable_type`, alias per i controller dei clienti). Si rivaluta
  dopo i batch 1-3.
- `Clienti`, `Ordini`, `Righe`, `DynamicTable`: **non si toccano**.
- Global scope per tenant: **no**. Il motore usa `DB::table()` in 400+ punti
  (`CBController`, `CRUDBooster::`, `ApiController`, dashboard): un global scope
  non proteggerebbe quei percorsi e darebbe una falsa sicurezza.
- Sostituire `DB::table()` con Eloquent nei controller: non è un obiettivo.
- Nessuna modifica a `CBController`, `CRUDBooster::`, proprietà/hook del motore.

## Vincoli

- Behavior-preserving. I metodi pubblici attuali dei modelli (`tenants()`,
  `tenant()`, `primary_group()`, `allowedGroups()`, `assign_default_*`,
  `add_tenant()`, ecc.) **restano con la stessa firma e lo stesso risultato**:
  i controller custom dei clienti (non visibili) possono chiamarli.
- Un batch alla volta; nessun commit/push; nessuna suite di test lanciata da
  te (la persona usa `/refactor-verify`); verifiche leggere = `php -l`, Pint
  `--test` solo sui file toccati, lettura del diff.
- Se un'azione cambia un comportamento visibile, fermati e riportalo.

## Fatti verificati sul codice (2026-10-29)

- Modelli letti: `User`, `Tenant`, `Group`, `Menu`, `Modules`, `QlikSyncRun`,
  `QlikItem`, `ChatAIConf`, `GroupTenants`, `Log`, `Clienti`, `DynamicTable`.
  Gli altri (`Role`, `UsersGroup`, `MenuGroups`, `MenuTenants`, `ModuleTenants`,
  `TenantsAllowed`, `ItemsAllowed`, `AccessLog`, `QlikSyncRunRecord`) vanno
  letti all'inizio del batch 1.
- Nessuna relazione Eloquent tranne `QlikSyncRun::records()`.
- `Menu::tenants()` restituisce un **array di id letti dal pivot** (non filtra i
  tenant soft-deleted); `Menu::tenants_name()` usa `Tenant::find()` (che
  **esclude** i soft-deleted) con una logica di virgole che con un tenant
  mancante in coda può lasciare una `, ` finale. Nei batch 0-3 preservare;
  si corregge nel batch 2b (cambio visibile dichiarato).
- `User::tenant()` e `User::primary_group()` hanno lo stesso nome di una colonna
  (`$this->tenant`, `$this->primary_group` leggono l'attributo): una relazione
  con quel nome verrebbe **oscurata dalla colonna**. Le nuove relazioni devono
  avere nomi diversi.
- `UserHelper::current_user_primary_group_name()` fa `->primary_group()->name`
  (`app/Helpers/UserHelper.php:197`): se l'utente **loggato** non ha un gruppo
  primario valido (colonna NULL, gruppo cancellato: `Group` ha `SoftDeletes`,
  quindi `Group::find()` restituisce null) la lettura di `->name` su null in
  Laravel diventa un errore. Il metodo è il `default` di campi form in
  `AdminCmsUsersController.php:259` e `ModuleHelper.php:861,884`, quindi
  colpisce l'apertura di quei form. Nei batch 0-3 preservare; si corregge nel
  batch 2b (cambio visibile dichiarato). Che l'errore si manifesti davvero è
  da confermare dal test del batch 0.
- Chiamanti dei metodi da toccare:
  - `assign_default_tenant`: `MenusController.php:741`, `ModulsController.php:880`
  - `assign_default_group`: `ModulsController.php:881`
  - `Group::add_tenant`: `AdminGroupsController.php:332`
  - `Menu::tenants_name`: `MenuHelper.php:152`; `Menu::tenants`: `UserHelper.php:96`
  - `allowedGroups/allowedTenants`: `QlikHelper.php:91,109`, `ChatAIHelper.php:98,123`

## Batch 0 — test di caratterizzazione (primo passo, prima di toccare i modelli)

Da scrivere come test HTTP/feature in `tests/Feature/` con il trait
`SeedsCmsData` già usato dai test esistenti, per **rischio decrescente**
(input → atteso). Se una riga qui sotto risulta già coperta, citare il test e
saltarla.

1. `Menu::tenants_name()` via `MenuHelper::menu_to_html` (`MenuHelper.php:152`):
   menu con 2 tenant → `"A, B"`; nessun tenant → `""`; un tenant soft-deleted
   in coda → fissare l'output attuale (comprese le virgole).
2. `UserHelper::can_menu` (`UserHelper.php:96`, usa `Menu::tenants()`): menu
   assegnato al tenant X → visibile all'utente del tenant X, non a quello del
   tenant Y; includere un tenant soft-deleted nel pivot e fissare l'esito.
3. `Menu::assign_default_group()` + `assign_default_tenant()` via
   `ModulsController::postStep1` (`ModulsController.php:880-881`): dopo la
   creazione di un modulo esistono la riga in `menu_tenants` (tenant
   dell'attore) e in `menu_groups` (gruppo primario dell'attore).
   (`MenusCrudTest.php:142` copre solo la parte tenant via `MenusController`.)
4. `QlikHelper::can_see_item` (`:91,:109`) e `ChatAIHelper::can_see_item`
   (`:98,:123`): item consentito al tenant/gruppo dell'utente → visibile; altrimenti no.
5. `User::tenant()` / `User::primary_group()`: caso valido → modello atteso;
   caso orfano → fissare il comportamento attuale (null / errore), senza correggerlo.
6. `QlikItem::enablePublicAccess()/disablePublicAccess()`: token e
   `proxy_enabled_at` valorizzati/azzerati, ritorno `true` la prima volta e
   `false` se già nello stato richiesto.

Già coperti (non riscrivere): `Group::add_tenant`
(`CrossModuleRelationsTest.php:277-292`, `GroupsCrudTest.php:62`),
`User::isSuperAdmin/isTenantAdmin` con privilegio orfano
(`CrossModuleRelationsTest.php:184-205`).

I test non vengono lanciati da `ch-senior-dev`: li lancia la persona.

## Batch 1 — igiene sul posto

Per ogni modello di `app/*.php` **tranne** `Clienti`, `Ordini`, `Righe`,
`DynamicTable`:

- Rimuovere `use` inutili o ridondanti (`use \App\Group;`/`\App\Tenant;` in
  `User.php` nello stesso namespace; `use QlikHelper;` in `QlikItem`/
  `ChatAIConf` se non referenziato; `$hidden = []` vuoti; `//dd(...)` commentati).
- `$casts` come proprietà → metodo `casts(): array` **solo** dove l'array resta
  identico. Rimuovere `'deleted_at' => 'datetime'` dove c'è `SoftDeletes`
  (il trait lo già aggiunge). **Non aggiungere cast nuovi** a colonne lette in
  giro (`is_superadmin == 1`, ecc.): cambierebbero i tipi restituiti.
- Mass assignment: **non aggiungere** `$guarded = []`. Per i modelli senza
  `$fillable`/`$guarded` cercare `::create(`, `->fill(`, `->update([`,
  `firstOrCreate`, `updateOrCreate` sul modello: se non ci sono usi, lasciare
  com'è e annotarlo; se ci sono, aggiungere `$fillable` con le sole colonne
  realmente scritte in quei punti.
- Tipi di ritorno sui metodi esistenti dove banali (`allowedGroups(): array`,
  `tenants(): array`, `tenants_name(): string`, `isActive(): bool` già c'è).
- Ordine dei membri PSR-12/Laravel (trait `use` in cima, poi proprietà, poi
  metodi); docblock `@var array` obsoleti.
- Pint `--test` solo sui file toccati.

Non cambiare: nomi di metodi/proprietà pubbliche, `$table`, `$timestamps`,
namespace, nomi di classe.

## Batch 2 — relazioni Eloquent additive

Aggiungere relazioni vere **con nomi nuovi** (per non collidere con colonne e
con i metodi esistenti), lasciando i metodi attuali come wrapper:

| Modello | Nuova relazione | Metodo esistente (resta, stessa firma) |
|---|---|---|
| `Menu` | `assignedTenants(): BelongsToMany` (pivot `menu_tenants`, `menu_id`/`tenant_id`), `assignedGroups()` (pivot `menu_groups`) | `tenants()` → array di id **dal pivot, inclusi i tenant soft-deleted** (usare `withTrashed()` o interrogare il pivot: la relazione di default li esclude); `tenants_name()` → stessa stringa di oggi, senza N+1 |
| `Group` | `assignedTenants()` (pivot `group_tenants`) | `add_tenant()` invariato nel batch 2 |
| `User` | `tenantRecord(): BelongsTo`, `primaryGroupRecord(): BelongsTo`, `memberGroups()` (pivot `users_groups`; nome scelto al posto di `groups()` per escludere collisioni con codice non indicizzato; filtra `deleted_at` del pivot perché `UsersGroup` ha SoftDeletes) | `tenant()` e `primary_group()` invariati |
| `QlikItem` | `allowedGroupRecords()`, `allowedTenantRecords()` (chiave `item_id` su `ItemsAllowed`/`TenantsAllowed`) | `allowedGroups()`/`allowedTenants()` → array di id come oggi; risolvono i `//TODO belongsTo` |
| `ChatAIConf` | **FATTO nel piano 318** (tabelle dedicate, `allowedGroupRecords()/allowedTenantRecords()`): nel batch 2 non si tocca. Storico del sospeso (esito del batch 0): `allowedGroups()/allowedTenants()` filtrano su `chataiconf_id`, colonna che nessuna migrazione crea; non toccare finché la persona non decide. Verificato 2026-10-29: la colonna manca sia in `customerhive` sia in `customerhive_testing`; il controller ChatAI usa `item_id` (stesso spazio di id dei `qlik_items`). Fuori dal piano 317: richiede una decisione/migrazione separata | invariati |

Verificare per ogni wrapper che il risultato sia **identico** (ordine degli id
compreso: oggi è l'ordine naturale del pivot) rispetto ai test del batch 0. Se
l'ordine o i soft-deleted non si possono riprodurre con la relazione, il
wrapper resta com'è e la relazione nuova viene solo aggiunta.

## Batch 3 — logica fuori dai modelli + local scope

- **Prima Action del repo**: `app/Actions/` (nuova cartella). Pilota:
  `AssignDefaultMenuTenant` / `AssignDefaultMenuGroup` e `AddTenantToGroup`.
  L'Action riceve gli id come parametri (niente `UserHelper::current_user_*`
  dentro l'Action); i punti di chiamata nei controller **di sistema** elencati
  sopra passano `UserHelper::current_user_tenant()` / `..._primary_group()`.
  `Menu::assign_default_tenant/group()` e `Group::add_tenant()` restano come
  wrapper sottili che chiamano l'Action con gli stessi valori di oggi (compat
  per i controller dei clienti). Registrare il pattern in
  `docs/architettura/pattern-nel-repo.md` a fine batch.
- **Local scope** (`scopeX`) solo dove la stessa condizione è ripetuta in ≥2
  punti identici: elencarli con `tokensave_search` all'inizio del batch e
  proporli alla persona prima di implementarli. Mai global scope.

## Batch 2b — due fix con cambio di comportamento visibile

Fuori dai batch behavior-preserving: **un commit e un documento per fix**, e
ognuno dichiara in "Rischi e note" che cambia un comportamento visibile. Si fa
dopo il batch 2, perché il test del batch 0 che fissa il comportamento attuale
va rovesciato deliberatamente (non "adattato" per far passare il codice).

1. **`Menu::tenants_name()`**: costruire l'elenco dei nomi presenti e unirlo
   con `implode(', ', ...)`. Cambia solo il caso "ultimo tenant mancante o
   soft-deleted": sparisce la `, ` finale. Tutti gli altri output restano
   identici (lo dimostra il test del batch 0).
2. **`UserHelper::current_user_primary_group_name()`**: restituire un valore
   vuoto quando manca un gruppo primario valido invece di sollevare l'errore
   (`?->name`). Il valore di ripiego (`''` o `null`) lo sceglie `ch-senior-dev`
   leggendo i 3 punti di chiamata, in modo che il form renda come "nessun
   default"; va riportato alla persona. Cambia solo il caso oggi in errore.
   Non modificare `User::primary_group()` né `current_user_primary_group()`.

Per ogni fix: test del batch 0 rovesciato + un test per il caso invariato,
`php -l`, Pint `--test` sul file toccato.

## Impatto sui clienti

Batch 0, 1, 2 e 3: nessun cambiamento visibile atteso. Batch 2b: due cambi
visibili, entrambi solo su casi oggi sbagliati (virgola finale; errore con
gruppo primario assente). Nessuna migrazione. Nessun cambio di
namespace o di firma pubblica: i controller custom dei clienti continuano a
funzionare. Se un wrapper non può garantire lo stesso risultato, non si
sostituisce (vedi batch 2).

## Rischio

Basso per il batch 1; medio per i batch 2-3 (wrapper che devono replicare
soft-delete e ordine; Action chiamata da `ModulsController::postStep1`, flusso
critico di creazione modulo). Mitigazione: batch 0 prima di tutto.

## Rollback

Ogni batch è un insieme di commit separato (deciso dalla persona): `git revert`
del batch. Nessuna modifica a DB/dati/config.

## Chiusura di ogni batch

`ch-reviewer` (include i18n: qui probabilmente "n/a") → correzioni →
`ch-doc-writer` (numerazione da `docs/refactoring/README.md`, uno alla volta) →
post-mortem in `docs/architettura/lezioni.md`.
