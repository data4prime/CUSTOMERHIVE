# 177 - Sincronizzazione Qlik: app e item da una configurazione (Fasi 3, 5, 6, 8)

- **Data**: 2026-09-30
- **Stato**: Completato (parziale: driver Qlik non verificati contro un Qlik reale)
- **Area**: Qlik
- **File/aree di codice coinvolte**:
  - `app/Services/QlikSync/` (`QlikDriver`, `AbstractQlikDriver`, `SaasQlikDriver`,
    `OnPremQlikDriver`, `QlikDriverFactory`, `QlikSyncService`, `QlikSyncRollback`,
    `QlikSyncUi`, `QlikSyncException`)
  - `app/Jobs/SyncQlikRunJob.php`
  - `app/Http/Controllers/System/QlikAppController.php`,
    `AdminQlikItemsController.php`, `Concerns/HandlesQlikSyncStart.php`
  - `routes/web.php`
  - `public/js/qlik_sync_modal.js`
  - `resources/views/qlik_sync/runs.blade.php`, `run.blade.php`,
    `resources/views/qlik_items/form.blade.php`
  - `resources/lang/{en,it}/crudbooster.php` (chiavi `qlik_sync_*`, `qlik_item_*`)

## Contesto

Cuore del piano [`../piano-qlik-sync-app-items.md`](../piano-qlik-sync-app-items.md):
dopo aver impostato la configurazione Qlik, app e item si ricavano da Qlik con
un pulsante invece di digitare ID e path a mano. Base in 176.

## Situazione prima

Nessuna enumerazione lato server: app e item si creavano a mano; l'elenco degli
oggetti del widget lo faceva il browser (Capability API).

## Situazione dopo

- **Pulsanti** "Sincronizza da Qlik" e "Sincronizzazioni" nelle liste `qlik_apps`
  e `qlik_items` (solo superadmin, modulo Qlik in licenza, solo nella lista).
  Modale con configurazione (elenco filtrabile, alfabetico) e, per gli item,
  app o "Tutte le app". Mai `innerHTML` con dati.
- **Coda**: `POST admin/qlik_apps|qlik_items/sync-start` valida (superadmin,
  utente Qlik associato alla conf, tenant e gruppo primario presenti, nessun
  run dello stesso tipo/conf già attivo) e accoda `SyncQlikRunJob`. Un run è una
  catena di passi brevi (app: un passo; item: init + uno per app), controllo
  di annullamento tra un passo e l'altro.
- **Regole di sincronizzazione**: record nuovi con tenant e gruppo primario di
  chi importa; record già esistenti (conf + `appid`, oppure conf + app + id
  foglio) riusati aggiungendo tenant/gruppo; niente sovrascrittura di titoli/nomi;
  item creati a mano agganciati per URL (non duplicati); `is_missing` sui
  record spariti da Qlik solo a run completato, mai cancellati; errore su una
  app: registrato, il run prosegue.
- **Monitoraggio** (`admin/qlik_apps/sync-runs`, dettaglio `/{id}`): stato,
  avanzamento, contatori, record per azione, avviso "nessun worker" (run in
  coda da più di 2 minuti), aggiornamento automatico finché il run è attivo.
- **Annulla**: dialogo mantieni/elimina i record creati; anche **dopo il
  completamento** ("Annulla import"). Rollback: elimina i record `created` del
  run solo se non modificati, non assegnati ad altri tenant/gruppi, non usati
  da widget/voci di menu/item collegati (altrimenti li tiene e scrive il motivo
  nel dettaglio); per i `linked` toglie solo il tenant/gruppo aggiunto.
- **Driver**: `SaasQlikDriver` (app: `GET /api/v1/items?resourceType=app`
  paginato dopo il login `jwt-session`; fogli: Engine API via WebSocket) e
  `OnPremQlikDriver` (QRS `qrs/app` e `qrs/app/object/full` con filtro
  `objectType eq 'sheet'`, JWT + xrfkey come `qrs/about` del test connessione).
  URL del foglio generato: `{url}[/{endpoint}]/sense/app/{appid}/sheet/{id}/state/analysis`.
- **Liste**: colonna "Stato Qlik" con badge "Non più su Qlik"; il form dell'item
  mostra app e id foglio solo se valorizzati (relazione facoltativa, nascosta
  se vuota). Nessun campo nuovo in `$this->form` (non si toccano i dati).
- Tutti i testi su `trans()` (en/it); testi del JS passati da PHP con `json_encode`.

## Motivazione

Vedi piano: meno digitazione manuale, meno errori di ID/path, coda per non
bloccare la richiesta e per poter monitorare/annullare.

## Test

- **Servizio con driver finto** (script temporaneo, poi rimosso): creazione app
  e tenant/gruppo, seconda sincronizzazione a vuoto (tutto `skipped`), app
  sparita → `is_missing` e ritorno a `updated`, item agganciato per URL senza
  duplicati, item ripetuto, foglio sparito, "tutte le app" con un'app che
  fallisce (il run prosegue), app nuova creata al volo, rollback (eliminati solo
  i creati; record assegnati ad altri o modificati mantenuti con motivo;
  rollback ripetuto no-op), annullamento di un run in coda e in corso (con
  rollback). Tutti OK; righe di prova ripulite (conteggi identici a prima).
- **Coda reale** con il worker Docker (vedi 176).
- Lint di tutti i file PHP, compilazione Blade e rendering con dati finti di
  `runs`/`run`, verificate chiavi di traduzione en/it e assenza di duplicati.
- **NON verificato**: nessuna chiamata contro un Qlik reale (SaaS/on-premise);
  nessun test da browser (il superadmin locale ha MFA attiva: non aggirata).

## Rischi e note

- **Fogli SaaS**: autenticazione del WebSocket (cookie di sessione + `qlik-csrf-token`,
  header `Origin` assente lato server) **da confermare con lo spike** su un
  tenant di test. Fogli on-premise: dipende dai permessi QRS dell'utente Qlik.
  Se qualcosa non torna il run fallisce con un messaggio chiaro; su Qlik si
  fanno solo letture.
- Vengono elencati anche i fogli privati/non pubblicati dell'utente che importa.
- Gli item importati **non hanno voce di menu**: come per quelli creati a mano,
  la voce `qlik_items/content/{id}` si crea dal menu manager.
- L'identità è quella dell'utente Qlik dell'importatore (tabella `qlik_users`):
  vede solo ciò che quell'utente può vedere in Qlik.
- Scoperto (non corretto): `QlikAppController::getMashups()` per non superadmin
  fa `join` su `qlikapps_tenants.qlikmashup_id`, colonna rinominata `qlik_apps_id`
  (migrazione 2025-01-13): quel ramo è rotto. Irrilevante finora (solo superadmin).
- Nuova dipendenza Composer e worker: vedi checklist pre-push.

## Rollback

Ripristinare i file elencati da git; vedi 176 per migrazioni, worker e dipendenza.
I record già importati restano (sono normali app/item).
