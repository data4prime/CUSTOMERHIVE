# Tracciamento del refactoring

Questa cartella traccia, passo per passo, il percorso di modernizzazione del
progetto: aggiornamento di Laravel, miglioramento dell'architettura,
refactoring per stabilità/manutenibilità, semplificazione di
installazione/setup, rinnovo della UI/UX.

Obiettivo: chiunque (oggi o tra un anno) deve poter capire **cosa è cambiato,
perché, e come funzionava prima**, senza dover ricostruire il contesto da
zero leggendo i diff di git.

## Struttura

- **`glossario.md`** — termini/concetti ricorrenti nel progetto (CRUDBooster,
  tenant, licenza, guard, ecc.), definiti una volta sola e richiamati dagli
  altri documenti.
- **`_template.md`** — modello da copiare per ogni intervento di refactoring.
- **`NNN-titolo-breve.md`** — un file per ogni intervento, con la situazione
  prima/dopo. Numerati in ordine cronologico (`001-`, `002-`, ...).

## Come aggiungere un intervento

1. Copia `_template.md` in un nuovo file `NNN-titolo-breve.md` (`NNN` = numero
   progressivo successivo, `titolo-breve` in kebab-case).
2. Compila le sezioni **prima di iniziare a modificare il codice** (situazione
   "prima" e motivazione) e completa il resto a lavoro fatto.
3. Se introduci un termine nuovo o non ovvio, aggiungilo a `glossario.md`.
4. Aggiungi una riga nell'indice qui sotto.
5. Se l'intervento richiede di riattivare/rimuovere qualcosa prima del prossimo
   push, aggiungilo anche a [`../pre-push-checklist.md`](../pre-push-checklist.md).

## Indice degli interventi

