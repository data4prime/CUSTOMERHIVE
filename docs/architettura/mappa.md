# Mappa dell'architettura di CustomerHive

> Letta da `/architect` e `ch-architect-analyst` come **primo passo** di ogni
> analisi. Breve e curata: se un fatto qui è diventato falso, **correggilo**.
> Generata il 2026-10-29 da tokensave (indice 652 file PHP). Le metriche sono
> una fotografia: rilanciale (`tokensave_god_class`, `tokensave_largest`,
> `tokensave_test_risk`) prima di basarci una decisione.

## 1. Struttura (dopo l'uscita di `packages/crocodicstudio/`)

| Cartella | Cosa contiene |
|---|---|
| `app/Http/Controllers/System/` | 28 controller del "motore" (ex CRUDBooster). `CBController` è la base di quasi tutti. |
| `app/Http/Controllers/` (fuori da `System/`) | Controller/moduli **per cliente**: gitignored, non visibili qui. |
| `app/Helpers/` | 25 classi statiche. `CRUDBooster` (88 metodi), `MfaHelper`, `UserHelper`, `ModuleGenerator*`, `TenantHelper`, `MailSettings`, `LicenseHelper`, `QlikHelper`, `functions.php` (funzioni globali). |
| `app/Services/` | Solo l'integrazione Qlik (`QlikSync/*`: driver On-Prem/SaaS + factory) e `ConnectorService`. |
| `app/Jobs/` | 3 job: mail ritardata, mail da template, sync Qlik. |
| `app/Console/Commands/` | Istanza Docker (`InstanceBootstrap`, `InstancePasswordLink`), licenza (`GetLicense`), scadenza utenti, pulizia MFA. |
| `app/Dashboards/` | Dataset e builder dei grafici/widget. |
| `app/*.php` (root di `app/`) | Modelli Eloquent (24): `User`, `Tenant`, `Group`, `Menu*`, `Modules`, `Qlik*`… |
| `app/Support/legacy_crudbooster_aliases.php` | `class_alias()` che tengono vivi i vecchi namespace. **API congelata.** |
| `routes/` | `web.php`, `crudbooster.php` (rotte del motore), `api.php`, `console.php`. |

**Esistono da poco**: `app/Actions/` (3 Action, piano 317 batch 3, doc 322),
relazioni Eloquent additive e local scope sui modelli di sistema.
**Non esistono** (oggi): Form Request, Policy, Event/Listener di
dominio, Resource, Observer. Validazione, autorizzazione ed effetti collaterali
vivono dentro i controller e gli helper statici.

## 2. Il motore CRUD (`CBController`)

Ogni modulo (sistema o cliente) è una sottoclasse di `CBController` che
dichiara la propria configurazione in `cbInit()` riempiendo proprietà pubbliche:
`$this->table`, `$this->col` (colonne lista), `$this->form` (campi form),
`$this->addaction`, `$this->button_*`, `$this->hide_form`, `$this->form_layout`…

Flusso: `cbLoader()` chiama `cbInit()`, normalizza (`ckeditor`→`tinymce`),
copia le proprietà in `$this->data` e le condivide alle viste con
`view()->share()`. Poi i metodi `getIndex`, `getAdd/postAddSave`,
`getEdit/postEditSave`, `getDelete`, `getDetail`, import/export, azioni bulk.
I clienti personalizzano con **hook** sovrascrivibili: `hook_before_add(&$arr)`,
`hook_after_add`, `hook_before_edit`, `hook_query_index`, `hook_row_index`…

Conseguenze per qualunque refactoring:
- Le **proprietà e gli hook sono l'API pubblica verso i controller dei
  clienti**. Rinominarli o cambiarne la firma rompe moduli che non vediamo.
- `$this->form` non va mai ridotto: i campi mancanti vengono azzerati al save.
  Per nascondere: `hide_form`.
- Il codice del motore non ha DI: usa la facade statica `CRUDBooster::`, `CB::`,
  `Session`, `Request`, funzioni globali (`g()`, `add_log_ch()`).
- **Ogni metodo pubblico di una sottoclasse di `CBController` diventa una
  rotta** (`routeController`, `routes/crudbooster.php`, ~600 rotte di System):
  un helper nuovo, anche da trait, deve essere `protected` o `private`.
  `CBBackend` controlla solo il login, **non** il permesso sul modulo.
- Le rotte custom di `web.php` (membri di gruppi/tenant, `module_generator/enable`,
  mashup) stanno sotto `CBBackend` dal doc 324; restano pubbliche di proposito
  solo login/forgot/MFA, `health`, `qi/{proxy_token}` e `uploads/*`.
- `AdminController`, `SettingsController`, `NotificationsController`,
  `LogsController`, `DashboardLayoutController` estendono `CBController`
  (gli standalone veri sono solo 4); `EmailTemplatesController`,
  `FileController`, `QlikItemsController` estendono tramite gli alias legacy.

## 3. Autenticazione, multi-tenant, licenza

