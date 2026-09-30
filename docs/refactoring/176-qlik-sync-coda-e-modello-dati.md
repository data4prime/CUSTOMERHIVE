# 176 - Sincronizzazione Qlik: coda Laravel e modello dati (Fasi 1-2)

- **Data**: 2026-09-30
- **Stato**: Completato
- **Area**: Qlik / Infrastruttura
- **File/aree di codice coinvolte**:
  - `config/queue.php` (nuova connessione `qlik_sync`)
  - `database/migrations/2026_09_30_100000_create_jobs_and_failed_jobs_tables.php`
  - `database/migrations/2026_09_30_100100_add_sync_columns_and_create_sync_tables.php`
  - `app/QlikSyncRun.php`, `app/QlikSyncRunRecord.php`
  - `docker-compose.yml` (servizio `worker`)
  - `composer.json`/`composer.lock` (`textalk/websocket`)

## Contesto

Prime due fasi di [`../piano-qlik-sync-app-items.md`](../piano-qlik-sync-app-items.md):
la sincronizzazione di app e item Qlik da una configurazione gira in coda, e
serve un posto dove registrare cosa ha fatto ogni sincronizzazione.

## Situazione prima

- `.env` senza righe `QUEUE_*`; `config/queue.php` nel formato vecchio (legge
  `QUEUE_DRIVER`, default `sync`), quindi nessuna coda asincrona.
- Nessuna migrazione `jobs`/`failed_jobs`, nessun `app/Jobs`, nessun worker in
  `docker-compose.yml`.
- `qlik_apps` e `qlik_items` senza alcun legame tra loro né campi di sincronizzazione.

## Situazione dopo

- **Connessione dedicata `qlik_sync`** (driver `database`, tabella `jobs`,
  coda `qlik_sync`, `retry_after` 3900 s). **Il default globale resta `sync`**:
  nient'altro usa le code, quindi nessun comportamento esistente cambia.
- Migrazione `jobs`/`failed_jobs` (con `hasTable`, additiva).
- Migrazione additiva, tutte colonne nullable o con default:
  - `qlik_apps`: `is_missing`, `last_synced_at`, indice (`conf`, `appid`) **non
    unico** (dati legacy possono avere duplicati).
  - `qlik_items`: `qlik_app_id` (nullable, relazione facoltativa), `item_type`
    (default `sheet`), `external_id`, `is_missing`, `last_synced_at`, indice di
    sincronizzazione.
  - Nuove `qlik_sync_runs` (con anche `missing`, `cancel_delete` rispetto al
    piano) e `qlik_sync_run_records`.
- Servizio `worker` in `docker-compose.yml` (stessa immagine e volumi dell'app,
  gira come `www-data` per non creare in `storage/` file di root):
  `queue:work qlik_sync --queue=qlik_sync --tries=1 --timeout=3600`.
- Nuova dipendenza Composer `textalk/websocket` 1.5.8 (solo per elencare i fogli
  SaaS via Engine API, vedi 177).

## Motivazione

Coda dedicata e non toccare il default: rischio zero per il resto dell'app.
`retry_after` alto perché con 90 s (default) un import lungo verrebbe rimesso
in coda mentre gira.

## Test

- Migrazioni eseguite in locale (Docker), nessun'altra migrazione pendente.
- **Coda end-to-end**: run di prova accodato, ripreso dal worker Docker, portato
  a `failed` con messaggio tradotto ("Configurazione Qlik non trovata") e
  `jobs`/`failed_jobs` vuote a fine giro.
- Non eseguita la suite di test (solo su richiesta).

## Rischi e note

- **Deploy su ogni installazione**: `composer install` (nuova dipendenza),
  `php artisan migrate`, e un **worker** in esecuzione (supervisor/systemd:
  `php artisan queue:work qlik_sync --queue=qlik_sync --sleep=3 --tries=1 --timeout=3600`).
  Senza worker le sincronizzazioni restano "in coda": la pagina di monitoraggio
  mostra un avviso dopo 2 minuti.
- Dopo ogni aggiornamento del codice: `php artisan queue:restart` (in Docker
  locale: `docker compose restart worker`), altrimenti il worker usa codice vecchio.
- La sezione `failed` di `config/queue.php` è nel formato vecchio; non è stato
  provato un job fallito "vero" scritto in `failed_jobs` (il job gestisce da sé
  gli errori e marca il run `failed`).
- Ricreare l'immagine con `docker compose up -d --build worker` ha ricreato anche
  il container `app` (dipendenza): comportamento normale del compose.

## Rollback

`php artisan migrate:rollback --step=2` (le `down()` tolgono colonne, indici e
tabelle nuove), togliere la connessione da `config/queue.php`, il servizio
`worker` dal compose e `composer remove textalk/websocket`.
