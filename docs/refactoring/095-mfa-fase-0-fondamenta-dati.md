# 095 - MFA (TOTP + Email OTP): Fase 0, fondamenta dati

- **Data**: 2026-09-25
- **Stato**: Completato (questa fase — prima di un percorso più lungo, vedi sotto)
- **Area**: Auth
- **File/aree di codice coinvolte**:
  - `composer.json` (nuove dipendenze)
  - `database/migrations/2026_09_25_000000_add_mfa_columns_to_cms_users.php`
  - `database/migrations/2026_09_25_000001_create_mfa_recovery_codes_table.php`
  - `database/migrations/2026_09_25_000002_create_mfa_trusted_devices_table.php`
  - `database/migrations/2026_09_25_000003_add_mfa_email_templates.php`
  - `database/seeders/CmsEmailTemplatesSeeder.php`

## Contesto

Richiesta esplicita dell'utente di implementare l'MFA (TOTP + Email OTP)
seguendo un documento di design di riferimento esterno (`Metodi di
autenticazione/MFA.md`, `2FA/Design MFA.md`, `2FA/TOTP.md`, `2FA/Email
OTP.md`, `2FA/WebAuthn.md`), analizzato e discusso in sessione prima di
scrivere codice. Riprende il lavoro lasciato in sospeso il 2026-09-16 (vedi
memoria di progetto `project-password-auth-hardening`), quando l'MFA era
stato progettato ma rimandato su richiesta dell'utente.

Decisioni prese con l'utente prima di questa fase (non ridiscusse qui):
- **Step-up email OTP in stile GitHub**: solo per chi non ha il TOTP attivo,
  e solo se il dispositivo non è già "fidato"; un TOTP attivo non viene mai
  bypassato dal device trust.
- **Dispositivi fidati**: scadenza scorrevole di 30 giorni dall'ultimo
  utilizzo (non fissa dalla creazione).
- **Cifratura del segreto TOTP**: `Crypt`/`APP_KEY` di Laravel, nessuna
  chiave dedicata — zero impatto sul processo di aggiornamento cliente
  (duplicazione DB + copia file, vedi CLAUDE.md). Nota per il futuro: se
  `APP_KEY` viene mai rigenerato su un ambiente con MFA già attivo per
  qualche utente, i segreti salvati diventano illeggibili (dovranno
  riattivare l'MFA da capo) — oggi `APP_KEY` non viene mai toccato dopo il
  setup iniziale, ma va tenuto a mente.
- **Rollout v1**: solo opt-in per tutti, nessun obbligo per superadmin/
  tenantadmin in questa fase (si riprende in futuro se richiesto).
- **Recovery** (Fase 3, non ancora scritta): un link via email programma la
  disattivazione dell'MFA fra 30 minuti, con possibilità di annullare nella
  stessa pagina di atterraggio entro quella finestra.
- **Scadenza email OTP** (Fase 2, non ancora scritta): 10 minuti.

## Situazione prima

- Nessun pacchetto 2FA/Fortify installato, nessuna colonna/tabella relativa
  all'MFA. Login completamente custom (`AdminController::postLogin()`,
  `app/Http/Controllers/System/AdminController.php:328`): solo
  `Hash::check()` + controllo tenant/dominio + licenza, poi popolamento
  sessione legacy (`Session::put('admin_id', ...)`) e guard nativo
  (`Auth::login($userModel)`).
- `cms_users` non aveva alcuna colonna relativa a un secondo fattore.
- Invio email esistente basato su `CRUDBooster::sendEmail()`
  (`app/Helpers/CRUDBooster.php:1012`), che legge un template da
  `cms_email_templates` per `slug` e sostituisce placeholder `[chiave]` —
  stesso meccanismo già riusato per il reset password
  (`docs/refactoring/072-*.md`).

## Situazione dopo

