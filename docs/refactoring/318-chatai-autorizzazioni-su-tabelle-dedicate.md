# 318 - Autorizzazioni ChatAI (Agent AI) su tabelle dedicate, come per i Qlik items

- **Data**: 2026-10-29
- **Stato**: Completato (test eseguiti nel container su `customerhive_testing`: 28/28 nuovi + 62 di regressione)
- **Area**: Chat AI / Autorizzazioni / Database
- **File/aree di codice coinvolte**:
  - `database/migrations/2026_10_29_100000_create_chatai_allowed_tables.php` (nuovo)
  - `app/ChatAIGroupAllowed.php`, `app/ChatAITenantAllowed.php` (nuovi, pivot)
  - `app/ChatAIConf.php`
  - `app/Http/Controllers/System/AdminChatAIController.php`
  - `app/Http/Controllers/System/Concerns/SafeRedirectTarget.php` (nuovo trait)
  - `app/Http/Controllers/System/AdminQlikItemsController.php` (solo uso del trait)
  - `app/Helpers/ChatAIHelper.php` (`can_see_item`)
  - `app/Http/Controllers/System/MenusController.php` (selettore "Agent AI", ~righe 523-535)
  - `resources/views/crudbooster/default/datamodal_relation/component.blade.php`
  - `resources/views/crudbooster/default/type_components/item_access_datamodal/browser.blade.php`
  - `resources/views/crudbooster/default/type_components/item_tenant_datamodal/browser.blade.php`
  - `tests/Feature/ChatAIAuthorizationTest.php` (nuovo)
  - Piano di riferimento: `docs/refactoring/piani/318-chatai-autorizzazioni-come-qlik.md`; decisione A12 in `docs/architettura/adr.md`

## Contesto

Intervento successivo al batch 0-1 del piano 317
([`317`](317-modelli-eloquent-caratterizzazione-e-igiene.md)), su branch
`refactor/models` ma in commit separati. Rileggendo `ChatAIConf` per il piano
317 è emerso che le autorizzazioni di ChatAI (gruppi e tenant che possono
vedere una conf "Agent AI") non funzionavano per i non superadmin e che ChatAI
si appoggiava, per errore di progetto, alle tabelle di Qlik.

Nessun cliente usa ChatAI. Nota correlata
([`300`](300-prova-generale-istanza-docker-end-to-end.md)): il modulo `ChatAI`
non esiste sul license server, quindi `isActiveChatAI()` non può essere vero
in produzione finché non lo si crea.

## Situazione prima

- `ChatAIConf::allowedGroups()` / `allowedTenants()` filtravano su
  `chataiconf_id`, colonna inesistente in ogni migrazione e in entrambi i DB
  locali (`customerhive`, `customerhive_testing`). Per un non superadmin
  `ChatAIHelper::can_see_item` sollevava `QueryException`;
  `AdminChatAIController::content_view` non la gestiva (probabile 500); la
  sidebar la catturava ma lasciava la voce con url "#".
- ChatAI scriveva e leggeva in `items_allowed` / `tenants_allowed` (tabelle di
  Qlik) con `item_id` = id della conf: nel controller, nel selettore menu e nei
  popup. Conf ChatAI N e item Qlik N si ereditavano a vicenda le
  autorizzazioni.
- Il controller ChatAI era una copia più vecchia di quello Qlik, senza i suoi
  controlli: `$_POST` grezzo, nessun controllo di tipo/esistenza, redirect da
  input non filtrato, nessuna cascata alla cancellazione.

## Situazione dopo

- **Migrazione** (create-only, `down()` = `dropIfExists`): `chatai_groups_allowed`
  e `chatai_tenants_allowed` (`id`, `chatai_conf_id`, `group_id`/`tenant_id`,
  colonne di audit come `items_allowed`, unique `(chatai_conf_id, group_id)` e
  `(chatai_conf_id, tenant_id)`, nessuna FK). Nessun dato migrato.
