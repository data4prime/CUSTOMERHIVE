# Piano: sincronizzazione di app e item Qlik da una configurazione

- **Data**: 2026-09-30
- **Stato**: Piano approvato a livello di disegno, **nessuna implementazione iniziata**
- **Area**: Qlik (configurazione, app, item, widget)

## Obiettivo

Oggi l'admin deve creare a mano configurazione, app e item, digitando ID e
path. Vogliamo che, dopo aver impostato la **configurazione Qlik** (`qlik_confs`),
le **app** (`qlik_apps`) e gli **item** (`qlik_items`) si possano ricavare da
Qlik con un pulsante, senza digitazione manuale.

Non è una riscrittura: è una funzionalità **additiva**. Le schermate e i dati
esistenti restano invariati (CustomerHive è in produzione presso clienti reali).

## Decisioni prese

| Tema | Decisione |
|---|---|
| Chi può importare | Solo **superadmin** (per ora) |
| Permessi dei record importati | Ereditano **tenant** e **gruppo primario** (`UserHelper::current_user_primary_group()`) di chi importa; poi si possono assegnare ad altri tenant/gruppi come oggi |
| Identità verso Qlik | Quella dell'**utente Qlik associato** a chi importa. Se non c'è, l'import è bloccato con messaggio "utente non associato" |
| Superadmin senza tenant o gruppo primario | Import bloccato con messaggio chiaro |
| Cosa è un item | Per ora **solo il foglio (sheet) di un'app**, ma il modello resta aperto ad altri tipi |
| Relazione item → app | **Facoltativa**. Item senza app si importano comunque; il campo relazione si nasconde in UI quando è vuoto |
| Stesso oggetto importato da due tenant | **Si riusa il record** (chiave conf + `appid`, e id del foglio) e si aggiunge tenant/gruppo di chi importa |
| Anteprima prima di importare | **Non serve**: basta il report finale (creati, riusati, aggiornati, saltati, falliti, non più presenti) |
| Esecuzione | **Coda Laravel** su connessione dedicata, monitorabile e annullabile dall'interfaccia |
| Annullamento | Chi annulla sceglie se **mantenere** i record già creati o **eliminarli** |
| Widget | Nuova **modalità aggiuntiva** "foglio", accanto a quella attuale (master object). Il widget mostra tutte le app (i widget li imposta solo il superadmin) |
| Select | In **ordine alfabetico** e **ricercabili** |
| Colonne DB | Nomi in **inglese** |

## Stato attuale rilevante (verificato nel codice)

- Nel repo **non esiste nessuna enumerazione lato server** di app o fogli.
  `QlikConnectionTester` fa solo il login JWT e, per on-premise, `qrs/about`.
- L'elenco di oggetti del widget oggi lo fa il **browser** con la Capability API
  (`qlik_login_widget_obj.js`: `getAppObjectList('masterobject')` + `CurrentSelections`,
  mostrati con `vis.show()`). Il widget oggi mostra **master object, non fogli**.
- `qlik_apps`: `appname`, `appid`, `conf`, tenant/gruppi (tabelle `qlikapps_tenants`, `qlikapps_groups`).
- `qlik_items`: `title`, `url` (path libero), `subtitle`, `url_help`, `qlik_conf`;
  tenant/gruppi abilitati tramite `TenantsAllowed`/`ItemsAllowed` (vedi
  `AdminQlikItemsController::add_tenant`/`add_authorization`). Nessun legame con l'app.
- Coda: `.env` senza righe `QUEUE_*`; `config/queue.php` è nel formato vecchio
  (legge `QUEUE_DRIVER`, default `sync`); nessuna migrazione `jobs`/`failed_jobs`;
  nessun uso di code nell'app; nessun worker in `docker-compose.yml`.

## Architettura

### 1. Driver di enumerazione (per tipo di conf)

Interfaccia comune, un'implementazione per **SaaS** e una per **On-Premise**:

- `listApps(conf, user)` → elenco di `{appid, name}`
- `listSheets(conf, user, appid)` → elenco di `{sheet id, title, subtitle/description}`

Ogni driver usa il JWT dell'utente che importa (`QlikHelper::getJWTToken` per SaaS,
`getJWTTokenOP` per on-premise), con gli stessi timeout del tester (connect 5s,
totale 15s), TLS verificato e nessun token nei log.

| | Apps | Sheets |
|---|---|---|
| SaaS | `GET /api/v1/items?resourceType=app` (paginato, `limit` ≤ 100) | Engine API via WebSocket (`OpenDoc` → oggetto di sessione `qType: sheet` → `GetLayout`) — **da validare con spike** |
| On-Premise | Repository Service (`qrs/app`) via virtual proxy JWT | `qrs/app/object` filtrato `objectType eq 'sheet'`, oppure Engine API via proxy — **da validare con spike** |

