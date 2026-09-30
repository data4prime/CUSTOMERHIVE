# 170 - Profilo utente a sezioni, con verifica OTP su email/password

- **Data**: 2026-09-30
- **Stato**: Completato (test scritti ma non ancora eseguiti, vedi "Test")
- **Area**: Auth / Frontend (UI/UX)
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/AdminCmsUsersController.php` (getProfile, nuovi `postProfile*`, `postUserResetPassword`, `hook_before_edit`, `getEdit`, password solo in creazione in `cbInit`)
  - `resources/views/users/profile.blade.php` (nuova), `resources/views/users/form.blade.php` (pulsante reset)
  - `app/Helpers/MfaHelper.php` (`sendEmailOtp($user, $to)`, `invalidateEmailOtpCodes`, `bumpSessionVersion`)
  - `app/Http/Middleware/CBBackend.php`, `app/Http/Controllers/System/AdminController.php` (sessione + reset)
  - `database/migrations/2026_09_30_000001_add_session_version_to_cms_users.php`
  - `resources/lang/{en,it}/crudbooster.php`, `tests/Feature/UserProfileSectionsTest.php`

## Contesto

`/admin/users/profile` era il form generico CRUDBooster (`default.form`) con
tutti i campi insieme più la sezione MFA sotto. Richiesta: pagina a sezioni
(Generale / MFA / Sistema / Password) con menu a destra (a sinistra c'è già la
sidebar dei moduli), cambio sezione senza ricaricare la pagina e salvataggio
indipendente per sezione. Layout scelto tra 3 mockup: variante A (card con
icone). In più: cambio email e password protetti da password attuale + codice
di verifica, e password altrui non più impostabile dagli admin.

## Situazione prima

- Un solo form, un solo `edit-save`: salvando si riscrivevano tutti i campi
  (con la lezione del 103: togliere campi da `$this->form` corrompeva i dati).
- Email e password modificabili senza verifica (la password con un campo
  "lascia vuoto per non cambiarla"). Nessuna chiusura delle altre sessioni.
- Status e Expiry date già `disabled` per chi non è admin, Tenant read-only per
  il tenant admin (questo già coerente con le regole di oggi).
- Gli admin potevano impostare la password di un altro utente dal form utenti.
- Tutta l'auth è su sessione legacy (`admin_id`) con driver `file`:
  non si possono enumerare/cancellare le sessioni di un utente.

## Situazione dopo

- Pagina a 4 pannelli, menu a destra (barra orizzontale sotto 800px), sezione
  attiva nell'hash dell'URL (`#mfa`, ecc.). Nessuna libreria nuova.
- Endpoint JSON separati, tutti con lista esplicita di campi (mai `$this->form`):
  - `postProfileGeneral`: nome, lingua, foto; status/scadenza solo se
    superadmin/tenant admin. Email e password ignorate anche se inviate.
    Aggiorna `admin_name`/`admin_photo` in sessione (prima restavano vecchi
    fino al login successivo).
  - `postProfileEmailStart/Confirm`: password attuale + unicità → codice al
    **nuovo** indirizzo (+ TOTP se attivo) → email cambiata, dispositivi MFA
    attendibili revocati, altre sessioni chiuse. Se l'invio del codice fallisce
    si ferma (nessun fail-open, a differenza del login).
  - `postProfileSystem`: tenant solo superadmin; primary group per superadmin e
    tenant admin, solo gruppi del proprio tenant (`group_tenants`); stessa
    logica di appartenenza ai gruppi di `hook_before_edit`. Utenti base: 403.
  - `postProfilePasswordStart/Confirm`: tre campi (attuale, nuova, conferma),
    policy NIST (`not_common_password`), codice TOTP se attivo altrimenti email
    OTP; in sessione solo l'hash della nuova; a fine flusso altre sessioni chiuse.
  - `postUserResetPassword/{id}`: pulsante "Resetta password" sulla scheda di un
    altro utente, invia il link con lo stesso flusso/mail di "Password
    dimenticata". Superadmin su tutti; tenant admin solo utenti base del proprio
    tenant (`UserHelper::can_do_on_user`); mai su se stessi.
