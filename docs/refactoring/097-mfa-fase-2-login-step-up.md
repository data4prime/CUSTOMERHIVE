# 097 - MFA (TOTP + Email OTP): Fase 2, login con step-up

- **Data**: 2026-09-25
- **Stato**: Completato
- **Area**: Auth
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/AdminController.php` (`postLogin()`, nuovo `completeLogin()`, `getMfaVerify()`, `postMfaVerify()`, `postMfaResendEmailOtp()`)
  - `app/Helpers/MfaHelper.php` (nessuna modifica di firma, gia' pronto dalla Fase 1)
  - `routes/crudbooster.php` (nuove route esplicite)
  - `resources/views/crudbooster/mfa_verify.blade.php` (nuovo)
  - `database/migrations/2026_09_25_000005_create_mfa_email_otp_codes_table.php` (nuovo)

## Contesto

Prosegue il piano MFA (`095`, `096`). Questa fase collega l'enrollment
della Fase 1 al login vero e proprio: chi ha il TOTP attivo deve superarlo
per entrare; chi non ce l'ha riceve, solo se il dispositivo non e' gia'
fidato, un codice via email come step-up (stile GitHub, decisione utente).

## Situazione prima

`AdminController::postLogin()` completava il login (sessione legacy +
`Auth::login()`) subito dopo `Hash::check()`, senza nessuna nozione di
secondo fattore, anche per utenti con `two_factor_confirmed_at` valorizzato
dalla Fase 1 (che quindi, fino a questa fase, restava un'attivazione "solo
nel profilo" senza alcun effetto reale sul login).

## Situazione dopo

- **Gate MFA in `postLogin()`**: inserito **subito dopo** la condizione
  password/status/tenant che gia' esisteva, **prima** di qualunque
  `Session::put('admin_*', ...)`/`Auth::login()`:
  - TOTP attivo → `Session::put('mfa_pending_user_id'/'mfa_pending_method'
    = 'totp')`, redirect a `getMfaVerify` (nessun completamento di login).
  - TOTP non attivo e nessun dispositivo fidato (`MfaHelper::
    hasTrustedDevice()`) → invia email OTP (`MfaHelper::sendEmailOtp()`),
    stesso schema di sessione con `method = 'email'`.
  - Altrimenti (nessun MFA o dispositivo gia' fidato) → comportamento
    **invariato** (`completeLogin()`, vedi sotto).
- **`completeLogin($users)`** (nuovo metodo privato): il vecchio corpo del
  ramo "login riuscito" di `postLogin()` spostato qui **senza modifiche**,
  per essere riusato anche da `postMfaVerify()` dopo una verifica MFA
  riuscita. Nessun comportamento cambiato per chi non ha MFA.
- **`getMfaVerify()`/`postMfaVerify()`**: pagina di verifica raggiungibile
  solo con un login "pending" in sessione (altrimenti redirect a
  `getLogin`). Accetta un codice TOTP **o** un backup code (Fase 1) quando
  il metodo e' `totp`; un codice email quando e' `email`. Rate limiting via
  `RateLimiter` nativo di Laravel (5 tentativi, poi blocco 15 minuti per
  combinazione utente+IP — niente tabella `login_attempts` dedicata, scelta
  deliberata per semplicita', vedi `095`). Su successo, chiama
  `completeLogin()` e, solo per il ramo email, accoda il cookie del
  dispositivo fidato (`MfaHelper::issueTrustedDeviceCookie()` - mai per il
  ramo TOTP, che non deve mai essere bypassato dal device trust).
- **`postMfaResendEmailOtp()`**: reinvio con rate limiting separato (3 al
  minuto per utente+IP).
- **Route esplicite** in `routes/crudbooster.php` (stesso gruppo di
  `login`/`forgot`, fuori da `CBBackend` - l'utente non e' loggato mentre e'
  su queste pagine): `mfa-verify` (GET/POST), `mfa-resend-email-otp`
  (POST).
- **Vista `mfa_verify.blade.php`**: stesso stile (`ch-auth-*`) di
  `login.blade.php`/`reset_password.blade.php`. Messaggio diverso per
  `method = totp` (invita anche al backup code) vs `email` (mostra il
  pulsante di reinvio).
- **`mfa_email_otp_codes`** (nuova tabella, mancava in `095`): codici a 6
  cifre hashati, scadenza 10 minuti, monouso.

## Motivazione

- Gate posizionato PRIMA di `Session::put`/`Auth::login()` (non dopo, non
  come controllo separato): un utente con MFA attivo non deve **mai**
  risultare autenticato, nemmeno per un istante, prima di aver superato il
  secondo fattore - coerente con la decisione presa con l'utente in `095`.
- `completeLogin()` estratto testualmente identico (nessuna riscrittura):
  comportamento zero-diff per tutti gli utenti senza MFA, verificabile a
  colpo d'occhio nel diff.
- Anti-replay e rate limiting nativi di Laravel invece di meccanismi
  custom: meno codice, stesso strumento gia' usato altrove nel progetto per
  scopi simili (throttle sul broker password).

## Test

Tutti manuali sull'ambiente Docker locale (nessuna richiesta di eseguire la
suite automatica), via `curl` con jar di cookie per riprodurre sessioni
reali:

1. **Ramo TOTP**: login con l'utente superadmin locale (MFA attivato in
   Fase 1) → redirect a `mfa-verify` confermato (`Location: .../mfa-verify`,
   non piu' diretto a `/admin`). Codice TOTP calcolato al volo dal secret
   decifrato (stessa libreria) → `POST mfa-verify` → redirect a `/admin`,
   sessione autenticata confermata (`GET /admin/users/profile` → 200,
   sezione MFA mostra "active").
2. **Anti-replay**: nuovo login con lo **stesso** codice gia' consumato →
   rifiutato (`Codice non valido o scaduto.`), confermando che
   `verifyKeyNewer()` blocca il riuso nella stessa finestra.
3. **Backup code**: stesso scenario, ma inserendo uno dei backup code della
   Fase 1 al posto del TOTP → login completato, il codice risulta marcato
   `used_at` nel DB; un secondo tentativo con lo stesso codice → rifiutato.
4. **Ramo Email OTP**: login con un utente di test locale (fixture del
   piano di test manuale, `elena.ricci@novaraenergia.test` - password
   temporaneamente impostata a un valore noto per il test, **cambiata
   rispetto a quella eventualmente gia' presente**, da segnalare
   all'utente) senza TOTP attivo → `postLogin()` raggiunge correttamente il
   ramo email (confermato dallo stack trace: l'eccezione arriva da dentro
   `MfaHelper::sendEmailOtp()` → `CRUDBooster::sendEmail()` →
   `Mail::send()`), ma il **vero invio email non e' verificabile in locale**:
   nessun MTA raggiungibile (`sendmail -bs`), stessa identica limitazione
   gia' documentata per `postForgot()`/reset password
   (`project-password-auth-hardening`, non introdotta qui.
   `MfaHelper::verifyEmailOtp()`, `hasTrustedDevice()`,
   `issueTrustedDeviceCookie()` e `revokeAllTrustedDevices()` verificati
   invece **direttamente** (chiamate dirette via script bootstrap Laravel,
   bypassando l'invio email): codice corretto accettato una sola volta,
   codice sbagliato rifiutato, cookie corretto riconosciuto come fidato,
   cookie manomesso rifiutato, revoca funzionante.
5. Ripulito lo stato di test creato per `elena.ricci` (dispositivi fidati e
   codici email OTP di prova rimossi dal DB locale) al termine.
- `php -l` su tutti i file PHP modificati.

## Rischi e note

- **Un fallimento del server SMTP durante l'invio dell'email OTP fa fallire
  l'intero tentativo di login con un errore 500**, perche' `MfaHelper::
  sendEmailOtp()` chiama `CRUDBooster::sendEmail()` in modo sincrono e
  senza try/catch - **esattamente lo stesso comportamento gia' presente
  oggi in `postForgot()`** per il reset password (nessun try/catch li'
  nemmeno). Non introdotto/corretto qui per restare coerenti con il resto
  della codebase; se si vuole indurire questo punto va fatto per entrambi i
  flussi insieme, come intervento a parte.
- In quello scenario, l'utente resta con la password verificata ma nessuno
  stato "pending" salvato in sessione (l'eccezione interrompe
  `postLogin()` prima di `Session::put()`): un nuovo tentativo di login
  rifara' comunque scattare l'invio (nessuno stato sporco lasciato in
  sessione).
- Rate limiting su base cache (`RateLimiter`, driver di cache di default
  del progetto): si azzera se la cache viene svuotata, coerente con il
  comportamento normale di Laravel, non specifico di questa feature.

## Rollback

- `php artisan migrate:rollback --step=1` rimuove `mfa_email_otp_codes`.
- Le route nuove in `routes/crudbooster.php` e i metodi aggiunti in
  `AdminController` sono additivi; rimuoverli riporta `postLogin()` al
  comportamento della Fase 1 (MFA attivabile ma senza effetto sul login).
- Per un utente bloccato per errore nel loop MFA: svuotare
  `Session::forget(['mfa_pending_user_id','mfa_pending_method'])` (basta
  aprire una sessione/browser nuovi) oppure disattivare l'MFA lato DB in
  emergenza (vedi rollback della Fase 1).
