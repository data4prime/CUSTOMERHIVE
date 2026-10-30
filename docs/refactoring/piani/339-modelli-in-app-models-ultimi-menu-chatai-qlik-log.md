# Piano 339 — Modelli in `app/Models/`, batch B5: gli ultimi 7 (`Menu`, `ChatAIConf`, `QlikItem`, `QlikSyncRun`, `QlikSyncRunRecord`, `Log`, `AccessLog`)

Stato: **bozza, in attesa di approvazione**. Branch: `refactor/models`.
Autore: `/architect`, 2026-10-30. Destinatario: l'implementatore (non vede la conversazione: questo
file è autosufficiente). Origine: dopo B4 (piano 338) la persona ha chiesto di spostare "il restante dei
models". Restano in `app/` solo `Clienti`/`Ordini`/`Righe` (**esclusi per scelta della persona**, non di
sistema) e `DynamicTable`/`DynamicColumn` (non sono modelli Eloquent: classi vuote, fuori perimetro).
Come per 337/338 è una **deroga consapevole** alla regola "un modello per batch"; l'ordine interno dei passi
isola i rischi (vedi "Passi").

## Obiettivo

Spostare i 7 modelli in `App\Models\`, con alias di compatibilità per i controller custom dei clienti,
**senza cambiare comportamento**. Dopo questo batch `app/` non contiene più modelli di sistema.

| Modello | Tabella | Note |
|---|---|---|
| `Menu` | `cms_menus` | scope `activeDashboard`/`qlikItemContent`, relazioni `assignedTenants`/`assignedGroups`, wrapper |
| `ChatAIConf` | `chatai_confs` | `SoftDeletes`, relazioni verso `Group`/`Tenant` |
| `QlikItem` | `qlik_items` | `SoftDeletes`, relazioni, `use QlikHelper;` (alias globale di facade/helper) |
| `QlikSyncRun` | `qlik_sync_runs` | costanti `TYPE_*`/`STATUS_*`, `activeStatuses()`, `records()` |
| `QlikSyncRunRecord` | `qlik_sync_run_records` | costanti `ACTION_*` |
| `Log` | `log` | senza timestamp; usato solo da `new App\Log` in `Helpers/functions.php:120` |
| `AccessLog` | `cms_logs` | senza timestamp; un solo uso (`AdminController`) |

## Fatti verificati (2026-10-30, ricerca a testo su `app/ routes/ tests/ database/ config/ resources/ storage/app/ bootstrap/`)

- **`Menu` è il più esposto**: 13 `use`, più `\App\Menu` inline in `Helpers/MenuHelper.php:152`,
  `Helpers/UserHelper.php:92`, **`Controllers/System/CBController.php` righe 789 (due volte), 1448, 1659
  (commentata), 1660, 1934**. **`app/Http/Controllers/AdminOrdiniController.php:280/307`** (controller
  per cliente, fuori da `System/`, gitignored) usa `\App\Menu::find(...)` per nome: prova concreta che i
  controller dei clienti citano questi modelli per nome, quindi l'alias è indispensabile. Anche i
  controller generati dal Module Generator potrebbero contenerlo (da cercare in `storage/app/custom-controllers/`
  se presente: oggi la ricerca non trova nulla).
- Riferimenti per modello (qualificati, da aggiornare):
  - `Menu`: `Actions/Menus/AssignDefaultMenu{Group,Tenant}.php`, `Helpers/MenuHelper.php`, `UserHelper.php`,
    `Controllers/System/{AdminChatAIController,AdminModuleHelperController,AdminQlikItemsController,CBController,MenusController,ModulsController,QlikItemsController}.php`,
    `Services/QlikSync/QlikSyncRollback.php`, **tests**: `ActionsTest`, `ModelRelationsTest`, `ModelScopesTest`,
    `PivotModelMoveTest`, `TenantGroupModelMoveTest`.
  - `ChatAIConf`: `Actions/ChatAI/{AllowGroup,AllowTenant,SetPrimary}…`, `Helpers/ChatAIHelper.php` (anche
    `\App\ChatAIConf::find` inline alla riga 83), `AdminChatAIController`, `AdminModuleHelperController`;
    tests: `ChatAIActionsTest`, `PivotModelMoveTest`, `TenantGroupModelMoveTest`.
  - `QlikItem`: `Helpers/QlikHelper.php` (anche inline riga 75), `AdminController`, `AdminQlikItemsController`,
    `QlikItemsController`, `StatisticBuilderController.php:1262` (inline), **`Providers/AppServiceProvider.php:5`**
    (`use App\QlikItem;`, **mai usato**: nel file c'è anche `use App\Observers\QlikItemObserver;` ma la
    cartella `app/Observers/` non esiste — anch'esso morto, **non toccarlo**, fuori perimetro), **Blade**
    `resources/views/chat_ai/form_body.blade.php:36` e `qlik_items/form_body.blade.php:36`
    (`App\QlikItem::find(...)` inline); tests: `ModelRelationsTest`, `QlikItemPublicAccessTest`,
    `PivotModelMoveTest`, `TenantGroupModelMoveTest`.
  - `QlikSyncRun`: `Controllers/System/{AdminQlikItemsController,QlikAppController}`, `Concerns/HandlesQlikSyncStart`,
    `Services/QlikSync/{QlikSyncService,QlikSyncRollback}`. `QlikSyncRunRecord`: `QlikSyncService`,
    `QlikSyncRollback`, e `QlikSyncRun::records()` (**senza `use`**, stesso namespace).
  - `Log`: solo `Helpers/functions.php:120` (`new App\Log;`, funzione globale). **Attenzione**: tanti file
    usano `Log::` della facade Laravel (`Illuminate\Support\Facades\Log`): sono un'altra classe e **non vanno
    toccati**. La ricerca va fatta su `App\Log` / `new Log` in file senza import della facade.
  - `AccessLog`: `AdminController.php:18` (`use \App\AccessLog;`).
- **Nessun nome di classe salvato nel DB**: l'unica colonna `*able_type`/`*model_type` (DB locale, `customerhive`
  e `customerhive_testing`) è `personal_access_tokens.tokenable_type`, che riguarda `User` (B1). **Nessuna
  migration.** I tre job (`SyncQlikRunJob` con `int $runId`, `SendDelayedEmailJob`, `SendTemplateEmailJob` con
  `array`) non contengono modelli Eloquent nel payload, quindi la serializzazione di code in volo non è
  coinvolta. (Da riconfermare sul DB di un cliente reale: query `*able_type` come nel piano 337.)
- **Dipendenze silenziose da togliere (inverso di B3/B4)**: dopo lo spostamento `Menu`, `ChatAIConf` e `QlikItem`
  sono nello stesso namespace dei modelli già spostati, quindi i loro `use App\Models\Group/Tenant/MenuTenants/
  ChatAIGroupAllowed/ChatAITenantAllowed/ItemsAllowed/TenantsAllowed` diventano **ridondanti** e vanno
  rimossi. `QlikSyncRun` → `QlikSyncRunRecord` e `Menu` → `AssignDefaultMenu*` (Actions, altro namespace:
  **`use` da mantenere**).
- **`QlikSyncRun`/`QlikSyncService` non hanno alcun test** (nessun file in `tests/` li nomina). È il punto
  debole del batch: il rischio vero è un `use` sbagliato in `QlikSyncService` (30+ usi) non visto da nessun test.
- `use QlikHelper;` in `QlikItem` (alias globale della classe `QlikHelper`): non toccare.
- `phpstan-baseline.neon` non va riverificato a mente: si controlla dal risultato di Larastan (passo 6).

## Perimetro

Dentro:
1. Spostare i 7 file in `app/Models/` (`namespace App\Models;` + `use` invariati salvo il punto 4).
2. 7 `class_alias(\App\Models\X::class, 'App\X')` nella sezione "Modelli spostati in App\Models" di
   `app/Support/legacy_crudbooster_aliases.php`.
3. Aggiornare tutti i riferimenti elencati: `use`, `\App\X` inline (compresi `CBController`,
   `MenuHelper`, `UserHelper`, `ChatAIHelper:83`, `QlikHelper:75`, `StatisticBuilderController:1262`), le
   **due Blade** `form_body`, i test esistenti che li importano (`ActionsTest`, `ModelRelationsTest`,
   `ModelScopesTest`, `ChatAIActionsTest`, `QlikItemPublicAccessTest`, `PivotModelMoveTest`,
   `TenantGroupModelMoveTest`). Non toccare i commenti storici in `docs/` né quelli in `CBController:1659`
   (riga commentata: **aggiornare comunque il testo** non serve, lasciarla com'è).
4. Rimuovere dai tre modelli i `use App\Models\…` diventati ridondanti (stesso namespace) e quello
   inutilizzato di `AppServiceProvider` (`use App\QlikItem;`, mai usato: **rimuovere**, non migrare).
5. `Helpers/functions.php:120`: `new App\Log;` → `new App\Models\Log;`.

Fuori (non toccare): metodi/relazioni/scope/costanti/tabelle dei modelli; rinomina (`Modules`→`Module`, ecc.);
`Clienti`/`Ordini`/`Righe` (esclusi dalla persona), `DynamicTable`/`DynamicColumn`;
`use App\Observers\QlikItemObserver;` in `AppServiceProvider` (import morto di una classe inesistente: da
segnalare, non correggere qui); `Log::` della facade Laravel; `CBController`/`CRUDBooster::` oltre al
cambio di namespace; controller custom dei clienti.

## Pattern e perché

Spostamento con alias, ADR A17, come B1/B3/B4. Nessuna migration (nessun nome di classe nel DB né nei
payload delle code). L'alias tiene vivi `\App\Menu`, `App\QlikItem`, ecc. nei controller dei clienti
(prova: `AdminOrdiniController`).

## Passi

1. **Test prima** (sezione sotto), scritti **ed eseguiti sul codice attuale**: gli alias rossi, il resto verde.
2. Spostare **in questo ordine**, con `php -l` dopo ciascuno, riferimenti e alias per ciascun gruppo:
   (a) `AccessLog` e `Log` (isolati, 1 uso ciascuno); (b) `QlikSyncRunRecord` poi `QlikSyncRun` (accoppiati,
   `use` nel service); (c) `ChatAIConf`; (d) `QlikItem` (Blade inline, `AppServiceProvider`); (e) `Menu`
   per ultimo (il più esposto, `CBController`). A ogni gruppo rimuovere i `use` ridondanti dei modelli
   coinvolti. **Per i namespace usare solo Edit, mai `sed` inline con backslash.**
3. `composer dump-autoload` nel container.
4. **Ricerca finale a testo fisso, indipendente dall'indice** (PowerShell `Select-String`, anche
   `resources/views/`, `storage/app/`): `App\Menu`, `App\ChatAIConf`, `App\QlikItem`, `App\QlikSyncRun`,
   `App\QlikSyncRunRecord`, `App\Log`, `App\AccessLog` con `\b` (attenzione: `App\QlikSyncRun` è prefisso di
   `App\QlikSyncRunRecord`, e `App\Log` di `App\Logs…`/`LogsController`). Devono restare solo alias e
   test di alias. Cercare anche gli usi **senza `use`** (`Nome::`, `new Nome`, `Nome::class`) nei file in
   `namespace App;` residui (`Clienti.php`, `Ordini.php`, `Righe.php`) e in `app/Models/`.
5. Pint `--test` sui soli file toccati (i legacy dei controller avranno rumore storico: non riformattare).
6. Larastan (`app/Helpers`, `Controllers/System`) **anche da cache vuota** (`phpstan clear-result-cache`).
7. Fine batch: documento in `docs/refactoring/` (prossimo numero libero), `mappa.md` (modelli: `app/` non ne
   contiene più di sistema), `pattern-nel-repo.md`, post-mortem in `lezioni.md`, ADR A17 con nota "B5: completato".

## Test di caratterizzazione

Nuovo file `tests/Feature/RemainingModelMoveTest.php` (modello: `PivotModelMoveTest`):

1. **Alias** per ciascuno dei 7 (data provider con `#[DataProvider]`): `class_exists('App\X')`, `new \App\X
   instanceof \App\Models\X`, i due `::class` coincidono. Rosso fino al passo 2.
2. **Tabella e comportamento invariati** (verdi anche prima): `getTable()` di ognuno; `Log`/`AccessLog`
   senza timestamp; `SoftDeletes` su `ChatAIConf`/`QlikItem` (positivo con riga viva, negativo con
   `deleted_at` valorizzato, `withTrashed()`); `Menu` e `QlikSyncRun*` senza soft delete.
3. **`QlikSyncRun`/`QlikSyncRunRecord` (il buco: nessun test oggi)**: `activeStatuses()` contiene gli stati
   attivi e non quelli finali; costanti `TYPE_*`/`STATUS_*`/`ACTION_*` con i valori attuali (leggerli dal
   codice, non indovinarli); `records()` restituisce solo le righe con quel `run_id` (positivo e negativo
   con due run); creare un run e un record via `QlikSyncRun::create`/`QlikSyncRunRecord::create` con i
   `$fillable` reali e rileggerli.
4. **Service**: un test minimo su `QlikSyncService` che esercita un percorso senza rete (es. l'annullo di un
   run in coda, `cancel`-like alle righe ~160-185: verificarne il nome leggendo il codice) per dimostrare
   che i `use` nuovi sono giusti. Se nessun percorso è eseguibile senza Qlik, dichiararlo nel documento
   come lacuna e **non** inventare un test su cURL.
5. **`add_log_ch()`** (`functions.php:120`): una chiamata inserisce una riga in `log` con
   `created_by`/`url` attesi (positivo); il negativo equivalente è una chiamata che non scrive (se non
   esiste, dichiararlo). Verificare prima se `ApiTokensAddSaveTest`/`UsersCrudTest` già coprono la scrittura
   su `log` o su `cms_logs` e aggiungere solo il mancante.
6. **`AccessLog`**: `AdminController` (dashboard) calcola `log_in_count` con `AccessLog::count()`; verificare
   se è osservabile via HTTP, altrimenti un test sul solo modello (`count()` dopo un insert in `cms_logs`).
7. **Le due Blade** `chat_ai/form_body` e `qlik_items/form_body` (riga 36, `App\QlikItem::find`): una
   richiesta che le renda (form di modifica di un item) risponde 200 e mostra l'item. Se già coperte,
   dichiararlo.
8. **`Menu` da `CBController`** (inline `\App\Menu::find(Request::get('m'))`, 5 punti): una richiesta a un
   modulo con `m=<id menu>` e `target_layout` impostato applica quel layout (positivo); con `m` inesistente
   resta `default` (negativo, stesso setup). Verificare prima se `MenusCrudTest`/`ModuleGeneratorCrudTest`
   lo coprono.

Regole: ogni negativo ha il suo positivo (lezione 2026-10-29); i test si scrivono **ed eseguono sul codice
vecchio** (lezione 2026-10-30); test con DDL/`TRUNCATE` con tearDown che ripulisce (doc 324); niente date
fisse lontane (lezione doc 328).

## Vincoli

- Behavior-preserving; nessun metodo/relazione/costante/scope cambia; nessun testo UI nuovo (niente i18n).
- Nessun type hint o "igiene" aggiunta mentre si sposta (lezione doc 331).
- I vecchi nomi funzionano finché ogni cliente non è migrato.
- Nessun commit/push; nessuna suite completa in autonomia (la lancia la persona con `/refactor-verify`).
- Il lavoro dei batch 336/337/338 è ancora non committato nel working tree: dirlo al reviewer, se lanciato.

## Impatto sui clienti

Nessuno atteso, a condizione che l'update rigeneri l'autoload di Composer. I controller custom che usano
`\App\Menu` (es. `AdminOrdiniController`) e gli altri vecchi nomi continuano a funzionare via alias.
**Cambia comportamento visibile? No.**

## Rischio

Medio. Solo namespace, ma: (1) `Menu` ha 5 punti inline in `CBController` e due helper: un `\App\Menu` non
aggiornato funziona per alias e non si vede; (2) `QlikSyncService` (30+ usi) non ha test: rete di sicurezza
solo `php -l`, Larastan e il test del punto 4; (3) `Log` va distinto dalla facade `Log::` (non toccarla);
(4) il rimando dei `use` ridondanti in `Menu`/`ChatAIConf`/`QlikItem` non deve eliminare quelli di altri
namespace (`App\Actions\…`, `App\Helpers\UserHelper`); (5) in produzione serve `composer dump-autoload`.

## Rollback

Rimettere i 7 file in `app/` con namespace `App`, togliere i 7 alias, ripristinare riferimenti, `use`
rimossi e `new App\Log`. Nessun dato toccato, nessuna migration.

## Decisioni aperte (per la persona)

- Una sola, già nel perimetro: `use App\QlikItem;` in `AppServiceProvider` è inutilizzato e viene
  **rimosso**; l'import morto `use App\Observers\QlikItemObserver;` (cartella inesistente) **non** si tocca.
  Se preferisci toglierlo nello stesso batch, dillo.
