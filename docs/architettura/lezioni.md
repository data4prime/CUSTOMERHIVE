# Lezioni apprese dagli interventi

> Letto da `/architect` e `ch-architect-analyst` all'inizio di ogni analisi.
> Una voce per lezione, **breve** (3-5 righe), più recenti in alto. Si
> aggiorna a fine batch con il post-mortem (vedi
> `.claude/rules/agenti-post-intervento.md`). Non è un diario: solo ciò che
> cambia il modo di proporre o di implementare la prossima volta.

## Formato di una voce

```
### AAAA-MM-GG — titolo breve (doc NNN)
- **Piano prevedeva:** …
- **È successo:** …
- **Sfuggito al reviewer / ai test:** … (o "nulla")
- **Regola per la prossima volta:** …
```

## Voci

### 2026-10-30 — Ultimi 7 modelli in `App\Models` (doc 339, piano 339)
- **Piano prevedeva:** test prima (alias, tabelle, buco `QlikSyncRun`, `add_log_ch`, `target_layout`, Blade),
  spostamento a gruppi (`AccessLog`/`Log`, Qlik sync, `ChatAIConf`, `QlikItem`, `Menu` per ultimo), rimozione dei
  `use App\Models\...` diventati ridondanti, nessuna migration.
- **È successo:** come previsto (prima 7 alias rossi e 24 verdi, dopo tutto verde; regressione 206 + 145 = 351
  verdi, Larastan pulito da cache vuota). Il piano ha trovato nel codice la prova che serve l'alias:
  `AdminOrdiniController` (controller di un cliente) cita `\App\Menu`. Un percorso senza rete di
  `QlikSyncService` (`requestCancel` con `cancel_delete`) ha permesso di coprire in parte il buco Qlik.
- **Sfuggito al reviewer / ai test:** nessun reviewer lanciato (solo namespace). `step()`/`upsertApp`/`upsertItem`
  di `QlikSyncService` (30+ usi) non testabili senza Qlik: solo `php -l` e Larastan. Il test delle Blade passa
  anche col nome vecchio (alias): non prova l'aggiornamento della riga 36.
- **Regola per la prossima volta:** (1) un test che passa per l'alias non dimostra che il riferimento sia stato
  aggiornato: la prova è la ricerca finale a testo, non il test; (2) i regressioni che condividono il DB di
  test vanno lanciate in gruppi **sequenziali**, non in parallelo; (3) alla fine di un cluster di batch di
  spostamento, cercare i controller **per cliente** nel repo (qui `AdminOrdiniController`): sono l'unica
  evidenza concreta che gli alias servono, e vanno citati nella mappa per non toglierli presto.

### 2026-10-30 — Pivot e `Modules` in `App\Models` (doc 338, piano 338)
- **Piano prevedeva:** test prima (alias, tabelle, relazioni dei tre modelli rimasti, Blade), spostamento
  dei 9 modelli a gruppi, `use` espliciti in `Menu`/`QlikItem`/`ChatAIConf`, 4 import mai usati rimossi,
  nessuna migration.
- **È successo:** come previsto (prima 9 alias rossi e 13 verdi, dopo 22/22 e 156 con la regressione,
  Larastan da cache vuota pulito). Lo spostamento è stato fatto in un solo passaggio invece che a gruppi,
  con lo stesso esito. Il test Blade era rosso per un errore del test (mancava `registerAdminModules`),
  corretto prima di spostare. Ricerca finale rifatta indipendentemente: nessun vecchio nome residuo.
- **Sfuggito al reviewer / ai test:** nessun reviewer lanciato (solo namespace). `ChatAIPagesAndContentViewTest` e
  `ChatAISendMessageAgentTest` (toccano `chatai_groups_allowed`) non erano nell'elenco del piano, ma
  `/refactor-verify` su 23 file li ha poi eseguiti: 319 verdi. Le 8 chiamate `GroupTenants` di `ModuleHelper`
  restano coperte solo indirettamente.
- **Regola per la prossima volta:** (1) nell'elenco dei test di regressione del piano includere **tutti**
  i test che toccano le tabelle del modello (`Select-String` sui nomi di tabella in `tests/`), non solo
  quelli per area; (2) per i modelli che usano questi dopo (`Menu`, `QlikItem`, `ChatAIConf`) ricordare che
  i `use` espliciti vanno tolti quando si spostano; (3) un import mai usato si rimuove, non si migra.