| N.  | Titolo | Area | Stato | Data |
|-----|--------|------|-------|------|
| [001](001-auth-guard-additivo-fase-1.md) | Refactoring auth: guard Laravel additivo (Fase 1) | Auth | Completato | 2026-08-26 |
| [002](002-cbbackend-guard-fase-3.md) | Refactoring auth: primo file migrato al guard (Fase 3), fix logout | Auth | Completato | 2026-08-26 |
| [003](003-licensing-hardening.md) | Licensing: env configurabile, registerLicense() sicuro, opzione "ho già una licenza", riattivazione controlli | Licensing | Completato | 2026-08-26 |
| [004](004-licensing-envelope-success-data.md) | Licensing: adeguamento alla busta {success, data} del license server, fix import mancanti, fix precompilazione dominio | Licensing | Completato | 2026-08-26 |
| [005](005-connectorservice-cleanup.md) | ConnectorService: cleanup, fix crash su login irraggiungibile, test di caratterizzazione | Licensing | Completato | 2026-08-27 |
| [006](006-controller-sistema-app-http-controllers-system.md) | Controller "di sistema" spostati da packages/ ad App\Http\Controllers\System | Architettura / CRUDBooster | Completato | 2026-08-27 |
| [007](007-upload-path-relativo.md) | Upload file: salvato path relativo invece di URL assoluto, fix controllo "file rotto", migration di bonifica dati | Upload/File | Completato | 2026-08-27 |
| [008](008-privileges-theme-color-bug.md) | PrivilegesController: creare un nuovo privilegio non deve cambiare il tema di chi lo crea | Bug fix | Completato | 2026-08-27 |
| [009](009-module-helpers-cms-moduls-mancante.md) | Modulo "Module Helpers" senza riga in cms_moduls (404), seeder aggiornato | Bug fix / dati | Completato | 2026-08-27 |
| [010](010-popup-select-non-si-chiude.md) | Popup "Browse data" non si chiudeva dopo Select (bug sistemico sui 7 componenti relazione) | Bug fix | Completato | 2026-08-27 |
| [011](011-settings-hardening.md) | Sezione Settings: autorizzazione mancante su delete-file, upload con path relativo, rimozione gruppo Qlik dai seeder, unique su name (senza deduplica automatica), campo password, email_sender svuotato | Hardening / Dati | Completato | 2026-08-27 |
| [012](012-controller-motore-shim-class-alias.md) | Prima classe "motore" spostata in App\Http\Controllers\System: Controller, con shim class_alias() | Architettura / CRUDBooster | Completato | 2026-08-28 |
| [013](013-importdata-exportdata-shim-class-alias.md) | ImportData/ExportData spostate in App\Http\Controllers\System, con shim class_alias() | Architettura / CRUDBooster | Completato | 2026-08-28 |
| [014](014-apicontroller-shim-class-alias.md) | ApiController spostata in App\Http\Controllers\System, con shim class_alias() | Architettura / CRUDBooster | Completato | 2026-08-28 |
| [015](015-cbcontroller-shim-class-alias.md) | CBController spostata in App\Http\Controllers\System, con shim class_alias() — ultima classe motore | Architettura / CRUDBooster | Completato | 2026-08-28 |
| [016](016-comando-migrazione-extends-legacy-clienti.md) | Comando artisan `crudbooster:migrate-legacy-extends` per riscrivere l'extends dei controller custom dei clienti | Architettura / CRUDBooster / Tooling | Completato | 2026-08-28 |
| [017](017-rimozione-cartella-controllers-legacy.md) | Rimossa packages/.../controllers/: alias consolidati in app/Support/legacy_crudbooster_aliases.php (composer autoload.files) | Architettura / CRUDBooster | Completato | 2026-08-28 |
| [018](018-commands-middlewares-validations-cleanup.md) | Mailqueues spostato in App\Console\Commands, comandi installer CRUDBooster eliminati, validation.php spostato in AppServiceProvider, CBBackend__.php eliminato | Architettura / CRUDBooster | Completato | 2026-08-28 |
| [019](019-rimozione-localization-legacy.md) | Rimossa packages/.../localization/ — mai caricata a runtime, resources/lang già la fonte viva e tracciata su git | Architettura / CRUDBooster | Completato | 2026-08-28 |
| [020](020-rimozione-userfiles-legacy.md) | Rimossa packages/.../userfiles/ — copie ridondanti di CBHook.php/readme.txt già tracciati, stub AdminCmsUsersController obsoleto | Architettura / CRUDBooster | Completato | 2026-08-28 |
| [021](021-rimozione-configs-legacy.md) | Rimossa packages/.../configs/ — mergeConfigFrom() e publishes() rimossi, unica chiave differente (API_PATH) confermata mai usata | Architettura / CRUDBooster | Completato | 2026-08-28 |
| [022](022-rimozione-database-legacy.md) | Rimossa packages/.../database/ — 0 migration e 1 solo seeder (già morto) esistevano solo nel pacchetto | Architettura / CRUDBooster | Completato | 2026-08-28 |
| [023](023-helpers-nnhelper-moduleHelperhelper-myhelper.md) | Helpers (1/N): NNHelper eliminato (morto), ModuleHelperHelper e MyHelper spostati in App\Helpers | Architettura / CRUDBooster | Completato | 2026-08-28 |
| [024](024-helpers-grouphelper-tenanthelper-cb.md) | Helpers (2/N): GroupHelper, TenantHelper, CB spostati in App\Helpers | Architettura / CRUDBooster | Completato | 2026-08-28 |
| [025](025-helpers-chatai-license-menu.md) | Helpers (3/N): ChatAIHelper, LicenseHelper, MenuHelper spostati in App\Helpers (incl. 7 FQCN inline nelle view) | Architettura / CRUDBooster | Completato | 2026-08-28 |
| [026](026-helpers-qlikhelper-modulehelper.md) | Helpers (4/N): QlikHelper e ModuleHelper spostati in App\Helpers | Architettura / CRUDBooster | Completato | 2026-08-28 |
| [027](027-helpers-userhelper.md) | Helpers (5/N): UserHelper spostato in App\Helpers | Architettura / CRUDBooster | Completato | 2026-08-28 |
| [028](028-helpers-functions-globali.md) | Helpers (6/N): funzioni globali (Helper.php) spostate in app/Helpers/functions.php | Architettura / CRUDBooster | Completato | 2026-08-28 |
| [029](029-helpers-crudbooster-ultimo-pezzo.md) | Helpers (7/7): CRUDBooster.php spostato, packages/.../helpers/ non esiste più, corretto bug latente su MyHelper | Architettura / CRUDBooster | Completato | 2026-08-28 |
| [030](030-middlewares-cbbackend-cbauthapi.md) | CBBackend/CBAuthAPI spostati in App\Http\Middleware, middlewares/ non esiste più (incl. riferimento in config/lfm.php) | Architettura / CRUDBooster | Completato | 2026-08-28 |
| [031](031-routes-standard-laravel.md) | routes.php spostato in routes/crudbooster.php, caricato da RouteServiceProvider invece che dal service provider del pacchetto | Architettura / CRUDBooster | Completato | 2026-08-28 |
| [032](032-crudboosterserviceprovider-pulizia-e-spostamento.md) | CRUDBoosterServiceProvider ripulito (3 registrazioni provider ridondanti + singleton morto rimossi) e spostato in App\Providers | Architettura / CRUDBooster | Completato | 2026-08-28 |
| [033](033-crudbooster-docs-spostati.md) | Documentazione originale CRUDBooster (49 file) spostata da packages/.../docs/en/ a docs/crudbooster/ | Documentazione | Completato | 2026-08-28 |
| [034](034-rimozione-file-vestigiali-pacchetto.md) | Rimossi .codeclimate.yml, .gitignore, composer.json, README.md — ultimi file vestigiali del pacchetto | Documentazione / Housekeeping | Completato | 2026-08-28 |
| [035](035-fontawesome-spostato.md) | Fontawesome.php spostato in App\Helpers, fonts/ non esiste più | Architettura / CRUDBooster | Completato | 2026-08-28 |
| [036](036-rimozione-assets-legacy.md) | Rimossa packages/.../src/assets/ (32 MB) — già pubblicata e tracciata in public/vendor/crudbooster/, zero contenuto mancante | Housekeeping | Completato | 2026-08-28 |
| [037](037-views-spostate-resources.md) | views/ spostata in resources/views/crudbooster/, corretti 16 path assoluti hardcoded, 2 file morti eliminati — packages/.../src/ ora vuota | Architettura / CRUDBooster | Completato | 2026-08-28 |
| [038](038-fix-open-redirect-return-url.md) | Fix: open redirect su CRUDBooster::redirect() — return_url non validato | Sicurezza | Completato | 2026-08-28 |
| [039](039-fix-type-components-non-trovati.md) | Fix: "tipo di componente non trovato" — 9 file fuori da packages/ dimenticati in 037 | Bug fix | Completato | 2026-08-28 |
| [040](040-fix-preview-immagini-mancante.md) | Fix: anteprima immagine mancante su colonne Logo/Favicon (Tenants) e un modulo custom | Bug fix | Completato | 2026-08-28 |
| [041](041-color-picker-tenants.md) | Background Color/Font Color (Tenants): da testo libero a color picker nativo HTML5 | UI/UX | Completato | 2026-08-28 |
| [042](042-tipo-color-lista-dettaglio.md) | Nuovo tipo di campo "color" riusabile: swatch anche in lista e dettaglio, non solo nel form | UI/UX | Completato | 2026-08-28 |
| [043](043-privileges-form-come-standard.md) | Vista privileges: markup del form (card, label a due colonne, footer) allineato allo standard `crudbooster::default.form` | UI/UX | Completato | 2026-08-28 |
| [044](044-privileges-delete-bloccato-e-messaggio-perso.md) | Bug: eliminazione privilegio bloccata da una regola sbagliata (`id < 4`) e messaggio di errore perso nel redirect verso la dashboard | Bug fix | Completato | 2026-08-28 |
| [045](045-messaggio-cancellazione-gruppo-piu-specifico.md) | Messaggio di blocco cancellazione gruppo più specifico (distingue membri e tenant assegnati), chiude anche un rischio di errore SQL non gestito sui gruppi con soli tenant | UI/UX / Bug fix | Completato | 2026-08-28 |
| [046](046-groups-members-leftjoin-privilegio-orfano.md) | Members del gruppo: un utente con privilegio cancellato (orfano) non deve sparire dalla lista (INNER JOIN → LEFT JOIN) | Bug fix | Completato | 2026-08-28 |
| [047](047-user-isSuperAdmin-crash-privilegio-orfano.md) | Crash "is_superadmin on null" per un utente con privilegio orfano: `User::isSuperAdmin()`/`isTenantAdmin()` ora tollerano `Role::find()` che non trova nulla | Bug fix | Completato | 2026-08-28 |
| [048](048-tenant-soft-deleted-non-deve-bloccare-cancellazione-gruppo.md) | Un tenant soft-deleted associato a un gruppo non deve più bloccarne la cancellazione (conteggio allineato al filtro già usato dalla pagina tenant del gruppo) | Bug fix | Completato | 2026-08-28 |
| [049](049-select-detail-crash-fk-orfana.md) | Crash sul dettaglio di un modulo per un campo "select" con FK orfana (componente condiviso, non solo Users) | Bug fix | Completato | 2026-08-28 |
| [050](050-dashboard-layouts-cms-moduls-mancante.md) | Modulo "Dashboard Layouts" senza riga in cms_moduls → 404 nonostante controller/tabella/link nel menu esistessero già (stesso pattern di 009) | Bug fix / dati | Completato | 2026-08-28 |
| [051](051-dashboard-layouts-builder-visuale.md) | Dashboard Layouts: builder visuale a righe/colonne al posto di TinyMCE, stesso formato HTML salvato (zero modifiche allo Statistic Builder); corretto anche un bug pre-esistente nel regex di assegnazione id | UI/UX | Completato | 2026-08-28 |
| [052](052-dashboard-layouts-rimossa-modalita-avanzata-preview-dettaglio.md) | Dashboard Layouts: rimossa la modalità "Avanzato (HTML)", aggiunto un preview visivo del layout nella pagina di dettaglio | UI/UX | Completato | 2026-08-28 |
| [053](053-smallbox-icon-searchable-color-picker.md) | Widget "Small Box": select ricercabile per l'icona (733 icone lette dal font vendorizzato) e color picker nativo al posto dei 4 colori fissi | UI/UX | Completato | 2026-08-28 |
| [054](054-smallbox-errore-sql-validazione-required-testo-link.md) | Widget "Small Box": mostra il vero errore SQL invece di "ERROR", corretto un bug di selettore jQuery che rendeva invisibile la validazione dei campi obbligatori (bug condiviso da tutti i widget), testo pulsante cambiato | Bug fix / UI-UX | Completato | 2026-08-28 |
| [055](055-statistic-builder-bordi-aree-layout.md) | Statistic Builder: bordo tratteggiato visibile per le aree del layout in modalità builder (drag&drop widget) | UI/UX | Completato | 2026-08-28 |
| [056](056-statistic-builder-max-un-widget-per-area.md) | Statistic Builder: ogni area del layout può contenere un solo widget (drop rifiutato via jQuery UI sortable('cancel') se già occupata) | UI/UX | Completato | 2026-08-28 |
| [057](057-table-widget-die-rimuove-widget-query-sbagliata.md) | Widget "Table": una query SQL sbagliata faceva sparire il widget (`die('ERROR')` rompeva l'intera risposta AJAX) — ora mostra l'errore reale, corretto anche un bug nella sostituzione dei placeholder di sessione | Bug fix | Completato | 2026-08-28 |
| [058](058-getshow-non-caricava-il-layout-assegnato.md) | `StatisticBuilderController::getShow()` non caricava il layout assegnato alla dashboard, ricadendo sempre sulla griglia di default a 9 aree — widget in posizione diversa tra builder e dashboard reale | Bug fix | Completato | 2026-08-28 |
| [059](059-table-widget-datatable-selettore-troppo-generico.md) | Widget "Table": selettore DataTable globale (`table.table`) poteva confliggere con altri widget Table sulla stessa dashboard ("tabella tagliata") — scoped al singolo widget con controllo anti-doppia-inizializzazione | Bug fix | Completato | 2026-08-28 |
| [060](060-groups-can-view-crash-standard-e-tenants-list-vuota.md) | `ModuleHelper::can_view()`: un utente Standard che lista `/admin/groups` con gruppi di piu' tenant otteneva un 500 (query/null-check sbagliati in `get_group_id()`/`get_tenant_id()`); documentato anche che un tenantadmin non vede nessun tenant in lista Tenants (nessun fix, solo caratterizzazione) | Bug fix / caratterizzazione | Parzialmente completato | 2026-09-01 |
| [061](061-icona-modulo-privileges-incoerente.md) | Icona del modulo Privileges incoerente tra testata pagina (`fa fa-cog`, da `cms_moduls`) e sidebar (`fa fa-key`, hardcoded) — allineata la testata alla sidebar via migration dati | Bug fix (UI) | Completato | 2026-09-01 |
| [062](062-pulsante-add-member-in-tenants-group.md) | `/admin/tenants/group/{id}`: il pulsante diceva "Add member" (copiato dalla view analoga di Groups) invece di "Add group" | Bug fix (UI) | Completato | 2026-09-01 |
| [063](063-menu-management-bug-e-test-crud.md) | Menu Management: 3 bug reali corretti (UrlGenerationException su ogni voce modificabile, crash creando la prima voce su tabella vuota, `CBController::getDelete()` crashava su qualunque cancellazione bloccata in QUALUNQUE modulo) + 14 test CRUD | Bug fix + test | Completato | 2026-09-01 |
| [064](064-settings-bug-e-test-crud.md) | Settings: 3 bug reali corretti (`CRUDBooster::valid()` chiamava `exit()` invece di tornare una Response, bloccando la testabilita' degli upload non validi; cancellare una riga di setting non invalidava la cache ne' cancellava il file associato) + 27 test CRUD | Bug fix + test | Completato | 2026-09-02 |
| [065](065-api-generator-rce-e-test.md) | **API Generator: RCE autenticata corretta** (`generateAPI()`/`postSaveApiCustom()` incollavano input utente grezzo dentro sorgente PHP scritto su disco come controller live - risolto con `var_export()`) + path traversal nel nome file + 2 bug null-safety + 19 test. Nota: nessun controllo di privilegio su gran parte del controller (punto A) resta deliberatamente non corretto, rimandato | Sicurezza + Bug fix + test | Completato (parziale) | 2026-09-02 |
| [066](066-api-execute-null-safety-e-test.md) | API Generator: `ApiController::execute_api()` crashava con 500 su un permalink senza riga `cms_apicustom` corrispondente (dereferenziato prima del controllo esistenza) + 6 test sul comportamento a runtime (parametri/risposte su list/detail) | Bug fix + test | Completato | 2026-09-02 |
| [067](067-statistic-builder-sql-arbitrario-e-test.md) | **Statistic Builder: SQL arbitrario corretto** (`postSaveComponent()` - unico punto di scrittura di `config`, incluso il campo "SQL Query" che alcuni widget eseguono via `DB::select()` in fase di rendering - non aveva alcun controllo di privilegio) + 14 test. Nota: stesso pattern di 065, gap generico di autorizzazione su altri 3 endpoint del controller resta deliberatamente non corretto, rimandato | Sicurezza + Bug fix + test | Completato (parziale) | 2026-09-02 |
| [068](068-module-generator-rce-e-test.md) | **Module Generator: 3 RCE + 1 SQL injection corretti** (`CRUDBooster::generateController()`, `postStep3()`, `postStep5()` incollavano input utente grezzo dentro sorgente PHP scritto su disco come controller live - risolto con `var_export()`/whitelist; `Schema::getIndexes()` con lo stesso valore era SQL injection vera per una `quoteString()` di Laravel non parametrizzata - risolto con `sql_name_encode()`) + path traversal nel nome file + `getDelete()` non ricontrollava `is_protected` + 2 endpoint senza alcun controllo di privilegio + 1 bug (flag "download" mai scritto) + 15 test | Sicurezza + Bug fix + test | Completato | 2026-09-03 |
| [069](069-uiux-revamp-fase0-fase1-guscio-e-auth.md) | **Revamp UI/UX, Fase 0+1**: token di design + font Plus Jakarta Sans self-hosted (variable font, un solo file), guscio condiviso (`admin_template`/`header`, zero modifiche a `sidebar`/`footer`) e pagine login/lockscreen/forgot riscritte, branding per-tenant preservato, `theme_color` (skin AdminLTE per ruolo) rimappato su un accento colore. Nota: scoperto (non introdotto) che il toggle sidebar/dropdown header non rispondono al click, probabile conflitto Bootstrap 3(AdminLTE)/5 preesistente, non risolto in questo intervento | UI/UX | Completato | 2026-09-03 |
| [070](070-datamodal-leak-cross-tenant.md) | **Popup "datamodal": un tenant admin vedeva record di altri tenant** — `AdminGroupsController::members()` (aggiungi membro, tabella `cms_users`) e `AdminCmsUsersController::groups()` (aggiungi a gruppo, tabella `groups`) lasciavano `datamodal_where` vuoto, senza lo scoping per tenant già applicato alle liste equivalenti. Corretto valorizzando `datamodal_where` in base a `UserHelper::isTenantAdmin()`. Verificati e scartati come falso allarme gli altri usi analoghi, tutti dietro `isSuperadmin()` | Sicurezza + Bug fix | Completato | 2026-09-16 |
| [071](071-privileges-modulo-logs-non-assegnabile.md) | Privileges: il modulo Logs (`cms_logs`, `is_protected=1`) non compariva mai nell'elenco moduli assegnabili di `getAdd()`/`getEdit()` (whitelist limitata a moduli Module Generator + `groups`/`cms_users`/`cms_menus`) — nessun ruolo, incluso Tenant Admin, poteva riceverne il permesso nonostante `LogsController` avesse già lo scoping per tenant pronto. Corretto aggiungendo `cms_logs` alla whitelist | Bug fix | Completato | 2026-09-16 |
| [072](072-forgot-password-link-invece-di-password-in-chiaro.md) | **Password dimenticata: link di reset invece di password in chiaro via email** — `postForgot()` generava una password di 5 caratteri e la mandava in chiaro via email, bypassando la policy password. Corretto usando il broker nativo Laravel (token con scadenza/uso singolo, tabella `password_resets` creata) mantenendo l'invio sul sistema di template email esistente; nuove pagine `reset-password` con la stessa policy password del form utenti | Sicurezza | Completato | 2026-09-16 |
| [073](073-module-generator-export-import.md) | Module Generator: nuova export/import di un modulo custom in JSON (tabella, colonne, form), riusando gli stessi meccanismi di scrittura gia' messi in sicurezza per il wizard (`var_export`/`min_var_export`, marcatori `# START/END` - vedi 068). Il modulo importato non viene assegnato automaticamente a menu/ruoli, va abilitato da Privileges | Feature | Completato | 2026-09-16 |
| [074](074-password-policy-nist.md) | **Policy password stile NIST 800-63B su `cms_users`**: nessuna regola prima (nemmeno il confronto tra password e conferma). Aggiunta lunghezza minima (12) invece di regole di composizione rigide, blocklist di password comuni/prevedibili, blocco di sequenze/ripetizioni e di password contenenti email/nome utente. Applicata solo in scrittura: gli hash gia' esistenti restano validi, il login non dipende dalla policy | Sicurezza | Completato | 2026-09-16 |
| [075](075-filter-column-sorting-mancante-500.md) | Vista lista: 500 `Undefined array key "sorting"` con un querystring `filter_column` parziale (senza la chiave `sorting`, solo URL costruiti a mano) — corretto sia in `default/table.blade.php` (frecce di ordinamento) sia in `CRUDBooster::getSortingFilter()` (popup "Ordina e filtra") con `??` | Bug fix | Completato | 2026-09-24 |
| [076](076-guard-licenza-e-login-moduli-qlik-chatai.md) | **Guard di licenza e login per Qlik/ChatAI**: le route custom di `routes/web.php` (Qlik e ChatAI) non passavano da `CBBackend` — `send_message`/`send_message_agent` erano eseguibili da anonimi fino alla chiamata al servizio AI. Aggiunto `CBBackend` + nuovo middleware `EnforceModuleLicense` (prefisso di path → modulo in licenza), applicato anche ai gruppi CRUD di `routes/crudbooster.php`: `qlik_items`/`qlik_confs`/`chat_ai` non più raggiungibili via URL senza il modulo in licenza, con protezione anti-loop per dashboard di tipo Qlik | Sicurezza + Licensing | Completato | 2026-09-24 |
| [077](077-rimossa-route-debug-testapi.md) | Rimossa la route pubblica di debug `GET /testapi` (`dd()` di un controller generato, senza login) | Sicurezza | Completato | 2026-09-24 |
| [078](078-api-generator-privilegi.md) | **API Generator: tutti gli endpoint riservati al superadmin** — 8 endpoint senza controllo di privilegio (creazione/cancellazione API, elenco/creazione/stato/cancellazione API key in chiaro, colonne di qualunque tabella, export Postman) usabili da qualunque utente loggato. Punto A di 065 | Sicurezza + test | Completato | 2026-09-24 |
| [079](079-statistic-builder-privilegi-componenti.md) | Statistic Builder: `add-component`/`update-area-component` riservati al superadmin come il resto dell'editor; `list-component` non espone più `config` (query SQL dei widget) ai non superadmin. Aperta la decisione sulla visibilità delle dashboard per ruolo | Sicurezza + test | Completato (parziale) | 2026-09-24 |
| [080](080-module-generator-wizard-solo-superadmin.md) | **Module Generator: tutto il wizard riservato al superadmin** — step 1/2/4 richiedevano solo il permesso di visualizzazione (`postStep1` creava moduli con controller, permessi e menu), `postStep4` nessun controllo | Sicurezza + test | Completato | 2026-09-24 |
| [081](081-fix-robustezza-helper-e-login.md) | Fix di robustezza: `getTableStructure()` size `"int"` su MySQL 8, deprecation `sendFCM()`, `getLang()`/`SetUserPreferredLanguage` null-safe, `postLogin()` con `Request::getHost()`, ciclo di redirect `/admin`↔`/admin/login` con sessione legacy orfana | Bug fix | Completato | 2026-09-24 |
| [082](082-rimossa-build-gulp-e-package-json.md) | Rimossi build gulp/elixir morta (`package.json`, `gulpfile.js`, servizio Docker `node`) e 2 `package.json` del plugin datetimepicker vendorizzato: fonte della maggior parte degli avvisi Dependabot, nulla girava in produzione | Dipendenze | Completato | 2026-09-24 |
| [093](093-execute-api-cbinit-privilegi.md) | `execute_api()`: chiamato `cbInit()` sul controller collegato (mai fatto prima) - risolve i privilegi per i moduli Module Generator (`mg_*`, tenant admin) e `cms_users`/`groups`; trovato un secondo gap in `ModuleHelper::can_list()`/simili (nessun ramo generico per `global_privilege=true` sulle altre tabelle), non corretto perché tocca l'autorizzazione di ogni pagina CRUD admin | Sicurezza / Bug fix | Completato (parziale) | 2026-09-24 |
| [092](092-execute-api-controller-self-healing.md) | `execute_api()`: risolto a runtime il `$this->controller` mancante nelle API generate da versioni vecchie del generatore (7 dei 14 controller già presenti in locale) - trovato un gap più ampio, non corretto: l'azione `list` sembra restituire sempre zero righe per un chiamante non superadmin | Bug fix | Completato | 2026-09-24 |
| [091](091-bug-noti-execute-api-logscontroller-visibilita-dashboard.md) | Tre bug noti chiusi in un giro: `execute_api()` (`$debug_mode_message` assegnata dopo un `goto`), `LogsController` (Edit/Delete visibili al superadmin, stesso bug di 089), visibilità dashboard implementata (menu-based, `StatisticBuilderController`) | Bug fix / Sicurezza | Completato | 2026-09-24 |
| [090](090-doc-api-generator-menziona-api2.md) | Documentazione API Generator (admin e pubblica): aggiunta la sezione "come usare api2" (Bearer token) accanto a quella esistente di `api/` | Documentazione / UI | Completato | 2026-09-24 |
| [089](089-button-edit-detail-visibili-a-superadmin.md) | `button_edit`/`button_detail=false` ignorati per il superadmin quando un modulo usa lo stile azioni di default (`ModuleHelper::can_edit()`/`can_view()` bypassano sempre per il superadmin) - aggiunto uno stile opt-in (`button_icon_strict`) che li rispetta davvero, usato da `api_tokens`; stile di default non toccato altrove (es. `LogsController`, stesso bug, non corretto di proposito) | Bug fix / UI | Completato | 2026-09-24 |
| [088](088-api-tokens-modello-pat.md) | `api_tokens`: rimosso il flag dedicato "client API" (creava confusione col conteggio licenza) - un token è ora un Personal Access Token generabile per qualunque utente esistente, invalidato automaticamente se l'utente viene disattivato | Sicurezza / API | Completato | 2026-09-24 |
| [087](087-dataenum-detail-view-valore-grezzo.md) | Pagine di dettaglio: campi `select`/`radio` con `dataenum` "valore\|Etichetta" mostravano il valore grezzo (es. "1"/"0") invece dell'etichetta (Users/Menu Management/Module Generator) - corretto nel componente condiviso; corretto anche un crash 500 preesistente e scollegato sul campo Target Layout | Bug fix | Completato | 2026-09-24 |
| [086](086-api2-sanctum.md) | Nuovo binario `api2` (Bearer token via Laravel Sanctum, scadenza scelta alla generazione) parallelo e additivo ad `api/` esistente (invariato); modulo admin superadmin-only per generare/revocare token; utenti "client API" bloccati dal login web | Sicurezza / API | Completato | 2026-09-24 |
| [085](085-pulizia-file-morti-e-branch-obsoleti.md) | Rimossi 3 file inutilizzati (`phpunit.xml.bak`, `5.0` vuoto, duplicazione 15MB `public/vendor/crudbooster/assets/assets/`); cancellati 8 branch remoti obsoleti, tutti confermati senza commit unici rispetto a main/dev | Housekeeping | Completato | 2026-09-24 |
| [084](084-host-request-path-e-chatai-cms-moduls.md) | `$_SERVER['HTTP_HOST']`/`REQUEST_URI` residui sostituiti con `Request`/`request()` in 4 metodi di `AdminController` e 3 di `CRUDBooster` (pattern segnalato in 081); aggiunta la riga mancante in `cms_moduls` per `AdminChatAIController` (stesso bug di 009/050, modulo ChatAI senza rotte) | Bug fix / robustezza / dati | Completato | 2026-09-24 |
| [083](083-firebase-php-jwt-7.md) | **`firebase/php-jwt` 6→7** (CVE-2025-45769): la 7.x rifiuta passphrase HS256 < 32 byte e chiavi RSA < 2048 bit — aggiunti try/catch con log e messaggio (niente più 500, anche per chiavi Qlik vuote), validazione `min:32` sulla Passphrase Chat AI. **Verificare passphrase e chiavi dei clienti prima del deploy** (query nel documento) | Dipendenze / Sicurezza | Completato | 2026-09-24 |
| [094](094-pulizia-librerie-js-morte-public-vendor.md) | Censimento delle librerie JS/CSS vendorizzate a mano in `public/vendor/crudbooster/` (punto cieco Dependabot segnalato in 082) — rimosse ~2.5MB confermate morte (edit_area, fancy, datetimepicker-master, bootstrap-datetimepicker, bootstrap, unslider, 4 CSS non importati, dateformat.js, font DroidNaskh, bg_blur*.jpg, jquery.numberformatter, laravel-filemanager duplicato) | Housekeeping | Completato | 2026-09-25 |
| [095](095-mfa-fase-0-fondamenta-dati.md) | MFA (TOTP + Email OTP): Fase 0, schema dati (`two_factor_secret`/`two_factor_confirmed_at` su `cms_users`, `mfa_recovery_codes`, `mfa_trusted_devices`, template email), librerie `pragmarx/google2fa`+`bacon/bacon-qr-code` — nessun controller/route toccato, login invariato | Auth | Completato | 2026-09-25 |
| [096](096-mfa-fase-1-enrollment-totp.md) | MFA: Fase 1, enrollment TOTP self-service dal profilo (QR, conferma primo codice, backup codes monouso, disable/regenerate/revoke devices) — `MfaHelper` nuovo, bug migration mancante trovato e risolto durante il test end-to-end | Auth | Completato | 2026-09-25 |
| [097](097-mfa-fase-2-login-step-up.md) | MFA: Fase 2, gate MFA in `postLogin()` (TOTP sempre richiesto se attivo, email OTP come step-up solo senza TOTP e dispositivo non fidato), rate limiting nativo, anti-replay verificato manualmente | Auth | Completato | 2026-09-25 |
| [098](098-mfa-fase-3-recovery.md) | MFA: Fase 3, recovery "ho perso il dispositivo e i backup codes" — link email con finestra di attesa 30 min + annulla, completamento lazy (nessun cron) sia da link sia da tentativo di login | Auth | Completato | 2026-09-25 |
| [099](099-mfa-fase-4-hardening.md) | MFA: Fase 4 (chiusura piano), rate limiting su enrollment/recovery, comando `mfa:cleanup` schedulato — bug reale (500 su `\RateLimiter` non aliasato) trovato e risolto durante il test | Auth | Completato | 2026-09-25 |
| [100](100-mfa-email-otp-fail-open-senza-smtp.md) | MFA: se l'invio dell'email OTP fallisce (SMTP non configurato/raggiungibile) il login procede normalmente invece di dare 500 — fail-open deliberato, solo sul ramo email, mai sul TOTP | Auth | Completato | 2026-09-25 |
| [101](101-settings-email-test-invio.md) | Settings "Email Setting": pulsante per testare l'invio email prima di salvare, con esito e log — scoperta tecnica: `config/mail.php` legacy fa ignorare `mail.mailers.*` a `MailManager` in questo progetto | Settings / Email | Completato | 2026-09-25 |
| [102](102-mfa-badge-bootstrap3-vs-bootstrap5.md) | MFA: badge di stato sul profilo senza forma/colore (Bootstrap 3 su tema Bootstrap 5), poi sovrapposizione con "System Information" — causa radice: bug preesistente in `form_body.blade.php` (div mai chiusi se manca il campo `group`), che rivela anche il pulsante "Save" del profilo gia' oggi invisibile | Auth / UI | Completato | 2026-09-25 |
| [103](103-fix-save-invisibile-profilo-utenti.md) | Fix: pulsante Save invisibile su `admin/users/profile` — `form_body.blade.php` ora chiude il box "System Information" anche su `primary_group` (non solo `group`); due approcci piu' invasivi scartati dopo aver causato corruzione dati reale in test | Users / UI | Completato | 2026-09-25 |
| [104](104-mfa-elenco-dispositivi-fidati.md) | MFA: elenco dei dispositivi fidati sul profilo (user agent, prima/ultima volta, scadenza) con revoca per singolo dispositivo, non solo "disconnetti tutti" | Auth | Completato | 2026-09-25 |
| [105](105-link-clicca-qui-senza-contesto.md) | Fix: link "Clicca qui" senza contesto su `reset_password`/3 viste MFA — testo esplicativo o link autoesplicativo | Auth / UI | Completato | 2026-09-25 |
| [106](106-restyle-template-email-auth.md) | Restyle template email auth (reset password, MFA OTP, MFA recovery) in stile app + fix `subject` mai valorizzato; wrapper condiviso `emails/header.blade.php`/`footer.blade.php` ristilizzato con logo — scoperto e corretto un commento HTML (invece di Blade) che sarebbe finito dentro ogni email reale | Email / Auth / UI | Completato | 2026-09-25 |
| [107](107-fix-test-login-logout-rotti-da-mfa.md) | Fix: 4 test in `LoginTest`/`LogoutTest` rotti dal gate MFA (redirect a step-up email OTP invece di popolare la sessione) — nuovo helper `trustDeviceFor()` che simula un dispositivo gia' fidato, nessuna logica di produzione toccata | Auth / Test | Completato | 2026-09-25 |

**Stato**: `Pianificato` → `In corso` → `Completato` (o `Annullato` se si
decide di non procedere, motivando il perché nel file stesso).

## Roadmap refactoring auth (strategia additiva)

Percorso concordato per sostituire l'auth custom di CRUDBooster con i guard
Laravel nativi (vedi [001](001-auth-guard-additivo-fase-1.md) per il
dettaglio della strategia):

- ✅ Fase 0 — Ricognizione
- ✅ Fase 1 — Guard additivo su `postLogin()`
- 🔶 Fase 3 — Migrazione dei 42 file individuati in Fase 0, uno alla volta.
  **In corso, non come sprint dedicato**: si migra un file quando lo si
  tocca comunque per un altro motivo. `CBBackend` migrato in
  [002](002-cbbackend-guard-fase-3.md), **41 file ancora sul meccanismo
  legacy**.
- ⏸️ Fase 4 — Rimozione della scrittura della sessione legacy (solo quando
  la Fase 3 sarà sostanzialmente completa). **Messa in pausa esplicitamente
  dall'utente**, da riprendere più avanti — per ora si passa ad altre
  sezioni del progetto da refactorare.

## Roadmap uscita da CRUDBooster (packages/)

Percorso per portare `packages/crocodicstudio/crudbooster` a standard
Laravel, tenendo separati i controller "di sistema" (versionati, nessun
contratto esterno) dai controller creati da interfaccia in produzione
(mai in questo repo, hanno un contratto esterno reale — vedi
[006](006-controller-sistema-app-http-controllers-system.md) per l'analisi
completa):

- ✅ 21 controller "schermata" spostati in `App\Http\Controllers\System`
  — [006](006-controller-sistema-app-http-controllers-system.md).
- ✅ Le 5 classi "motore" (`CBController`, `ApiController`, `Controller`,
  `ExportData`, `ImportData`) — la base che i controller generati da
  interfaccia estendono per FQCN letterale
  (`extends \crocodicstudio\crudbooster\controllers\CBController`) — sono
  state spostate una alla volta in `App\Http\Controllers\System`, con uno
  shim `class_alias()` nel vecchio path che mantiene risolvibile il
  vecchio FQCN:
  - ✅ `Controller` spostata — [012](012-controller-motore-shim-class-alias.md).
  - ✅ `ImportData`/`ExportData` spostate — [013](013-importdata-exportdata-shim-class-alias.md).
  - ✅ `ApiController` spostata — [014](014-apicontroller-shim-class-alias.md).
  - ✅ `CBController` spostata — [015](015-cbcontroller-shim-class-alias.md).
- ✅ **La cartella `packages/.../controllers/` non esiste più** — i 5
  `class_alias()` sono stati consolidati in un unico bootstrap,
  `app/Support/legacy_crudbooster_aliases.php`, caricato via
  `composer.json` → `autoload.files` — [017](017-rimozione-cartella-controllers-legacy.md).
  Il vecchio FQCN resta comunque risolvibile per chi lo estende da fuori:
  questo intervento ha spostato *dove* vive la compatibilità, non l'ha
  eliminata.
- ⬜ **Rimozione definitiva degli alias** (`app/Support/legacy_crudbooster_aliases.php`
  e l'entry in `composer.json`): possibile solo dopo che *ogni* cliente
  attivo è stato aggiornato almeno una volta con
  `php artisan crudbooster:migrate-legacy-extends --apply` sui propri
  controller custom (comando pronto —
  [016](016-comando-migrazione-extends-legacy-clienti.md) — ma mai ancora
  eseguito su un ambiente reale). Finché anche un solo cliente non è
  passato da questo comando, gli alias restano necessari.
- ✅ `commands/`, `middlewares/CBBackend__.php` (morto), `validations/` —
  ripuliti: `Mailqueues` spostato in `App\Console\Commands`, i 3 comandi
  installer storici (`crudbooster:install`/`:update`/`:version`, zero
  riferimenti nel repo) eliminati, `validation.php` spostato in
  `AppServiceProvider::boot()` — [018](018-commands-middlewares-validations-cleanup.md).
- ✅ **`middlewares/` non esiste più** — `CBBackend`/`CBAuthAPI` spostati
  in `App\Http\Middleware`, aggiornati anche i 3 riferimenti in
  `routes.php` e 1 in `config/lfm.php` (unisharp/laravel-filemanager) —
  [030](030-middlewares-cbbackend-cbauthapi.md).
- ✅ **`routes.php` spostato in `routes/crudbooster.php`**, caricato da
  `App\Providers\RouteServiceProvider::map()` (nuovo metodo
  `mapCrudboosterRoutes()`, registrato per ultimo per preservare l'ordine
  di prima) invece che da un `require` dentro il service provider del
  pacchetto — [031](031-routes-standard-laravel.md).
- ✅ **`CRUDBoosterServiceProvider` ripulito e spostato in
  `App\Providers`** — rimosse 3 registrazioni di provider terze parti
  ridondanti (già coperte dall'auto-discovery di Laravel, verificato nei
  `composer.json` dei pacchetti) e un singleton morto; gli 8 alias custom
  spostati in `config/app.php` → `aliases` (standard Laravel, invece di
  `AliasLoader` programmatico) — [032](032-crudboosterserviceprovider-pulizia-e-spostamento.md).
  **`packages/crocodicstudio/crudbooster/src/` ora contiene solo
  `assets/`, `fonts/`, `views/`** — nessuna logica applicativa residua.
- ✅ **`helpers/` non esiste più** — le 14 file spostate in `App\Helpers`,
  un file (o pochi) alla volta dal più piccolo al più grande, nessuno
  shim necessario (nessun controller custom cliente referenzia un helper
  per FQCN, diversamente da `controllers/`):
  - ✅ `NNHelper` eliminato (morto), `ModuleHelperHelper`/`MyHelper`
    spostati — [023](023-helpers-nnhelper-moduleHelperhelper-myhelper.md).
  - ✅ `GroupHelper`, `TenantHelper`, `CB` spostati —
    [024](024-helpers-grouphelper-tenanthelper-cb.md).
  - ✅ `ChatAIHelper`, `LicenseHelper`, `MenuHelper` spostati —
    [025](025-helpers-chatai-license-menu.md).
  - ✅ `QlikHelper`, `ModuleHelper` spostati —
    [026](026-helpers-qlikhelper-modulehelper.md).
  - ✅ `UserHelper` spostato — [027](027-helpers-userhelper.md).
  - ✅ `Helper.php` (funzioni globali) spostato in
    `app/Helpers/functions.php` — [028](028-helpers-functions-globali.md).
  - ✅ `CRUDBooster.php` spostato per ultimo (80 KB, 101 metodi) —
    [029](029-helpers-crudbooster-ultimo-pezzo.md).
- ✅ `localization/` rimossa interamente — mai caricata a runtime
  (nessun `loadTranslationsFrom()`, `resources/lang/*/crudbooster.php` già
  la fonte viva e tracciata su git) — [019](019-rimozione-localization-legacy.md).
- ✅ `userfiles/` rimossa interamente — le sue 3 copie erano ridondanti
  (identiche a `app/Http/Controllers/CBHook.php` e
  `resources/views/vendor/crudbooster/type_components/readme.txt`, già
  tracciati) o uno stub obsoleto (`AdminCmsUsersController`, superato da
  [006](006-controller-sistema-app-http-controllers-system.md)) —
  [020](020-rimozione-userfiles-legacy.md).
- ✅ `configs/` rimossa interamente — a differenza degli altri due, era
  referenziata anche da `mergeConfigFrom()` (attivo ad ogni richiesta, non
  solo un `publishes()` pigro); rimossa anche quella riga. Unica chiave
  differente (`API_PATH`) confermata mai letta da nessuna parte —
  [021](021-rimozione-configs-legacy.md).
- ✅ `database/` rimossa interamente — 0 migration e 1 solo seeder
  (`Qlik_Sett`, già rimosso esplicitamente dal `DatabaseSeeder.php` reale)
  esistevano solo nel pacchetto — [022](022-rimozione-database-legacy.md).
- ✅ `assets/` (32 MB) rimossa — già pubblicata e tracciata in
  `public/vendor/crudbooster/`, zero contenuto mancante una volta
  scartata una duplicazione ricorsiva interna al pacchetto (non
  referenziata) — [036](036-rimozione-assets-legacy.md). **Nota**:
  la stessa duplicazione esiste anche dentro
  `public/vendor/crudbooster/assets/assets/` (15 MB, file live/tracciati
  su git) — non toccata, possibile pulizia futura separata.
- ✅ `views/` spostata in `resources/views/crudbooster/` (non
  `resources/views/vendor/crudbooster/` — scelta esplicita dell'utente).
  Trovati e corretti 16 path assoluti hardcoded (`base_path('packages/.../views/default/type_components/...')`
  dentro un `file_exists()` a guardia di un `@include` — senza
  correzione, ogni asset/component per tipo di campo form sarebbe
  sparito silenziosamente da tutti i form dell'app) e 2 file morti
  eliminati — [037](037-views-spostate-resources.md).
  **`packages/crocodicstudio/crudbooster/src/` è ora vuota.**

## Backlog — emerso ma non ancora assegnato a un intervento numerato

Cose notate durante altri lavori (setup Docker, CI/CD), non ancora
trasformate in un intervento vero e proprio:

- ~~70 vulnerabilità segnalate da GitHub Dependabot~~ (conteggio di
  agosto, era Laravel 9) — **rivalutato il 2026-09-24**: lato PHP ne
  restava 1 (`firebase/php-jwt`, aggiornata in [083](083-firebase-php-jwt-7.md),
  `composer audit` ora pulito); il resto veniva dai `package.json` della build morta,
  rimossi in [082](082-rimossa-build-gulp-e-package-json.md). ~~Punto cieco
  rimasto: librerie JS copiate a mano in `public/vendor/` (nessun manifest,
  Dependabot non le vede) — serve un censimento dedicato~~ — **censimento
  fatto e parte morta rimossa in
  [094](094-pulizia-librerie-js-morte-public-vendor.md)** (2026-09-25).
  Resta fuori scope `assets/adminlte/plugins/` (11 MB, non auditata
  internamente).
- ~~`ApiCustomController`: nessun controllo di privilegio~~ — **risolto in
  [078](078-api-generator-privilegi.md)** (2026-09-24).
- ~~`StatisticBuilderController`: nessun controllo di privilegio su
  add/update/list component~~ — **risolto in
  [079](079-statistic-builder-privilegi-componenti.md)** (2026-09-24).
  ~~**Resta aperto**: la visibilità delle dashboard per ruolo non è mai
  verificata lato server~~ — **implementato in
  [091](091-bug-noti-execute-api-logscontroller-visibilita-dashboard.md)**
  (2026-09-24): visibile solo a superadmin o a chi ha un menu (stesso
  meccanismo di `cms_menus_privileges` già in uso) verso quella specifica
  dashboard. **Comportamento visibile cambiato**: un link condiviso
  funziona solo se la dashboard è davvero nel menu del ruolo di chi lo
  apre.
- ~~`ModulsController`: privilegio debole sul wizard~~ — **risolto in
  [080](080-module-generator-wizard-solo-superadmin.md)** (2026-09-24).
  Prima del deploy verificare sui clienti che nessun ruolo non superadmin
  usi il wizard.
- **Autenticazione delle API custom (`CRUDBooster::authAPI()`, modulo API
  Generator)**: schema "fatto in casa" (chiave condivisa in `cms_apikey` +
  timestamp + user agent → `md5()`, confrontato con l'header
  `X-Authorization-Token`). Problemi noti: nessuna scadenza reale sul
  timestamp (solo l'uguaglianza dell'hash), MD5 invece di un HMAC standard,
  autenticazione **disattivata di default** a meno che il setting
  `api_debug_mode` non sia impostato esplicitamente a `'false'`, chiavi che
  non scadono/ruotano mai. **Non risolto per `api/` esistente** (nessuno
  di questi problemi è stato toccato lì) — **risolto per i nuovi client**
  con un binario parallelo e additivo, `api2` (Bearer token via Laravel
  Sanctum, scadenza per-token scelta alla generazione), vedi
  [086](086-api2-sanctum.md). Migrazione volontaria, per cliente, non uno
  switch globale: `api/` resta con lo schema debole finché un cliente non
  passa a `api2`. **Deciso 2026-09-24**: si accetta così per ora; quando
  i client saranno migrati su `api2`, `api/` verrà disattivato del
  tutto (nessuna data ancora).
- ~~**Bug scollegato trovato lavorando su 086, priorità alta**:
  `ApiController::execute_api()` va in `ErrorException` ("Undefined
  variable $debug_mode_message")~~ — **risolto in
  [091](091-bug-noti-execute-api-logscontroller-visibilita-dashboard.md)**
  (2026-09-24): la variabile veniva assegnata dopo il primo `goto show;`
  del metodo (permalink senza riga `cms_apicustom`), non prima come gli
  altri 7 punti che leggono `show:`. ~~**Nuovo bug trovato verificando il
  fix con un permalink reale**: i controller generati già presenti in
  locale (es. `ApiHiveController.php`) non impostano
  `$this->controller`~~ — **risolto in
  [092](092-execute-api-controller-self-healing.md)** (2026-09-24), a
  runtime, senza toccare i file generati esistenti.
- ~~**Trovato risolvendo 092, priorità da valutare**: l'azione `list` del
  modulo API Generator sembra restituire **sempre zero righe** per
  qualunque chiamante non superadmin~~ — **`cbInit()` ora chiamato in
  [093](093-execute-api-cbinit-privilegi.md)** (2026-09-24): risolve i
  moduli Module Generator (`mg_*`, tenant admin) e `cms_users`/`groups`.
  **Resta aperto**: `ModuleHelper::can_list()`/`can_view()`/`can_edit()`/
  `can_delete()`/`can_add()` non hanno un ramo generico per
  `global_privilege=true` su altre tabelle (a differenza delle pagine
  CRUD admin normali, dove basta da solo) — un modulo di sistema con
  `global_privilege=true` ma senza scoping tenant/gruppo resta comunque
  negato per un non superadmin. Non corretto: tocca `ModuleHelper`,
  usato da ogni pagina CRUD admin, non solo dall'API Generator — serve
  una valutazione a parte.
- ~~`app/Http/Controllers/` nel `.gitignore`~~ — **voluto, non un rischio**:
  da interfaccia si possono creare moduli custom (controller generati), che
  devono restare specifici dell'ambiente in cui vengono creati e non
  finire nel repo condiviso. I 52 controller già tracciati lo erano prima
  che la regola venisse introdotta. **Aggiornato in
  [006](006-controller-sistema-app-http-controllers-system.md)**: la regola
  ora esclude `app/Http/Controllers/*` con un'eccezione esplicita per
  `System/` (i controller "di sistema" spostati lì sono codice vero e
  proprio del progetto, vanno tracciati) — il resto della cartella
  (controller generati da interfaccia) resta ignorato come prima.
- ~~Compatibilità delle migration con SQLite non verificata~~ — risolto
  passando i test a MySQL vero (stesso motore della produzione), vedi
  [`../test-coverage.md`](../test-coverage.md).
- ~~**Branch remoti obsoleti da ripulire**: `main_backup`, `main_backup2`,
  `sapienza`, `qlikdashboard`, `bootstrapupdate`, `ckeditor`,
  `license-local`~~ — **risolto in
  [085](085-pulizia-file-morti-e-branch-obsoleti.md)** (2026-09-24):
  verificato che tutti (+ `master`) sono ancestor completo di `main`/`dev`
  (zero commit unici), cancellati da origin.
- ~~`AdminController::postLogin()` legge `$_SERVER['HTTP_HOST']`~~ —
  **risolto in [081](081-fix-robustezza-helper-e-login.md)** (2026-09-24).
  ~~Stesso pattern ancora presente in `getLogin()`/`getForgot()`/
  `getResetPassword()`/`getLicensescreen()` e in
  `CRUDBooster::isEditPage()`/`isAddPage()`/`isProfilePage()`~~ —
  **risolto in [084](084-host-request-path-e-chatai-cms-moduls.md)**
  (2026-09-24).

- **Bug lato server di licenza remoto** (`license.thecustomerhive.com`,
  progetto separato gestito dall'utente): il trial di attivazione
  restituisce `LicenseService::getLicenseByDomain(): Argument #1 ($domain)
  must be of type string, null given` — vedi
  [003](003-licensing-hardening.md#rischi-e-note). Da correggere in
  quel progetto, non qui.

- ~~`GET /testapi` pubblico con `dd()`~~ — **rimossa in
  [077](077-rimossa-route-debug-testapi.md)** (2026-09-24).
- ~~`getTableStructure()` size `"int"`~~, ~~deprecation `sendFCM()`~~,
  ~~`SetUserPreferredLanguage` senza null-check~~ — **risolti in
  [081](081-fix-robustezza-helper-e-login.md)** (2026-09-24).
- ~~**Ambiente locale: modulo ChatAI non registrato in `cms_moduls`**~~ →
  ~~`chat_ai/access|tenant/{id}` danno 500 (`Route
  [AdminChatAIControllerGetIndex] not defined`) anche con licenza ChatAI
  attiva.~~ — **risolto in
  [084](084-host-request-path-e-chatai-cms-moduls.md)** (2026-09-24): non
  era solo il DB locale, mancava la riga anche nel seeder (si sarebbe
  ripresentato su ogni installazione pulita), stesso pattern di 009/050.

- ~~**`form_body.blade.php`: il box collassabile "System Information" non si
  chiude mai per moduli con campo `tenant` ma senza campo `group`**~~
  (scoperto il 2026-09-25 lavorando su [102](102-mfa-badge-bootstrap3-vs-bootstrap5.md))
  — **risolto in [103](103-fix-save-invisibile-profilo-utenti.md)**
  (2026-09-25): il modulo Users usa `primary_group`, non `group` - la
  condizione di chiusura ora riconosce anche quel nome. Nessun campo tolto
  dal form (due tentativi precedenti che rimuovevano campi sono stati
  scartati dopo aver causato una corruzione dati reale, verificata e
  corretta a mano sull'ambiente locale - vedi 103, sezione dedicata).

## Documenti correlati

- [`../docker-local-dev.md`](../docker-local-dev.md) — ambiente di sviluppo locale
- [`../login-e-licensing.md`](../login-e-licensing.md) — sistema di login e licensing attuale
- [`../pre-push-checklist.md`](../pre-push-checklist.md) — cose da ripristinare/verificare prima di un push
- [`../test-coverage.md`](../test-coverage.md) — catalogo dei test automatici esistenti
- [`../ui-ux-annotazioni.md`](../ui-ux-annotazioni.md) — cose notate sull'interfaccia da tenere a mente per il rinnovo UI/UX
