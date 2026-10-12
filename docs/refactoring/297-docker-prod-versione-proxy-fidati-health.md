# 297 - Immagine di produzione, parte codice: APP_VERSION, proxy fidati, /health

- **Data**: 2026-10-08
- **Stato**: Completato (verificato in locale con Docker, non in produzione)
- **Area**: Docker / Config / Infrastruttura
- **File/aree di codice coinvolte**:
  - `config/app.php` (`version`)
  - `config/trustedproxy.php` (nuovo), `app/Http/Kernel.php`
  - `app/Http/Controllers/System/HealthController.php` (nuovo), `app/Providers/RouteServiceProvider.php`
  - `.env.example` (variabili commentate)

## Contesto

CustomerHive deve girare in container Docker di produzione, una istanza per
cliente, dietro un reverse proxy (Traefik) che termina l'HTTPS e con un
orchestratore esterno che controlla ogni 5 secondi se l'istanza è viva. Per
questo servono tre piccole modifiche al codice, tutte *behavior-preserving*:
senza le nuove variabili d'ambiente il comportamento è identico a oggi.
Il Dockerfile è nell'intervento [298](298-dockerfile-produzione.md).

## Situazione prima

1. `config('app.version')` = `MyHelper::version()` → `tremby/laravel-git-version`
   (`git describe`, con ripiego sul file `version`). Nell'immagine non ci sono
   né `.git` né il binario `git`.
2. Nessun middleware per i proxy fidati: dietro Traefik Laravel avrebbe visto
   `http` e l'IP del proxy (URL e redirect sbagliati).
3. Nessun endpoint di salute.

## Situazione dopo