### 2026-10-30 — Cluster `Tenant`/`Group`/`Role`/`UsersGroup` in `App\Models` (doc 337, piano 337)
- **Piano prevedeva:** test prima (alias, tabelle, soft delete, relazioni dei modelli rimasti in `App\`,
  `add_tenant`), spostamento in ordine `Role`, `UsersGroup`, `Tenant`, `Group`, alias, `use` espliciti nei
  tre modelli che usavano `Tenant`/`Group` senza `use`, nessuna migration.
- **È successo:** come previsto (prima 4 rossi attesi e 4 verdi, dopo 8 verdi, Larastan pulito). I test hanno
  però corretto due ipotesi del piano: `add_tenant()` non è idempotente (la seconda chiamata lancia l'unique,
  non "non duplica") e `Tenant::delete()` fallisce (niente `updated_at`): fotografati, non cambiati. Un
  `@dataProvider` come annotazione non gira con questo PHPUnit: serve `#[DataProvider]`. Il `sed` inline con
  backslash ha di nuovo prodotto `AppModels` (2 file, corretti con Edit).
- **Sfuggito al reviewer / ai test:** nulla di noto; i test esistenti e il rendering delle 4 viste Blade non
  sono stati eseguiti in sessione (li lancia la persona).
- **Regola per la prossima volta:** (1) per i namespace usare sempre Edit, mai `sed` inline; (2) prima di
  spostare un modello cercare chi lo usa **senza `use`** (stesso namespace): `grep` su `Nome::class`,
  `Nome::`, `new Nome`, non solo su `App\Nome`; (3) scrivendo un test di caratterizzazione, verificare le
  ipotesi del piano con il test, non fidarsi del docblock del codice.

### 2026-10-30 — `User` in `App\Models` con alias e migration (doc 336, piano 336)
- **Piano prevedeva:** test prima (alias, migration, token vecchio e nuovo, guard `web`), file spostato
  con solo namespace e `use`, alias nel file esistente, migration dei `tokenable_type`, riferimenti del
  repo aggiornati.
- **È successo:** tutto come previsto sul piano (sul codice vecchio 1 verde e 6 rossi attesi; dopo 109
  verdi con la regressione, Larastan pulito). Ma la sostituzione dei riferimenti con un `sed` **inline**
  ha scritto `AppModelsSER` al posto di `App\Models\User` in 35 file: il Bash di questo ambiente
  consuma i backslash (`\M` perso, `\U` = maiuscolo in sed). Ripristinato con uno script `sed` scritto
  su file. Un file (`RegisterController`) non era nell'indice di tokensave e il conteggio sembrava
  completo: l'ho trovato leggendo il file.
- **Sfuggito al reviewer / ai test:** nulla di funzionale; nessun reviewer lanciato (nuova regola).
- **Regola per la prossima volta:** (1) mai `sed` inline con backslash: scrivere lo script con Write e
  lanciare `sed -i -f`, oppure Edit file per file; (2) dopo un replace massivo verificare con una
  ricerca **indipendente dall'indice** (PowerShell `Select-String`), perché tokensave può non coprire
  tutti i file; (3) `grep -F 'App\User'` trova anche `App\UsersGroup`: filtrare prima di sostituire;
  (4) con l'alias un riferimento dimenticato al vecchio nome non si vede (funziona): cercarli a
  testo fisso prima di chiudere; (5) `composer dump-autoload` nel container serve dopo aver spostato
  un file di classe, come lo servirà nell'update dei clienti.

### 2026-10-30 — Profilo utente: 4 Form Request e trait condiviso (doc 335, piano 334)
- **Piano prevedeva:** test di caratterizzazione prima (Qlik non ne aveva), quattro Form Request
  iniettati dalla firma con un trait per 403/422 JSON, `EmailStart`/`PasswordStart` fuori perché il
  429 precede la validazione.
- **È successo:** nessuno scostamento. 16 test nuovi verdi al primo run sul codice vecchio e di nuovo
  dopo; 47 verdi con la regressione. La bozza del piano esisteva già da prima della sessione e l'ho
  trovata solo a metà analisi (avevo contato 7 endpoint, erano 6 `Validator::make` e 4 nel batch).
  Nessun reviewer lanciato (nuova regola: solo per modifiche sostanziali).
- **Sfuggito al reviewer / ai test:** nulla emerso; non provato l'upload di una foto reale (solo un
  PDF rifiutato).
