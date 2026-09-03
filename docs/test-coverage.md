# Test implementati

Catalogo di cosa è coperto da test automatici, mantenuto aggiornato ad ogni
aggiunta. Obiettivo: sapere a colpo d'occhio cosa è già protetto da un test
di caratterizzazione (vedi [`refactoring/glossario.md`](refactoring/glossario.md))
prima di toccare quella parte di codice, e cosa invece è ancora "alla cieca".

Come aggiungere una voce: quando si scrive un nuovo file di test, aggiungere
qui una sezione con lo stesso schema di quelle sotto.

---

## `tests/Unit/ExampleTest.php`

**Cosa copre**: niente di funzionale — è un canary minimo per validare che
la pipeline di test (locale e CI) funzioni davvero (introdotto quando si è
scoperto che la CI non eseguiva mai i test, vedi
[`cicd-passaggi-setup.md`](cicd-passaggi-setup.md)).

| Test | Cosa verifica |
|---|---|
| `test_application_boots` | l'app Laravel si avvia senza errori |
| `test_testing_environment_is_configured` | `APP_ENV=testing` viene letto correttamente |

Nessuna dipendenza da database.

## `tests/Feature/LoginTest.php`

**Cosa copre**: `AdminController::postLogin()` (CRUDBooster) — il login
dell'area admin. Test di **caratterizzazione**: fissano il comportamento
attuale. Dopo l'intervento [001](refactoring/001-auth-guard-additivo-fase-1.md)
il controller ha un'aggiunta additiva (`Auth::login()` accanto alla sessione
legacy) — questi test verificano anche quella.

| Test | Cosa verifica |
|---|---|
| `test_login_con_credenziali_corrette_riesce` | email+password corretti, utente `Active`, dominio del proprio tenant → sessione popolata (`admin_id`, `admin_is_superadmin`), redirect, **e** guard Laravel `Auth::check()`/`Auth::id()` popolato |
| `test_login_con_password_sbagliata_fallisce` | password errata → nessuna sessione impostata, `$this->assertGuest()` |
| `test_login_con_email_inesistente_fallisce` | email non presente in `cms_users` → rifiutato, messaggio d'errore in sessione (flash `message`, non il sistema standard di validation error di Laravel — dettaglio del codice reale) |
| `test_login_utente_non_active_fallisce` | credenziali corrette ma `status != 'Active'` → rifiutato |
| `test_login_da_dominio_di_un_altro_tenant_fallisce` | utente non-superadmin, login dal sottodominio di un **altro** tenant → rifiutato |
| `test_superadmin_bypassa_il_controllo_tenant` | stesso scenario sopra ma con `is_superadmin=1` → login riuscito comunque, guard popolato |
| `test_dopo_il_login_si_accede_a_una_pagina_protetta` | end-to-end: la sessione creata dal login basta davvero a superare il middleware `CBBackend` su una richiesta successiva, e il guard Laravel resta autenticato anche sulla seconda richiesta |

**Dipendenze/scelte note**:
- Il controllo di licenza (`LicenseHelper::canLicenseLogin()`) è oggi
  bypassato incondizionatamente (`LICENSE-CHECK-DISABLED-DEV`) — questi test
  dipendono da quel bypass per non fare chiamate di rete verso il license
  server esterno. **Quando il bypass verrà rimosso, questi test andranno
  rivisti** (vedi [`pre-push-checklist.md`](pre-push-checklist.md)).
- I test usano MySQL vero (non SQLite), tramite il DB dedicato
  `customerhive_testing` — vedi sotto.
- `postLogin()` legge il dominio da `$_SERVER['HTTP_HOST']` **direttamente**
  invece che dall'oggetto `Request` di Laravel: il client di test non
  sincronizza quella superglobale con l'URL simulato, quindi il test la
  imposta a mano (`postLoginFrom()` nel file). In produzione (richieste
  Apache reali) non è un problema — è solo un limite di testabilità del
  codice attuale, segnato come backlog per il futuro refactoring dell'auth
  (vedi [`refactoring/README.md`](refactoring/README.md)).

## `tests/Feature/CBBackendTest.php`

**Cosa copre**: il middleware `CBBackend` (CRUDBooster) — il gate che
protegge ogni pagina dell'area admin ad ogni richiesta (non solo al login).
Testato in isolamento usando la rotta vuota `GET /admin` come bersaglio
minimo, per non dipendere da un controller applicativo specifico. Dopo
l'intervento [002](refactoring/002-cbbackend-guard-fase-3.md) il check
principale del middleware è `Auth::guest()` — simulare una richiesta
autenticata richiede quindi anche il guard, non solo la sessione legacy
(`getAdmin()` usa `actingAs()` oltre a `withSession()`).

| Test | Cosa verifica |
|---|---|
| `test_richiesta_senza_sessione_reindirizza_al_login` | nessuna sessione/guard → redirect a `/admin/login` |
| `test_richiesta_con_sessione_valida_passa` | sessione + guard validi, non bloccata → richiesta passa (200) |
| `test_richiesta_con_sessione_bloccata_reindirizza_al_lock_screen` | `admin_lock=1` in sessione (letto ancora dal meccanismo legacy, non migrato) → redirect a `/admin/lock-screen` |

**Scoperta scrivendo questo test** (non corretta, il middleware non è stato
toccato): il middleware globale `App\Http\Middleware\SetUserPreferredLanguage`
(gira su *ogni* richiesta web, non solo su quelle autenticate) presume che
l'id utente in sessione corrisponda sempre a una riga reale in `cms_users`,
altrimenti va in errore fatale (nessun controllo di null). In produzione non
è un problema perché `admin_id` viene sempre impostato da un login riuscito
su un utente reale — ma è un altro punto fragile da tenere presente per il
refactoring dell'auth.

## `tests/Feature/LogoutTest.php`

**Cosa copre**: `AdminController::getLogout()`. Ha permesso di scoprire
(prima di introdurre una regressione in `CBBackend`) che `Session::flush()`
da solo non invalida il guard Laravel — vedi
[002](refactoring/002-cbbackend-guard-fase-3.md).

