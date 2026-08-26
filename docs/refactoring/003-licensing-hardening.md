# 003 - Licensing: env configurabile, registerLicense() sicuro, opzione "ho già una licenza", riattivazione controlli

- **Data**: 2026-08-26
- **Stato**: Completato
- **Area**: Licensing
- **File/aree di codice coinvolte**:
  - `packages/crocodicstudio/crudbooster/src/helpers/LicenseHelper.php`
  - `packages/crocodicstudio/crudbooster/src/controllers/AdminController.php` (`getLicensescreen`, `postActivateLicense`, nuovo `postActivateExistingLicense`, `licenseEnvironmentIsConfigured`)
  - `packages/crocodicstudio/crudbooster/src/views/license.blade.php`
  - `packages/crocodicstudio/crudbooster/src/routes.php`
  - `app/Services/ConnectorService.php`
  - `config/license-connector.php`
  - `.env`, `.env.example`

## Contesto

Dopo aver disattivato temporaneamente i controlli di licenza per poter
lavorare in locale (vedi memoria `project-license-disabled-dev` e
`docs/pre-push-checklist.md`), si è deciso di affrontare tre miglioramenti
concordati con l'utente prima di riattivare i controlli:

1. verifica preventiva che l'ambiente sia configurato (`APP_PATH`/`APP_DOMAIN`)
   prima di tentare un'attivazione licenza;
2. sostituire l'implementazione cURL grezza di `registerLicense()`
   (verifica SSL disattivata, timeout infinito) con il client HTTP di
   Laravel;
3. aggiungere un'opzione "ho già una licenza" per chi ha già ottenuto una
   chiave fuori dal flusso trial.

A questi si sono aggiunti, emersi durante l'implementazione e la verifica
manuale, la riattivazione vera e propria dei controlli e la correzione di
due bug preesistenti che l'avrebbero altrimenti resa impossibile.

## Situazione prima

- `config/license-connector.php` aveva l'URL del server di licenza
  **hardcoded**, nessuna verifica che `APP_PATH`/`APP_DOMAIN` fossero
  configurati prima di tentare un'attivazione.
- `LicenseHelper::registerLicense()` usava cURL grezzo con
  `CURLOPT_SSL_VERIFYHOST/VERIFYPEER => 0` e `CURLOPT_TIMEOUT => 0` (nessun
  timeout — una richiesta bloccata restava appesa indefinitamente).
- Nessuna opzione per attivare con una chiave già esistente: l'unico modo
  era il flusso trial (`postActivateLicense`).
- I 4 controlli di licenza (`canLicenseLogin`, `canAddTenant`, `canAddUser`,
  `getLicenseInfo`) erano disattivati con return anticipati marcati
  `LICENSE-CHECK-DISABLED-DEV`.
- `ConnectorService::getLicenseFromFile()` aveva return type `array` non
  nullable ma poteva restituire `null`/`false` internamente → `TypeError`
  non gestito (i `catch` coprivano solo `\Exception`, non `\Error`) ogni
  volta che `storage/app/license.json` non esisteva ancora — cioè **su
  ogni prima attivazione, sia trial che con chiave esistente**, dato che
  quel file è in `.gitignore` e non viene mai distribuito con il codice.
- Nello stesso metodo, `getAccessToken()` referenziava una variabile di
  cache mai definita (`$accessTokenCacheKey`, riga commentata) — innocuo
  finché non si arrivava a quella riga, ma sarebbe stato un secondo crash
  una volta risolto il primo.
- `AdminController::postActivateLicense()` accedeva direttamente a
  `$response->success`/`$response->result` sulla risposta del server di
  licenza: qualunque risposta inattesa (formato diverso, errore di
  validazione, JSON malformato) causava un 500 (`Undefined property`)
  invece di un messaggio d'errore leggibile.

## Situazione dopo

- **Env check**: nuovo `AdminController::licenseEnvironmentIsConfigured()`
  (verifica `env('APP_PATH') && env('APP_DOMAIN')`), chiamato all'inizio di
  `getLicensescreen()` e `postActivateLicense()` — se manca, redirect al
  login con messaggio esplicativo invece di proseguire verso un errore
  generico del server di licenza.
- **`LICENSE_SERVER_URL` configurabile**: `config/license-connector.php` ora
  usa `env('LICENSE_SERVER_URL', 'http://license.thecustomerhive.com')`;
  aggiunto a `.env.example` con il default reale come valore (non lasciato
  vuoto, per evitare che una chiave vuota in `.env` "vinca" sul default —
  vedi Rischi e note).
- **`registerLicense()` riscritto**: usa `Http::` (facade Laravel) con
  timeout esplicito di 15s; su `ConnectionException` ritorna un JSON di
  errore nello stesso formato che il chiamante già si aspetta
  (`{success:false, result:"..."}`) invece di lasciar propagare
  l'eccezione o bloccare la richiesta indefinitamente.
- **Nuova opzione "Ho già una licenza"**: link nella schermata di licenza
  che apre un mini-form con solo il campo chiave, POST su una nuova route
  `activate-existing-license` → `AdminController::postActivateExistingLicense()`.
  Salta la registrazione trial remota (`/licenses`), inserisce la chiave
  direttamente in `license` e riusa `LicenseHelper::writeLicense()` (lo
  stesso meccanismo già usato dal trial per scaricare/validare i dati
  della licenza dal server). Gestisce anche l'eccezione `AuthException`
  che il server remoto solleva per una chiave non valida/non trovata,
  pulendo lo stato locale e mostrando un messaggio in italiano invece di
  un 500.
