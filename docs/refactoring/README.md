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

- **70 vulnerabilità segnalate da GitHub Dependabot** sul branch di default
  (2 critiche, 22 alte, 41 moderate, 5 basse) — da valutare come parte
  dell'audit di compatibilità dipendenze (vedi ordine di lavoro sotto).
- **[Priorità alta] `ApiCustomController` (modulo API Generator): nessun
  controllo di privilegio** su `postSaveApiCustom()`, `getDeleteApi()`,
  `getGenerateScreetKey()`, `getStatusApikey()`, `getDeleteApiKey()` —
  `CBBackend` verifica solo "sei loggato", non il modulo/privilegio.
  Qualunque utente autenticato, indipendentemente dal privilegio, può
  creare/modificare/cancellare API generate e chiavi API. Deliberatamente
  rimandato durante [065](065-api-generator-rce-e-test.md) (che ha corretto
  la RCE raggiungibile tramite questo gap, non il gap stesso) — vedi
  "Rischi e note" in quel documento. Riprendere aggiungendo lo stesso check
  `CRUDBooster::isSuperadmin()` già presente su `getIndex()`/`getGenerator()`/
  `getEditApi()` dello stesso controller.
- **Autenticazione delle API custom (`CRUDBooster::authAPI()`, modulo API
  Generator) da modernizzare**: schema "fatto in casa" (chiave condivisa in
  `cms_apikey` + timestamp + user agent → `md5()`, confrontato con l'header
  `X-Authorization-Token`). Problemi noti: nessuna scadenza reale sul
  timestamp (solo l'uguaglianza dell'hash), MD5 invece di un HMAC standard,
  autenticazione **disattivata di default** a meno che il setting
  `api_debug_mode` non sia impostato esplicitamente a `'false'`, chiavi che
  non scadono/ruotano mai. Candidato naturale: **Laravel Sanctum** (già nel
  core della versione Laravel in uso) — token opachi con scadenza/revoca
  per-token, hashati correttamente nel DB. Da trattare come lavoro a sé
  (tocca `CBAuthAPI`/`authAPI()` e potenzialmente i controller generati da
  API Generator e chi consuma queste API oggi — verificare prima chi le
  chiama in produzione), non un fix isolato. Stessa area di codice del
  gap di autorizzazione sopra: valutare di riprenderli insieme.
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
- **Branch remoti obsoleti da ripulire**: `main_backup`, `main_backup2`,
  `sapienza`, `qlikdashboard`, `bootstrapupdate`, `ckeditor`,
  `license-local` — chiarire quali sono ancora utili prima di fare pulizia.
- **`AdminController::postLogin()` legge `$_SERVER['HTTP_HOST']`
  direttamente** invece che tramite l'oggetto `Request` di Laravel —
  funziona in produzione (Apache lo popola sempre) ma rende il codice
  testabile solo forzando a mano la superglobale nei test (vedi
  [`../test-coverage.md`](../test-coverage.md)). Da sistemare quando si
  affronterà il refactoring dell'auth (sostituzione con `request()->getHost()`
  o equivalente).

- **Bug lato server di licenza remoto** (`license.thecustomerhive.com`,
  progetto separato gestito dall'utente): il trial di attivazione
  restituisce `LicenseService::getLicenseByDomain(): Argument #1 ($domain)
  must be of type string, null given` — vedi
  [003](003-licensing-hardening.md#rischi-e-note). Da correggere in
  quel progetto, non qui.

## Documenti correlati

- [`../docker-local-dev.md`](../docker-local-dev.md) — ambiente di sviluppo locale
- [`../login-e-licensing.md`](../login-e-licensing.md) — sistema di login e licensing attuale
- [`../pre-push-checklist.md`](../pre-push-checklist.md) — cose da ripristinare/verificare prima di un push
- [`../test-coverage.md`](../test-coverage.md) — catalogo dei test automatici esistenti
- [`../ui-ux-annotazioni.md`](../ui-ux-annotazioni.md) — cose notate sull'interfaccia da tenere a mente per il rinnovo UI/UX