| Test | Cosa verifica |
|---|---|
| `test_logout_invalida_sia_la_sessione_legacy_che_il_guard` | dopo il logout, sia `admin_id` è assente dalla sessione sia `Auth::guest()` è vero |

## `tests/Feature/TenantsCrudTest.php`, `GroupsCrudTest.php`, `PrivilegesCrudTest.php`, `UsersCrudTest.php`

**Cosa coprono**: il CRUD di ciascuno dei 4 moduli di sistema Tenants,
Groups, Privileges, Users — creazione/modifica/cancellazione via
`CBController` (Tenants, Groups, Users) o via il proprio controller
completamente custom (`PrivilegesController`), inclusi gli hook
(`hook_before_add`/`hook_before_edit`/`hook_before_delete`/
`hook_after_add`) e le regole di business specifiche di ciascun modulo
(derivazione del `domain_name` per Tenants, traduzione
`superprivilege` → `is_superadmin`/`is_tenantadmin` per Privileges,
associazione al gruppo primario per Users, ecc.). Attore sempre
superadmin fresco (`SeedsCmsData::actingAsSuperadmin()`); fuori scope
volutamente il sotto-form/le sotto-pagine Qlik.

Ha richiesto un refactoring esteso e propedeutico di
`CRUDBooster::redirect()`/`CBController::validation()` (da `exit()` a
`return` di una `Response`, 155 punti di chiamata in tutto il codebase)
perché il flusso `exit()`-based del codice legacy non era altrimenti
testabile con il client HTTP di Laravel. Ha anche fatto emergere e
corretto 3 bug reali preesistenti: `TenantHelper::unique_domain_name()`
(un `return` mancante nel ramo ricorsivo), `CRUDBooster::
sidebarDashboard()` (nessun controllo di null), il placeholder
`%field%` mai sostituito in `SettingsController::cbInit()`.

**Dipendenze/scelte note**:
- `Tests\Concerns\SeedsCmsData::registerAdminModule(s)()` registra a
  mano, in `setUp()`, sia la riga `cms_moduls` sia la rotta stessa
  (`CRUDBooster::routeController()`), perché `routes/crudbooster.php`
  legge `cms_moduls` al boot dell'app, prima che un test possa
  seminarla — e registra sempre l'intero set di moduli di sistema,
  perché il layout admin condiviso li referenzia tutti incondizionatamente.
- `UsersCrudTest` imposta/ripristina a mano in `setUp()`/`tearDown()`
  `$_SERVER['REQUEST_URI']`/`HTTP_HOST`/`REMOTE_ADDR`/`HTTP_USER_AGENT`
  — letti direttamente (non via `Request` Laravel) da
  `AdminCmsUsersController::cbInit()`, `CRUDBooster::isAddPage()`/
  `isProfilePage()` e `add_log_ch()` (chiamata da
  `GroupHelper::add()`), non popolati dal client di test.

## `tests/Feature/ListIndexSearchAndPaginationTest.php`

**Cosa copre**: la ricerca (`?q=`) e il numero di record per pagina
(`?limit=`) di `CBController::getIndex()` — due parametri GET comuni a
tutte le liste admin, non specifici di un modulo. Verificati sul modulo
Tenants (il più semplice tra quelli coperti da `*CrudTest.php`: nessun
hook/gruppo/privilege da seminare per popolare la lista).

| Test | Cosa verifica |
|---|---|
| `test_ricerca_q_filtra_per_nome` | `?q=testo` mostra solo le righe la cui colonna visibile combacia (LIKE) |
| `test_ricerca_q_senza_corrispondenze_non_mostra_righe` | nessuna corrispondenza → riga non mostrata |
| `test_limit_riduce_i_record_mostrati_alla_pagina` | `?limit=1` con 2 record → solo l'ultimo creato (ordinamento esplicito `id,desc` di `AdminTenantsController`) |
| `test_limit_piu_alto_del_numero_di_record_li_mostra_tutti` | `?limit=200` (> record esistenti) → tutti mostrati |
| `test_sort_ascendente_ordina_i_risultati_per_colonna` | `?filter_column[name][sorting]=asc` (click su header colonna) → ordine diverso da quello di default (id,desc), a prova che il sort e' applicato davvero |
| `test_sort_discendente_ordina_i_risultati_per_colonna` | stesso, `sorting=desc` |
| `test_filter_like_su_una_colonna_specifica` | `?filter_column[name][type]=like&value=...` (modale filtro avanzato) — like scoped a una sola colonna |
| `test_filter_uguale_richiede_corrispondenza_esatta` | `type='='` non deve combaciare con un valore che e' solo un prefisso/sottostringa (a differenza di `like`) |
| `test_filter_su_piu_colonne_applica_and` | due `filter_column[...]` insieme sono in AND, non OR: una riga che soddisfa solo una condizione resta esclusa |

## `tests/Feature/MenusCrudTest.php`

**Cosa copre**: il CRUD del modulo Menu Management (`MenusController` -
non usa la view standard di `CBController::getIndex()`, ha un albero
drag-and-drop dedicato). Ha fatto emergere e corretto 3 bug reali
(dettagli in [063](refactoring/063-menu-management-bug-e-test-crud.md)):
`UrlGenerationException` su ogni voce di menu modificabile,
crash creando la primissima voce di menu su una `cms_menus` vuota, e
un crash in `CBController::getDelete()` (controller BASE, non solo
Menu Management) su qualunque cancellazione bloccata da
`ModuleHelper::can_delete()`.

