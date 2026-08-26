# Sistema di login e di licensing

Panoramica breve di come funzionano oggi l'autenticazione dell'area admin e
il controllo della licenza. Il progetto è basato su **CRUDBooster**
(`packages/crocodicstudio/crudbooster`), un pacchetto legacy vendorizzato
dentro il repo (non su Packagist), che implementa un proprio sistema di
autenticazione **non basato sui guard/Auth standard di Laravel**.

## Login

- **Utenti**: tabella `cms_users` (email, password hash, `id_cms_privileges`,
  `tenant`, `status`). I ruoli/permessi vengono da `cms_privileges` e
  `cms_privileges_roles`; i "tenant" (multi-tenancy) da `tenants`.
- **Flusso**: `AdminController::postLogin()`
  (`packages/crocodicstudio/crudbooster/src/controllers/AdminController.php:222`)
  - valida email/password con `Validator` e `Hash::check()` (bcrypt standard
    Laravel);
  - richiede `status == 'Active'`;
  - controlla che il dominio del tenant corrisponda all'host della richiesta
    (o che l'utente sia superadmin);
  - **prima e dopo** il controllo password, verifica la licenza tramite
    `LicenseHelper::canLicenseLogin()` — se non valida, redirige alla
    schermata di licenza invece di far entrare l'utente;
  - se tutto ok, salva i dati utente in **sessione Laravel** (`Session::put('admin_id', ...)`,
    `admin_is_superadmin`, `admin_privileges_roles`, ecc.) — non usa
    `Auth::login()`/`Auth::user()`.
- **Verifica sessione su ogni richiesta admin**: middleware
  `CBBackend` (`packages/crocodicstudio/crudbooster/src/middlewares/CBBackend.php`),
  applicato alle route sotto il prefisso admin (`config('crudbooster.ADMIN_PATH')`,
  di norma `/admin`). Controlla `CRUDBooster::myId()` (= `Session::get('admin_id')`);
  se vuoto, redirige al login. Gestisce anche lo "schermo di blocco"
  (`admin_lock` in sessione) e il redirect alla dashboard configurata.
- **API**: le route API custom (`routes.php` del pacchetto) passano invece dal
  middleware `CBAuthAPI`, con un meccanismo a token/timestamp header
  (`X-Authorization-Token`, `X-Authorization-Time`), separato dal login web.
- **Qlik JWT login** (`public/js/qlik_*login*.js`,
  `QlikHelper::getJWTToken*`): è un meccanismo **distinto**, usato per il
  Single Sign-On verso Qlik Sense (embedding delle dashboard), non per
  autenticare gli utenti dell'app.
- Nota: `VerifyCsrfToken` è **disabilitato** nel middleware group `web`
  (commentato in `app/Http/Kernel.php`) — la protezione CSRF di Laravel non è
  attiva sulle form, incluso il login.

## Licensing

Il controllo della licenza usa il pacchetto `laravel-ready/license-connector`
più una classe applicativa (`app/Services/ConnectorService.php`) e un helper
del pacchetto CRUDBooster (`LicenseHelper`).

- **Dove sta la licenza**: la chiave di licenza è salvata nella tabella
  `license` (colonna `license_key`, una riga per installazione). Il dato di
  stato/validità viene invece messo in cache su file:
  `storage/app/license.json` (disk `license` in `config/filesystems.php`,
  root = `storage/app/`).
- **Server di licenza remoto**: `config/license-connector.php` legge
  `env('LICENSE_SERVER_URL', 'http://license.thecustomerhive.com')` — un
  servizio esterno, separato da questo progetto, gestito dallo stesso
  utente. Configurabile via `.env` (`LICENSE_SERVER_URL`) per puntare a un
  ambiente dev/staging diverso; se non impostata usa il default di
  produzione. **Attenzione**: una riga `LICENSE_SERVER_URL=` presente ma
  vuota nel `.env` blocca il fallback (una chiave vuota "vince" sul
  default) — se non serve un valore diverso dal default, la riga va
  rimossa del tutto, non lasciata vuota. `ConnectorService`:
  - `getAccessToken()` — fa login al server di licenza (`/auth/login`) con la
    `license_key`;
  - `writeLicense()` — chiama `/license-server/license` sul server remoto e
    scrive la risposta su `storage/app/license.json`;
  - `validateLicense()` — legge `license.json` e controlla `status == 'active'`,
    numero di tenant/utenti consentiti (`tenants_number`, `clients_number`),
    e che `path`/`domain` corrispondano a `APP_PATH`/`APP_DOMAIN` nel `.env`.
- **Quando viene controllata**:
  - **al login** (`postLogin`, vedi sopra) tramite `LicenseHelper::canLicenseLogin()`;
  - **quando si aggiungono tenant/utenti** (`LicenseHelper::canAddTenant()`,
    `canAddUser()`) per rispettare i limiti della licenza;
  - **periodicamente**: il comando schedulato `command:GetLicense`
    (`app/Console/Commands/GetLicense.php`, registrato in
    `app/Console/Kernel.php` con `->hourly()`) richiama `LicenseHelper::writeLicense()`
    ogni ora per rinfrescare `license.json` dal server remoto.
  - **non** viene ricontrollata ad ogni richiesta admin (`CBBackend` controlla
    solo la sessione, non la licenza) — solo al login e nelle azioni sopra.
- **Moduli abilitati dalla licenza**: `LicenseHelper::isActiveQlik()` /
  `isActiveChatAI()` leggono l'array `modules` dentro la licenza per
  abilitare/disabilitare le feature Qlik e Chat AI.
- **Nota operativa**: perché il login funzioni in un ambiente (locale o
  staging) serve una riga in `license` con una `license_key` valida e
  raggiungibilità verso il server di licenza — altrimenti
  `canLicenseLogin()` ritorna `false` e si viene reindirizzati alla schermata
  "License is missing or not valid" invece che alla dashboard.
- **Attivazione della licenza** (`/admin/register-license`,
  `AdminController::getLicensescreen()`/`postActivateLicense()`/
  `postActivateExistingLicense()`): prima di qualunque tentativo, verifica
  che `APP_PATH`/`APP_DOMAIN` siano configurati nel `.env`
  (`licenseEnvironmentIsConfigured()`) — se mancano, redirect al login con
  messaggio esplicativo invece di un errore generico del server remoto.
  Due modalità:
  - **Trial** (form principale): invia i dati al server remoto
    (`LicenseHelper::registerLicense()`, endpoint `/licenses`) per ottenere
    una nuova `license_key`.
  - **"Ho già una licenza"** (link sotto il form): per chi ha già una
    chiave ottenuta fuori da questo flusso. Salta la registrazione remota,
    inserisce direttamente la chiave in `license` e riusa
    `LicenseHelper::writeLicense()` per scaricare/validare i dati dal
    server. In caso di chiave non valida, ripulisce lo stato locale e
    mostra un messaggio d'errore invece di un 500.
  - Entrambe leggono la risposta del server remoto in modo sicuro
    (`isset()`/`??`, mai accesso diretto a proprietà non garantite): una
    risposta inattesa produce un messaggio d'errore leggibile
    (loggato anche in `storage/logs/laravel.log`), non un 500. Vedi
    [`refactoring/003-licensing-hardening.md`](refactoring/003-licensing-hardening.md)
    per il dettaglio dei fix e un bug noto lato server di licenza remoto
    che al momento blocca il completamento del trial.
