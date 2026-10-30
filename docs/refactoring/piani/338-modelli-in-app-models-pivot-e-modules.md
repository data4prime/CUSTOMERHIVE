# Piano 338 — Modelli in `app/Models/`, batch B4: 8 pivot/autorizzazioni e `Modules`

Stato: **bozza, in attesa di approvazione**. Branch: `refactor/models`.
Autore: `/architect`, 2026-10-30. Destinatario: l'implementatore (non vede la
conversazione: questo file è autosufficiente). Origine: dopo B3 (piano 337) la persona ha scelto di
spostare in **un unico batch** le tabelle pivot/autorizzazioni più `Modules`, lasciando fuori
`Menu`, `ChatAIConf`, il cluster Qlik, `Log`/`AccessLog` e `Clienti`/`Ordini`/`Righe` (non di sistema).
Come per il 337 è una **deroga consapevole** alla regola "un modello per batch" (piano 336): sono
modelli da 12-17 righe, senza metodi né relazioni, e si verificano bene insieme.

## Obiettivo

Spostare in `App\Models\` i 9 modelli sotto, con alias di compatibilità per i controller custom dei
clienti, **senza cambiare comportamento**: nessun metodo, tabella, trait o scope cambia.

| Modello | Tabella | Note |
|---|---|---|
| `GroupTenants` | `group_tenants` | senza timestamp |
| `MenuTenants` | `menu_tenants` | senza timestamp |
| `MenuGroups` | `menu_groups` | senza timestamp |
| `ModuleTenants` | `module_tenants` | senza timestamp |
| `TenantsAllowed` | `tenants_allowed` | senza timestamp (Qlik) |
| `ItemsAllowed` | `items_allowed` | senza timestamp (Qlik) |
| `ChatAIGroupAllowed` | `chatai_groups_allowed` | senza timestamp, docblock (piano 318) |
| `ChatAITenantAllowed` | `chatai_tenants_allowed` | senza timestamp, docblock (piano 318) |
| `Modules` | `cms_moduls` | `SoftDeletes` |

## Fatti verificati (2026-10-30, ricerca a testo su `app/ routes/ tests/ database/ config/ resources/ storage/app/`)

- **Nessun modello ha relazioni, scope o metodi** (solo `$table`, `$timestamps = false`; `Modules` ha
  `SoftDeletes`). Il batch è di puro namespace.
- **Riferimenti qualificati `App\<Nome>` (da aggiornare):**
  - `GroupTenants`: `Actions/Groups/AddTenantToGroup.php:5`, `Helpers/ModuleHelper.php:7` (8 usi),
    `Controllers/System/AdminGroupsController.php:14` (`use \App\GroupTenants`),
    `Controllers/System/AdminTenantsController.php:13` (`use \App\GroupTenants`), **Blade**
    `resources/views/crudbooster/default/type_components/group_members_datamodal/browser.blade.php:5`
    (`\App\GroupTenants::where(...)` inline).
  - `MenuTenants`: `Actions/Menus/AssignDefaultMenuTenant.php:6`, `Controllers/System/MenusController.php:21`
    (`use \App\MenuTenants`, **import mai usato** nel file).
  - `MenuGroups`: `Actions/Menus/AssignDefaultMenuGroup.php:6`.
  - `ModuleTenants`: `Helpers/ModuleHelper.php:5`, `Helpers/TenantHelper.php:6`.
  - `TenantsAllowed`: `Controllers/System/AdminQlikItemsController.php:16`, `QlikAppController.php:17`,
    `AdminModuleHelperController.php:19` (**import mai usato**).
  - `ItemsAllowed`: `AdminQlikItemsController.php:15`, `QlikAppController.php:15`,
    `AdminModuleHelperController.php:18` (**import mai usato**), **`AdminGroupsController.php:590` e
    `:599` usano `\App\ItemsAllowed` inline, senza `use`**.
  - `ChatAIGroupAllowed`/`ChatAITenantAllowed`: `Actions/ChatAI/{Allow,Revoke}{Group,Tenant}ForChatAI.php`,
    `Controllers/System/AdminChatAIController.php:22-23`.
  - `Modules`: `Helpers/TenantHelper.php:5` (`use \App\Modules`, **import mai usato**),
    `Controllers/System/AdminController.php:15`, `ModulsController.php:26`.
  - Nessun riferimento in `tests/` (i test passano da HTTP/DB, non dal nome della classe), `config/`,
    `routes/`, `database/`, `phpstan*.neon`, né nello scaffolding che `CRUDBooster::generateController`
    scrive nei controller generati.
- **Dipendenze silenziose nello stesso namespace** (il rischio principale, come nel 337): tre modelli
  ancora in `App\` usano questi modelli **senza `use`**:
  - `Menu.php:52` → `MenuTenants` (dentro `tenantsIds()`-like, `MenuTenants::where('menu_id',…)`);
  - `QlikItem.php:43` → `ItemsAllowed`, `QlikItem.php:56` → `TenantsAllowed`;
  - `ChatAIConf.php:25` → `ChatAIGroupAllowed`, `ChatAIConf.php:38` → `ChatAITenantAllowed`.
  Dopo lo spostamento l'alias li farebbe funzionare per caso: vanno corretti con `use` espliciti
  (`use App\Models\MenuTenants;` ecc.), da **togliere** quando `Menu`/`QlikItem`/`ChatAIConf` si
  spostano a loro volta in `App\Models`.
- **Colonne polimorfiche**: nel DB locale (`customerhive` e `customerhive_testing`, verificato con
  `information_schema`) l'unica colonna `*able_type` è `personal_access_tokens.tokenable_type`
  (già gestita in B1, riguarda `User`). **Nessuna migration di dati.** Da riconfermare sul DB di un
  cliente reale prima di aggiornarlo (query nel piano 337).
- `phpstan-baseline.neon` non cita nessuno di questi nomi.
- Test esistenti che toccano queste tabelle (regressione): `ActionsTest`, `ChatAIActionsTest`,
  `ChatAIAuthorizationTest`, `ChatAIPrimaryAndRedirectsTest`, `GroupsCrudTest`, `MenusCrudTest`,
  `MenuTenantsCharacterizationTest`, `ModelRelationsTest`, `QlikItemVisibilityTest`,
  `ModuleGeneratorCrudTest`, `ModuleStep1MenuDefaultsTest`, `PublicSystemRoutesSaveEnableTest`,
  `CrossModuleRelationsTest`.

## Perimetro

Dentro:
1. Spostare i 9 file in `app/Models/`: solo `namespace App\Models;`; `Modules` mantiene
   `use ...\SoftDeletes;`. Nessun altro cambio (nemmeno la formattazione, salvo Pint sui file toccati).
2. 9 `class_alias(\App\Models\X::class, 'App\X')` nella sezione "Modelli spostati in App\Models" di
   `app/Support/legacy_crudbooster_aliases.php`.
3. Aggiornare ogni riferimento elencato sopra al nuovo nome: `use`, `\App\X` inline
   (`AdminGroupsController` righe 590/599), la Blade `group_members_datamodal/browser.blade.php`.
   Per i **tre import mai usati** (`MenusController` `MenuTenants`, `AdminModuleHelperController`
   `ItemsAllowed`/`TenantsAllowed`, `TenantHelper` `Modules`): **rimuoverli**, non migrarli (un import
   morto non va aggiornato; l'alias comunque lo terrebbe vivo, quindi rimuoverlo non cambia
   comportamento). Se per qualunque motivo risultasse usato, aggiornarlo invece di rimuoverlo.
4. Aggiungere i `use App\Models\…` espliciti in `Menu` (`MenuTenants`), `QlikItem`
   (`ItemsAllowed`, `TenantsAllowed`), `ChatAIConf` (`ChatAIGroupAllowed`, `ChatAITenantAllowed`).

Fuori (non toccare): `Menu`, `ChatAIConf`, `QlikItem`, `QlikSyncRun*`, `Log`, `AccessLog`,
`Clienti`/`Ordini`/`Righe`, `DynamicTable`/`DynamicColumn`; metodi, relazioni e tabelle; la rinomina
al singolare (`GroupTenants` → `GroupTenant`, ecc.); `CBController`/`CRUDBooster::`; controller custom dei
clienti; documentazione storica in `docs/refactoring/`.

## Pattern e perché

Spostamento con alias, ADR A17, come B1/B3. Niente migration (nessun nome di classe salvato nel DB per
questi modelli). L'alias tiene vivi `use App\GroupTenants;` ecc. nei controller dei clienti finché
ogni cliente non è migrato.

## Passi

1. **Test prima** (sezione sotto): scriverli **ed eseguirli sul codice attuale** (lezione 2026-10-30).
   I test di alias sono rossi fino al passo 2 (dichiararlo nel docblock e **pulirlo al passo 2**).
2. Spostare i file in quest'ordine, con `php -l` dopo ciascun gruppo: (a) pivot Gruppi/Menu/Moduli
   (`GroupTenants`, `MenuTenants`, `MenuGroups`, `ModuleTenants`), (b) Qlik/ChatAI
   (`TenantsAllowed`, `ItemsAllowed`, `ChatAIGroupAllowed`, `ChatAITenantAllowed`), (c) `Modules`.
   Per ogni gruppo: spostare il file, aggiungere l'alias, aggiornare i riferimenti di quei modelli,
   aggiungere i `use` espliciti nei tre modelli rimasti in `App\`. **Per i namespace usare Edit, mai
   `sed` inline con backslash** (lezioni 2026-10-30).
3. `composer dump-autoload` nel container (serve dopo aver spostato file di classe).
4. **Ricerca finale a testo fisso, indipendente dall'indice** (PowerShell `Select-String`, anche
   `resources/views/`): `App\GroupTenants`, `App\MenuTenants`, `App\MenuGroups`, `App\ModuleTenants`,
   `App\TenantsAllowed`, `App\ItemsAllowed`, `App\ChatAIGroupAllowed`, `App\ChatAITenantAllowed`,
   `App\Modules`. Devono restare solo l'alias, i commenti storici in `docs/` e i test di alias. Attenzione
   al prefisso: `App\ModuleTenants` non deve far confondere `App\Modules` (filtrare con `\b`).
   Cercare anche gli usi **senza `use`** (`Nome::`, `new Nome`, `Nome::class`) nei file di `App\`.
5. Pint `--test` sui soli file toccati; Larastan (`app/Helpers`, `Controllers/System`) anche da cache
   vuota (`phpstan clear-result-cache`).
6. Fine batch: doc in `docs/refactoring/` (prossimo numero libero al momento, README), aggiornare
   `mappa.md` (modelli ancora in root: ora `Menu`, `Qlik*`, `ChatAIConf`, `Log`, `AccessLog`,
   `Clienti`/`Ordini`/`Righe`, `Dynamic*`) e `pattern-nel-repo.md`, post-mortem in `lezioni.md`.

## Test di caratterizzazione

Nuovo file `tests/Feature/PivotModelMoveTest.php` (modello: `TenantGroupModelMoveTest`):

1. **Alias** (uno per modello, data provider con `#[DataProvider]`): `class_exists('App\X')`,
   `new \App\X instanceof \App\Models\X`, i due `::class` coincidono. Rosso fino al passo 2.