| Test | Cosa verifica |
|---|---|
| `test_lista_mostra_le_voci_di_menu_attive_e_inattive` | lista attiva/inattiva (regressione bug UrlGenerationException) |
| `test_creazione_prima_voce_di_menu_su_tabella_vuota_non_va_in_crash` | regressione crash su `cms_menus` vuota |
| `test_creazione_menu_di_tipo_url_riesce_e_aggiunge_il_parametro_m_al_path` | `path` per type URL riceve sempre `?m=<id>` |
| `test_creazione_menu_di_tipo_module_costruisce_il_path_dal_modulo_selezionato` | `path` per type Module = path del modulo + `?m=<id>` |
| `test_creazione_menu_di_tipo_statistic_costruisce_il_path_dalla_statistic_selezionata` | idem per type Statistic |
| `test_creazione_di_un_tenantadmin_assegna_automaticamente_il_suo_tenant` | `hook_after_add()` -> `Menu::assign_default_tenant()` |
| `test_modifica_menu_riesce_e_aggiorna_i_campi_base` | modifica riesce |
| `test_modifica_di_un_menu_di_tipo_module_perde_il_parametro_m` | **caratterizzazione** (non un fix): il suffisso `?m=` sparisce ad ogni modifica di un menu Module/Statistic/Qlik/Agent AI |
| `test_non_si_puo_disattivare_lunica_voce_impostata_come_dashboard` | regola dashboard-unica (regressione del fix mirato che sostituisce `redirectBack()` con `redirect()` in questo solo punto) |
| `test_cancellazione_menu_orfanizza_i_figli_invece_di_cancellarli` | `hook_after_delete()` -> `MenuHelper::promote_orphans()` |
| `test_cancellazione_menu_rimuove_le_associazioni_privilege` | pulizia `cms_menus_privileges` |
| `test_tenantadmin_non_puo_modificare_un_menu_condiviso_tra_piu_tenant` | `can_menu()`: un tenantadmin non tocca un menu con piu' di un tenant associato (regressione bug `CBController::getDelete()`) |
| `test_creazione_sincronizza_privileges_tenants_e_gruppi_nelle_pivot_table` | meccanismo generico `relationship_table` di CBController |
| `test_post_save_menu_riordina_e_reimposta_il_genitore` | endpoint drag-and-drop `postSaveMenu()` |

**Dipendenze/scelte note**:
- `SeedsCmsData::actingAsSuperadmin()`/`actingAsTenantUser()` ora
  impostano anche `admin_privileges` in sessione (mancava - alcuni
  hook, qui `MenusController`, leggono `CRUDBooster::myPrivilegeId()`).
  Cambio additivo, non ha impattato i test preesistenti.
- Fuori scope volutamente: i tipi Qlik/Agent AI (gated da licenza) e
  `CRUDBooster::redirectBack()` (ancora `exit()`-based, usata anche da
  2 Blade view - vedi 063 per il perche' non e' stata toccata).

## `tests/Feature/SettingsCrudTest.php`

**Cosa copre**: il modulo Settings — meta' CRUD standard su `cms_settings`
(`SettingsController` + `CBController`), meta' le due schermate custom che
sono il vero front-end (`getShow()`/`postSaveSetting()`, il salvataggio
massivo di un intero gruppo di setting) e `getDeleteFileSetting()`. Ha fatto
emergere e corretto 3 bug reali (dettagli in
[064](refactoring/064-settings-bug-e-test-crud.md)): `CRUDBooster::valid()`
chiamava `exit()` invece di tornare una Response (bloccava la testabilita'
degli upload non validi — stesso refactor gia' fatto per
`CRUDBooster::redirect()`/`CBController::validation()`), e cancellare una
riga di setting non invalidava la cache ne' cancellava il file caricato
associato (nessun `hook_before_delete()`/`hook_after_delete()`).