- **Regola per la prossima volta:** (1) prima di analizzare un'area, cercare in `docs/refactoring/piani/`
  se esiste già una bozza di piano: evita di rifare l'analisi e di dire cifre sbagliate; (2) con
  `routeController` i parametri del metodo non cambiano gli URL (wildcard fisse), quindi un Form
  Request nella firma di un `post*` è sicuro; (3) un Form Request che inverte l'ordine di due
  controlli (qui rate limit e validazione) cambia la risposta su un flusso di sicurezza: se
  l'ordine conta, il metodo resta fuori dal batch.

### 2026-10-30 — Module Generator: import/export in due Service (doc 334, piano 335)
- **Piano prevedeva:** prima i test di caratterizzazione sul codice vecchio (import/export non
  ne avevano), poi due Service con il codice dei privati spostato identico, testi in `trans()`
  (D1, cambio visibile solo per l'inglese), controller ridotto a glue HTTP.
- **È successo:** nessuno scostamento. Al primo run i 22 test erano 21 verdi + 1 rosso atteso
  (le chiavi i18n non esistevano ancora); dopo il refactoring 106 verdi con la regressione.
  Le ipotesi dichiarate dal senior dev (redirect `back()`, autoload dei controller generati,
  `int(11)` dipendente da MySQL) hanno retto. Il reviewer ha confrontato i privati riga per
  riga: nessun bloccante.
- **Sfuggito al reviewer / ai test:** nulla di funzionale. Il reviewer ha trovato solo
  documentazione del test diventata falsa (docblock "rosso fino al passo 3" e un `#[Group]`
  rimasti dopo il passo 3). Restano lacune dichiarate: nessun test sul fallimento a metà
  dell'import (non transazionale, come prima), tipi di colonna `mediumtext`/sconosciuti coperti
  solo in export, `getImport()` e file non valido non coperti.
- **Regola per la prossima volta:** (1) per codice senza test, scrivere i test **ed eseguirli sul
  codice vecchio** prima di spostare nulla: è costato ~8 minuti e ha reso il refactoring
  verificabile (verde al primo colpo); (2) un test "rosso fino al passo N" porta docblock e
  attributo obsoleti quando passa: metterne la pulizia nel piano come passo del passo N;
  (3) i test che fanno DDL (`Schema::create`) rompono il rollback di `RefreshDatabase`: snapshot
  degli id massimi in `setUp`, drop delle tabelle e cancellazione dei file generati nel
  `tearDown`; (4) **prima di numerare un piano guardare anche `docs/refactoring/piani/`**, non
  solo il README: la bozza 334 (profilo utente) occupava già il numero, quindi piano 335 e doc 334
  non coincidono; (5) anche con il codice spostato identico Pint impone 4 spazi e `new X()`: il
  diff dei file nuovi non sarà "identico" al carattere, e va detto al reviewer.

### 2026-10-30 — ChatAI: Form Request per `send_message*` (doc 333 = batch 2 del piano 332)
- **Piano prevedeva:** due Form Request iniettati dalla firma (non `validateCurrent()`), rifiuto
  `denied_access` identico (JSON, stato 200), dati non validi con JSON 422 e chiave nuova (D2);
  i test esistenti sull'accesso "verdi senza modifiche".
- **È successo:** il piano si sbagliava sul "senza modifiche": il `denied_access` ora esce come
  `Response` JSON e non come `echo`, e l'helper di `ChatAISendMessageAgentTest` leggeva solo il
  buffer: i suoi casi 1-4 sarebbero stati rossi. Lo ha visto il reviewer **leggendo**, prima
  dell'esecuzione; corretto l'helper. Larastan da cache vuota senza errori (l'iniezione dalla
  firma non è `app()`). Tolto un `use Request;` rimasto inutilizzato. Test eseguiti: 58 verdi
  al primo run (10 nuovi + 48 di regressione).
- **Sfuggito al reviewer / ai test:** nulla. Un rilievo del reviewer sul "messaggio trimmato"
  non è stato verificato e probabilmente è un falso positivo (stesso input prima e dopo).
  Restano lacune dichiarate: nessun test con body JSON, con soli spazi o numerico.
- **Regola per la prossima volta:** (1) cambiare **come** esce una risposta (da `echo` a
  `Response`) rompe i test che la leggono dal buffer, anche se il corpo e lo stato sono identici:
  cercare nei test esistenti chi legge quell'endpoint prima di dire "restano verdi"; (2) per
  i metodi custom non override di `CBController` il Form Request si inietta dalla firma, dopo
  aver controllato che `routeController` non li invochi senza container; (3) dopo l'esperienza
  del batch 1 (17 rossi al primo run), eseguire i test prima di dare un batch per chiuso rimane
  la verifica che la lettura non sostituisce, anche quando il reviewer non trova nulla.

### 2026-10-30 — ChatAI: pulizia, i18n di `access`/`tenant`, `content_view` locale (doc 332 batch 1, piano 332)
- **Piano prevedeva:** use inutilizzati e hook, metodo privato per le due pagine gemelle con
  tutti i testi in `trans()`, `content_view` ripulito solo in locale (D1), test di
  caratterizzazione prima; batch 2 (Form Request) rimandato.
- **È successo:** due ipotesi del piano si sono ridimensionate leggendo il codice: le viste
  non usano `$chat_ai`, quindi con una conf inesistente la pagina non va in errore (la
  guardia ipotizzata non serviva); `tenant.blade.php` non era la gemella perfetta di
  `access.blade.php` (un `<h4>` in inglese in più, tradotto). Il rischio principale (tradurre
  `datamodal_columns_alias`) è stato chiuso leggendo il browser del datamodal: l'alias è solo
  l'intestazione di colonna. Il reviewer non ha trovato bloccanti; ha chiesto di dichiarare nel
  test il comportamento probabilmente scorretto dei soft-deleted e di riscrivere al presente
  i commenti che raccontavano il processo.
- **Sfuggito al reviewer / ai test:** **17 test su 19 rossi al primo run**, per due difetti del
  setup del test nuovo che né il senior dev né il reviewer avevano visto leggendo: la licenza
  simulata aveva solo `modules`, ma le pagine che rendono l'admin template completo includono
  `license_modal.blade.php`, che legge `domain` e altre chiavi; e la conf non aveva `url`/`token`
  validi, mentre `chat_ai.view` chiama `getToken()`. Corretti i due setup: 19/19, e 70 verdi con
  la regressione. Nessuna instabilità emersa (una sola run).
- **Regola per la prossima volta:** (1) il client di test di Laravel non popola `$_GET`: un
  codice che lo legge direttamente (qui `content_view`) è invisibile ai test HTTP; passare a
  `request()->query` lo rende testabile con la sola URL; (2) un'affermazione del piano come
  "la vista è la gemella dell'altra" va verificata riga per riga prima di basarci un
  refactoring (qui ne è uscito un testo non tradotto); (3) prima di tradurre un valore che
  finisce in un datamodal/JS, leggere chi lo consuma: un'etichetta e un identificatore si
  somigliano; (4) un test che fissa un comportamento probabilmente scorretto lo dichiara nel
  nome e nel docblock, e dice che va rovesciato alla correzione; (5) un test che rende una
  **pagina admin intera** (non un redirect o un JSON) richiede un ambiente più completo: la
  licenza simulata con tutte le chiavi del modale e una conf con `url`/`token` validi. Un test
  scritto "alla cieca" su pagine che oggi non hanno test va eseguito prima di darlo per buono:
  la lettura non basta a prevedere questi 500.

### 2026-10-30 — ChatAI: client della chat in un servizio (doc 331, piano 331)
- **Piano prevedeva:** servizio `ChatAIClient` (payload, cURL, lettura risposta) e un
  metodo privato nel controller per il resto; tre decisioni della persona (D1 semantica
  della risposta, D2 nessun timeout, D3 testo di errore tradotto).
- **È successo:** le due `send_message*` non erano copie: l'unica differenza di logica era
  la lettura della risposta, e unificarla era un cambio visibile da decidere, non da
  scegliere in silenzio. Il reviewer ha trovato un solo difetto: i type hint nuovi su
  `send()` (`string $url`, `string $message`) cambiavano il comportamento su input non
  stringa, mentre il codice vecchio non li aveva. Tolti.
- **Sfuggito al reviewer / ai test:** nulla. Test eseguiti su richiesta della persona: 54
  verdi (181 asserzioni, 2 file nuovi + 3 di regressione). Il payload cURL non è
  verificabile senza un server (solo lettura del diff) e la caratterizzazione non è stata
  rieseguita sul codice precedente.
- **Regola per la prossima volta:** (1) estraendo codice in una funzione nuova **non
  aggiungere type hint** che il codice di origine non aveva: sono un cambio di
  comportamento su input anomali, anche se sembrano igiene; (2) quando due metodi
  "quasi uguali" convergono in un servizio, elencare le differenze in una tabella nel
  piano e far decidere alla persona quelle di logica; (3) per codice dietro cURL, che non
  si può simulare, separare la lettura della risposta (`interpret()`) dalla chiamata, così
  la parte con più rami si prova senza rete.

### 2026-10-30 — ChatAI: 5 Action e conf soft-deleted ignorate dalla chat (doc 330, piano 330)
- **Piano prevedeva:** parte 1 refactoring (Action per regola primary e 4 scritture di
  autorizzazione, behavior-preserving) e parte 2 correzione (la chat ignora le conf
  soft-deleted), con test di caratterizzazione scritti prima.
- **È successo:** nessuna regola primary aveva test; il senior dev li ha scritti via HTTP
  (add-save/edit-save), senza fallback. La parte 2 nasce da un'ipotesi emersa **leggendo**
  `ChatAIConf` (`SoftDeletes`) e `send_message()` (`DB::table` non applica lo scope): la
  persona l'ha fatta includere dopo che `MenusController.php:534` confermava `deleted_at`.
  Il reviewer non ha trovato difetti.
- **Sfuggito al reviewer / ai test:** nulla. Test eseguiti su richiesta della persona: 58
  verdi (199 asserzioni, 3 file nuovi + 2 di regressione). La parte 2 resta dedotta: i
  rossi attesi dei casi 1-3 non sono stati osservati sul codice precedente.
- **Regola per la prossima volta:** (1) `DB::table()` su una tabella con modello
  `SoftDeletes` non filtra i cancellati: quando una query raw convive con un modello
  soft-delete, controllare che i due filtrino allo stesso modo; (2) un'ipotesi di bug
  trovata durante un refactoring va tenuta in una **parte separata** del piano, con test
  propri scritti prima, così il refactoring resta verificabile come behavior-preserving;
  (3) per fissare "quale conf è stata usata" senza poter fare finta del cURL, distinguere
  le conf dal tipo di risposta (token valido o no), non dall'URL.

### 2026-10-30 — ChatAI: `send_message_agent` controlla l'`agent_id` (doc 329, piano 329)
- **Piano prevedeva:** correzione minima (due guardie in testa al metodo, risposta
  JSON), 8 test scritti prima, 5 rossi di proposito sul codice vecchio.
- **È successo:** nessuno scostamento. L'analisi del controller ha mostrato che il
  buco non era nell'interfaccia (la pagina passa già da `can_see_item`) ma
  nell'endpoint chiamabile a mano. Il reviewer non ha trovato bloccanti; ha
  chiesto di rafforzare i positivi (assert su `chat_ai_history`), aggiunto dopo.
- **Sfuggito al reviewer / ai test:** nulla. Test eseguiti su richiesta della
  persona: 36 verdi (8 nuovi + `ChatAIAuthorizationTest`), assert aggiunto dopo la
  review compreso. Non si è verificato che i 5 negativi fossero rossi sul codice
  vecchio. Resta aperto `send_message()` (conf `primary` per tutti), senza test.
- **Regola per la prossima volta:** (1) per un endpoint AJAX il rifiuto è un JSON
  nello stesso formato degli altri errori del metodo, non un redirect di
  `CRUDBooster::redirect()` (il `fetch` riceverebbe HTML); (2) un controllo di
  autorizzazione presente solo nella pagina che chiama l'endpoint non protegge
  l'endpoint: per ogni rotta custom cercare dove avviene il controllo, non solo
  chi la chiama; (3) un'asserzione aggiunta dopo la review va comunque eseguita
  prima di chiudere il batch.

### 2026-10-30 — `ApiTokensController`: Action `GenerateApiToken` (doc 328, piano 328)
- **Piano prevedeva:** una sola Action (scadenza + `createToken()`), controller con
  il solo glue HTTP; due ipotesi da verificare prima (utenti soft-deleted nel
  menu, revoca del token alla cancellazione).
- **È successo:** entrambe le ipotesi sono cadute leggendo il codice (`User` non ha
  `SoftDeletes`; `personal_access_tokens` non ha `deleted_at`, quindi la delete di
  `CBController` revoca davvero). Il batch è passato senza scostamenti salvo un
  `?? null` su `expiry_custom_date` (la chiave può mancare in `validated()`).
  Il reviewer ha trovato un solo difetto, nel test: data fissa 2099 su una colonna
  `TIMESTAMP` MySQL (limite 2038). Corretto con una data relativa.
- **Sfuggito al reviewer / ai test:** nulla. Test del batch (nuovo +
  `ApiTokensAddSaveTest`) eseguiti su richiesta della persona: 29 verdi, 122 asserzioni.
- **Regola per la prossima volta:** (1) nei test niente date fisse lontane: usare
  `now()->addDays(n)`, perché le colonne `timestamp` di MySQL finiscono nel 2038;
  (2) un'ipotesi di incoerenza va chiusa leggendo modello/schema prima di scrivere
  il piano, come qui, così il piano non include correzioni inesistenti; (3) in un
  diff su un file già modificato dal batch precedente (qui il 327, non committato)
  il reviewer lo legge insieme: dirlo nel prompt.

### 2026-10-29 — Rotte custom di sistema sotto login (doc 324, piano 324)
- **Piano prevedeva:** `CBBackend` sulle rotte custom di `web.php` e su
  `api-documentation`, controllo `isSuperadmin` in `saveEnable`; test scritti
  prima per il comportamento obiettivo (rossi di proposito sul codice vecchio).
- **È successo:** primo run 21 rossi/21 verdi: 20 rossi attesi e 1 falso rosso
  (`AdminCmsUsersController::cbInit()` legge `$_SERVER['REQUEST_URI']`, il client
  di test non lo imposta). Dopo la modifica 42/42. La correzione di un bug
  (`$row` in `enable()`) ha reso **obsoleta** una voce della baseline Larastan,
  che esce con errore (`ignore.unmatched`): la voce va tolta nello stesso
  intervento. Il report dell'analyst era giusto sui punti verificati; il
  reviewer ha anche trovato che il piano non copriva il log del rifiuto.
- **Sfuggito al reviewer / ai test:** nulla; resta non rilanciata la regressione
  correlata (`ModuleGeneratorCrudTest`, `MenusCrudTest`, `GroupsCrudTest`,
  `UsersCrudTest`, `CBBackendTest`) e non coperti `remove_member` sul gruppo
  primario (`redirectBack` fa `exit`) e le `add_item`/`add_tenant` di Groups.
- **Regola per la prossima volta:** (1) i metodi di `System/` che leggono
  `$_POST`/`$_SERVER` direttamente non vedono il client di test: i test li
  simulano con un helper che ripristina in `finally`; (2) un test che innesca un
  `TRUNCATE`/DDL rompe il rollback di `RefreshDatabase`: serve un tearDown che
  ripulisca; (3) una rotta messa sotto `CBBackend` non basta a dire "protetta":
  controllare anche il permesso per modulo, che `CBBackend` non fa (S3, S2 aperti);
  (4) quando si corregge un bug citato in `phpstan-baseline.neon`, togliere la
  voce nello stesso intervento e dirlo.

### 2026-10-29 — Audit compatibilità PHP 8.5 (doc 323)
- **Piano prevedeva:** nessun piano scritto: audit richiesto dalla persona, poi
  "fai tutto" sui 4 punti emersi (10 `curl_close()`, due blade rotti, immagini).
- **È successo:** `php -l` su 450 file e sul vendor era pulito, ma **non vede i
  Blade**: compilandoli col compilatore reale sono usciti 2 parse error veri
  (preesistenti, non di 8.5). `scheduler`/`worker` erano in crash-loop da
  settimane su un'immagine PHP 8.3 mai ricostruita dopo il passaggio a 8.5 (il
  compose li costruisce dallo stesso Dockerfile di `app`, ma non da solo).