- **`ConnectorService::getLicenseFromFile()`** reso `?array` (nullable),
  con verifica esplicita `is_array()` sul risultato di `json_decode()` e
  ritorno di `null` invece di lasciar propagare un `TypeError` sul return
  type. `getAccessToken()` aggiornato per gestire `null` in sicurezza
  (`?? null`) e con la cache key ripristinata (`getAccessTokenKey()`,
  prima commentata) così la chiamata `Cache::put()` più sotto non riceve
  più una variabile indefinita.
- **`postActivateLicense()`** ora legge la risposta del server con
  `isset()`/`??` invece di accesso diretto, con un fallback del messaggio
  d'errore (`result` → `message` → messaggio generico) e logga
  (`Log::warning`) la risposta grezza per debug futuro.
- **Controlli di licenza riattivati**: rimossi i 4 return anticipati
  `LICENSE-CHECK-DISABLED-DEV`. `canAddTenant()`, `canAddUser()` e
  `getLicenseInfo()` (quest'ultimo chiamato su **ogni pagina** del
  pannello via `license_modal.blade.php`) hanno ricevuto lo stesso guard
  "nessuna licenza ancora" che aveva già solo `canLicenseLogin()` — senza
  questo, il pannello sarebbe andato in errore fatale su ogni pagina non
  appena riattivati i controlli con la tabella `license` vuota.

## Motivazione

L'obiettivo primario era permettere il test reale del flusso di
licensing senza reintrodurre i problemi (SSL disattivato, timeout
infinito, nessun feedback su errori) che avevano portato a disattivare i
controlli. I bug in `ConnectorService` e nella gestione della risposta di
`registerLicense()` sono stati corretti perché **bloccavano
concretamente** la verifica delle funzionalità appena costruite (senza il
fix di `getLicenseFromFile()`, sia il trial sia "ho già una licenza"
avrebbero dato 500 su qualunque installazione pulita) — non è stata una
scelta di allargare lo scope, ma la conseguenza diretta del testare
davvero il flusso end-to-end invece di fermarsi alla sintassi.

## Test

Tutte le verifiche sono state fatte manualmente contro l'ambiente Docker
locale reale (richieste HTTP dirette via `curl`, non la suite PHPUnit —
su richiesta esplicita dell'utente i test automatici non vengono
eseguiti in autonomia):

- `registerLicense()`: chiamata diretta che raggiunge realmente
  `license.thecustomerhive.com` (niente più `ConnectionException`/
  `Could not resolve host`).
- `postActivateExistingLicense()`: chiave non valida → redirect pulito
  (302) con messaggio in italiano, tabella `license` tornata a 0 righe
  dopo il fallimento (nessuno stato orfano).
- Riattivazione controlli: login con l'unico utente presente
  (`admin@customerhive.local`) → redirect corretto a
  `/admin/register-license` (302, non più errore fatale), pagina di
  licenza carica (200).
- `postActivateLicense()` (trial): richiesta completa → non più 500;
  redirect pulito con il messaggio d'errore reale restituito dal server
  di licenza remoto (che al momento rifiuta la richiesta per un bug
  lato loro, vedi Rischi e note).
- **Non verificato**: il percorso di successo completo (chiave valida
  che porta a un `license.json` scritto correttamente) — manca una
  chiave di licenza reale funzionante per testarlo, sia per il trial
  (bloccato da un bug lato server di licenza) sia per "ho già una
  licenza".

## Rischi e note

- **Bug noto lato server di licenza remoto** (`license.thecustomerhive.com`,
  gestito separatamente dall'utente): il trial (`postActivateLicense`)
  riceve `App\Services\LicenseService::getLicenseByDomain(): Argument #1
  ($domain) must be of type string, null given` — il campo `domain`
  inviato non arriva (o non viene usato) correttamente lato server. Non
  è un problema di questo repository; da correggere quando si lavorerà
  su quel progetto. Notato anche che quel servizio ha `APP_DEBUG` attivo
  (la risposta include stack trace completo con percorsi del server).
- **Footgun `.env` confermato due volte in questa sessione**: una chiave
  presente ma vuota (`LICENSE_SERVER_URL=`) blocca il fallback
  `env('KEY', 'default')` — diverso da una chiave del tutto assente.
  Successo anche con `APP_DOMAIN` sparito dal `.env` locale tra una
  sessione e l'altra (causa non accertata, possibile modifica manuale).
  Attenzione a non reintrodurre questi problemi editando `.env` a mano.
- Il flusso di successo di entrambe le opzioni di attivazione resta da
  verificare con una chiave reale.
- `ConnectorService::checkLicense()` (metodo separato, probabilmente non
  usato) ha un'incoerenza preesistente array/oggetto
  (`$license->tenants_number` su un valore che `getLicenseFromFile()`
  restituisce come array) — non toccato, fuori dallo scope di questo
  intervento.

## Rollback

- `LicenseHelper.php`: reintrodurre i 4 return anticipati
  `LICENSE-CHECK-DISABLED-DEV` (vedi git history di questo file per il
  testo esatto) per tornare a controlli disattivati.
- `AdminController.php`: rimuovere `postActivateExistingLicense()` e la
  route corrispondente in `routes.php`; su `postActivateLicense()` il
  cambiamento è solo nella gestione dell'errore, sicuro da tenere anche
  in caso di rollback parziale.
- `ConnectorService.php`: i fix di null-safety sono comportamento
  strettamente più sicuro (nessun cambiamento nella logica di business),
  non richiedono un piano di rollback dedicato.
- `.env`/`.env.example`: rimuovere `LICENSE_SERVER_URL` per tornare
  all'URL hardcoded in `config/license-connector.php` (sconsigliato).