| Test | Cosa verifica |
|---|---|
| `test_lista_mostra_le_righe_di_setting` | lista, regressione bug storico `%field%` in `cbInit()` |
| `test_creazione_setting_genera_name_da_slug_della_label` | `hook_before_add()`: `name` derivato dallo slug di `label` |
| `test_creazione_setting_con_name_duplicato_viene_bloccata` | `name` e' la chiave logica (usata da cache/`getSetting()`), duplicati bloccati |
| `test_modifica_setting_invalida_la_cache` | `hook_after_edit()` -> `Cache::forget()` |
| `test_cancellazione_riga_invalida_la_cache` | **regressione bug 2**: `hook_before_delete()` invalida la cache |
| `test_cancellazione_riga_con_file_associato_rimuove_il_file_fisico` | **regressione bug 3**: `hook_before_delete()` cancella anche il file fisico |
| `test_cancellazione_riga_senza_content_non_va_in_errore` | guardia sul `content` vuoto |
| `test_getshow_nega_accesso_a_non_superadmin` | `getShow()`: check `isSuperadmin()` esplicito |
| `test_getshow_mostra_i_setting_del_gruppo_richiesto` | filtro per `group_setting` |
| `test_getshow_ripara_le_label_vuote` | side-effect noto: una GET ripara le label vuote nel DB |
| `test_postsavesetting_nega_accesso_a_non_superadmin` | `postSaveSetting()`: check `isSuperadmin()` esplicito |
| `test_postsavesetting_ignora_i_campi_non_presenti_nella_request` | regressione di un bug gia' corretto: un campo assente non azzera il setting |
| `test_postsavesetting_permette_di_svuotare_un_campo_di_testo` | "assente" ≠ "vuoto": un campo di testo inviato vuoto resta cancellabile |
| `test_postsavesetting_password_vuota_non_sovrascrive_il_valore_esistente` | password vuota = "non modificare" |
| `test_postsavesetting_password_valorizzata_aggiorna_il_valore` | password valorizzata aggiorna |
| `test_postsavesetting_upload_image_valido_salva_path_relativo` | path salvato relativo (`/storage/uploads/YYYY-MM/...`), non URL assoluto |
| `test_postsavesetting_upload_image_non_valido_viene_rifiutato` | **regressione bug 1**: prima non testabile (`exit()`) |
| `test_postsavesetting_upload_file_con_estensione_non_ammessa_viene_rifiutato` | stesso fix, ramo mimes generico |
| `test_postsavesetting_upload_fallito_lato_storage_non_azzera_il_valore` | `Storage::putFileAs()` → `false` (bug gia' corretto in passato): messaggio di warning, valore precedente conservato |
| `test_postsavesetting_salvataggio_riuscito_invalida_la_cache_per_ogni_setting_toccato` | cache invalidata per ogni riga salvata |
| `test_getdeletefilesetting_nega_accesso_a_non_superadmin` | **il bug piu' a rischio trovato in analisi**: prima nessun controllo di accesso |
| `test_getdeletefilesetting_rimuove_il_file_e_azzera_il_content` | rimozione file fisico via `public_path()` + azzeramento `content` |
| `test_getdeletefilesetting_su_content_gia_vuoto_non_fa_nulla` | guardia gia' presente |
| `test_getdeletefilesetting_invalida_la_cache` | `Cache::forget()` |
| `test_getsetting_legge_dal_db_e_mette_in_cache_forever` | `CRUDBooster::getSetting()`: cache-first, `Cache::forever()` |
| `test_getsetting_una_volta_in_cache_ignora_i_cambi_nel_db` | cache non invalidata da un `UPDATE` diretto sul DB |
| `test_getsetting_su_nome_inesistente_torna_null` | nome inesistente → `null` |

**Dipendenze/scelte note**:
- Gli upload riusciti/rifiutati via `postSaveSetting()` usano
  `Storage::fake('local')` (il disco di default in
  `config/filesystems.php`) — nessun file reale toccato.
- `getDeleteFileSetting()`/la cancellazione riga leggono e cancellano il
  file via `public_path()` diretto, non tramite lo Storage facade —
  `Storage::fake()` non li intercetta. Questi test scrivono un file reale
  sotto `public/storage/uploads/phpunit-settings-test/`, ripulito in
  `tearDown()` ad ogni test.
- `test_postsavesetting_upload_fallito_lato_storage_non_azzera_il_valore`
  sostituisce l'intero facade Storage con un mock (`Storage::shouldReceive`)
  per simulare una scrittura fallita — piu' fragile se in futuro
  `postSaveSetting()` chiamasse altri metodi Storage nello stesso percorso.

## `tests/Feature/ApiCustomCrudTest.php`

**Cosa copre**: il modulo API Generator (`ApiCustomController` +
`CRUDBooster::generateAPI()`). A differenza degli altri moduli, il focus
principale non è il CRUD in sé ma una vulnerabilità seria trovata in
analisi e corretta prima di scrivere i test (dettagli in
[065](refactoring/065-api-generator-rce-e-test.md)): **Remote Code
Execution autenticata**. `postSaveApiCustom()` scrive su disco un vero
controller Laravel (diventa immediatamente un file autoloaded), il cui
sorgente veniva costruito incollando `$table_name`/`$permalink`/
`$method_type` grezzi dentro literal PHP a doppi apici — una virgoletta
nel valore rompeva il literal e permetteva di iniettare PHP arbitrario.
Corretto con `var_export()` (literal a singoli apici, mai interpolato) sia
in creazione sia in modifica (dove è emerso un secondo bug indipendente:
la sintassi speciale `$1`/`\1` della stringa di replacement di
`preg_replace()` — risolto con `preg_replace_callback()`). Sanificato
anche `$controllerName` (nome classe/file del controller generato — un
permalink con `/`/`..` permetteva path traversal nel file scritto).

| Test | Cosa verifica |
|---|---|
| `test_creazione_api_con_valori_pericolosi_in_tabella_e_method_type_vengono_salvati_come_dato_letterale` | **regressione RCE**: `tabel`/`method_type` finiscono nel file generato solo via `var_export()` |
| `test_creazione_api_con_permalink_pericoloso_sanifica_il_nome_controller_e_salva_il_permalink_come_dato` | nome classe/file sanificato, permalink grezzo comunque salvato come dato letterale |
| `test_creazione_api_con_permalink_contenente_slash_non_scrive_file_fuori_da_controllers` | regressione path traversal |
| `test_modifica_api_con_valori_pericolosi_non_inietta_codice_nel_controller_esistente` | stessa regressione RCE, ramo modifica (`preg_replace_callback`) |
| `test_modifica_api_con_permalink_contenente_dollaro_e_backslash_non_viene_alterato_dalla_sostituzione` | regressione specifica del bug `$1`/`\1` di `preg_replace()` |
| `test_getdeleteapi_su_id_inesistente_non_va_in_crash` | null-safety: prima 500, ora `{"status":0}` |
| `test_creazione_api_con_nome_file_collidente_ma_senza_riga_corrispondente_non_va_in_crash` | null-safety: file orfano su collisione di nome |
| `test_modifica_api_con_permalink_duplicato_viene_rifiutata` | regressione dello swap `redirectBack()` → `redirect(referer)` |
| `test_modifica_api_con_id_senza_controller_associato_viene_rifiutata` | idem |
| `test_getgeneratescreetkey_crea_una_riga_cms_apikey_attiva` | caratterizzazione |
| `test_getstatusapikey_aggiorna_lo_stato_di_una_api_key` | caratterizzazione |
| `test_getstatusapikey_senza_id_o_status_viene_rifiutato` | regressione del fix su `CRUDBooster::valid()` (condiviso con Settings, [064](refactoring/064-settings-bug-e-test-crud.md)) — prima `exit()`, non testabile |
| `test_getdeleteapikey_cancella_la_riga` / `test_getdeleteapikey_su_id_inesistente_ritorna_status_zero` | caratterizzazione |
| `test_getscreetkey_mostra_le_api_key_esistenti` | caratterizzazione |
| `test_getindex_nega_accesso_a_non_superadmin` / `test_getgenerator_nega_accesso_a_non_superadmin` / `test_geteditapi_nega_accesso_a_non_superadmin` | controlli di accesso già esistenti (non un gap, a differenza degli altri metodi del controller) |
| `test_caratterizzazione_postsaveapicustom_e_raggiungibile_da_utente_senza_alcun_permesso` | **caratterizzazione di un gap noto, non un fix**: nessun controllo di privilegio su `postSaveApiCustom()` — vedi backlog in [`refactoring/README.md`](refactoring/README.md) |
| `test_creazione_api_salva_i_parametri_e_le_risposte_configurate` | persistenza: `parameters`/`responses` salvati esattamente come inviati dal form — il comportamento a runtime di questa configurazione è coperto da [`ApiExecuteTest.php`](#testsfeatureapiexecutetestphp) sotto |

**Dipendenze/scelte note**:
- A differenza di Settings (che scrive in `public/storage/`), qui i file
  generati/modificati finiscono **dentro l'albero sorgente vero e
  proprio** (`app/Http/Controllers/`, tracciato da git). Ogni test traccia
  i file che crea, ripuliti in `tearDown()` più uno sweep di sicurezza su
  qualunque `*PhpunitTest*.php` rimasto (tutti i permalink/nomi usati nel
  file contengono "PhpunitTest" per essere intercettati).
- `generateAPI()` cerca il controller "genitore" del modulo via
  `cms_moduls.table_name` (bug null-safety pre-esistente, non toccato in
  questo intervento, se la riga manca) — i test seminano sempre quella
  riga per non inciamparci mentre testano altro.
- **Non verificato che l'API generata sia poi effettivamente richiamabile
  end-to-end tramite la rotta dinamica**: `generateAPI()` costruisce lo
  `use` statement del controller "figlio" del modulo (es.
  `AdminTenantsController`) assumendo o `App\Http\Controllers\` (root,
  corretto solo per tabelle `mg_*`/Module Generator) o
  `crocodicstudio\crudbooster\controllers\` — quest'ultimo risolve solo
  per le 5 classi "motore" aliasate in
  `app/Support/legacy_crudbooster_aliases.php` (`ApiController` inclusa,
  **non** i 52 controller "schermata" come `AdminTenantsController`, oggi
  sotto `App\Http\Controllers\System\`). Per una tabella di sistema
  (non-`mg_`) il file generato non è quindi caricabile. La logica di
  `execute_api()` in sé (parametri/risposte/azione) **è** invece coperta,
  bypassando la generazione file — vedi
  [`ApiExecuteTest.php`](#testsfeatureapiexecutetestphp) sotto e
  [066](refactoring/066-api-execute-null-safety-e-test.md).

## `tests/Feature/ApiExecuteTest.php`

**Cosa copre**: `ApiController::execute_api()` — il metodo che gestisce
davvero una chiamata a una API generata dal modulo API Generator, letto da
`cms_apicustom` **per `permalink` a ogni richiesta** (parametri/risposte/
azione/where non sono mai "cotti" nel controller generato). A differenza
di `ApiCustomCrudTest.php` (che copre la creazione/generazione), qui si
registra in `setUp()` una rotta ad-hoc che istanzia `ApiController`
direttamente, bypassando del tutto `generateAPI()`/il routing dinamico —
vedi la nota sopra sul perché quella catena non è affidabile per una
tabella di sistema, e [066](refactoring/066-api-execute-null-safety-e-test.md)
per il bug null-safety trovato e corretto scrivendo questi test.

| Test | Cosa verifica |
|---|---|
| `test_execute_api_list_rispetta_i_parametri_e_le_risposte_configurate` | un parametro filtra i risultati, solo i campi `used=1` compaiono nella risposta |
| `test_execute_api_detail_rispetta_le_risposte_configurate` | idem — scoperta di comportamento: `detail`, a differenza di `list`, fa il merge dei campi della riga direttamente nel livello superiore della response, non sotto `data` |
| `test_execute_api_senza_header_x_user_viene_rifiutato` | `ApiController::login()`: richiede l'header `X-user` con l'email di un `cms_users` attivo |
| `test_execute_api_rifiuta_il_metodo_http_non_configurato` | `method_type` configurato vs verbo HTTP della richiesta |
| `test_execute_api_su_permalink_inesistente_non_va_in_crash` | **regressione**: `$row_api` veniva dereferenziato prima del controllo di esistenza — prima 500, ora messaggio d'errore gestito |

**Dipendenze/scelte note**:
- Questi test **non** passano dal middleware `CBAuthAPI`/
  `CRUDBooster::authAPI()` (token+timestamp+user agent) — la rotta ad-hoc
  non lo applica deliberatamente, per isolare la logica di `execute_api()`
  da quel layer. Non ancora testato — vedi backlog in
  [`refactoring/README.md`](refactoring/README.md) (stessa voce sulla
  modernizzazione dell'autenticazione API).
- La rotta ad-hoc istanzia una sottoclasse anonima di `ApiController` che
  dichiara la proprietà `$controller` (assente sulla classe base —
  impostarla dall'esterno senza dichiararla creerebbe una proprietà
  dinamica, deprecata in PHP 8.2+), valorizzata con un vero
  `SettingsController` — necessario perché `execute_api()` lo usa per
  `cbInit()`/`ModuleHelper::can_view()` sul ramo `detail` anche per un
  attore superadmin.
- Non verificati `aksi` diversi da `list`/`detail` (`insert`/`update`/
  `delete`, visti nel file ma non testati) né i singoli `type` di
  parametro oltre al caso base (`exists`, `unique`, `date_format`, ecc.) —
  fuori scope di questo intervento.

## `tests/Feature/StatisticBuilderCrudTest.php`

**Cosa copre**: il modulo Statistic Builder (`StatisticBuilderController` —
dashboard BI con widget configurabili: Small Box, Table, Chart Area/Bar/
Line, Qlik). Ha fatto emergere e corretto una vulnerabilità più grave di
quella di API Generator ([065](refactoring/065-api-generator-rce-e-test.md)):
`postSaveComponent()` — l'unico punto di scrittura di `config`, compresa
la chiave `sql` che 6 dei 10 tipi di widget eseguono letteralmente via
`DB::select()` in fase di rendering — non aveva **nessun** controllo di
privilegio: qualunque utente autenticato poteva scrivere SQL arbitrario in
un widget di qualunque dashboard esistente ed eseguirlo contro il DB reale
alla semplice visualizzazione di quella pagina. Dettagli in
[067](refactoring/067-statistic-builder-sql-arbitrario-e-test.md).

| Test | Cosa verifica |
|---|---|
| `test_postsavecomponent_nega_accesso_a_non_superadmin` | **regressione del fix**: un non-superadmin non scrive più `config` |
| `test_postsavecomponent_riesce_per_un_superadmin` | l'uso legittimo (superadmin) continua a funzionare |
| `test_lista_mostra_le_dashboard_esistenti` | caratterizzazione CRUD base |
| `test_creazione_dashboard_genera_slug_da_name` | `hook_before_add()`: slug da `str_slug($name)` |
| `test_modifica_dashboard_non_rigenera_lo_slug` | **caratterizzazione, non un bug**: lo slug è il permalink pubblico, `hook_before_edit()` è vuoto apposta |
| `test_cancellazione_dashboard_riesce` | `cms_statistics` non ha `deleted_at`: DELETE fisica |
| `test_getshow_su_slug_inesistente_reindirizza` | redirect gestito (già presente) |
| `test_getshow_con_layout_assegnato_risolve_code_layout` | layout da `dashboard_layouts` |
| `test_getshow_senza_layout_usa_griglia_di_default` | griglia di default a 9 aree |
| `test_getbuilder_nega_accesso_a_non_superadmin` / `test_geteditcomponent_nega_accesso_a_non_superadmin` | controlli di accesso già esistenti (non un gap) |
| `test_caratterizzazione_postaddcomponent_e_raggiungibile_da_utente_senza_permessi` | **caratterizzazione di un gap noto, non un fix** — vedi backlog in [`refactoring/README.md`](refactoring/README.md) |
| `test_caratterizzazione_postupdateareacomponent_e_raggiungibile_da_utente_senza_permessi` | idem |
| `test_caratterizzazione_getlistcomponent_e_raggiungibile_da_utente_senza_permessi` | idem |

**Dipendenze/scelte note**:
- Verificato e scartato come falso allarme: `component_name` (usato senza
  sanificazione in `view('...components.'.$component_name)`) sembrava un
  possibile path traversal — `Illuminate\View\FileViewFinder` converte
  ogni `.` in `/` su tutta la stringa, quindi un `..` letterale non
  sopravvive mai come vera risalita di directory.
- Deliberatamente non testato: il rendering end-to-end di un widget SQL
  configurato correttamente (proverebbe che il fix non ha rotto l'uso
  legittimo, ma richiede di renderizzare Blade reali con dipendenze
  esterne — rimandato, deciso con l'utente).
- Bug null-safety su `getBuilder()`/`getEditComponent()`/`getViewComponent()`
  con un id/componentID inesistente (crash 500) trovati in analisi ma non
  corretti — i test sui controlli di accesso usano sempre un id/componentID
  reale per non sfiorare quel path (il check `isSuperadmin()` gira comunque
  prima di qualunque dereferenziazione pericolosa).

## `tests/Feature/ModuleGeneratorCrudTest.php`

**Cosa copre**: il modulo Module Generator (`ModulsController` +
`CRUDBooster::generateController()` — il wizard a 5 step che genera i
controller CRUD dei moduli custom). Ha fatto emergere e corretto **3 RCE
indipendenti + 1 SQL injection vera**, stessa classe di vulnerabilità di
API Generator ([065](refactoring/065-api-generator-rce-e-test.md)) ma in
più punti: `CRUDBooster::generateController()`, `postStep3()` (colonne) e
`postStep5()` (configurazione) incollavano input utente grezzo dentro il
sorgente PHP di un controller scritto su disco e autoloaded — risolto con
`var_export()`/whitelist. `Schema::getIndexes()` di Laravel (chiamata da
`CB::pk()` dentro `generateController()`) usa una `quoteString()` **non
parametrizzata** — lo stesso valore non sanificato era quindi anche SQL
injection reale, non solo un'iniezione PHP. Dettagli in
[068](refactoring/068-module-generator-rce-e-test.md).

| Test | Cosa verifica |
|---|---|
| `test_step1_con_nome_tabella_pericoloso_lo_salva_come_dato_letterale_nel_controller_generato` | **regressione**: `table_name` pericoloso sanificato (`sql_name_encode()`) prima di raggiungere sia il sorgente generato sia `Schema::getIndexes()` |
| `test_step1_con_nome_contenente_slash_sanifica_il_controller_e_non_scrive_file_fuori_da_controllers` | **regressione path traversal**: nome controller sanificato a `[A-Za-z0-9_]` |
| `test_step3_con_valori_pericolosi_nelle_colonne_li_salva_come_dato_letterale` | **regressione RCE**: label/name/join/width/callback_php/query pericolosi via `var_export()` |
| `test_step3_scrive_il_flag_download_quando_richiesto` | **regressione bug**: flag "download" (controllava una variabile mai definita) ora scritto |
| `test_step3_nega_accesso_a_non_superadmin_anche_con_pieno_accesso_al_modulo` | **regressione privilegio**: `isSuperadmin()` aggiunto, non bypassabile con soli permessi CRUD sul modulo |
| `test_step5_con_valori_pericolosi_li_salva_come_dato_letterale_e_forza_sempre_table` | **regressione RCE**: title_field/orderby pericolosi via `var_export()`, `table` sempre dal valore reale in DB |
| `test_step5_ignora_le_chiavi_post_non_previste_dal_form` | **regressione whitelist**: una chiave POST non prevista (es. proprietà arbitraria) non finisce più nel file |
| `test_step5_nega_accesso_a_non_superadmin_anche_con_pieno_accesso_al_modulo` | **regressione privilegio**: prima non c'era ALCUN controllo su questo metodo |
| `test_getdelete_nega_cancellazione_di_un_modulo_protetto_anche_via_id_diretto` | **regressione**: `is_protected` ricontrollato anche bypassando il filtro della index list |
| `test_getdelete_su_id_inesistente_non_va_in_crash` | **regressione null-safety** |
| `test_getdelete_cancella_un_modulo_non_protetto` | nessuna regressione sul comportamento esistente |
| `test_gettablecolumns_nega_accesso_senza_privilegio_di_visualizzazione` / `test_getcheckslug_nega_accesso_senza_privilegio_di_visualizzazione` | **regressione**: prima nessun controllo, schema di qualunque tabella esposto a chiunque loggato |
| `test_gettablecolumns_restituisce_le_colonne_con_privilegio` / `test_getcheckslug_conta_gli_slug_esistenti_con_privilegio` | l'uso legittimo (con privilegio) continua a funzionare |

**Dipendenze/scelte note**:
- Come `ApiCustomCrudTest.php`, i file generati/modificati finiscono
  dentro `app/Http/Controllers/` (tracciato da git): ogni test traccia i
  file toccati in `$fixtureFiles`, ripuliti in `tearDown()` insieme a uno
  sweep su qualunque `*PhpunitTest*.php` rimasto.
- Bug indipendente corretto per far passare i test: `add_log_ch()`
  (`app/Helpers/functions.php`) leggeva `$_SERVER['REMOTE_ADDR']`/
  `HTTP_USER_AGENT` senza fallback — crash `Undefined array key` in un
  contesto senza quelle chiavi (richieste HTTP simulate). Corretto con
  `?? null`.
- Deliberatamente non estesi a `isSuperadmin()`: `getStep1`-`postStep2`/
  `getStep4` restano protetti dal solo `isView()` — vedi backlog in
  [`refactoring/README.md`](refactoring/README.md).

## `tests/Feature/CrossModuleRelationsTest.php`

**Cosa copre**: le relazioni TRA i 4 moduli sopra (non il CRUD interno
di ciascuno), in particolare l'isolamento per tenant nelle liste admin
per un attore non superadmin. L'isolamento **non** vive in una `WHERE`
nella query di `CBController::getIndex()` (quella riga esiste ma è
commentata) ma riga-per-riga dopo il fetch, in `ModuleHelper::
can_view()`.

| Test | Cosa verifica |
|---|---|
| `test_lista_utenti_isola_per_tenant_un_tenantadmin` | un tenantadmin non vede utenti di un tenant diverso dal proprio |
| `test_lista_tenants_nessun_tenant_visibile_per_un_tenantadmin` | **caratterizzazione di un gap**: nessun ramo per la tabella `tenants` in `can_view()`/`get_tenant_id()` → un non-superadmin non vede nessun tenant in lista, nemmeno il proprio (non un crash, solo lista vuota) — vedi [060](refactoring/060-groups-can-view-crash-standard-e-tenants-list-vuota.md), non corretto |
| `test_lista_gruppi_tenantadmin_vede_solo_i_gruppi_del_proprio_tenant` | isolamento Groups per tenantadmin (filtro SQL + `can_view()` ridondanti) |
| `test_lista_gruppi_standard_vede_solo_i_gruppi_del_proprio_tenant` | isolamento Groups per uno Standard (solo `can_view()`, nessun filtro SQL) — prima del fix in [060](refactoring/060-groups-can-view-crash-standard-e-tenants-list-vuota.md) crashava con 500 |
| `test_cancellazione_privilegio_in_uso_riesce_e_utente_orfano_non_ha_piu_permessi_speciali` | comportamento già deciso in 044/046/047: nessun guard "in uso", l'utente orfano perde solo i permessi speciali, nessun crash |
| `test_cancellazione_tenant_con_gruppo_associato_ma_senza_utenti_riesce_e_lascia_association_orfana` | `AdminTenantsController::hook_before_delete()` controlla solo gli utenti membri, non `group_tenants`: riesce e lascia una riga orfana |
| `test_modifica_privilegio_utente_non_tocca_lappartenenza_ai_gruppi` | cambiare `id_cms_privileges` non innesca la logica di `hook_before_edit()` che gestisce cambi di tenant/gruppo primario |
| `test_creazione_gruppi_in_tenant_diversi_non_si_mescolano` | `Group::add_tenant()` associa il nuovo gruppo solo al tenant di chi lo crea |

**Scoperto scrivendo questo test** (corretto, vedi
[060](refactoring/060-groups-can-view-crash-standard-e-tenants-list-vuota.md)):
`ModuleHelper::get_group_id()`/`get_tenant_id()`, ramo `groups`,
crashavano con un 500 per un attore Standard che lista `/admin/groups`
con gruppi di più di un tenant (query sbagliata + nessun controllo di
null prima di dereferenziare `first()`).

**Dipendenze/scelte note**:
- `SeedsCmsData::actingAsTenantUser()` (nuovo helper): autentica il
  test come Tenantadmin/Standard, popolando `cms_privileges_roles` e la
  sessione `admin_privileges_roles` con la stessa forma prodotta da
  `AdminController::postLogin()` — necessario perché `CRUDBooster::
  isView()`/`isRead()` per un non superadmin leggono da lì.

## `tests/Unit/Services/ConnectorServiceTest.php`

**Cosa copre**: `App\Services\ConnectorService` — il client verso il license
server esterno (auth, scrittura/lettura del fallback locale `license.json`,
validazione della licenza). Test di **caratterizzazione**, scritti come
parte dell'intervento [005](refactoring/005-connectorservice-cleanup.md).
Manipola direttamente `storage/app/license.json` (salvato/ripristinato in
`setUp()`/`tearDown()`) perché la classe mischia `Storage::disk('license')`
in scrittura e `file_get_contents()` diretto in lettura — `Storage::fake()`
non avrebbe intercettato quest'ultima. Le chiamate HTTP verso il license
server sono sempre fake (`Http::fake()`), nessuna richiesta di rete reale.

| Test | Cosa verifica |
|---|---|
| `test_usa_il_token_in_cache_senza_chiamare_auth_login` | token già in cache → nessuna chiamata a `/auth/login`, usato come Bearer |
| `test_senza_token_in_cache_chiama_auth_login_e_lo_mette_in_cache` | cache vuota → login, token restituito messo in cache |
| `test_auth_login_con_risposta_di_fallimento_lancia_AuthException` | risposta non-ok dal login → `AuthException` con il messaggio del server |
| `test_login_irraggiungibile_non_va_in_crash_ma_disattiva_il_token` | eccezione di rete su `/auth/login` → nessun crash (bug corretto in 005), token nullo gestito a valle |
| `test_writeLicense_scrive_il_file_locale_quando_il_server_risponde_con_un_id` | risposta con `data.id` → `license.json` scritto |
| `test_writeLicense_ritorna_false_se_la_risposta_non_ha_un_id` | risposta senza id → `false`, nessun file scritto |
| `test_writeLicense_ritorna_false_se_la_richiesta_fallisce` | eccezione di rete → `false` |
| `test_getLicense_legge_il_file_locale_se_presente` | file valido presente → contenuto restituito |
| `test_getLicense_ritorna_false_se_il_file_contiene_json_non_valido` | file corrotto → `false` |
| `test_getLicense_se_il_file_manca_prova_a_riscriverlo_dal_server` | file assente → auto-riscrittura riuscita dal server → restituito |
| `test_getLicense_ritorna_false_se_il_file_manca_e_il_server_non_lo_ricrea` | file assente, auto-riscrittura fallita → `false` |
| `test_validateLicense_true_se_licenza_attiva_e_path_dominio_combaciano` | caso base positivo |
| `test_validateLicense_false_se_status_non_active` | status diverso da `active` → `false` |
| `test_validateLicense_false_se_tenants_number_insufficiente` | quota tenant insufficiente → `false` |
| `test_validateLicense_false_se_clients_number_insufficiente` | quota client insufficiente → `false` |
| `test_validateLicense_false_se_path_esplicito_non_combacia` | `path` esplicito diverso → `false` |
| `test_validateLicense_false_se_nessun_dominio_combacia` | dominio licenza diverso sia dall'esplicito (assente) sia da `env('APP_DOMAIN')` → `false` |
| `test_validateLicense_domain_esplicito_che_non_combacia_ricade_su_env_app_domain` | **pinna un quirk non ovvio**: `$data['domain']` esplicito che non combacia NON fa fallire subito, ricade sul confronto con `env('APP_DOMAIN')` — vedi commento su `licenseMatchesDomain()` nel codice |
| `test_validateLicense_false_e_ripulisce_la_riga_license_se_manca_il_file` | nessuna licenza valida → `false` **e** riga corrispondente cancellata da `DB::table('license')` |

**Dipendenze/scelte note**:
- La tabella `license` non aveva mai avuto una migration (creata
  manualmente nei DB reali in un momento imprecisato) — aggiunta in questo
  stesso intervento (`database/migrations/2026_08_27_000000_create_license_table.php`,
  guardata con `Schema::hasTable()` per non toccare i DB dove esiste già).
- **Non ancora eseguiti**: questi test sono stati scritti e verificati solo
  con `php -l` (sintassi) e lettura manuale — non è stata lanciata la suite
  PHPUnit (regola esplicita dell'utente, vedi memoria). Da confermare verdi
  con `php artisan test --filter=ConnectorServiceTest` alla prima occasione.

## Helper di test condivisi

- `tests/Concerns/SeedsCmsData.php` — trait con i metodi di seeding minimi
  validi per `tenants`/`groups`/`cms_privileges`/`cms_users` (i campi NOT
  NULL senza default in questo schema legacy vanno sempre passati
  esplicitamente: `tenants.domain_name`, `cms_users.tenant`/
  `primary_group`, ecc.). Usato da `LoginTest`, `CBBackendTest`,
  `LogoutTest`.
- `tests/Concerns/LogsInAdmin.php` — trait con `postLoginFrom()` (simula
  `POST /admin/login` da un dominio specifico, gestendo le due
  particolarità di testabilità di `postLogin()` documentate nel file).
  Usato da `LoginTest` e `LogoutTest`.

---

## Infrastruttura di test (non specifica a un file)

- **Database**: MySQL vero (non SQLite), stesso motore della produzione —
  scelta deliberata per un progetto già in produzione con clienti reali
  (vedi motivazione discussa in sessione). DB dedicato `customerhive_testing`,
  separato da quello di sviluppo, creato automaticamente da
  `docker/entrypoint.sh` in locale e da un service MySQL nella CI.
- **Bug corretto durante questo lavoro**: `phpunit.xml` impostava
  `CACHE_STORE=array`, ma questa app (Laravel 9) legge `CACHE_DRIVER`
  (`CACHE_STORE` è la chiave usata da Laravel 11+) — i test stavano quindi
  usando silenziosamente la cache su file, condivisa con qualunque altro
  processo dell'app sullo stesso filesystem, causa di comportamento non
  riproducibile tra run. Corretto in `phpunit.xml`.
- **Bug più serio corretto in seguito** (vedi
  [002](refactoring/002-cbbackend-guard-fase-3.md) per il dettaglio
  completo): `docker-compose.yml` forzava `DB_DATABASE`/`DB_USERNAME`/
  `DB_PASSWORD` a livello di container per il servizio `app` — queste,
  essendo già presenti nell'ambiente di processo, bloccavano
  silenziosamente l'override di `.env.testing` (dotenv non sovrascrive
  variabili già impostate). I test lanciati in locale via `docker compose
  exec` stavano quindi girando sul **database di sviluppo vero**
  (`customerhive`) invece che su `customerhive_testing`, svuotandolo ad
  ogni run. La CI su GitHub Actions non ne era affetta (non passa da
  `docker-compose.yml`). Corretto rimuovendo quelle tre variabili dal
  blocco `environment:` del servizio `app`.