Ciò che si vede dipende dai permessi dell'utente Qlik che importa. I fogli sono
la parte con più incognite (autenticazione del WebSocket col JWT, permessi QRS):
richiede un tenant Qlik di test (da preparare) e una nuova dipendenza Composer
per un client WebSocket PHP, da valutare dopo lo spike.

### 2. Servizio di sincronizzazione

Un servizio unico (indipendente dal driver) che, per ogni elemento trovato:

- **App**: cerca per `conf` + `appid`. Se non esiste, la crea con tenant e gruppo
  primario di chi importa (azione `created`). Se esiste, aggiunge tenant/gruppo
  dell'importatore se mancanti (azione `linked`), senza toccare i campi modificati
  a mano (es. `appname`).
- **Item**: cerca per `conf` + app + `external_id` (id del foglio). Item creati a
  mano prima: al primo import si prova ad agganciarli per `url` (solo se il
  formato dell'URL generato coincide, vedi sotto) invece di duplicarli.
- Registra ogni azione in `qlik_sync_run_records`.
- Segna `is_missing = 1` i record che non compaiono più su Qlik, **solo se la
  sincronizzazione arriva a fine** (mai su run annullata o fallita a metà).
  Non cancella mai in automatico: widget e voci di menu puntano a quei record.
- Se sincronizzando gli item di "tutte le app" un'app non è ancora in `qlik_apps`,
  la crea al volo.

### 3. Coda

- **Connessione dedicata** (es. `qlik_sync`) in `config/queue.php`, con
  `retry_after` alto (l'attuale 90s farebbe ripartire un import ancora in corso).
  **Non si cambia il default globale**: oggi nient'altro usa le code.
- Migrazione per le tabelle `jobs` e `failed_jobs`.
- Un run è una sequenza di **job piccoli in catena** (uno per app; per le app un
  solo job con paginazione), non in parallelo per non caricare Qlik. Ogni job
  controlla `cancel_requested` prima di partire.
- Il job salva l'utente che ha lanciato l'import e genera il JWT all'avvio
  (e lo rigenera per ogni app se la scadenza è breve — da verificare).
- Errore su una singola app: viene registrato e il run continua; a fine run lo
  stato è `completed` con `failed > 0` evidenziato.
- **Worker**: aggiungere un servizio nel `docker-compose.yml` locale e nella
  checklist di deploy di dev, staging e ogni installazione cliente
  (`php artisan queue:work --queue=... `, con supervisor/systemd). Ad ogni
  aggiornamento del codice: `php artisan queue:restart`.
- **Avviso "nessun worker attivo"**: la pagina di monitoraggio segnala job in
  coda fermi da troppo tempo.
- Da verificare: che la sezione `failed` di `config/queue.php` (formato vecchio)
  funzioni con `failed_jobs`.

## Modello dati (tutte modifiche additive, colonne nullable)

**`qlik_apps`**: `is_missing` (bool, default 0), `last_synced_at`.
L'id Qlik è già `appid`. Indice su (`conf`, `appid`) **non unico** (dati legacy
potrebbero avere duplicati).

**`qlik_items`**: `qlik_app_id` (nullable), `item_type` (default `sheet`),
`external_id` (id del foglio), `is_missing` (bool, default 0), `last_synced_at`.

**`qlik_sync_runs`**: `id`, `type` (`apps`|`items`), `qlik_conf_id`,
`qlik_app_id` (nullable, se scelta una sola app), `user_id`, `tenant_id`,
`group_id` (snapshot di quelli assegnati), `status` (`queued`, `running`,
`cancelling`, `cancelled`, `completed`, `failed`), contatori (`total`,
`processed`, `created`, `linked`, `updated`, `skipped`, `failed`), `error`,
`cancel_requested`, `rolled_back_at`, `started_at`, `finished_at`, timestamps.

**`qlik_sync_run_records`**: `id`, `run_id`, `record_type` (`app`|`item`),
`record_id`, `action` (`created`|`linked`|`updated`|`skipped`|`failed`),
`added_tenant_id`, `added_group_id` (per il rollback), `message`, timestamps.

Gli item e le app esistenti non cambiano: le nuove colonne restano vuote.

## Interfaccia

### Modale su `admin/qlik_apps`
Pulsante "Sincronizza da Qlik": scelta della **configurazione** (select
alfabetico e ricercabile) → conferma → il run parte in coda e l'utente vede
il collegamento alla pagina di monitoraggio.

### Modale su `admin/qlik_items`
Scelta della **configurazione** e dell'**app** (select ricercabile, con
opzione "Tutte le app") → conferma.

### Pagina "Sincronizzazioni" (monitoraggio)
- Elenco dei run: tipo, conf, app, utente, stato, avanzamento, contatori, date.
- Dettaglio: elenco record e azioni, errori per app.
- **Annulla** (se `queued`/`running`): dialogo con la scelta "mantieni i record
  già creati" / "elimina quelli creati da questa sincronizzazione".
- **Annulla import** anche a run completata, finché la cronologia resta.
- Avviso "nessun worker attivo".