- **Modelli**: pivot `ChatAIGroupAllowed` e `ChatAITenantAllowed` (senza
  timestamp). `ChatAIConf::allowedGroups()` / `allowedTenants()` leggono le
  nuove tabelle e restano array di id. Nuove relazioni `allowedGroupRecords()` /
  `allowedTenantRecords()` (`belongsToMany`): prima applicazione del pattern
  del batch 2 del piano 317.
- **`AdminChatAIController`**: `access` / `tenant` con `select('groups.*')` /
  `select('tenants.*')`; `add_tenant` / `add_authorization` con
  `request()->input()`, `is_int`, verifica di esistenza di conf/gruppo/tenant e
  redirect filtrato; `remove_*` sulle nuove tabelle; `hook_before_delete` con
  cascata (hard delete, solo tabelle ChatAI); `content_view($chat_ai_id)`; i due
  form passano `'datamodal_allowed_scope' => 'chatai'`.
- **Trait `SafeRedirectTarget`**: corpo identico al vecchio metodo privato di
  `AdminQlikItemsController`, ora `protected`, usato da entrambi i controller.
  È l'unico tocco al controller Qlik.
- **`ChatAIHelper::can_see_item`**: `is_int` prima del `find`.
- **`MenusController`** (selettore "Agent AI"): join su
  `chatai_tenants_allowed.chatai_conf_id`.
- **Popup condivisi**: scelto un parametro opzionale al posto di due nuovi tipi.
  `datamodal_relation/component.blade.php` aggiunge `&allowed_scope=...`
  all'URL solo se `datamodal_allowed_scope` è valorizzato;
  `item_access_datamodal/browser.blade.php` e
  `item_tenant_datamodal/browser.blade.php` scelgono la tabella da una
  whitelist fissa (`qlik` default = query invariate, `chatai`); valore
  sconosciuto o non stringa torna a `qlik`. Il nome della tabella non arriva
  mai dalla query string.
- **i18n**: nessun testo nuovo visibile. Le stringhe hardcoded preesistenti in
  `access` / `tenant` del controller ChatAI ("Authorize Group" ecc.) non sono
  toccate e sono identiche a quelle di Qlik.

**Cambi di comportamento visibile** (tutti voluti):
- ChatAI funziona per i non superadmin (prima `QueryException`).
- ChatAI non condivide più le autorizzazioni con Qlik.
- Le righe ChatAI storiche rimaste in `items_allowed` / `tenants_allowed` non
  valgono più per ChatAI (restano dove sono; nessun cliente le ha).
- Add/remove con input non validi o redirect esterni vanno al fallback o a
  "accesso negato" invece di scrivere righe.
- Cancellare una conf cancella le sue autorizzazioni.

Qlik: nessun cambio visibile.

## Motivazione

Un modulo che solleva eccezioni per metà degli utenti e che si mescola con i
dati di un altro modulo è un difetto latente da chiudere prima che qualcuno
attivi ChatAI. Allineare il controller ai controlli già presenti in Qlik
elimina anche input non validati e redirect aperti.

Alternativa scartata: aggiungere `chataiconf_id` alle tabelle esistenti,
perché avrebbe obbligato a rendere nullable `item_id` su tabelle Qlik già in
produzione. Per il popup è stata scartata l'alternativa dei due nuovi tipi
(`chatai_access_datamodal` / `chatai_tenant_datamodal`), che avrebbe toccato
whitelist di `CBController`, `ModuleGeneratorFields` e le etichette in en/it.

## Test

Eseguiti nel container su `customerhive_testing`:

- Nuovo `tests/Feature/ChatAIAuthorizationTest.php`: 28 test, 76 assert.
  Copre la matrice `ChatAIHelper::can_see_item`, l'isolamento da Qlik a parità
  di id nelle due direzioni, add/remove (doppio POST = una riga, id non
  validi, redirect esterno per `add_authorization` e `add_tenant`, non
  superadmin negato), i popup con `allowed_scope` (default, `qlik`, `foo`,
  array → `qlik`; `chatai`), la cancellazione di una conf con cascata solo
  sulle tabelle ChatAI, la `dataquery` del selettore menu.
- Il test simula una licenza con il modulo ChatAI (`Storage::fake('license')`
  con `modules: [ChatAI]`, riga in `license`, token in cache): le route custom
  `admin/chat_ai/*` passano da `EnforceModuleLicense` e il campo "Agent AI"
  del menu è mostrato solo con licenza attiva.
- Primo giro: 4 fallimenti dovuti al test (licenza non attiva, 302 dal
  middleware; GET `admin/menu_management/add` sempre rimandata perché
  `MenusController` ha `button_add = false`). Dopo la correzione del test:
  28/28 verdi. Prima della licenza simulata alcuni test negativi (id non
  validi, redirect esterno, non superadmin) erano verdi "a vuoto" perché il
  middleware rimandava ogni richiesta; ora sono significativi.
- Regressione nello stesso giro, tutta verde (62 test): `QlikItemVisibilityTest`,
  `QlikItemPublicAccessTest`, `MenuTenantsCharacterizationTest`,
  `ModuleStep1MenuDefaultsTest`, `UserModelLookupsTest`, `MenusCrudTest`,
  `GroupsCrudTest`, `CrossModuleRelationsTest`.
- Non verificato: prova nel browser dei popup e delle pagine
  `admin/chat_ai/access|tenant`; esecuzione della migrazione su un dump di
  cliente.

## Rischi e note

Rilievi di `ch-reviewer`: nessun bloccante.

1. La cascata di `hook_before_delete` cancella in modo definitivo le righe di
   autorizzazione anche se la conf è in soft delete: un eventuale ripristino
   perderebbe le autorizzazioni (coerente con Qlik).
2. Gruppi/tenant cancellati lasciano righe orfane nelle due tabelle nuove;
   innocue perché le query fanno join (come per Qlik).
3. `safeRedirectTarget` passa da `private` a `protected`: una sottoclasse
   cliente con un metodo omonimo privato darebbe fatal (improbabile).
4. La migrazione crea due tabelle vuote sui DB dei clienti al prossimo
   aggiornamento.
5. Il test del popup cerca i nomi nell'HTML renderizzato (un po' fragile).
6. Pint segnala solo violazioni storiche su righe non toccate (`ChatAIConf.php`
   e altri).
7. Avvisi git "LF will be replaced by CRLF" su alcuni file (autocrlf, innocuo).
8. Se un cliente avesse autorizzazioni ChatAI vecchie (non risulta), vanno
   reimpostate dal superadmin.

Altre note:
- `isActiveChatAI()` non può essere vero in produzione finché il modulo
  `ChatAI` non viene creato sul license server (vedi
  [`300`](300-prova-generale-istanza-docker-end-to-end.md)).
- **Punto aperto separato, NON parte di questo intervento**:
  `resources/views/crudbooster/menus/form_body.blade.php:68` contiene
  `$value = @$join_query_{$join_table}->{$join_title};`, sintassi non valida in
  PHP 8. Nei test, renderizzare il form di modifica di una voce di menu
  (`admin/menu_management/edit/{id}`, `MenusController::customEdit`) dà un 500
  `ParseError`. Il file non è stato modificato; da verificare nel browser se la
  pagina funzioni nell'istanza di sviluppo. Da valutare a parte.

## Rollback

`git revert` dei commit dell'intervento, più `php artisan migrate:rollback`
per la sola migrazione `2026_10_29_100000_create_chatai_allowed_tables.php`
(droppa solo le due tabelle nuove). Qlik non viene toccato dal rollback;
le vecchie righe in `items_allowed` / `tenants_allowed` non sono state né
modificate né cancellate.
