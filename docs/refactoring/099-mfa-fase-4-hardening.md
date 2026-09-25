# 099 - MFA (TOTP + Email OTP): Fase 4, hardening

- **Data**: 2026-09-25
- **Stato**: Completato
- **Area**: Auth
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/AdminCmsUsersController.php` (rate limit su `postMfaConfirm`)
  - `app/Http/Controllers/System/AdminController.php` (rate limit su `postMfaRecovery`)
  - `app/Console/Commands/MfaCleanup.php` (nuovo)
  - `app/Console/Kernel.php` (registrazione comando + schedule)

## Contesto

Ultima fase del piano MFA (dopo `095`-`098`): irrobustire i punti lasciati
volutamente semplici nelle fasi precedenti (l'enrollment non aveva ancora
un limite ai tentativi sul codice di conferma, la richiesta di recovery non
aveva un limite alla frequenza) e aggiungere manutenzione di base (pulizia
righe scadute) coerente con il resto del progetto (stesso meccanismo di
scheduling gia' usato per `user:check-expiry`).

## Situazione prima

- `postMfaConfirm()` (Fase 1): nessun limite ai tentativi - un codice a 6
  cifre e' forzabile a forza bruta senza un freno sulla frequenza (OWASP
  raccomanda un limite ~5, gia' applicato invece a `postMfaVerify()` e
  `postMfaResendEmailOtp()` fin dalla Fase 2).
- `postMfaRecovery()` (Fase 3): nessun limite - un indirizzo email poteva
  essere bersagliato di email di recovery ripetute, e il volume di
  risposte poteva in teoria essere usato per enumerare account.
- Tabelle `mfa_email_otp_codes`, `mfa_trusted_devices`,
  `mfa_recovery_requests`: righe scadute/concluse restano per sempre senza
  nessuna pulizia.

## Situazione dopo

- **`postMfaConfirm()`**: stesso schema di rate limiting delle altre
  verifiche (5 tentativi, poi blocco 15 minuti, chiave per utente).
  **Bug trovato e corretto durante il test**: la prima stesura usava
  `\RateLimiter::` (backslash, stesso stile di `\Hash::` gia' presente nel
  file) assumendo un alias globale che pero' esiste per `Hash` (registrato
  in `config/app.php`) ma non per `RateLimiter` - causava un 500
  ("Class RateLimiter not found") ad ogni tentativo. Corretto con un vero
  `use Illuminate\Support\Facades\RateLimiter;` e chiamate senza backslash.
- **`postMfaRecovery()`**: rate limit 3 richieste/ora per combinazione
  email+IP (hash dell'email nella chiave, l'email in chiaro non serve).
  Risposta identica sia che il limite scatti sia che la richiesta vada a
  buon fine (stesso messaggio generico gia' deciso in `098`), per non far
  trapelare nemmeno il fatto che il rate limit e' scattato.
- **`MfaCleanup`** (nuovo comando `mfa:cleanup`): rimuove email OTP scaduti
  da >1 giorno, dispositivi fidati scaduti, richieste di recovery concluse
  (annullate o completate) da >30 giorni. Pulizia di manutenzione, non di
  sicurezza - le righe scadute non vengono comunque mai accettate dalle
  query di verifica in `MfaHelper`, quindi un ritardo nella pulizia non e'
  un rischio. Schedulato giornalmente (`dailyAt('03:00')`) nello stesso
  `App\Console\Kernel::schedule()` gia' usato per `user:check-expiry`/
  `user:expiry-notification` - stesso meccanismo, stesso presupposto
  (dipende da un cron `schedule:run` configurato sull'installazione, non
  garantito su ogni cliente, ma coerente con cio' che il progetto gia' fa
  per altre pulizie).

## Motivazione

Chiusura simmetrica: ogni punto che accetta un codice/segreto fornito
dall'utente (TOTP di conferma, TOTP/backup code di login, email OTP,
richiesta di recovery) ha ora lo stesso tipo di freno alla forza bruta/spam,
nessuno lasciato scoperto.

## Test

- `php -l` su tutti i file modificati.
- `php artisan mfa:cleanup` eseguito su Docker locale: con tabelle vuote
  (0 rimosse su tutte e tre), poi con righe di test inserite ad-hoc
  (un email OTP scaduto di 2 giorni, un dispositivo fidato scaduto di 1
  giorno, una recovery request annullata 35 giorni fa) → tutte e tre
  correttamente rimosse in un colpo solo, verificato via `mysql`.
- **`postMfaConfirm` rate limit**: riprodotto dal vivo via `curl` con
  sessione autenticata reale - 6 tentativi con codice errato di fila.
  Prima esecuzione → **500 reale** (`Class "RateLimiter" not found`),
  bug corretto (vedi sopra), poi ripetuto: dopo `RateLimiter::clear()` di
  pulizia, un singolo tentativo errato porta il contatore a 1
  (verificato con uno script diretto), sesto tentativo su sei bloccato dal
  limite (messaggio "Too many attempts" mostrato sulla pagina di
  enrollment).
- **`postMfaRecovery` rate limit**: 4 richieste di fila per la stessa email
  (mai registrata) → tutte 302 con lo stesso messaggio generico, ma il
  contatore interno (verificato via script diretto) si ferma a 3, il quarto
  tentativo non lo incrementa oltre - confermato il blocco silenzioso.
- Stato di test ripulito al termine (contatori `RateLimiter` azzerati).
- Non eseguita la suite di test automatici (nessuna richiesta esplicita).

## Rischi e note

- **Il bug del 500 su `postMfaConfirm` sarebbe stato visibile a chiunque
  avesse provato ad attivare l'MFA** prima di questo test - trovato e
  corretto nella stessa sessione in cui e' stato introdotto (Fase 4),
  quindi mai arrivato a uno stato "consegnato" senza verifica.
- Il rate limiting su `postMfaRecovery` e su `postMfaConfirm` condivide la
  stessa cache di Laravel usata per tutto il resto dell'app: uno
  svuotamento della cache (`php artisan cache:clear`) azzera anche questi
  contatori, comportamento normale e non specifico di questa feature.
- `mfa:cleanup` presuppone un cron `schedule:run` configurato
  sull'installazione - se assente (non garantito per ogni cliente, vedi
  CLAUDE.md sul processo di aggiornamento per-cliente), le righe scadute
  restano ma restano anche innocue (mai accettate dalle query di verifica).

## Chiusura del piano MFA

Con questa fase si chiude l'implementazione funzionale del piano MFA
discusso con l'utente (`095`-`099`): enrollment TOTP con backup codes,
login con step-up email in stile GitHub e dispositivi fidati a scadenza
scorrevole, recovery con finestra di attesa e annullamento, rate limiting e
anti-replay su ogni punto che accetta un codice. **Fase 5 (obbligo MFA per
superadmin/tenantadmin) non e' stata pianificata per questa v1**, su
richiesta esplicita dell'utente - resta opt-in per tutti finche' non verra'
richiesto altrimenti.

## Rollback

- I metodi di rate limiting sono additivi (solo aggiunta di controlli
  prima della logica esistente): rimuoverli riporta al comportamento della
  Fase 1/3 (nessun limite).
- Rimuovere `MfaCleanup` dai `$commands`/`schedule()` di
  `App\Console\Kernel` e cancellare il file disattiva la pulizia
  automatica (nessun impatto sulla sicurezza, solo sulle dimensioni delle
  tabelle nel tempo).