### Form item
Il campo relazione con l'app si mostra solo se valorizzato (o se l'item è di
tipo foglio); un item senza app resta modificabile senza quel campo.

### Select
Ordinamento alfabetico (conf per `confname`, app per `appname`, item per
`title`) e ricerca (select2). Va verificata l'opzione di ordinamento del tipo
`select`/`select2` di CRUDBooster (es. `datatable_orderby`); nel widget i select
sono `<select>` semplici e vanno resi ricercabili.

## Regole di rollback (annulla con eliminazione)

- Si eliminano solo i record con azione `created` in quel run.
- Per i record `linked` si rimuove solo il tenant/gruppo aggiunto
  (`added_tenant_id`/`added_group_id`), **mai il record**.
- Un record creato dal run ma nel frattempo **modificato, assegnato ad altri o
  usato da un widget/voce di menu** viene **saltato** e segnalato nel report.
- L'eliminazione passa dalla stessa logica dei `hook_before_delete` (pulizia di
  `TenantsAllowed`/`ItemsAllowed`).
- Gli aggiornamenti (`updated`) non vengono ripristinati.

## Widget: modalità "foglio"

- Nuova chiave di configurazione (es. `config[mode]` = `master_object` | `sheet`).
  Widget senza la chiave si comportano **esattamente come oggi**.
- Modalità `sheet`: select app (da `qlik_apps`, alfabetico, ricercabile; tutte,
  perché i widget li imposta solo il superadmin) e select fogli (dagli item con
  `qlik_app_id` = app scelta). Item senza app non compaiono qui.
- Rendering: iframe sull'URL dell'item (come gli item), non Capability API.
- Il selettore attuale dei master object non cambia.
- `CurrentSelections` resta nella modalità attuale.

## Fasi

1. **Infrastruttura coda**: migrazione `jobs`/`failed_jobs`, connessione
   dedicata, worker nel compose, avviso in UI, nota nella checklist di deploy.
2. **Modello dati**: migrazioni additive di app, item, run, record.
3. **Sincronizzazione app**: interfaccia driver + driver SaaS (REST) + servizio +
   job + modale su `qlik_apps` + pagina di monitoraggio (con annullamento e
   rollback).
4. **Select ordinati e ricercabili** (conf/app/item).
5. **Spike sui fogli** (quando c'è il tenant di test): autenticazione del
   WebSocket SaaS, permessi QRS on-premise, formato URL degli item.
6. **Sincronizzazione item**: driver dei fogli, modale su `qlik_items`, campo
   relazione facoltativo/nascosto, aggancio degli item creati a mano.
7. **Widget modalità "foglio"**.
8. **Driver on-premise** per app (e fogli, secondo lo spike).

Ogni fase è un intervento a sé, con la sua voce in `docs/refactoring/`.

## Rischi e note

- **Fogli**: la parte con più incognite (WebSocket, permessi, dipendenza
  Composer). Se l'enumerazione lato server è impraticabile, alternativa ibrida:
  il browser raccoglie l'elenco come oggi e la coda scrive nel DB.
- **Formato URL degli item**: quello generato dalla sincronizzazione deve essere
  identico a quello scritto a mano oggi (viste `view`/`view_saas`), altrimenti
  gli embed si rompono. Va rilevato nello spike.
- **Permessi Qlik**: l'elenco dipende dall'utente Qlik dell'importatore; se non
  vede un'app in Qlik, non verrà importata.
- **Endpoint di annulla/rollback**: usare **POST** e verificare il ruolo
  superadmin (CSRF è disabilitato globalmente, nessuna azione distruttiva via GET).
- **Log e errori nei report**: mai token o segreti; messaggi di errore ripuliti.
- **Worker sui clienti**: senza worker l'import resta "in coda"; è un requisito
  di deploy per installazione.
- **Menu**: da capire se e come gli item importati ricevono la voce di menu
  `qlik_items/content/{id}` come quelli creati a mano.
- **Tabelle tenant/gruppi**: al momento dell'implementazione ricontrollare i
  nomi esatti di quelle degli item (`TenantsAllowed`, `ItemsAllowed`).

## Regole del progetto da rispettare

- Ogni testo visibile in UI: chiave `trans('crudbooster.…')` in **en e it**;
  per il JS, stringhe passate da Blade con `json_encode(trans(...))`.
- Modifiche behavior-preserving; ciò che cambia comportamento visibile va detto.
- Nessun commit/push in autonomia; suite di test solo su richiesta.
- Documentare ogni fase in `docs/refactoring/NNN-*.md` e aggiornare l'indice.

## Fuori scope (per ora)

- Import da parte dei tenant admin (richiede il filtro per tenant su conf/app).
- Modalità `Ticket` (già non funzionante, da decidere se rimuoverla).
- Sincronizzazione di altri tipi di item (storie, oggetti singoli, mashup).
- Sincronizzazione degli utenti Qlik.