1. **Versione**: `'version' => env('APP_VERSION') ?: MyHelper::version()`. Con
   `APP_VERSION` non vuota si usa quella e `MyHelper::version()` **non viene
   nemmeno chiamata** (l'operatore `?:` non valuta il secondo termine).
   Senza variabile: identico a prima.

   **Dove si usa `config('app.version')`** (ricerca letterale su tutto il
   repo): *solo* nel footer, `resources/views/crudbooster/footer.blade.php:9`,
   `{{ Session::get('appname') }} {{ config('app.version') }}`. Non è mostrata
   altrove, **non** è inviata dal licensing né dal check-in (nessun punto del
   codice la legge), non finisce in log o intestazioni. Un valore come
   `dev-3f2a1c9` o `v1.2.3` è quindi solo testo nel footer (escapato da
   `{{ }}`): nessun problema. Nota: in locale oggi il footer mostra `5` perché
   `MyHelper::version()` taglia il `git describe` al primo punto; il valore di
   `APP_VERSION` invece è usato così com'è, senza tagli.
2. **Proxy fidati**: `Illuminate\Http\Middleware\TrustProxies` (quello del
   framework, nessuna sottoclasse) come primo middleware globale in
   `app/Http/Kernel.php` (il progetto usa il Kernel classico, non
   `bootstrap/app.php` con `withMiddleware`). Legge
   `config('trustedproxy.proxies')` (il framework già lo prevede come
   ripiego), definito in `config/trustedproxy.php` come
   `env('TRUSTED_PROXIES') ?: null`: lista di IP/CIDR separati da virgola
   oppure `*`. Non impostata = `null` = nessun proxy fidato = comportamento di
   oggi. L'`env()` sta nel file di config: compatibile con `config:cache`.
3. **`GET /health`**: `HealthController` (invocabile) → `select 1` sul DB di
   default. `200 {"status":"ok"}` se risponde, `503 {"status":"unavailable"}`
   per qualunque eccezione; `Cache-Control: no-store`. Registrato in
   `RouteServiceProvider::mapHealthRoute()` **senza gruppo di middleware**:
   niente `web` (quindi niente sessione né cookie — verificato, nessun
   `Set-Cookie`), niente `CBBackend`/`EnforceModuleLicense`, fuori da
   `ADMIN_PATH`. Nessun dettaglio nel corpo (no versione, host, messaggi).
   Il timeout di connessione PDO per questa richiesta è portato a 3 s.

   **Scelta importante**: per `GET`/`HEAD /health` (solo quelli, non da riga di
   comando) `RouteServiceProvider::map()` registra *solo* la rotta di salute e
   salta gli altri file di rotte. Motivo: `routes/crudbooster.php` interroga il
   DB (`cms_moduls`) e scandisce/istanzia tutti i controller custom **a ogni
   richiesta** (non c'è `route:cache`); con il DB giù il controllo impiegava
   47 s invece di rispondere 503, e con un controller custom rotto rispondeva
   500. Effetto utile: `/health` resta leggero (~0,4 s in container, contro
   ~2 s di una pagina normale in dev) e non dipende dai moduli dei clienti.
   Nessun conflitto con rotte esistenti: non ci sono rotte jolly in radice, i
   moduli vivono sotto `admin/`, `api/`, `api2/`, `uploads/`.

   **Log di accesso**: l'applicazione non ha un log delle richieste; il rumore
   possibile è l'access log di Apache, che nell'immagine di produzione esclude
   `/health` (vedi [298](298-dockerfile-produzione.md)).

## Motivazione

Prerequisiti minimi per l'immagine di produzione, senza toccare il percorso
di sviluppo locale. Alternative scartate: `public/health.php` standalone (non
passa da Laravel ma dovrebbe leggere le credenziali DB da `getenv()` invece
che dalla config, rompendo chi usa `.env`); `/up` di Laravel (qui non c'è
`withRouting(health:)`).

## Test

Fatto (Docker locale: immagine di produzione + MySQL usa-e-getta, e container
di sviluppo):
- `php -l` sui file toccati.
- Produzione: `/health` 200 con DB su; 503 con DB spento; nessun `Set-Cookie`;
  `Server: Apache` senza versione; `POST /health` → 405; risponde 200 anche
  con DB senza tabelle/licenza e con un controller custom non caricabile;
  `config('app.version')` = `APP_VERSION`.
- Proxy: con `TRUSTED_PROXIES=*` e `X-Forwarded-Proto: https` +
  `X-Forwarded-Host` il redirect di `/` è `https://<host forwarded>/admin`;
  senza la variabile resta `http://localhost:8081/admin`.
- Sviluppo (container attuale, nessuna variabile): `app.version` = `5` (git),
  `trustedproxy.proxies` = `null`, un `X-Forwarded-Proto: https` viene
  ignorato, `/health` 200, `/admin/login` 200.
- **Non verificato**: la suite di test (non lanciata, per regola);
  `config:cache`; HTTPS reale con Traefik; `X-Forwarded-For` (IP client)
  end-to-end; `TRUSTED_PROXIES` come lista di CIDR; /health con una licenza
  reale valida/scaduta (provato solo senza riga di licenza).

## Rischi e note

- **Cambio di comportamento visibile: nessuno** senza le nuove variabili. Con
  `APP_VERSION` il footer mostra quel valore verbatim.
- `TRUSTED_PROXIES=*` fa fidare di qualunque chiamante: ammesso solo se il
  container non è raggiungibile se non attraverso il proxy. Meglio il CIDR
  della rete Traefik.
- Con DB giù **per risoluzione DNS** (nome host che non risolve più, tipico di
  un container DB eliminato) il 503 arriva dopo ~8–16 s (il DNS scade due
  volte, il timeout PDO non lo copre); con DB che rifiuta la connessione è
  immediato, con rete che scarta i pacchetti ~3–6 s. L'orchestratore deve
  avere un timeout e trattarlo come "non vivo".
- `/health` è pubblico: non rivela nulla, ma è raggiungibile da chiunque
  raggiunga l'istanza (si può nascondere a livello Traefik).

## Rollback

Ripristinare `config/app.php` (`'version' => \App\Helpers\MyHelper::version()`),
togliere la riga `TrustProxies` da `app/Http/Kernel.php`, rimuovere
`config/trustedproxy.php`, `HealthController`, `mapHealthRoute()` e il blocco
`if (...) return;` in `RouteServiceProvider::map()`.
