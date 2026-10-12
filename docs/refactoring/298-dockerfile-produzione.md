# 298 - Dockerfile di produzione (docker/Dockerfile.prod) e .dockerignore a lista bianca

- **Data**: 2026-10-08
- **Stato**: Completato (build e avvio verificati in locale; CI/Docker Hub/produzione non provati)
- **Area**: Docker / Infrastruttura
- **File/aree di codice coinvolte**:
  - `docker/Dockerfile.prod` (nuovo)
  - `docker/php/prod.ini`, `docker/apache/000-default.prod.conf`, `docker/apache/zz-prod.conf` (nuovi)
  - `.dockerignore` (riscritto)
  - Non toccati: `docker/Dockerfile`, `docker/entrypoint.sh`, `docker-compose.yml`, `.github/workflows/deploy.yml`

## Contesto

Istanze "Managed" per cliente in container, gestite da un pannello esterno;
l'immagine la costruirà la CI e andrà su Docker Hub privato. Qui c'è solo il
Dockerfile e il necessario per costruirlo; l'entrypoint di produzione (migrazioni)
e il comando di bootstrap dell'admin sono interventi **separati, non fatti**.
Le modifiche al codice sono nel [297](297-docker-prod-versione-proxy-fidati-health.md).

## Situazione prima

Solo l'immagine di sviluppo (`docker/Dockerfile`): codice in bind mount,
git/composer/client MySQL/pdo_sqlite, entrypoint che crea `.env`, installa
composer e crea il DB di test con la password root, `local.ini` con
`display_errors=On`. `.dockerignore` escludeva `*.md`, `tests`, `.env`, ecc.
(elenco nero) ma non i file di lavoro locali.

## Situazione dopo

**Dockerfile.prod, tre stadi** (non due: `base` è condiviso da `vendor` e
`runtime`, così composer verifica i requisiti di piattaforma contro le
estensioni vere invece di ignorarli):
1. `base`: `php:8.3-apache` + stesse estensioni del dev (pdo_mysql, mbstring,
   exif, pcntl, bcmath, gd con freetype/jpeg, zip, intl) + `opcache`
   (nell'immagine ufficiale non è abilitato di default). Compilatore e librerie
   `-dev` rimossi dopo la compilazione (si tengono le librerie runtime).
   Niente git, unzip, composer, client MySQL. `pdo_sqlite` c'è comunque perché
   compilato nell'immagine ufficiale (non è un'aggiunta nostra).
2. `vendor`: `composer install --no-dev --no-scripts --no-autoloader` (layer
   cacheabile su `composer.json/lock`), poi `composer dump-autoload --no-dev
   --optimize --no-scripts`. **Niente `--classmap-authoritative`**: i controller
   del module builder (`storage/app/custom-controllers`) si caricano via PSR-4
   (docs 288). **`post-autoload-dump`**: `--no-scripts` perché
   `php artisan package:discover` avvia l'intera app (provider, rotte che
   interrogano il DB) senza `.env` né DB; il manifest dei pacchetti
   (`bootstrap/cache/packages.php`) lo genera lo stadio runtime con
   `PackageManifest::build()` **senza avviare l'app**. `services.php` si
   rigenera alla prima richiesta.