- **Sfuggito al reviewer / ai test:** nulla di nuovo; resta non coperto il
  render di `menus/form_body.blade.php` (ora compila, ramo `join` mai provato).
- **Regola per la prossima volta:** (1) un controllo di sintassi/compatibilità
  senza compilare i Blade è incompleto: usare lo script di compilazione + lint
  (compileString, poi `php -l`); (2) dopo ogni cambio di versione PHP/immagine
  controllare `docker compose ps` **di tutti i servizi**, non solo `app`;
  (3) le deprecazioni solo-runtime non si vedono staticamente e
  `config/logging.php` non ha il canale `deprecations`: per coprirle serve una
  run della suite con deprecazioni loggate (solo su richiesta).

### 2026-10-29 — Relazioni additive, due fix, prime Action e scope (doc 319-322, piano 317 batch 2-3)
- **Piano prevedeva:** batch 2 relazioni additive con wrapper; 2b i due fix con
  i test del batch 0 rovesciati di proposito; 3 prima Action e local scope.
- **È successo:** i wrapper `Menu::tenants()` e `QlikItem::allowedGroups/Tenants()`
  non si sono potuti sostituire con le relazioni (queste escludono i
  soft-deleted, i wrapper no): restano intatti e la relazione è solo aggiunta.
  Un test scritto per il 2b (id duplicati nella pivot) era sbagliato: la pivot ha
  `unique(menu_id, tenant_id)`. Per gli scope il reviewer ha trovato un punto
  identico dimenticato (`AdminGroupsController:515`) e uno scope applicato dentro
  un commento. Tutto verde: 123 test.