- **Dipendenze**: `pragmarx/google2fa` (^9.1, algoritmo TOTP puro RFC 6238,
  compatibile con qualunque app authenticator — non solo Google
  Authenticator, vedi discussione in sessione) e `bacon/bacon-qr-code`
  (^3.1, generazione QR lato server per l'enrollment, Fase 1).
- **`cms_users`**: due nuove colonne nullable (zero impatto su chi non
  attiva l'MFA): `two_factor_secret` (text, verrà salvato cifrato con
  `Crypt`, non hashato — va riletto per ricalcolare il codice atteso) e
  `two_factor_confirmed_at` (timestamp — assenza = MFA non attivo, stesso
  criterio già usato per `two_factor_enabled_at` nella policy password di
  `project-password-auth-hardening`).
- **`mfa_recovery_codes`** (nuova tabella): backup codes monouso generati
  all'enrollment (Fase 1). `code_hash`, non testo cifrabile: sono
  equivalenti a una password monouso.
- **`mfa_trusted_devices`** (nuova tabella): pattern selector/validator
  (come un "remember me" sicuro — selettore in chiaro per la lookup via
  indice, validator hashato per il confronto), `trusted_until` pensato per
  essere rinnovato ad ogni uso valido (scorrevole, la logica di rinnovo è
  nella Fase 2, non ancora scritta — questa migration crea solo lo schema).
- **`cms_email_templates`**: due nuovi template seedati via migration dati
  (idempotente, guardia `whereNotExists` sullo slug) — `mfa_email_otp`
  (placeholder `[otp_code]`) e `mfa_recovery_request` (placeholder
  `[recovery_url]`) — più lo stesso inserimento in
  `CmsEmailTemplatesSeeder.php` per le installazioni nuove che partono dai
  seeder invece che dallo storico delle migration (stesso criterio già
  seguito per `forgot_password_backend`).

Nessun controller, nessuna route, nessuna vista toccati in questa fase:
tabelle e colonne restano inutilizzate finché non si scrive la Fase 1
(enrollment). Comportamento del login **invariato** per tutti gli utenti
esistenti.

## Motivazione

Fase 0 isolata dal resto del piano per poter verificare lo schema dati (e il
rollback) prima di costruirci sopra logica applicativa. Scelte tecniche
principali:
- `Crypt`/`APP_KEY` invece di una chiave di cifratura dedicata: discusso con
  l'utente, preferito per non impattare il processo di aggiornamento
  cliente (duplicazione DB + copia file per singolo cliente).
- Nessun vincolo di chiave esterna (`foreign()`) su `user_id`: coerente con
  lo stile già usato dalle altre tabelle `cms_*`/satellite di questo
  progetto (es. `cms_logs.id_cms_users`), che non usano FK a livello DB.
- `unsignedInteger` per `user_id` (non `unsignedBigInteger`): `cms_users.id`
  è `increments()` (int unsigned), non `bigIncrements()` — tipi coerenti.

## Test

- `php -l` su tutti i file nuovi/modificati (nessun errore di sintassi).
- Migration eseguite sull'ambiente Docker locale: `php artisan migrate`
  (le 4 migration nuove applicate correttamente), poi `php artisan
  migrate:rollback --step=4` (rollback pulito, nessun errore) e infine
  `php artisan migrate` di nuovo per riportare l'ambiente allo stato atteso.
- Verificato a mano via `mysql` lo schema risultante (`DESCRIBE cms_users`,
  `mfa_recovery_codes`, `mfa_trusted_devices`) e il contenuto seedato di
  `cms_email_templates` per i due nuovi slug.
- Smoke test delle due librerie dentro il container (`Google2FA::
  generateSecretKey()` + `getCurrentOtp()`, `class_exists('BaconQrCode\Writer')`):
  entrambe funzionanti.
- Non eseguita la suite di test automatici (nessuna richiesta esplicita,
  vedi `feedback-tests-only-on-request`) — nessun test esistente tocca
  queste tabelle/colonne comunque, essendo additive e inutilizzate.

## Rischi e note

- Questa è solo la Fase 0 di 5 (vedi piano discusso in sessione): Fase 1
  (enrollment TOTP self-service da `AdminCmsUsersController::getProfile()`),
  Fase 2 (login con step-up in `AdminController::postLogin()`), Fase 3
  (recovery), Fase 4 (rate limiting/hardening) restano da scrivere. Fase 5
  (obbligo per ruoli critici) non pianificata per la v1 su richiesta
  esplicita dell'utente.
- Le due nuove tabelle e le due nuove colonne sono presenti ma **non lette
  né scritte da nessun codice applicativo** finché non si implementa la
  Fase 1: nessun rischio di regressione sul login esistente.
- Nota su `APP_KEY` già segnalata sopra (Contesto): da tenere a mente per
  chi gestirà in futuro la rotazione di quella chiave.

## Rollback

- `php artisan migrate:rollback --step=4` (verificato funzionante in
  questa sessione) rimuove le due tabelle, le due colonne su `cms_users` e i
  due template email.
- In alternativa, `composer remove pragmarx/google2fa bacon/bacon-qr-code`
  per rimuovere anche le dipendenze (non necessario se si prevede di
  proseguire con la Fase 1).
