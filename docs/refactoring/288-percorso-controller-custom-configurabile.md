# 288 - Percorso dei controller custom configurabile (CUSTOM_CONTROLLERS_PATH)

- **Data**: 2026-10-08
- **Stato**: Completato (non committato)
- **Area**: Docker / Module builder / Routing
- **File/aree di codice coinvolte**:
  - `app/Helpers/CRUDBooster.php` (nuovi `customControllersPath()`, `customControllersScan()`; `isExistsController`, `generateAPI`, `generateController`)
  - `config/crudbooster.php` (chiave `CUSTOM_CONTROLLERS_PATH`)
  - `app/Http/Controllers/System/ModulsController.php`
  - `app/Http/Controllers/System/ApiCustomController.php`
  - `app/Helpers/ApiDocBuilder.php`
  - `routes/crudbooster.php`
  - `composer.json` (autoload), `.gitignore`
  - `app/Http/Controllers/System/CBController.php` (rimosso un `file_put_contents` di debug)

## Contesto

Si prepara CustomerHive a girare in container Docker multi-istanza. Un'immagine
Docker è immutabile, ma il module builder e l'API generator scrivono a runtime i
controller dei moduli custom in `app/Http/Controllers/` (radice, gitignored;
`System/` è invece tracciato). Nelle sole istanze Docker quei controller devono
stare in un percorso persistente (`storage/app/custom-controllers/`); ovunque
altrove (clienti in produzione, ambiente locale) non deve cambiare nulla.

## Situazione prima

Il percorso `app/Http/Controllers` era scritto a mano in ~35 punti (helper,
`ModulsController`, `ApiCustomController`, `ApiDocBuilder`, `routes/crudbooster.php`),
con tre varianti: `base_path("app/Http/Controllers/")`,
`app_path('Http/Controllers/' . $x . '.php')` e `scandir(base_path(...))`.
Le scansioni delle rotte (`scandir`) non erano protette contro la cartella
inesistente (quella di `api/` fuori da qualunque `try`).

## Situazione dopo

- `CRUDBooster::customControllersPath($create = false)` restituisce la cartella
  (senza slash finale). Variabile `CUSTOM_CONTROLLERS_PATH` non impostata o
  vuota → `base_path('app/Http/Controllers')`, identico a prima. Impostata →
  assoluta (`/…`, `\…`, `C:\…`) o relativa a `base_path()`, slash finali
  rimossi. Con `$create = true` crea la cartella se manca (solo nei punti che
  scrivono un file nuovo).
- `CRUDBooster::customControllersScan()` = `scandir()` che restituisce `[]` se la
  cartella non esiste (nessun warning). Con la variabile impostata le rotte
  `api/*` e `api2/*` leggono **solo** la cartella custom.
- Config: `crudbooster.CUSTOM_CONTROLLERS_PATH` = `env('CUSTOM_CONTROLLERS_PATH')`
  (letto da config, quindi compatibile con `config:cache`).
- Autoload: `"App\\Http\\Controllers\\": ["app/Http/Controllers/", "storage/app/custom-controllers/"]`
  oltre a `"App\\": "app/"`. Composer prova prima il prefisso più lungo e poi
  ripiega su `App\` → le classi in `System/` si risolvono come prima.
- `.gitignore`: aggiunto `/storage/app/custom-controllers/` (era già coperto da
  `storage/app/.gitignore`, che ignora tutto tranne `public`).
- `CBController.php`: rimosso `file_put_contents(__DIR__.'/test.txt', ...)` in
  `postDoImportChunk()`; era dentro un blocco `/* ... */` (righe ~2127-2244,
  metodo commentato), quindi codice morto: nessun effetto a runtime. I
  `//file_put_contents(__DIR__...)` commentati più su (~2008-2019) sono in un
  altro blocco e sono stati lasciati.

### Punti sostituiti (33 + 4 righe di scansione/is_dir nelle rotte)

- `CRUDBooster.php`: `isExistsController` (path e path2 `ControllerMaster/`),
  `generateAPI` (scrittura, `create`), `generateController` (path per
  glob/scrittura, `create`) → 4