- **Sfuggito al reviewer / ai test:** il test sbagliato lo ha preso il reviewer
  leggendo la migrazione, non il senior dev; `AdminQlikItemsController:566` era
  dentro `/* */` e il senior dev non lo aveva notato.
- **Regola per la prossima volta:** (1) prima di scrivere un test che inserisce
  dati "non validi" in una pivot, leggere la migrazione per vincoli unique e FK;
  (2) prima di sostituire con uno scope/Action, verificare che il punto non sia
  commentato; (3) una relazione Eloquent non è un sostituto drop-in di un
  `pluck()` su tabella pivot se ci sono soft-delete in gioco: confrontare i
  risultati con un test.

### 2026-10-29 — Autorizzazioni ChatAI su tabelle dedicate (doc 318, piano 318)
- **Piano prevedeva:** tabelle `chatai_*_allowed`, controller allineato a Qlik,
  popup condivisi con un parametro opzionale; test del comportamento obiettivo.
- **È successo:** al primo giro 4 test su 28 erano rossi per colpa del test, non
  del codice: le route `admin/chat_ai/*` passano da `EnforceModuleLicense`
  (licenza ChatAI assente in test → 302) e `GET menu_management/add` è sempre
  rimandata (`button_add = false`). Con la licenza simulata i test sono diventati
  28/28. Scoperto di passaggio un bug preesistente: `menus/form_body.blade.php:68`
  non compila in PHP 8 (`$join_query_{$join_table}`).
