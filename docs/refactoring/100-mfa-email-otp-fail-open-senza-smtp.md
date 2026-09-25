# 100 - MFA: login normale (senza email OTP) se l'SMTP non è raggiungibile

- **Data**: 2026-09-25
- **Stato**: Completato
- **Area**: Auth
- **File/aree di codice coinvolte**:
  - `app/Helpers/MfaHelper.php` (`sendEmailOtp()`)
  - `app/Http/Controllers/System/AdminController.php` (`postLogin()`, `postMfaResendEmailOtp()`, `postMfaRecovery()`)
  - `resources/lang/en/crudbooster.php`, `resources/lang/it/crudbooster.php`

## Contesto

Segnalato dall'utente durante il test manuale in locale: con l'MFA
disattivata (TOTP non attivo), il login andava in **500 reale** ogni volta
che il server non riesce a inviare l'email OTP (nessun SMTP configurato o
raggiungibile) - gia' notato come limite noto in `097`/`098`/`099`, ma
lasciato cosi' di proposito fino a questa richiesta esplicita: **"se non ho
un SMTP configurato, fai il login normale, senza email OTP"**.

## Situazione prima

`MfaHelper::sendEmailOtp()` chiamava `CRUDBooster::sendEmail()` senza
try/catch: un fallimento del transport (es. `sendmail -bs` non
funzionante, nessun MTA raggiungibile) risaliva non gestito fino a
`postLogin()`, che si interrompeva con un 500 **prima** di popolare
`Session::put('mfa_pending_user_id', ...)` - l'utente restava bloccato
fuori con un errore invece di poter accedere con la sola password.

## Situazione dopo

- **`MfaHelper::sendEmailOtp()`**: ora ritorna `bool` invece di `void`.
  L'invio e' avvolto in try/catch: se fallisce, la riga del codice appena
  creata in `mfa_email_otp_codes` viene rimossa (non sarebbe comunque mai
  consegnato), l'errore viene loggato (`Log::warning`, con `user_id` e
  messaggio originale) e il metodo ritorna `false`.
- **`postLogin()`**: il gate MFA ora legge questo valore di ritorno - se
  l'invio fallisce, **non** salva lo stato "pending" e **procede con il
  login normale** (`completeLogin($users)`), esattamente come se l'utente
  non avesse alcun secondo fattore. Se l'invio riesce, il comportamento
  resta quello della Fase 2 (redirect a `mfa-verify`).
- **`postMfaResendEmailOtp()`**: stesso controllo sul valore di ritorno,
  ma qui non c'e' un login da "far proseguire" - mostra un messaggio
  d'errore dedicato (`mfa_resend_email_failed`) invece di quello di
  successo, senza piu' andare in 500.
- **`postMfaRecovery()`**: l'invio dell'email di recovery e' ora avvolto
  nello stesso try/catch (solo per evitare il 500 - qui **non** si applica
  nessun fallback "apri comunque l'accesso", non avrebbe senso per un
  flusso di recovery). Il messaggio di risposta resta identico in ogni
  caso (stesso principio anti-enumerazione di `098`).

## Motivazione

Decisione esplicita dell'utente, con un compromesso di sicurezza dichiarato
apertamente: se l'invio email fallisce (SMTP non configurato/irraggiungibile
in un dato momento), **tutti gli utenti senza TOTP attivo** accedono con la
sola password, senza alcun secondo fattore, finche' l'email non torna a
funzionare - "fail open" invece di "fail closed". Scelto deliberatamente
per non rischiare di bloccare fuori l'intera base utenti per un problema di
infrastruttura email, a costo di perdere lo step-up email in quella
finestra. **Non riguarda mai il TOTP**: un utente con TOTP attivo continua
a doverlo sempre inserire, indipendentemente dallo stato dell'email - il
fail-open si applica solo al ramo email OTP, l'unico che dipende da un
invio riuscito.

## Test

- `php -l` su tutti i file modificati.
- Riprodotto dal vivo su Docker locale: utente di test senza TOTP
  (`elena.ricci@novaraenergia.test`) → login con credenziali corrette →
  prima di questo fix, 500 reale (`TransportException`); dopo il fix,
  **302 diretto a `/admin`**, sessione autenticata confermata (`GET
  /admin/users/profile` → 200).
- Verificato che non resta nessuna riga orfana in `mfa_email_otp_codes`
  per quell'utente, e che il fallimento è stato loggato
  (`storage/logs/laravel.log`, un'occorrenza di "invio email OTP fallito").
- Non ripetuto il test sul ramo TOTP (invariato, gia' verificato a fondo in
  `097`; questo fix tocca solo il ramo email).
- Non eseguita la suite di test automatici (nessuna richiesta esplicita).

## Rischi e note

- **Compromesso di sicurezza consapevole**: finche' l'SMTP non funziona,
  chiunque non abbia il TOTP attivo accede con la sola password, esattamente
  come se l'MFA non esistesse per quegli account. In produzione, con un
  SMTP funzionante, questo caso non si presenta mai nella pratica - il
  rischio reale e' un guasto/blackout temporaneo del server di posta.
- Da tenere a mente se in futuro si vuole un comportamento diverso per
  produzione (es. avvisare un superadmin quando questo accade, invece di
  limitarsi al log applicativo) - non richiesto ora, solo segnalato.

## Rollback

Ripristinare `sendEmailOtp()` a `void` (senza try/catch) e togliere il
controllo `if (MfaHelper::sendEmailOtp(...))` in `postLogin()`/
`postMfaResendEmailOtp()` riporta al comportamento "fail closed" (500 se
l'email non parte) della Fase 2 originale.
