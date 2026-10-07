# 220 - Email Setting: flag "Email attiva" (on/off)

- **Data**: 2026-10-05
- **Stato**: Completato (non committato)
- **Area**: Settings, Email, MFA
- **File/aree di codice coinvolte**:
  - `database/migrations/2026_10_05_100000_add_email_enabled_setting.php` (nuovo)
  - `database/seeders/CmsSettingsSeeder.php`
  - `app/Helpers/CRUDBooster.php` (`isEmailEnabled()`, guardie in `sendEmail`/`sendEmailQueue`)
  - `app/Helpers/MfaHelper.php` (`isSmtpConfigured`, `sendEmailOtp`)
  - `app/Http/Controllers/System/AdminController.php` (`postForgot`, `postMfaRecovery`)
  - `app/Http/Controllers/System/AdminCmsUsersController.php` (`postUserResetPassword`)
  - `app/Console/Commands/UserExpiryNotification.php`
  - `resources/views/crudbooster/setting.blade.php`, `resources/lang/{en,it}/crudbooster.php`

## Contesto

Richiesta: poter dichiarare dal gruppo "Email Setting" se l'email è attiva.
Con il flag spento l'app deve comportarsi come se l'email non ci fosse,
comprese le verifiche via codice email (login, cambio email/password dal
profilo).

## Situazione prima

Nessun flag. "Email configurata" era dedotta solo da `smtp_driver = smtp` +
`smtp_host` valorizzato (`MfaHelper::isSmtpConfigured()`, usata da
`emailChangeMode()`); ogni altro punto tentava comunque l'invio e gestiva
l'eventuale errore con un fallback.

## Situazione dopo

- Nuovo setting `email_enabled` (radio yes/no, default `yes`) nel gruppo Email
  Setting; label/help tradotti EN/IT dalla blade (chiavi
  `setting_label_email_enabled`, `setting_helper_email_enabled`).
- `CRUDBooster::isEmailEnabled()`: solo un `no` esplicito disattiva; setting
  assente/vuoto = attiva (nessun cambio per chi non ha migrato).
- Flag off:
  - `sendEmail()` / `sendEmailQueue()`: ritornano `false` in silenzio, nessun
    invio né accodamento (anche con `send_at`).
  - `isSmtpConfigured()` = false → `emailChangeMode()` = `none` (cambio
    email/password dal profilo con sola password attuale, se non c'è TOTP).
  - `sendEmailOtp()`: ritorna `false` senza generare il codice → il login
    prosegue senza step-up email (TOTP invariato).
  - `postMfaRecovery`: non crea la richiesta, stesso messaggio generico.
  - `postForgot` e "Resetta password" da admin: messaggio esplicito
    `email_disabled_notice` (la voce "Password dimenticata" resta nel login).
  - `UserExpiryNotification`: esce subito.
- Il pulsante "Test invio" resta utilizzabile anche a flag off (serve a
  provare la configurazione prima di attivarla).

## Motivazione

Un unico interruttore esplicito invece di dedurre lo stato dai campi SMTP;
punto di verifica unico (`isEmailEnabled`) richiamato da tutti i flussi.

## Test

Lint dei file toccati, migration eseguita in Docker, verifica manuale via
script PHP: flag yes → attiva; flag no → `isEmailEnabled`, `isSmtpConfigured`
e `sendEmail` tutti `false`; ripristinato a yes. Aggiunto
`tests/Feature/EmailDisabledTest.php` (8 casi: flag, migration idempotente,
sendEmail, isSmtpConfigured, emailChangeMode, sendEmailOtp, forgot password,
reset da admin) - scritto ma NON ancora eseguito (suite non lanciata).
Non verificato a vista: pagina Settings, forgot password, login con flag off.

## Rischi e note

- Con flag off e utente MFA che ha perso il dispositivo, il recovery via email
  non è disponibile (comportamento voluto).
- `Mailqueues` ha un bug preesistente (`$queue` non definita): non toccato.
- Il rendering "Select" del driver e i valori yes/no non sono tradotti
  (come gli altri campi radio/select dei Settings).

## Rollback

Rimuovere i punti di guardia `isEmailEnabled()` e fare `down()` della
migration (cancella la riga `email_enabled`).
