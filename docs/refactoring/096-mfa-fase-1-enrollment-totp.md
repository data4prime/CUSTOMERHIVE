# 096 - MFA (TOTP + Email OTP): Fase 1, enrollment TOTP self-service

- **Data**: 2026-09-25
- **Stato**: Completato
- **Area**: Auth
- **File/aree di codice coinvolte**:
  - `app/Helpers/MfaHelper.php` (nuovo)
  - `app/Http/Controllers/System/AdminCmsUsersController.php`
  - `app/User.php` (`$hidden`, `$casts`)
  - `resources/views/crudbooster/mfa_setup.blade.php` (nuovo)
  - `resources/views/crudbooster/mfa_backup_codes.blade.php` (nuovo)
  - `resources/views/crudbooster/default/form.blade.php` (sezione MFA sul profilo)
  - `resources/lang/en/crudbooster.php`, `resources/lang/it/crudbooster.php`
  - `database/migrations/2026_09_25_000004_add_two_factor_last_timeslice_to_cms_users.php` (nuovo)

## Contesto

Prosegue il piano MFA discusso con l'utente (vedi `095-*` per lo schema
dati e le decisioni prese). Questa fase implementa l'enrollment TOTP
self-service dal profilo utente, incluso il calcolo del secret, il QR, la
conferma del primo codice e i backup codes monouso.

## Situazione prima

