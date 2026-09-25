# 098 - MFA (TOTP + Email OTP): Fase 3, recovery

- **Data**: 2026-09-25
- **Stato**: Completato
- **Area**: Auth
- **File/aree di codice coinvolte**:
  - `app/Helpers/MfaHelper.php` (metodi di recovery)
  - `app/Http/Controllers/System/AdminController.php` (`getMfaRecovery()`, `postMfaRecovery()`, `getMfaRecoveryStatus()`, `postMfaRecoveryCancel()`, lazy-apply in `postLogin()`)
  - `routes/crudbooster.php`
  - `resources/views/crudbooster/mfa_recovery.blade.php` (nuovo)
  - `resources/views/crudbooster/mfa_recovery_status.blade.php` (nuovo)
  - `database/migrations/2026_09_25_000006_create_mfa_recovery_requests_table.php` (nuovo)

## Contesto

Ultima fase "funzionale" del piano MFA (dopo `095`, `096`, `097`): cosa
succede a chi ha il TOTP attivo ma perde sia il dispositivo sia i backup
codes. Decisione presa con l'utente: un link via email che programma la
disattivazione fra 30 minuti, con possibilita' di annullare nella stessa
pagina di atterraggio.

## Situazione prima

Chi perdeva sia il dispositivo TOTP sia i backup codes restava bloccato
fuori dal proprio account senza alcun percorso di recupero (a parte un
intervento manuale diretto sul DB).

## Situazione dopo

- **`mfa_recovery_requests`** (nuova tabella): pattern selector/validator
  (stesso di `mfa_trusted_devices`), `requested_at`/`effective_at`
  (+30 minuti), `cancelled_at`/`completed_at`.
- **`MfaHelper`**: `createRecoveryRequest()`, `findRecoveryRequestByToken()`,
  `cancelRecoveryRequest()`, `applyDueRecovery($row)` (disattiva l'MFA se
  scaduta e non annullata/gia' completata),
  `applyDueRecoveryForUser($user)` (cerca richieste scadute per un utente).
- **Applicazione "lazy", nessun cron richiesto**: valutata in due punti
  naturali - `getMfaRecoveryStatus()` (chi riapre il link email) e in cima
  al gate MFA di `postLogin()` (chi prova a fare login prima di riaprire il
  link, o senza mai riaprirlo). Qualunque dei due tocchi arrivi prima rende
  effettiva la disattivazione. Scelta deliberata per non dipendere da uno
  scheduler/cron che, per come e' distribuito questo progetto (installazione
  per-cliente, vedi CLAUDE.md), non e' garantito configurato ovunque.
- **`getMfaRecovery()`/`postMfaRecovery()`**: form pre-login "hai perso il
  dispositivo?", raggiungibile dal link aggiunto in `mfa_verify.blade.php`.
  **Messaggio sempre generico** (`mfa_recovery_sent`), a prescindere
  dall'esistenza dell'account o dallo stato del TOTP: decisione presa qui
  (non nel design doc originale) per non rivelare ne' l'esistenza di
  un'email registrata ne' se ha l'MFA attivo - piu' cauto della validazione
  `exists:` gia' usata da `postForgot()` per il reset password (vedi
  "Rischi e note").
- **`getMfaRecoveryStatus($token)`/`postMfaRecoveryCancel($token)`**:
  landing page con 4 stati (`invalid`, `pending` con countdown e pulsante
  annulla, `cancelled`, `completed`).
- **Route esplicite** in `routes/crudbooster.php`, stesso gruppo/motivo
  delle route MFA della Fase 2 (fuori da `CBBackend`).

## Motivazione

- Messaggio generico invece di rispecchiare la validazione `exists:` di
  `postForgot()`: questo e' un endpoint nuovo, non un comportamento
  esistente da preservare - si e' scelta la versione che rivela meno
  informazioni, senza pero' toccare `postForgot()` stesso (fuori scope,
  comportamento esistente lasciato invariato).
