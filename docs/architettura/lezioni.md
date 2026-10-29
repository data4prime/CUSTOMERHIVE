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
