# 005 - ConnectorService: cleanup, fix crash su login irraggiungibile, test di caratterizzazione

- **Data**: 2026-08-27
- **Stato**: Completato
- **Area**: Licensing
- **File/aree di codice coinvolte**:
  - `app/Services/ConnectorService.php`
  - `tests/Unit/Services/ConnectorServiceTest.php` (nuovo)
  - `database/migrations/2026_08_27_000000_create_license_table.php` (nuovo)

## Contesto

Dopo il fix del bug access-token/401 (vedi [004](004-licensing-envelope-success-data.md)
e la sessione precedente), un'analisi di complessità con tokensave ha
segnalato `ConnectorService` come il file peggiore di tutto `app/` (non
vendorizzato): `validateLicense()` con cognitive complexity 28 e crap score
90, `getAccessToken()` con crap score 90, entrambi a **0% di copertura
test** nonostante gestiscano il gate di licenza (codice di sicurezza).
Deciso con l'utente di affrontarlo come prossimo intervento.

## Situazione prima

- `validateLicense()`: logica di match status/tenants/clients/path/domain
  tutta in linea, 4 livelli di nesting, una condizione ridondante
  (verificava l'uguaglianza del dominio nell'`if` e poi la ri-verificava
  identica nel corpo).
- `getLicenseFromFile()` e `getAccessToken()` avevano un
  `catch (NotFoundHttpException $e)` posizionato **dopo** un
  `catch (\Exception $e)` — dato che `NotFoundHttpException` estende
  `\Exception`, quel ramo era morto, non poteva mai scattare.
- **Bug reale**: in `getAccessToken()`, se la chiamata HTTP a `/auth/login`
  lanciava un'eccezione (es. server di licenza irraggiungibile), il codice
  continuava comunque fino a `$response->json()` con `$response` non
  definita → `Error` fatale non gestito, non un `AuthException` pulito.
  In pratica: se `license.thecustomerhive.com` era irraggiungibile, uno
  qualsiasi dei 4 controlli di licenza (`canLicenseLogin`, `canAddTenant`,
  `canAddUser`, `getLicenseInfo` — quest'ultimo chiamato su ogni pagina del
  pannello) andava in 500 invece di degradare in modo gestito.
- Nessun test automatico sulla classe.
- **Scoperta collaterale**: la tabella `license` (usata da `validateLicense()`
  per il cleanup su licenza mancante) esiste nei database reali ma **non
  aveva mai avuto una migration** — creata manualmente in un momento
  imprecisato. Senza una migration, un database di test/CI creato da zero
  (solo migration, senza dump manuale) non avrebbe questa tabella.

## Situazione dopo

- `validateLicense()` scomposto in metodi privati piccoli e mirati:
  `licenseMeetsQuota()`, `licenseMatchesPath()`, `licenseMatchesDomain()`,
  combinati con un `&&` a catena (short-circuit) al posto della sequenza di
  `$ret = $ret && ...`. **Comportamento preservato esattamente**, incluso un
  quirk non ovvio in `licenseMatchesDomain()`: se `$data['domain']` è
  presente ma non combacia col dominio della licenza, il codice NON fallisce
  subito — ricade sul confronto con `env('APP_DOMAIN')` (con lo stesso
  taglio sul primo punto usato altrove). Non è un bug: `LicenseHelper`
  passa spesso `env('APP_DOMAIN')` grezzo come `$data['domain']`, che su
  domini con sottodominio (es. `dev.thecustomerhive.com`) non combacerebbe
  mai col dominio già tagliato salvato in licenza (`dev`) senza questo
  fallback. Pinnato esplicitamente in un test dedicato per non perderlo in
  futuro.
- Rimossi i due blocchi `catch (NotFoundHttpException $e)` morti.
- **Fix del crash**: `getAccessToken()` ora ritorna `null` esplicitamente
  dai blocchi `catch` invece di continuare fino a `$response->json()` su
  una variabile non definita. Un login irraggiungibile ora produce un
  `accessToken` nullo gestito normalmente da `writeLicense()`/
  `getLicenseFromFile()` (che già controllavano `if ($this->accessToken)`),
  non più un 500.
