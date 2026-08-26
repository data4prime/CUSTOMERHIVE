# 004 - Licensing: adeguamento alla busta {success, data} del license server, fix import mancanti, fix precompilazione dominio

- **Data**: 2026-08-26
- **Stato**: Completato
- **Area**: Licensing
- **File/aree di codice coinvolte**:
  - `app/Services/ConnectorService.php` (`getAccessToken`, `writeLicense`, import)
  - `packages/crocodicstudio/crudbooster/src/controllers/AdminController.php` (`getLicensescreen`, `postActivateLicense`)
  - `.env` (locale, non versionato) — `LICENSE_SERVER_URL`, `APP_DOMAIN`

## Contesto

Il progetto **LICENSES** (server di licenza esterno, repo separato, stesso
utente) ha cambiato il formato delle risposte delle sue API da una busta
"piatta" (`status`/`access_token`/`result` a livello radice) a una busta
standard `{success, message, data}` (metodi `apiSuccess()`/`apiError()` nel
progetto LICENSES). CUSTOMERHIVE, che consuma quelle API da
`ConnectorService` e `LicenseHelper`, doveva essere aggiornato per
continuare a leggere correttamente le risposte.

Durante il test end-to-end in locale (login contro un'istanza Docker locale
di LICENSES, vedi `docs/docker-local-dev.md`) sono emersi altri due problemi
non legati alla busta ma che bloccavano comunque la verifica:

1. `ConnectorService.php` referenzia `ConnectionException`/`RequestException`
   nei `catch`, ma non li importa — stesso tipo di bug già corretto in
   `LicenseHelper.php` nell'intervento [003](003-licensing-hardening.md), ma
   rimasto in questo file.
2. Il form di attivazione precompila il campo `domain` con
   `$_SERVER['HTTP_HOST']` (host della richiesta corrente, porta incluso),
   mentre il login (`ConnectorService::getAccessToken()`) invia come
   `ls_domain` il valore di `APP_DOMAIN` da `.env`. Se i due non
   coincidono esattamente (es. form compilato su `localhost:8080` ma
   `APP_DOMAIN=localhost` in `.env`), la licenza viene registrata sul
   server con un `domain` diverso da quello che verrà poi usato per il
   login — che quindi fallisce con "Invalid license key or license
   source." anche con una licenza valida ed esistente lato server (bug
   scoperto e diagnosticato in questa sessione, vedi anche
   `LicenseService::getLicenseByDomain()` nel progetto LICENSES, che fa un
   confronto esatto `domain` + `license_key`).

## Situazione prima

- `ConnectorService::getAccessToken()` leggeva `$data['status']` e
  `$data['access_token']` direttamente dalla radice della risposta.
- `ConnectorService::writeLicense()` leggeva `$license = $response->json()`
  direttamente, assumendo che il corpo della licenza fosse alla radice.
- `AdminController::postActivateLicense()` leggeva
  `$response->result->license_key` / `$response->result->status`, e il
  fallback per il messaggio d'errore era `$response->result ?? $response->message`.
- `ConnectorService.php` non importava `ConnectionException`/
  `RequestException` di `Illuminate\Http\Client`, referenziate nei `catch`
  di `writeLicense()` e `getAccessToken()` — una connessione fallita al
  server di licenza avrebbe generato un fatal error ("Class not found")
  invece di essere gestita.
- `AdminController::getLicensescreen()` impostava
  `$tenant_domain_name = $_SERVER['HTTP_HOST']` per precompilare il campo
  `domain` del form — nessun collegamento con `APP_DOMAIN`, nonostante sia
  quest'ultimo il valore realmente usato per il login.

## Situazione dopo

- `getAccessToken()`: legge `$data['success']` e
  `$data['data']['access_token']`.
- `writeLicense()`: `$body = $response->json(); $license = $body['data'] ?? null;`
  — la licenza salvata su `storage/app/license.json` resta comunque il
  modello "nudo" (senza busta), solo l'estrazione cambia.
