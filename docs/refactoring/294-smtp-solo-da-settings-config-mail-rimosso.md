# 294 - SMTP solo dai Settings: `config/mail.php` rimosso

- **Data**: 2026-10-08
- **Stato**: Completato
- **Area**: Email / Config
- **File/aree di codice coinvolte**:
  - `app/Helpers/MailSettings.php` (nuovo)
  - `app/Helpers/CRUDBooster.php` (`sendEmail`), `app/Jobs/SendDelayedEmailJob.php`,
    `app/Http/Controllers/System/SettingsController.php` (`postTestEmail`)
  - `config/mail.php` (eliminato), `phpunit.xml`, `tests/TestCase.php`,
    `tests/Feature/MailSettingsTest.php` (nuovo), `CLAUDE.md`

## Contesto

Revisione di mail e test (dopo [289](289-email-ritardate-con-job-in-coda.md)–
[293](293-scadenza-account-al-login-e-protezione-superadmin.md)). Decisione:
l'unico SMTP da usare è quello configurato da interfaccia.

## Situazione prima

- `config/mail.php` (formato Laravel ≤ 6, chiave `driver`) faceva da base;
  `sendEmail()` ne sovrascriveva a runtime `driver/host/port/username/
  password` con i Settings, ma **non** `tls_ssl` né il mittente: le mail vere
  partivano da `service@data4prime.com` (o dal `from_email` del template)
  anche con `email_sender` compilato, mentre il test email dalla pagina
  Settings usava il mittente del form e `tls_ssl`.
- Il file conteneva `env('service@data4prime.com')` e `env('<stringa che
  sembra una password>')`: valori sempre `null` a runtime, ma la stringa è nel
  repository e nella sua storia.
- `mail.encryption` non è letto dal mailer di questa versione di Laravel.
- `phpunit.xml` impostava `MAIL_MAILER=array`, senza effetto con la chiave
  `driver`.

## Situazione dopo

- `App\Helpers\MailSettings`: unica routine che costruisce il mailer.
  `fromSettings()` (invii veri: `sendEmail()` e `SendDelayedEmailJob`) legge
  `smtp_driver/host/port/username/password`, `tls_ssl` (SSL → `smtps`,
  TLS → `smtp`, altro → dalla porta) e il mittente (`email_sender`, poi
  l'username se è un indirizzo, poi nessuno; il `from_email` del template
  prevale). `configure()` fa lo stesso con i valori del form per il test email,
  che quindi coincide con gli invii veri.
- Il mailer ha un nome che dipende dai valori: un cambio di Settings produce un
  mailer nuovo invece di riusare quello già messo in cache.
- `config/mail.php` eliminato. Nessun valore di ripiego in file o `.env`
  (`MAIL_*` non è più letto): senza driver valido l'invio fallisce con
  `Unsupported mail transport []`.
- Test: `Mail::fake()` per tutti i test in `tests/TestCase.php` (nessun test
  può spedire email vere); rimosso `MAIL_MAILER` da `phpunit.xml`.
- `CLAUDE.md`: sostituito il vecchio gotcha su `config/mail.php`.

## Motivazione

Una sola fonte di verità per l'invio, coerente con ciò che l'utente vede in
Settings, nessuna credenziale nel repository e nessun SMTP "nascosto" che
possa partire per errore (clienti o test).

## Test

- `MailSettingsTest` (6): il file non esiste; mailer dai Settings; scelta del
  mittente; `tls_ssl` → scheme; mailer diverso a settings diversi; assenza di
  ripiego senza Settings; `configure()` con i valori del form. **Non
  eseguiti** (suite solo su richiesta).
- Verificato: `php -l`; script usa-e-getta che risolve il trasporto reale dai
  Settings senza inviare (`smtp://host:587`, `smtps://host`) e che senza
  driver dà `Unsupported mail transport []`; `php artisan list` ok.
- Non verificato: invio reale con un SMTP vero (dev/staging).

## Rischi e note

- **Cambi visibili**: (1) le mail dei clienti con `email_sender` compilato
  partono da quell'indirizzo e non più da `service@data4prime.com`; senza
  `email_sender` né template `from_email`, il mittente è l'username SMTP se è
  un indirizzo, altrimenti l'invio fallisce (prima c'era il ripiego sul
  file); (2) `tls_ssl` ora vale anche per gli invii veri, se il setting
  esiste nel DB del cliente; (3) un'installazione con un driver `mail` (valore
  di default del seeder, non supportato da Laravel moderno) continua a non
  poter inviare, come prima.
- **Azione richiesta fuori dal codice**: la stringa che sembra una password in
  `config/mail.php` è nella storia git (commit `f253e6f1` e successivi); se è
  una credenziale vera va cambiata sul provider.
- Il `.env` (locale, Docker, server) non viene più letto per `MAIL_*`:
  eventuali variabili residue sono ignorate.

## Rollback

`git checkout <commit> -- config/mail.php` e ripristinare le righe
`Config::set('mail.*')` in `sendEmail()`/job e il blocco `config([...])` in
`postTestEmail()`; `MailSettings` può restare inutilizzato.