- Rimosso codice morto/commentato (`//dd($data);`, righe vuote in eccesso).
- **Nuova migration** `2026_08_27_000000_create_license_table.php`: crea la
  tabella `license` con lo schema verificato via `SHOW CREATE TABLE` in
  dev, ma solo se non esiste già (`Schema::hasTable()` come guardia) — non
  tocca i database dove la tabella è già presente manualmente (dev, e
  probabilmente staging/prod), la crea da zero altrove (CI, installazioni
  pulite). Verificata su dev: nessun cambiamento allo schema/dati esistenti.
- **19 test di caratterizzazione nuovi** in
  `tests/Unit/Services/ConnectorServiceTest.php`, che coprono:
  `getAccessToken()` (cache hit, cache miss + login, fallimento auth →
  `AuthException`, server irraggiungibile → nessun crash), `writeLicense()`
  (successo, risposta senza id, richiesta fallita), `getLicense()`/
  `getLicenseFromFile()` (file presente, JSON non valido, file mancante con
  auto-riscrittura riuscita/fallita), `validateLicense()` (tutti i rami:
  status, tenants, clients, path, domain — incluso il quirk sopra — e
  cleanup della riga `license` quando la licenza manca).

## Motivazione

`ConnectorService` gestisce il gate di licenza, non è codice periferico:
avere il file più complesso e meno testato di `app/` proprio qui era il
rischio più concreto e già segnalato dall'analisi. Il fix del crash su
login irraggiungibile non era in scope originariamente, ma è emerso
leggendo il codice per scrivere i test — coerente con la stessa logica già
seguita in [003](003-licensing-hardening.md): un bug che il refactoring
stesso avrebbe reso visibile/testabile va corretto, non lasciato lì.

## Test

Non eseguita la suite PHPUnit in autonomia (regola esplicita dell'utente).
Verificato invece con mezzi più leggeri:
- `php -l` su tutti e tre i file (nessun errore di sintassi).
- Migration eseguita per davvero contro il DB di sviluppo
  (`php artisan migrate`): idempotente, nessuna modifica alla tabella
  `license` esistente (schema e righe invariati).
- Analisi manuale riga per riga della logica di `validateLicense()` prima e
  dopo per confermare l'equivalenza booleana della riscrittura (incluso il
  quirk sul dominio).

**Eseguiti su richiesta esplicita dell'utente**: `php artisan test --filter=ConnectorServiceTest`
→ **19/19 verdi**. Confermato anche che il file reale `storage/app/license.json`
(la licenza già attivata in locale) è stato ripristinato identico dopo la
run, senza effetti collaterali sull'ambiente dev.

## Rischi e note

- `checkLicense()` **non toccato**: bug preesistente già segnalato in
  [003](003-licensing-hardening.md#rischi-e-note) (accede a
  `$license->tenants_number` su un array), probabilmente non usato da
  nessun chiamante — fuori scope anche qui.
- **Non unificato** l'accesso misto Storage-facade/file raw a
  `license.json`: `writeLicense()` scrive con `Storage::disk('license')`,
  `getLicenseFromFile()` legge con `file_get_contents(storage_path(...))`
  diretto. In produzione puntano allo stesso file fisico (il disk `license`
  ha root `storage_path('app/')`), quindi nessun problema di comportamento,
  ma è per questo che i test manipolano il file reale invece di usare
  `Storage::fake('license')` (che non avrebbe intercettato la lettura
  raw). Da valutare in un intervento successivo se dà fastidio.
- `getAccessToken()` non può più tornare `null` per nessun'altra ragione se
  non "server irraggiungibile" (prima il tipo `null|string` era in parte
  vestigiale, dato che ogni altro percorso lanciava `AuthException` o
  ritornava una stringa) — ora il caso `null` è realmente raggiungibile e
  gestito, non solo dichiarato nel tipo.

## Rollback

- `ConnectorService.php`: tutte le modifiche sono behavior-preserving o
  strictly-safer (fix del crash), nessun piano di rollback dedicato
  necessario — in caso di problemi, `git revert` del commit basta.
- `database/migrations/2026_08_27_000000_create_license_table.php`: se
  crea problemi, `php artisan migrate:rollback` la rimuove (drop della
  tabella `license`) — **attenzione**: su un database dove la tabella
  esisteva già da prima di questa migration, il rollback la cancellerebbe
  comunque (perde le licenze registrate). Da NON eseguire su dev/staging/
  prod senza backup.