3. `runtime`: codice copiato con elenco esplicito di cartelle (non `COPY . .`);
   `a2enmod rewrite headers`; stessa conf Apache (DocumentRoot `public/`,
   `AllowOverride All`) più: `/health` escluso dall'access log,
   `ServerTokens Prod`, `ServerSignature Off`, `TraceEnable Off`.
   Codice di proprietà di root (l'app non può riscrivere se stessa), solo
   `storage/` e `bootstrap/cache/` di www-data.

**Utente e porta**: tutto il processo gira come `www-data` (`USER www-data`),
quindi Apache ascolta su **8080**, non 80 (la 80 richiede root): la regola
Traefik deve puntare a 8080. Log di Apache e PHP su stderr (`docker logs`).

**php.ini** (`docker/php/prod.ini` → `conf.d/zz-prod.ini`): `display_errors=Off`,
`log_errors=On` (con mod_php finisce nell'ErrorLog di Apache → stderr),
`error_reporting=E_ALL & ~E_DEPRECATED & ~E_STRICT`, `expose_php=Off`,
upload/post 64M, `max_execution_time=300`, `memory_limit=512M` (come in dev).
OPcache attivo con **`validate_timestamps=1`, `revalidate_freq=2`**: il module
builder scrive/modifica controller a runtime (con `0` non verrebbero visti).
Niente `route:cache` (le rotte dei moduli dipendono dai file presenti) e niente
`config:cache` per ora (variabili a runtime).

**Versione**: `ARG APP_VERSION=unknown` → `ENV APP_VERSION` + label
`org.opencontainers.image.version`. (Quindi `APP_VERSION` è sempre valorizzata
nell'immagine e `MyHelper::version()`/git non viene mai chiamata.)

**Cartelle e symlink**: create nell'immagine `storage/framework/{sessions,views,cache}`,
`storage/logs`, `storage/app/public`, `storage/app/custom-controllers`,
`bootstrap/cache`; `public/storage` è un symlink **relativo**
(`../storage/app/public`), creato a build, senza `storage:link`.

**Nessun segreto**: né `.env` né chiavi; `docker history` non mostra variabili
sensibili (solo `APP_VERSION` e i `ENV` dell'immagine base php). Nessun
ENTRYPOINT (solo `CMD ["apache2-foreground"]`).

**`.dockerignore` a lista bianca**: `*` poi `!app/ !bootstrap/ !config/
!database/ !public/ !resources/ !routes/ !artisan !composer.json !composer.lock
!docker/`, con eccezioni: in `app/Http/Controllers/` (radice) entrano solo
`System/`, `Controller.php`, `CBHook.php`, `Auth/`; fuori `bootstrap/cache/*`,
`public/storage` (symlink assoluto locale), `database/*.sqlite`, `.env*`,
`*.log`. Vale la stessa lista per il build dev (che ha solo `COPY docker/...`:
verificato che `docker/` resti incluso). Nessun `.md` serve a runtime
(solo `public/tinymce/**.md`, di terze parti, che entra comunque in `public/`).

### Cosa succede a `storage/` montato come volume

- **Volume nominato vuoto**: Docker lo popola con il contenuto di
  `/var/www/html/storage` dell'immagine al primo avvio (cartelle create sopra,
  proprietario www-data) → funziona subito. Verificato.
- **Bind mount (cartella host)**: NON viene popolato; la cartella host
  (vuota, di root) va preparata a mano: `mkdir -p` delle sottocartelle sopra e
  `chown -R 33:33` (uid di www-data). Senza, Laravel non può scrivere sessioni,
  viste compilate e log, e `public/storage` resta un link a vuoto.
- Il populate dei volumi nominati avviene **solo se il volume è vuoto**: un
  volume già popolato non riceve le nuove cartelle di versioni successive
  dell'immagine (l'entrypoint futuro dovrebbe fare `mkdir -p`).
- I file che il module builder scrive restano sul volume e sopravvivono a
  `down/up` (verificato). Il codice dell'immagine invece è immutabile.

## Motivazione

Immagine riproducibile e autosufficiente, senza strumenti di sviluppo né
segreti, con la stessa conf Apache/PHP funzionale di oggi. Lista bianca nel
`.dockerignore` perché `COPY` legge il filesystem, non git: in locale la
cartella contiene controller custom di clienti, upload, `local-scripts/`,
`.scratch/`, `docs/`, `.tokensave/`, html/pdf di lavoro, `.env`. Un file
nuovo in radice resta fuori finché non lo si ammette di proposito. Alternative
scartate: `--ignore-platform-reqs` (nasconderebbe estensioni mancanti), Apache
come root con workers www-data (meno sicuro, anche se più semplice).

## Test

Build locale (`docker build -f docker/Dockerfile.prod --build-arg APP_VERSION=dev-local`),
poi compose di prova nello scratchpad (fuori dal repo) con MySQL usa-e-getta:
- Immagine **889 MB** (dev: 1,02 GB). Utente `uid=33(www-data)`. `php -m`:
  bcmath, ctype, curl, dom, exif, fileinfo, gd, intl, mbstring, mysqlnd,
  openssl, pcntl, PDO, pdo_mysql, pdo_sqlite, sodium, sqlite3, xml*, zip,
  zlib, **OPcache**. `php -i`: `display_errors=Off`, `log_errors=On`,
  `expose_php=Off`, `opcache.enable=On`, `validate_timestamps=On`,
  `revalidate_freq=2`, upload/post 64M, memory 512M.
- Contenuto: nella radice dell'immagine solo `app artisan bootstrap composer.*
  config database public resources routes storage vendor`. Niente `.env`,
  `.git`, `tests/`, `docs/`, `.tokensave/`, `local-scripts/`, upload,
  `storage/app/license.json`, né i 10 controller custom locali non tracciati
  (`AdminAziende…`, `ApiMgAziende…`, ecc.). `public/` ha 426 file = i 426
  tracciati da git.
- Avvio: Apache come www-data su 8080, log su stderr; `migrate --force` via
  `docker exec` (come www-data) completo; `/admin/login` 200.
- Module builder (simulato con `CRUDBooster::generateController`, come in 288,
  dentro il container): il file nasce in `storage/app/custom-controllers`
  (www-data, 0644); registrato un `cms_moduls` di prova, `/admin/probe` →
  302 al login (404 per un modulo inesistente), cioè il controller è caricato
  via PSR-4 con l'autoload ottimizzato; una modifica del file è vista da
  Apache entro la finestra di revalidate (2 s); i file sopravvivono a
  `compose down/up` con il volume `storage` persistente.
- Dev invariato: vedi 297 (punto e).

## Rischi e note

- **Sorpresa nel `.dockerignore`/git**: in `app/Http/Controllers/` (radice)
  ci sono ~40 controller **tracciati da git** nonostante il `.gitignore`
  (`AdminLabiosthetique…`, `AdminOPIS…`, `AdminSmartphones…`, ecc.: moduli
  storici di singoli clienti). Il `.dockerignore` li esclude, quindi
  un'immagine costruita dalla CI da checkout pulito **non li contiene** (a
  differenza di una distribuzione "git pull" classica). Nessun codice di
  sistema li referenzia (verificato): per le istanze nuove va bene; per
  clienti che li usassero vanno copiati in `storage/app/custom-controllers`.
- Il primo `migrate` su MySQL vuoto crea lo schema (le migrazioni 2016+
  creano le `cms_*`); *non* crea un admin né la licenza: è il lavoro del
  bootstrap futuro.
- Il log di accesso di Apache mostra l'IP del proxy (non `mod_remoteip`);
  l'IP del client lo vede Laravel (via `TRUSTED_PROXIES`).
- Con `LOG_CHANNEL` non impostato Laravel scrive in `storage/logs/laravel.log`
  (sul volume); per `docker logs` impostare `LOG_CHANNEL=stderr`.
- `system("ip addr")` in `AdminController` (schermata licenza) fallirebbe
  silenziosamente senza `iproute2`: il risultato è comunque scartato
  (`$mac_address = ""`), nessun effetto.
- Scansione di vulnerabilità dell'immagine non fatta.

## Cosa NON è stato verificato

La suite di test (non lanciata); build da checkout pulito e in CI; push su
Docker Hub / pull dall'orchestratore; Traefik e HTTPS reali; creazione di un
modulo **dall'interfaccia** (solo simulato via `generateController`) e il
login con utente reale (nessun admin nel DB di prova); licenza valida/scaduta
con il server di licenza reale; coda/worker/scheduler con questa immagine
(solo `QUEUE_DRIVER=database` impostato, nessun worker lanciato);
`config:cache`; build multi-architettura; carico; bind mount di `storage/`;
`mod_remoteip`; build su host Linux (solo Docker Desktop su Windows).

## Rollback

Eliminare `docker/Dockerfile.prod`, `docker/php/prod.ini`,
`docker/apache/000-default.prod.conf`, `docker/apache/zz-prod.conf` e ripristinare
il `.dockerignore` precedente (`.git .github .idea node_modules vendor
storage/logs storage/framework/{cache,sessions,views} tests *.md .env`).