- Applicazione lazy invece di un comando schedulato: piu' semplice, zero
  nuova infrastruttura, funziona identicamente su qualunque installazione
  cliente senza bisogno di verificare che il cron giri.

## Test

Tutti manuali sull'ambiente Docker locale, via `curl` e script diretti
(bootstrap Laravel) per i casi non raggiungibili in HTTP per la stessa
limitazione SMTP gia' vista nella Fase 2:

1. `GET /admin/mfa-recovery` → 200, form mostrato.
2. `POST /admin/mfa-recovery` con l'email del superadmin locale (TOTP
   attivo) → riga creata correttamente in `mfa_recovery_requests`
   (`effective_at` = `requested_at` + 30 minuti, verificato via `mysql`)
   **prima** del tentativo di invio email, che fallisce con la stessa
   `TransportException` gia' documentata in `097` (nessun MTA in locale) -
   comportamento coerente, non un bug introdotto qui.
3. Token reale ottenuto chiamando `MfaHelper::createRecoveryRequest()`
   direttamente (bypassando l'invio email, stesso approccio della Fase 2
   per l'email OTP): `GET /admin/mfa-recovery-status/{token}` → stato
   `pending` mostrato con l'orario corretto.
4. `POST /admin/mfa-recovery-cancel/{token}` → redirect, stato successivo
   `cancelled` confermato, `two_factor_confirmed_at` dell'utente **non**
   toccato (l'MFA resta attiva).
5. **Completamento lazy via pagina di stato**: creata una richiesta con
   `effective_at` retrodatato (simula 30+ minuti passati) → `GET
   mfa-recovery-status/{token}` → stato `completed` mostrato, e verificato
   via `mysql` che `two_factor_confirmed_at`/`two_factor_secret` sono stati
   azzerati e i backup codes eliminati.
6. **Completamento lazy via login**: ri-registrato il TOTP per lo stesso
   utente, creata un'altra richiesta scaduta, chiamata direttamente
   `MfaHelper::applyDueRecoveryForUser($user)` (la stessa funzione invocata
   in cima al gate di `postLogin()`) → confermato che disattiva l'MFA anche
   senza mai passare dalla pagina di stato. Non ripetuto via HTTP end-to-end
   perche', con l'MFA nel frattempo disattivato dal test precedente, un
   nuovo `POST /admin/login` sarebbe caduto nel ramo email OTP e quindi
   nella stessa limitazione SMTP nota - la funzione e' pero' identica,
   chiamata dallo stesso punto del codice.
7. Ripulite tutte le righe di test in `mfa_recovery_requests` al termine.
- `php -l` su tutti i file PHP modificati.

## Rischi e note

- **Stato finale lasciato sull'utente superadmin locale**: l'MFA e'
  **disattivata** (conseguenza dei test di recovery sopra, non un difetto).
  Chi vuole rivedere l'enrollment puo' rifarlo dal profilo
  (`/admin/users/profile`).
- Come gia' notato in `097`, un'email di recovery che fallisce l'invio
  (SMTP giu') produce un 500 anche qui, stesso pattern di `postForgot()`/
  `sendEmailOtp()` - non corretto qui per coerenza, da affrontare come
  intervento a parte se si vuole indurire tutti e tre insieme.
- Il completamento lazy significa che l'orario "esatto" mostrato all'utente
  (`mfa_recovery_pending_message`, es. "alle 12:29") e' quando la richiesta
  *diventa idonea* ad essere completata, non quando lo sara' con precisione
  al secondo - dipende dal prossimo tocco (login o riapertura del link).
  Accettabile per il caso d'uso (non e' un timer di sicurezza in tempo
  reale), ma vale la pena saperlo se in futuro si vuole comunicarlo in modo
  ancora piu' preciso.

## Rollback

- `php artisan migrate:rollback --step=1` rimuove `mfa_recovery_requests`.
- Route e metodi controller sono additivi; rimuoverli lascia la Fase 2
  intatta (nessuna via di recovery per chi perde TOTP+backup codes, come
  prima di questo intervento).