- Rate limit su invio/verifica codici (5 tentativi / 15 min, come `postMfaConfirm`).
- **Chiusura altre sessioni**: nuova colonna `cms_users.session_version`
  (default 0). Il login salva il valore in sessione (`admin_session_version`);
  `CBBackend` lo confronta a ogni richiesta con `Auth::user()->session_version`
  (nessuna query in più) e fa logout se è rimasto indietro. Si incrementa con
  `MfaHelper::bumpSessionVersion()`: cambio password/email dal profilo, reset
  password via link (`postResetPassword`), email di un altro utente cambiata da
  un admin.
- Form utenti: il campo password esiste solo in **creazione** (`isAddPage()`);
  in modifica non c'è, quindi `input_assignment()` non tocca mai la password,
  nemmeno con una POST costruita a mano. `users/edit/{mio id}` ora rimanda al
  profilo e `hook_before_edit` ignora email/password quando ci si modifica da
  soli (altrimenti il form utenti avrebbe aggirato OTP e password attuale).
- Le redirect delle azioni MFA ora tornano a `users/profile#mfa`.

## Motivazione

Sezioni indipendenti = salvataggi piccoli e verificabili, niente più
riscrittura di tutti i campi. Email e password sono le credenziali di accesso
(e l'email è anche l'identificativo delle integrazioni API via header
`X-User`): cambiarle senza prova di possesso permetteva a chi trova una sessione
aperta di prendersi l'account. Alternativa scartata per le sessioni:
`Auth::logoutOtherDevices` (non copre la sessione legacy) o passare a driver
sessione `database` (cambio di infrastruttura per ogni cliente).

## Test

- `php -l` su tutti i file PHP toccati e compilazione Blade + `php -l` del
  compilato per `users/profile` e `users/form`; `php artisan migrate` eseguita
  sul DB locale Docker; `route:list` mostra le 6 nuove route POST.
- Scritti 20 test in `tests/Feature/UserProfileSectionsTest.php` (salvataggio per
  sezione, flussi OTP email/password, sessioni invalidate, permessi Sistema,
  reset password per ruolo, aggiramento via form utenti). **Non eseguiti**:
  la suite parte solo su richiesta esplicita. Da lanciare prima del push:
  `docker compose exec app php artisan test --filter=UserProfileSectionsTest`
  e poi `UsersCrudTest`, `LoginTest`, `LogoutTest`, `CBBackendTest`.
- Non verificato: rendering reale nel browser (layout, JS AJAX, tab), invio di
  email vere (SMTP), comportamento Qlik SaaS con un claim email diverso.

## Rischi e note

- **Comportamenti visibili cambiati**: la password non si imposta più dal form
  utenti in modifica; cambio email/password richiedono password attuale + codice;
  senza SMTP (e senza TOTP) email/password **non sono modificabili** finché la
  posta non è configurata; `users/edit/{mio id}` porta al profilo; dopo un reset
  password via link le altre sessioni si chiudono.
- **Integrazioni API**: `ApiController::login` identifica l'utente per email
  (`X-User`): chi cambia email rompe le proprie integrazioni. L'avviso è nel pannello.
- **Deploy**: serve `php artisan migrate` su ogni cliente (colonna additiva con
  default 0, non tocca le sessioni esistenti finché nessuno cambia credenziali).
  Durante un deploy parziale (codice nuovo, migration non ancora eseguita) il
  controllo sessioni non fa nulla (`session_version` null = 0).
- Qlik SaaS: il claim `email` del JWT cambia con l'email; `sub` (idp_qlik) no.
  Non verificato come reagisce Qlik (aggiorna/ignora/duplica).
- Il log contiene l'email nel testo: le righe vecchie mostrano quella vecchia.
- Tenant admin: già non poteva modificare superadmin/altri tenant admin né utenti
  di altri tenant (`can_do_on_user`); il reset password usa la stessa regola.
- Le chiavi dei dispositivi MFA e i codici OTP email condividono la tabella con
  il login: prima di inviare un codice dal profilo i precedenti non usati vengono
  invalidati (`invalidateEmailOtpCodes`).

## Rollback

Ripristinare da git i file elencati sopra e rimuovere la vista
`users/profile.blade.php`; la colonna `session_version` può restare (inerte) o
essere tolta con `php artisan migrate:rollback --step=1` (è l'ultima migration).