- **Sfuggito al reviewer / ai test:** il reviewer aveva segnalato solo la
  fragilità del test del menu; non aveva visto che **tutti** i test negativi
  sulle route custom erano verdi in modo vuoto (il middleware rimandava ogni
  richiesta, quindi nessuna riga veniva mai scritta).
- **Regola per la prossima volta:** un test che asserisce "non è stata creata
  nessuna riga" deve prima dimostrare, con lo stesso setup, che il caso positivo
  crea la riga: altrimenti passa anche se la richiesta non arriva al controller.
  Per le route di moduli a licenza (Qlik, ChatAI) il test deve simulare la
  licenza (`Storage::fake('license')` + riga in `license` + token in cache).

### 2026-10-29 — Modelli: caratterizzazione e igiene (doc 317, piano 317)
- **Piano prevedeva:** batch 0 di test per fissare il comportamento, batch 1
  di igiene sul posto, poi relazioni additive con `chataiconf_id`/`item_id` "da
  verificare".
- **È successo:** i test del batch 0 (34 verdi) hanno fatto emergere cose che la
  sola lettura del codice non mostrava: `chataiconf_id` non esiste in nessun DB
  e ChatAI condivide lo spazio di `item_id` con Qlik; `cms_users.primary_group`/
  `tenant` sono NOT NULL (l'orfano reale è un id su record soft-deleted); `can_menu`
  dipende dal path HTTP. Ne è nato il piano 318 (ADR A12).
- **Sfuggito al reviewer / ai test:** gap su `QlikSyncRun::records()` e sui
  `casts()` di `User`/`QlikSyncRun`; i test non erano stati rilanciati dopo il
  batch 1 al momento della documentazione.
- **Regola per la prossima volta:** prima di pianificare relazioni o scope su un
  modello, verificare lo **schema reale** (`information_schema` nel container) e
  chi scrive quelle tabelle, non solo il codice del modello: un metodo che
  "sembra" funzionare può puntare a una colonna inesistente. Rilanciare i test
  del batch 0 dopo il batch 1 prima di chiudere la documentazione.

### 2026-10-30 — B1b: la rotta `uploads/*` non protegge i file (doc 325)
- **Piano prevedeva:** B1b = mettere `uploads/*` dietro login dopo aver verificato chi
  genera quegli URL.
- **È successo:** la verifica ha mostrato che i file escono da `/storage/uploads/...`
  (symlink servito da Apache, 200 da ospite) e che la rotta `uploads/*` era già
  morta: `Storage::exists` guardava in `storage/app/public`, ma il file veniva
  aperto da `storage/app/`, quindi 500 sui file reali. Mettere la rotta sotto
  login non avrebbe protetto nulla. Fatta la sola correzione 500 → 404 (opzione A).
- **Sfuggito al reviewer / ai test:** nessun test esercitava un file esistente
  (c'era solo il 404 per il file inesistente), per questo il 500 non si vedeva.
- **Regola per la prossima volta:** prima di "proteggere una rotta" verificare
  con una richiesta reale da ospite (`curl`) se la risorsa è raggiungibile anche
  per un'altra via (symlink, webserver, altra rotta). Per una risorsa servita
  dal webserver il middleware non conta.

### 2026-10-30 — B2: un filtro anti open-redirect va provato con input malformati (doc 326)
- **Piano prevedeva:** applicare `SafeRedirectTarget` ai 5 `add_*` di
  Groups/Users/Tenants, senza toccare il trait.
- **È successo:** il reviewer ha trovato un bypass nel trait già usato da
  ChatAI e Qlik: `parse_url('http:///evil.com')` dà `false` e il filtro lo
  accettava; `/<TAB>/evil.com` passava come path interno (i browser tolgono il
  tab e lo leggono come `//evil.com`). Test scritto prima (5 rossi), fix nel
  trait approvato dalla persona (che era stata avvisata che il piano non lo
  toccava), poi 144 test verdi con ChatAI e Qlik.
- **Sfuggito al reviewer / ai test:** i test di ChatAI/Qlik provavano solo
  `https://evil` e `//host`: il trait era "coperto" ma non contro i valori
  malformati.
- **Regola per la prossima volta:** per un filtro di sicurezza testare sempre
  input malformati (scheme senza host, `///`, caratteri di controllo,
  `parse_url === false`). Se il reviewer trova un difetto in codice condiviso
  fuori dal perimetro dichiarato, fermarsi e chiedere alla persona prima di
  toccarlo. Un residuo noto (qui `hook_before_add` via `CRUDBooster::redirect`)
  va nelle "Rischi e note", non corretto di passaggio.

### 2026-10-30 — B5: Larastan esegue `app(Classe::class)` (doc 327)
- **Piano prevedeva:** iniettare `StoreApiTokenRequest` in `postAddSave()`.
- **È successo:** la firma non si può cambiare (`CBController::postAddSave()`
  non ha parametri: un override con parametro obbligatorio è un errore fatale,
  uno opzionale riceve `null`). La prima soluzione, `app(StoreApiTokenRequest::class)`,
  faceva passare tutti i test ma produceva in Larastan un warning non ignorabile
  in `CRUDBooster::isCreate()`: `AppMakeHelper` risolve davvero le classi passate
  ad `app()` durante l'analisi e quindi esegue `authorize()` senza sessione.
  Si vedeva **solo da cache dei risultati svuotata** (`phpstan clear-result-cache`).
  Soluzione: metodo statico `validateCurrent()`.
- **Sfuggito al reviewer / ai test:** il secondo run di Larastan dava "No errors"
  (risultato in cache); il test funzionali non esercitano l'analisi statica.
- **Regola per la prossima volta:** dopo una modifica che introduce chiamate al
  container (`app()`, `resolve()`, `make()`) lanciare Larastan anche da cache
  vuota e, se compare un errore in un file non toccato, bisecare escludendo il
  file nuovo con un `.neon` temporaneo che include `phpstan.neon`.

### (migrata dalla memoria, senza data) — Rimuovere campi da `$this->form` corrompe i dati (doc 103)
- **Piano prevedeva:** togliere un campo dal form per non mostrarlo.
- **È successo:** al save i campi assenti da `$this->form` venivano azzerati.
- **Regola per la prossima volta:** mai rimuovere campi; usare `hide_form`.

### (migrata dalla memoria, senza data) — Un `data-*` "morto" può agganciare JS vendorizzato
- **Regola per la prossima volta:** prima di rimuovere un attributo, cercare il
  valore letterale con `tokensave_search literal:true`.

### 2026-10-29 — La copertura riportata da tokensave è ingannevole (osservata creando questa documentazione)
- **È successo:** `tokensave_test_risk` dà 0% perché i test sono HTTP.
- **Regola per la prossima volta:** usarlo per ordinare il rischio, e per la
  copertura vera guardare i nomi in `tests/Feature/`.