- `ModulsController.php`: 25 punti (`getDelete` ×2, `removeModuleController`,
  `postStep1` ×2, `getStep2V2`, `postStep2V2`, `getStep3` ×2, `postStep3` ×2,
  `getStep3V2`, `postStep3V2`, `getStep4` ×2, `postStep4` ×2, `getStep4V2`,
  `postStep4V2`, `getStep5` ×2, `postStep5` ×2, `replaceControllerBlock`,
  `writeImportedLayout`)
- `ApiCustomController.php`: `postSaveApiCustom` (lettura), `storeNewApi`
  (scrittura, `create`), `getDeleteApi` (unlink) → 3. **Il terzo non era nella
  lista iniziale.**
- `ApiDocBuilder.php`: `formEntries` → 1
- `routes/crudbooster.php`: 2 `scandir` + 2 `is_dir` (api v1 e api2) → 4
- Non toccato di proposito: `glob(app_path('Http/Controllers/System/*.php'))` in
  `routes/crudbooster.php`.

## Motivazione

Un solo punto di verità per il percorso, richiesto per le istanze Docker.
**Il namespace dei controller non cambia mai** (`App\Http\Controllers\X`): i
controller custom dei clienti, le righe `cms_moduls.controller`, gli alias e
tutto il codice che li referenzia per nome restano validi; cambia solo dove
stanno i file, risolto dall'autoload PSR-4 con due cartelle. Alternativa
scartata: un namespace diverso per le istanze Docker (avrebbe richiesto
migrare dati e controller dei clienti).

## Test

Eseguiti in Docker locale, senza suite di test:

- `php -l` su tutti i file toccati: OK. `composer dump-autoload` nel container: OK.
- **Variabile non impostata**: `customControllersPath()` + `/` è identico
  stringa per stringa a `base_path("app/Http/Controllers/")`;
  `generateController()` scrive in `app/Http/Controllers/`,
  `isExistsController()` lo trova, la classe si autocarica da lì; probe pulito.
  Elenco rotte (`route:list`) invariato tra prima e dopo gli interventi di
  verifica.
- **`CUSTOM_CONTROLLERS_PATH=storage/app/custom-controllers`**: con cartella
  inesistente `customControllersScan()` = `[]` senza warning; il file nasce in
  `storage/app/custom-controllers/` (cartella creata da `create`); con un
  controller admin + uno API di prova e una riga `cms_moduls` di prova sono
  comparse le rotte `admin/<path>/*`, `api/<permalink>` e `api2/<permalink>`;
  le classi `System\*` si risolvono da `app/Http/Controllers/System/`.
  Rispetto al default mancano solo le 38 rotte `api/*`/`api2/*` dei controller
  presenti nella radice di `app/Http/Controllers` (atteso: con la variabile si
  legge solo la cartella custom). Nessuna rotta in più, nessun warning.
  Righe e file di prova rimossi.
- **Non verificato**: il wizard del module builder passo-passo da browser
  (step 1-5, modifica/cancellazione di un modulo dall'interfaccia), le chiamate
  HTTP reali a `api/` e `api2/` (solo registrazione rotte), un container Docker
  reale con `CUSTOM_CONTROLLERS_PATH` nell'ambiente, `config:cache`, Windows
  (percorsi assoluti con drive).

## Rischi e note

- Con la variabile impostata, i controller già presenti nella radice di
  `app/Http/Controllers` (clienti attuali) non vengono scansionati per le rotte
  API: è voluto, ma un'istanza Docker che parte da un DB/cartella di un cliente
  esistente deve copiare quei file nella cartella custom.
- Altri punti che leggono/scrivono controller custom senza passare dalla
  funzione: i test in `tests/Feature/*`
  (scrivono in `app/Http/Controllers`, valido a variabile non impostata) e
  riferimenti per **classe** come `\App\Http\Controllers\CBHook`
  (`AdminController`) che si risolvono via autoload da entrambe le cartelle.
- `ModulsController`: le scritture su file già esistenti non creano la
  cartella; solo `generateController`/`generateAPI`/`storeNewApi` la creano.

## Rollback

Ripristinare i file elencati con `git checkout` (nessun dato o DB toccato);
dopo `composer.json` rilanciare `composer dump-autoload`.
