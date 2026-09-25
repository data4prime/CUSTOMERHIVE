# 104 - MFA: elenco dei dispositivi da cui si è effettuato l'accesso

- **Data**: 2026-09-25
- **Stato**: Completato
- **Area**: Auth
- **File/aree di codice coinvolte**:
  - `app/Helpers/MfaHelper.php` (`listTrustedDevices()`, `revokeTrustedDevice()`)
  - `app/Http/Controllers/System/AdminCmsUsersController.php` (`getProfile()`, `postMfaRevokeDevice()`)
  - `resources/views/crudbooster/default/form.blade.php`
  - `resources/lang/en/crudbooster.php`, `resources/lang/it/crudbooster.php`

## Contesto

Richiesta esplicita dell'utente: poter vedere i dispositivi da cui è stato
effettuato l'accesso. Il progetto non traccia sessioni attive in generale
(sessioni su file, non su database - costruire quello sarebbe una feature
molto piu' grande, non richiesta). La tabella `mfa_trusted_devices`
(Fase 2, `097`) gia' registra esattamente questo per il ramo email OTP:
ogni dispositivo che ha superato la verifica email e viene ricordato per
30 giorni scorrevoli - mancava solo una UI per vederli/gestirli
singolarmente (c'era gia' solo "disconnetti tutti in blocco").

## Situazione prima

`mfa_trusted_devices` esisteva e veniva scritta/letta solo internamente
(`MfaHelper::hasTrustedDevice()`/`issueTrustedDeviceCookie()`), nessun modo
per l'utente di vedere quali dispositivi sono ricordati ne' di revocarne
uno singolo (solo "Sign out all remembered devices", tutto o niente).

## Situazione dopo

- **`MfaHelper::listTrustedDevices(User $user)`**: righe non scadute
  dell'utente, piu' recenti prima (per `last_used_at`).
- **`MfaHelper::revokeTrustedDevice(User $user, int $deviceId)`**: cancella
  una singola riga, **scoped a `user_id`** (chi chiama non puo' revocare
  il dispositivo di un altro utente indovinando l'id della riga).
- **`AdminCmsUsersController::getProfile()`**: passa alla vista l'elenco
  dei dispositivi fidati dell'utente corrente.
- **`postMfaRevokeDevice($id)`** (nuovo, auto-instradato per riflessione
  come gli altri metodi Mfa* di questo controller): revoca un singolo
  dispositivo.
- **Vista profilo**: nuova sezione "Devices you have signed in from"
  dentro la card MFA (sotto ai pulsanti esistenti, visibile **indipendente**
  dallo stato del TOTP - riguarda solo il ramo email OTP), tabella con
  User-Agent, prima volta riconosciuto, ultimo utilizzo, scadenza, e un
  pulsante "Sign out" per riga. Sezione nascosta del tutto se non ci sono
  dispositivi ricordati.

## Test

- `php -l` sui file PHP modificati.
- Creato un dispositivo fidato di test (stesso helper usato internamente,
  `issueTrustedDeviceCookie()`, user agent finto "TestDevice/1.0") e
  verificato dal vivo in browser che compare nella tabella con i valori
  corretti.
- Click reale su "Sign out" per quella riga → toast di conferma, riga
  sparita dalla tabella, verificato via `mysql` che la riga e' stata
  davvero cancellata (`COUNT(*) = 0`).
- Non eseguita la suite di test automatici (nessuna richiesta esplicita).

## Rischi e note

Nessuno di rilievo: funzionalita' additiva, nessuna colonna/tabella nuova
(riusa `mfa_trusted_devices` gia' esistente), nessun cambiamento al
comportamento di login esistente.

## Rollback

Rimuovere la sezione dalla vista e i due nuovi metodi (`postMfaRevokeDevice`,
`listTrustedDevices`/`revokeTrustedDevice`) - nessun impatto su altro,
`mfa_trusted_devices` continua a funzionare per lo step-up email anche
senza questa UI.
