# 289 - Email ritardate: job in coda con delay al posto di mailqueues

- **Data**: 2026-10-08
- **Stato**: Completato
- **Area**: Email / Code
- **File/aree di codice coinvolte**:
  - `app/Jobs/SendDelayedEmailJob.php` (nuovo)
  - `app/Helpers/CRUDBooster.php` (`sendEmail()`; rimosso `sendEmailQueue()`)
  - `app/Console/Commands/Mailqueues.php` (eliminato) e `app/Console/Kernel.php`
  - `database/migrations/2026_10_08_110000_drop_cms_email_queues_table.php` (nuova)
  - `docker-compose.yml` (worker), `tests/Feature/EmailDisabledTest.php`

## Contesto

Revisione di schedule e job del progetto. `mailqueues` era l'unico comando
non schedulato, spostato in [018](018-commands-middlewares-validations-cleanup.md)
senza poter verificare se un cron esterno lo invocasse.

## Situazione prima

- `CRUDBooster::sendEmail()` con l'opzione `send_at` inseriva una riga in
  `cms_email_queues`; il comando `mailqueues` doveva spedirla. Nel codice di
  progetto nessuno passa `send_at` (solo un test); l'utente conferma che
  neanche i controller custom dei clienti lo usano.
- Il comando non poteva funzionare: `$limit_an_hour` e `$queue` non definite,
  `$q->subject` inesistente, cancellazione dalla tabella sbagliata
  (`mailqueues`). Non era schedulato.

## Situazione dopo

- `sendEmail()` con `send_at` accoda un `SendDelayedEmailJob` con
  `->delay(<send_at>)`, connessione `mail`, coda `mail`. Il contenuto è
  già composto dal template; al momento dell'invio il job rilegge le
  impostazioni SMTP e il flag "email attiva" (se spento nel frattempo, non
  invia). 3 tentativi, backoff 5 minuti, errore finale nel log.
- Il worker in `docker-compose.yml` ora serve `--queue=qlik_sync,mail`.
  Stessa connessione database/tabella `jobs` della sync Qlik, quindi nessuna
  nuova infrastruttura. **Sui server il worker va avviato/riavviato con
  `--queue=qlik_sync,mail`**.
- Eliminati comando `mailqueues`, `CRUDBooster::sendEmailQueue()` e (migration)
  la tabella `cms_email_queues`. La migration `down()` la ricrea vuota.
- Senza `send_at` nulla cambia: invio immediato come prima.

## Motivazione

Nessuno usa l'invio ritardato e il vecchio meccanismo era rotto; un job con
delay è lo stesso servizio con meno codice proprio e retry gratuiti, usando
l'infrastruttura di coda già presente.

## Test

- `php -l` su tutti i file toccati; migration eseguita in locale
  (`cms_email_queues` rimossa); `php artisan list` non mostra più `mailqueues`.
- Script usa-e-getta con `Queue::fake()`: `sendEmail(... 'send_at' => +1h)`
  ritorna `true` e mette in coda un `SendDelayedEmailJob` su coda `mail`,
  connessione `mail`, con il delay atteso.
- `EmailDisabledTest` adattato (`Queue::assertNothingPushed()` al posto del
  conteggio righe); suite non lanciata.
- Non verificato: invio reale dal worker.

## Rischi e note

- Un DB cliente con righe residue in `cms_email_queues` le perderebbe con la
  migration (mail mai partite, quindi nessuna regressione visibile). Se si
  vuole una conferma prima del rilascio: `SELECT COUNT(*) FROM
  cms_email_queues;` sul DB del cliente.
- Senza worker in esecuzione le mail ritardate restano nella tabella `jobs`
  (come per Qlik). Oggi comunque nessuno le genera.
- `DashboardDatasetRegistry::TABLE_DENYLIST` cita ancora `cms_email_queues`:
  innocuo, lasciato.

## Rollback

Ripristinare i file dal commit precedente ed eseguire il `down()` della
migration (ricrea la tabella vuota).
