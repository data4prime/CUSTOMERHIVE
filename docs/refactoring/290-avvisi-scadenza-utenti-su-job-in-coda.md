# 290 - Avvisi di scadenza utenti su job in coda

- **Data**: 2026-10-08
- **Stato**: Completato
- **Area**: Email / Code
- **File/aree di codice coinvolte**:
  - `app/Console/Commands/UserExpiryNotification.php`
  - `app/Jobs/SendTemplateEmailJob.php` (nuovo)
  - `app/Helpers/CRUDBooster.php` (`sendEmail()`: opzione `content_replace`)
  - `config/queue.php` (`mail_connection`), `.env.example`

## Contesto

Seguito di [289](289-email-ritardate-con-job-in-coda.md). Le email
transazionali partono tutte in modo sincrono da `CRUDBooster::sendEmail()`;
quelle interattive (OTP, reset, recovery) devono restare così. Gli avvisi di
scadenza invece sono un comando notturno che manda molte email in sequenza:
un SMTP lento o un errore su un destinatario bloccava l'intero comando.

## Situazione prima

- `user:expiry-notification` (00:10) inviava in linea una mail a ogni utente
  in scadenza (30/7/1 giorni), una a ogni tenant admin e una a ogni
  superadmin.
- Per la tabella degli utenti nelle mail ai tenant admin/superadmin il
  comando **modificava il template in `cms_email_templates`** (sostituiva il
  segnaposto con l'HTML, inviava, poi lo ripristinava): un'interruzione a metà
  lasciava il template sporco.
- Dopo il ciclo sui tenant `$users` era stata sovrascritta dal `foreach`
  (conteneva solo gli utenti dell'ultimo tenant), quindi i superadmin
  ricevevano l'elenco di un solo tenant. Se non scadeva nessuno, ai superadmin
  arrivava comunque ogni notte una mail con la tabella vuota.

## Situazione dopo

- Ogni email è un `SendTemplateEmailJob` (3 tentativi, backoff 5 min, errore
  nel log) che richiama `CRUDBooster::sendEmail()`. Coda `mail`; connessione
  da `config('queue.mail_connection')` = env `MAIL_QUEUE_CONNECTION`,
  **default `sync`** (invio immediato nel comando, come prima, nessun worker
  necessario). Con `MAIL_QUEUE_CONNECTION=mail` finiscono nella tabella
  `jobs` e le invia il worker (`queue:work --queue=qlik_sync,mail`).
- `sendEmail()` accetta `content_replace` (`[cerca => sostituisci]` applicato
  al contenuto prima dei segnaposto `[chiave]`): la tabella HTML viaggia nel
  job e il template nel DB non viene più toccato.
- Nel payload del job i dati utente scartano le chiavi che contengono
  `password`, `token`, `secret`, `mfa`, `otp`, così le credenziali non finiscono
  nella tabella `jobs`.
- **Cambi di comportamento visibili (intenzionali)**: (1) i superadmin
  ricevono l'elenco di *tutti* gli utenti in scadenza e non solo dell'ultimo
  tenant; (2) se non scade nessuno non ricevono più la mail con la tabella
  vuota. Per tornare al comportamento precedente basta togliere il
  `if ($users->isNotEmpty())`.
- Le mail interattive (OTP, reset, recovery) restano sincrone.

## Motivazione

Isolare gli errori per destinatario, togliere il comando dal percorso lento
dell'SMTP e smettere di modificare un template condiviso a runtime. Il default
`sync` evita che un server senza worker smetta di mandare gli avvisi.

## Test

- `php -l` sui file toccati; `php artisan user:expiry-notification` in locale
  senza errori (email disattivata → esce subito).
- Script usa-e-getta in transazione con rollback e `Queue::fake()`: un utente
  in scadenza produce i job attesi (mail all'utente, ai tenant admin e al
  superadmin con `content_replace`), su coda `mail`, nessuna chiave
  `password` nel payload. Trovato e corretto un conflitto di nome: il metodo
  statico `queue()` collideva con `Queueable::queue()` (ora `enqueue()`).
- Non verificato: invio reale via SMTP, né dal worker né con `sync`; la suite
  di test non è stata lanciata.

## Rischi e note

- Con `MAIL_QUEUE_CONNECTION=mail` serve un worker che serva la coda
  `mail`; altrimenti gli avvisi restano nella tabella `jobs`.
- I template `notifica_scadenza_utente_*` stanno solo nel DB: i segnaposto
  `[chiave]` usati sono quelli delle colonne di `cms_users` (più
  `giorni_mancanti`); le colonne scartate dal filtro (es. `password`) non
  sono quindi utilizzabili nei template.
- Un job che fallisce dopo aver inviato (raro) può essere ritentato e
  duplicare la mail.

## Rollback

Ripristinare `UserExpiryNotification.php` e `CRUDBooster.php` dal commit
precedente; `SendTemplateEmailJob` e la chiave di config possono restare
inutilizzati.