- **Auth in migrazione "strangler fig"**: la sessione custom
  (`Session::put('admin_id', …)`) e i guard Laravel (`Auth::login()`) si
  scrivono **in parallelo**; i punti di lettura passano ai guard uno alla
  volta. Middleware di riferimento: `CBBackend` (area admin, gestisce anche
  l'embed in iframe), `CBAuthAPI` (API). Dettagli: docs 001 e 002.
- **MFA** (TOTP + OTP email, opt-in) in `MfaHelper`; policy password in
  `PasswordPolicy`.
- **Multi-tenant**: tabella `tenants` + pivot (`menu_tenants`, `group_tenants`,
  `module_tenants`, `tenants_allowed`). L'utente corrente risolve il tenant con
  `UserHelper::current_user_tenant()`; la FK convenzionale è `tenant_id`
  (`CRUDBooster::getForeignKey`). **Non esiste uno scope globale Eloquent**: il
  filtro per tenant è applicato a mano nelle query/hook. Ogni nuovo punto di
  accesso ai dati va verificato su questo (è il rischio di sicurezza n.1).
- **Licenza**: `LicenseHelper` + `EnforceModuleLicense` (middleware) +
  comando `GetLicense`. Attiva anche in locale. Richiede `APP_PATH` nel `.env`.
- **CSRF disabilitato globalmente** (scelta nota, vedi
  `.claude/rules/auth-csrf-licenza.md`).
- **Mail**: SMTP solo dai Settings, via `App\Helpers\MailSettings`; invio via
  `CRUDBooster::sendEmail()` (immediato, o con `send_at` accodato come
  `SendDelayedEmailJob` su connessione `mail`) e `SendTemplateEmailJob::enqueue()`
  (connessione `queue.mail_connection`, default `sync` = come prima, senza worker).

- **Autorizzazioni per gruppo/tenant degli oggetti a licenza**: Qlik usa
  `items_allowed` / `tenants_allowed` (chiave `item_id`); ChatAI usa tabelle
  proprie, `chatai_groups_allowed` / `chatai_tenants_allowed` (chiave
  `chatai_conf_id`, ADR A12). Le route custom `admin/qlik_items/*` e
  `admin/chat_ai/*` (`routes/web.php`) passano da `CBBackend` +
  `EnforceModuleLicense`. Il modulo `ChatAI` non esiste ancora sul license
  server: `isActiveChatAI()` è falso in produzione (docs/refactoring/300).

## 4. Ambiente e verifica

- Locale **sempre Docker** (`customerhive_app`, `customerhive_db`). Comandi con
  `docker compose exec app …`.
- Test su **MySQL vero** (`customerhive_testing`). Suite in `tests/Feature/`
  (37 file, per lo più test HTTP di CRUD e login) + 2 unit. Si lanciano **solo su
  richiesta** (`/refactor-verify`).
- ⚠️ `tokensave_test_risk` riporta copertura 0%: i test sono HTTP e non
  chiamano i metodi direttamente, quindi il grafo non li associa. Usarlo per
  l'**ordine di rischio**, non come percentuale reale. Per la copertura vera
  controllare i nomi in `tests/Feature/` e il catalogo `docs/test-coverage.md`.

## 5. Punti caldi (fotografia 2026-10-29)

**Classi più grandi** (membri): `CBController` 98 · `CRUDBooster` 88 ·
`ModulsController` 59 · `StatisticBuilderController` 37 · `AdminCmsUsersController` 36 ·
`DashboardDatasetRegistry` 34 · `MfaHelper` 33.

**Metodi più lunghi** (righe): `ApiController::execute_api` 865 ·
`MenusController::cbInit` 599 · `CRUDBooster::generateController` 582 ·
`QlikConfController::cbInit` 577 · `CBController::getIndex` 538 ·
`ModulsController::save_table` 386.
I `cbInit()` lunghi sono per lo più **configurazione dichiarativa** (array di
colonne/campi), non logica: non vanno "spezzati" per principio.

**Più dipendenti da** (fan-in file): `functions.php` 44 · `DateFormat` 28 ·
`CBController` 19 · `CRUDBooster` 18 · `ExportData` 15.

**Rischio senza test** (metodo, complessità, fan-in): `CRUDBooster::redirect`
(143 chiamanti) · `CBController::getIndex` (cc 132) · `ApiController::execute_api`
(cc 198) · `ModulsController::save_table` (cc 101) · `CBController::postDoImportChunk`,
`postAddSave`, `postEditSave`, `input_assignment`.
I test esistenti coprono i flussi CRUD a livello HTTP: un test di
caratterizzazione per questi metodi si scrive come test HTTP, non unit.

**Sicurezza aperta, scelta consapevole (2026-10-29, doc 324)**: gli endpoint
generici di `CBController` — `postDataQuery` (`DB::select(Request::get('query'))`),
`getUpdateSingle` (update con tabella/colonna/valore da input, via GET),
`whereRaw` da input in `getModalData`/`getDataTable` — sono raggiungibili da
qualunque utente loggato su ogni modulo. Lasciati com'è su decisione della
persona; non rimuoverli senza prima cercarne l'uso nel JS dei clienti. Aperti
anche S2 (controllo tenant su `add_member`/`remove_member`/`add_item`/`add_group`)
e la pubblicità di `uploads/*` (B1b). Metriche cc: `complexity` dà
`execute_api` 146 e `getIndex` 89, `test_risk` 198 e 132 (metriche diverse).

## 6. Dove è facile (e dove no) introdurre struttura nuova

Facile, a basso rischio: nuova logica in `app/Services/` o `app/Actions/`
**accanto** al codice esistente (come già fatto per Qlik); Form Request e
Policy per i soli controller **di sistema** non ereditati dai clienti; Job per
lavoro lento.
Difficile / alto rischio: qualunque cosa in `CBController`, `CRUDBooster::`,
`ExportData`, `execute_api` — superfici condivise con i moduli dei clienti.