- `postActivateLicense()`: legge `$response->data->license_key` /
  `$response->data->status`; il fallback del messaggio d'errore è ora solo
  `$response->message ?? null` (rimosso `result`, non più presente nella
  nuova busta).
- Aggiunti in `ConnectorService.php`:
  ```php
  use Illuminate\Http\Client\ConnectionException;
  use Illuminate\Http\Client\RequestException;
  ```
- `getLicensescreen()`: `$tenant_domain_name = env('APP_DOMAIN')` invece di
  `$_SERVER['HTTP_HOST']` — il form mostra ora lo stesso valore che verrà
  effettivamente inviato al login.

## Motivazione

I cambiamenti sulla busta sono un adeguamento obbligato al nuovo contratto
del server di licenza, non una scelta di design lato CUSTOMERHIVE. I due fix
aggiuntivi (import mancanti, precompilazione dominio) sono stati corretti
perché scoperti bloccando concretamente la verifica manuale di questo stesso
intervento — stesso criterio già seguito in
[003](003-licensing-hardening.md).

## Test

Verifica manuale contro un'istanza Docker locale di LICENSES (vedi
`docs/docker-local-dev.md`; `LICENSE_SERVER_URL` puntato a
`http://host.docker.internal:8000` nel `.env` locale, non versionato):

- Login falliva con `"Invalid license key or license source."` (401 dal
  server) pur con una licenza visibile nel pannello admin di LICENSES —
  diagnosticato come mismatch tra `domain` della licenza registrata
  (`localhost:8080`, dal form) e `APP_DOMAIN` inviato al login
  (`localhost`).
- Dopo aver allineato `APP_DOMAIN=localhost:8080` nel `.env` locale, login
  completato con successo (token ottenuto, licenza scritta in
  `storage/app/license.json`).
- **Non verificato**: il flusso completo con `APP_DOMAIN` corretto fin
  dall'inizio grazie al fix di `getLicensescreen()` (introdotto dopo aver
  già diagnosticato il problema manualmente) — dovrebbe prevenire lo stesso
  mismatch in futuro, ma non è stato rieseguito da zero un nuovo trial per
  confermarlo.
- Non eseguita la suite PHPUnit (su richiesta esplicita, i test automatici
  non vengono lanciati in autonomia).

## Rischi e note

- **Presuppone che il server di licenza di produzione
  (`license.thecustomerhive.com`) risponda già con la nuova busta
  `{success, data}`.** Se in produzione LICENSES non è ancora stato
  aggiornato, questo intervento **rompe il login e l'attivazione in
  produzione** non appena viene pushato su `main`. Da verificare
  esplicitamente prima del push — vedi voce aggiunta a
  `../pre-push-checklist.md`.
- Il mismatch `domain` tra form e `.env` resta comunque possibile se
  qualcuno modifica `APP_DOMAIN` a mano **dopo** aver già registrato una
  licenza con un valore diverso: il fix qui previene il caso più comune
  (form compilato prima che `APP_DOMAIN` sia impostato correttamente), non
  lo elimina come classe di errore.
- `LICENSE_SERVER_URL=http://host.docker.internal:8000` è specifico
  dell'ambiente Docker locale su Windows/Mac (Docker Desktop) e serve solo
  per puntare a un'istanza locale di LICENSES; non è (e non deve essere)
  versionato in `.env.example` con questo valore.

## Rollback

- `ConnectorService.php` / `AdminController.php`: `git revert` del commit
  di questo intervento per tornare a leggere la busta piatta
  (`status`/`result`) — necessario **solo** se il server di licenza remoto
  viene riportato al formato precedente.
- Gli import di `ConnectionException`/`RequestException` sono comportamento
  strettamente più sicuro (nessun cambiamento di logica), non richiedono
  rollback dedicato.
- Il fix di `getLicensescreen()` è comportamento strettamente più corretto
  (elimina un disallineamento silenzioso), non richiede rollback dedicato.