Colonne/tabelle create in `095` ma non lette/scritte da nessun codice
applicativo. Nessuna UI per attivare l'MFA. `AdminCmsUsersController::
getProfile()` mostrava solo il form standard di CRUDBooster (nome, email).

## Situazione dopo

- **`MfaHelper`** (nuovo, `app/Helpers/MfaHelper.php`): centralizza tutta la
  logica MFA (generazione/cifratura secret, QR SVG via `bacon/bacon-qr-code`
  — nessuna dipendenza da `imagick`, non installato nel container —,
  verifica TOTP con anti-replay, backup codes, dispositivi fidati, email
  OTP). Riusata anche dalle fasi successive (2, 3).
- **Anti-replay**: aggiunta la colonna `two_factor_last_timeslice` (mancante
  in `095`, necessaria da subito per `PragmaRX\Google2FA\Google2FA::
  verifyKeyNewer()`, che rifiuta un codice gia' usato in quella o in una
  finestra precedente).
- **`AdminCmsUsersController`**: nuovi metodi, tutti auto-instradati da
  `CRUDBooster::routeController()` (nessuna route esplicita necessaria,
  stesso meccanismo di `getProfile`/`getEdit`, modulo `users` gia' registrato
  in `cms_moduls`):
  - `getMfaSetup()` / `postMfaConfirm()`: genera un secret **temporaneo in
    sessione** (mai salvato finche' l'utente non conferma il primo codice),
    mostra QR + chiave manuale, verifica il primo codice e solo allora
    cifra e salva il secret, genera 10 backup codes.
  - `getMfaBackupCodes()`: mostra i backup codes in chiaro **una sola
    volta** (`Session::pull`, non `Session::get` - un refresh non li
    ri-mostra).
  - `postMfaRegenerateBackupCodes()`: invalida e rigenera i backup codes
    (per chi li ha consumati).
  - `postMfaDisable()`: richiede la password corrente (verificata con
    `Hash::check`) prima di disattivare, per evitare che una sessione
    dirottata possa spegnere l'MFA senza altro attrito.
  - `postMfaRevokeDevices()`: disconnette tutti i dispositivi fidati
    (utile anche per chi usa solo l'email OTP, Fase 2).
- **Vista profilo**: `resources/views/crudbooster/default/form.blade.php`
  (template condiviso da *tutti* i moduli CRUD) ha gia' un precedente per
  uno speciale-caso su `getProfile` (nasconde il link "torna alla lista");
  la sezione MFA segue lo stesso criterio, quindi non tocca nessun'altra
  pagina del progetto.
- **Traduzioni**: tutte le stringhe UI/log della Fase 1 (e delle fasi 2/3
  gia' previste, per non riaprire i file ad ogni fase) aggiunte in inglese e
  italiano.
- **`App\User`**: `two_factor_secret` aggiunto a `$hidden` (mai esposto in
  array/JSON del modello), `two_factor_confirmed_at` castato a `datetime`.

## Motivazione

- `$this->cbView()` invece di `view()` diretto: la prima bozza usava
  `view('crudbooster::mfa_setup', ...)` e falliva con `Undefined variable
  $button_export` — `admin_template.blade.php` si aspetta le variabili
  condivise da `CBController::cbLoader()` (chiamato da `cbView()`, non da
  `view()` diretto). Scoperto e corretto durante il test manuale.
- Secret temporaneo in sessione fino alla conferma: se l'utente abbandona
  l'enrollment a meta', non resta nessun secret orfano nel DB.
- Backup codes monouso mostrati una sola volta: stesso principio del design
  di riferimento (`Design MFA.md`) - vanno salvati offline dall'utente, il
  server non li tiene mai in chiaro dopo la generazione.

## Test

- `php -l` su tutti i file PHP nuovi/modificati.
- **End-to-end manuale** sull'ambiente Docker locale, via `curl` con jar di
  cookie (login reale con l'utente superadmin locale, CSRF disabilitato
  globalmente nel progetto — vedi CLAUDE.md):
  1. Login → `GET /admin/users/mfa-setup` → secret e QR mostrati.
  2. Codice TOTP calcolato al volo con la stessa libreria
     (`Google2FA::getCurrentOtp()`) sul secret mostrato, come farebbe una
     vera app authenticator.
  3. **Riprodotto un bug reale**: prima esecuzione → `500 Internal Server
     Error`, `SQLSTATE[42S22]: Column not found: 'two_factor_last_timeslice'`
     — la migration per quella colonna (creata insieme a questa fase) non
     era ancora stata eseguita (`php artisan migrate` dimenticato dopo
     averla scritta). Eseguita la migration, ripetuto il test: `POST
     /admin/users/mfa-confirm` → 302 verso `mfa-backup-codes`, 10 codici
     mostrati in formato `XXXX-XXXX`.
  4. Verificato via `mysql`: `two_factor_confirmed_at` popolato,
     `two_factor_secret` cifrato (256 caratteri, coerente con
     `Crypt::encryptString()`), 10 righe in `mfa_recovery_codes`.
  5. Rivisitata `mfa-backup-codes`: nessun codice mostrato una seconda
     volta (consumo confermato).
  6. `GET /admin/users/profile`: sezione MFA mostra correttamente "Two-factor
     authentication is active."
  7. `POST /admin/users/mfa-regenerate-backup-codes` → 302, 10 nuovi codici
     mostrati, conteggio in tabella resta 10 (sostituzione, non accumulo).
  8. `POST /admin/users/mfa-revoke-devices` → 302 (nessun dispositivo
     presente in questo test, verificato solo che non erroni).
  9. `POST /admin/users/mfa-disable` con password errata → 302 (rifiutato),
     `two_factor_confirmed_at` resta valorizzato (verificato via `mysql`).
- **Non testato in questa fase** (rimandato apposta): disattivazione con
  password corretta - l'utente superadmin locale resta con l'MFA attivo
  per poter testare il gate di login nella Fase 2 con un account reale.
- Non eseguita la suite di test automatici (nessuna richiesta esplicita).

## Rischi e note

- Il flusso di login (`AdminController::postLogin()`) **non e' ancora
  stato modificato**: un utente con TOTP attivo (come il superadmin locale
  ora) continua a fare login con la sola password fino alla Fase 2 - questo
  e' il prossimo intervento, non un difetto di questa fase.
- La UI di enrollment e' funzionale ma minimale (nessuno stile dedicato
  oltre alle classi Bootstrap/AdminLTE gia' presenti nel tema): coerente con
  l'obiettivo di questa fase (funzionare correttamente), un passaggio di
  UI/UX piu' curato puo' essere fatto in seguito se richiesto.

## Rollback

- `php artisan migrate:rollback --step=1` rimuove `two_factor_last_timeslice`.
- I metodi aggiunti a `AdminCmsUsersController` e la sezione in
  `form.blade.php` sono additivi e circoscritti (`CRUDBooster::
  getCurrentMethod() == 'getProfile'`): rimuoverli non impatta altre pagine.
- Per riportare un utente allo stato "MFA mai attivato": eseguire
  `postMfaDisable` (via UI) oppure, in emergenza, azzerare manualmente
  `two_factor_secret`/`two_factor_confirmed_at`/`two_factor_last_timeslice`
  su `cms_users` e svuotare `mfa_recovery_codes` per quel `user_id`.