2. **Tabella e comportamento invariati** (verdi anche prima): `getTable()` di ognuno uguale alla
   tabella in elenco; `$timestamps === false` per gli 8 pivot; `Modules` soft-delete attivo
   (riga con `deleted_at` valorizzato non compare in `Modules::find`, e compare con `withTrashed()`).
3. **Modelli rimasti in `App\` (il rischio silenzioso)**: con dati veri, `Menu` (tenant del menu),
   `QlikItem` (`allowedGroups`/`allowedTenants` o i loro wrapper) e `ChatAIConf`
   (`allowedGroups`/`allowedTenants` o wrapper) restituiscono ancora le righe giuste. Verificare prima
   quali dei casi sono già coperti da `ModelRelationsTest`, `MenuTenantsCharacterizationTest`,
   `QlikItemVisibilityTest`, `ChatAIAuthorizationTest` e **aggiungere solo i mancanti**. Per ogni
   negativo ("un gruppo non assegnato non compare") il positivo con lo stesso setup (lezione 2026-10-29).
4. **Percorsi che toccano le pivot da codice spostato** (se non già coperti dai test elencati sopra):
   `ModuleHelper` visibilità per gruppo/tenant (`GroupTenants`), `ModuleHelper::saveEnable`-like
   (`ModuleTenants`, con `truncate`: tearDown che ripulisce, lezione doc 324) e la Blade
   `group_members_datamodal/browser.blade.php` (una richiesta che la renda; se già coperta, dichiararlo).

Se un test fa DDL o `TRUNCATE`: tearDown che ripulisce. I test non vengono lanciati da `/architect`.

## Vincoli

- Behavior-preserving; nessun testo UI nuovo (niente i18n); nessun metodo/relazione cambia.
- Vecchi nomi funzionanti finché ogni cliente non è migrato.
- Niente commit/push; nessuna suite completa in autonomia (`/refactor-verify` su richiesta della persona).
- Non aggiungere type hint o altre "igiene" mentre si sposta (lezione doc 331).
- Il lavoro dei batch 336/337 è ancora non committato sul working tree: dirlo al reviewer, se lanciato.

## Impatto sui clienti

Nessuno atteso, a condizione che l'update rigeneri l'autoload di Composer (già richiesto per ogni classe
nuova). Il codice custom che usa i vecchi nomi continua a funzionare via alias. **Cambia comportamento
visibile? No.**

## Rischio

Basso-medio. Modifica di solo namespace su modelli minimali, ma su dati di isolamento tra tenant e di
visibilità dei moduli. Punti di attenzione: (1) i tre modelli rimasti in `App\` che li usano senza `use`
(test 3); (2) `AdminGroupsController:590/599` con `\App\ItemsAllowed` inline e la Blade `browser.blade.php`
con `\App\GroupTenants` inline: non hanno un `use` da cercare; (3) `ModuleHelper` concentra 8 usi di
`GroupTenants` nei controlli di visibilità per tenant: un `use` sbagliato romperebbe la lista dei
moduli; (4) con `--classmap-authoritative` in produzione serve il dump dopo l'update.

## Rollback

Rimettere i 9 file in `app/`, togliere i 9 alias, ripristinare riferimenti e `use` (il diff li elenca).
Nessun dato toccato, nessuna migration.

## Decisioni aperte (per la persona)

- Nessuna di logica. Una scelta di dettaglio: i quattro **import mai usati** (`MenusController`,
  `AdminModuleHelperController` ×2, `TenantHelper`) sono **rimossi** invece di essere migrati. Cambia
  solo il codice morto, non il comportamento; ditemi se preferite migrarli e basta.
