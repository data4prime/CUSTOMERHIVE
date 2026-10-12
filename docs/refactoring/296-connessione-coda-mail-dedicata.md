# 296 - Connessione di coda `mail` dedicata

- **Data**: 2026-10-08
- **Stato**: Completato
- **Area**: Code / Email
- **File/aree di codice coinvolte**:
  - `config/queue.php` (nuova connessione `mail`)
  - `app/Helpers/CRUDBooster.php` (invio ritardato), `.env.example`
  - `tests/Feature/QueuedEmailTest.php`, `tests/Feature/UserExpiryNotificationTest.php`
  - `docs/refactoring/289-*`, `290-*` (nomi aggiornati)

## Contesto

Seguito di [289](289-email-ritardate-con-job-in-coda.md) e
[290](290-avvisi-scadenza-utenti-su-job-in-coda.md): per mettere le email in
coda bisognava impostare `MAIL_QUEUE_CONNECTION=qlik_sync`, un nome che parla
di Qlik per un lavoro che non c'entra.

## Situazione prima

- Le email in coda usavano la connessione `qlik_sync` (coda `mail`).

## Situazione dopo

- Nuova connessione `mail` (driver `database`, tabella `jobs`, coda `mail`).
  `MAIL_QUEUE_CONNECTION` accetta `sync` (default, invio immediato) oppure
  `mail`; anche `SendDelayedEmailJob` usa `mail`.
- Il worker **non cambia**: con il driver database i job stanno tutti nella
  tabella `jobs` e il worker li legge per nome di coda, indipendentemente da
  quale connessione li ha accodati. Basta che serva la coda `mail`:
  `queue:work qlik_sync --queue=qlik_sync,mail`.
- Nota: per un job rimasto appeso (worker ucciso) vale il `retry_after` della
  connessione del worker (3900 s), non quello della connessione `mail` (90 s).

## Motivazione

Nome sensato per chi configura, nessuna modifica al worker né alla sync Qlik.

## Test

- `QueuedEmailTest` e `UserExpiryNotificationTest` aggiornati alla connessione
  `mail`; suite non rilanciata dopo questa modifica.
- Verificato in locale con il worker reale: accodato un `SendTemplateEmailJob`
  su connessione `mail`; il worker lo ha eseguito (`DONE`, tabella `jobs`
  vuota, nessun fallito). Con `email_enabled=no` il job non invia nulla.
- **Correzione**: il container `worker` in esecuzione aveva ancora il comando
  vecchio (`--queue=qlik_sync` senza `,mail`): `docker compose restart` non
  applica le modifiche al file compose. Ricreato con
  `docker compose up -d worker`.

## Rischi e note

- Dopo aver modificato `docker-compose.yml` il container va ricreato
  (`docker compose up -d <servizio>`), non solo riavviato.
- Su un server il worker va (ri)avviato con `--queue=qlik_sync,mail` prima di
  impostare `MAIL_QUEUE_CONNECTION=mail`.

## Rollback

Rimettere `qlik_sync` come connessione in `CRUDBooster::sendEmail()` e come
valore di `MAIL_QUEUE_CONNECTION`; la connessione `mail` può restare.
