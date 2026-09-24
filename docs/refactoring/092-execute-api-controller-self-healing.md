# 092 - `execute_api()`: risolto il controller mancante nelle API generate da versioni vecchie

- **Data**: 2026-09-24
- **Stato**: Completato
- **Area**: Bug fix
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/ApiController.php`

## Contesto

Seguito di [091](091-bug-noti-execute-api-logscontroller-visibilita-dashboard.md):
verificando il fix di `execute_api()` con un permalink reale, 7 dei 14
controller generati già presenti in locale (es. `ApiHiveController.php`)
sono andati in `ErrorException` ("Undefined property $controller") — non
impostano affatto `$this->controller`, usata più sotto da
`ModuleHelper::can_list()`/simili per lo scoping tenant/gruppo riga per
riga. Sembrano generati da una versione più vecchia di
`CRUDBooster::generateAPI()`, precedente all'introduzione di quella
proprietà (le altre 7 ce l'hanno).

## Situazione prima

- `App\Http\Controllers\System\ApiController` (la classe base di ogni
  controller generato) non dichiara affatto `$controller` — ogni
  sottoclasse generata è responsabile di dichiararla e valorizzarla nel
  proprio costruttore. Un file generato senza quella riga fa leggere una
  proprietà mai scritta da nessuna parte nella gerarchia → `ErrorException`.
- Scartata l'opzione "salta il controllo se manca": `can_list()` lo usa
  per decidere se ogni riga rispetta lo scoping tenant/gruppo
  dell'utente — saltarlo vorrebbe dire eliminare quel controllo invece
  di limitarsi a non far crashare la richiesta.

## Situazione dopo

- `$controller` dichiarata anche sulla classe base (`public $controller
  = null;`) — rete di sicurezza, nessun impatto su chi la ridichiara già
  (ridichiarare una proprietà pubblica con lo stesso default in una
  sottoclasse è innocuo in PHP).
- In `execute_api()`, se `$this->controller` è ancora `null` dopo la
  dichiarazione, viene risolto **a runtime con la stessa query già usata
  da `CRUDBooster::generateAPI()`** per crearlo la prima volta
  (`cms_moduls` → colonna `controller` per la tabella dell'API),
  provando prima il namespace `App\Http\Controllers\System\`, poi
  `App\Http\Controllers\`. Nessuna riscrittura dei file esistenti sul
  disco di alcun cliente.

## Motivazione

Stesso risultato di un file rigenerato da zero (stesso controller,
stessa istanza), ma senza toccare file generati esistenti — evita la
categoria di rischio delle 3 RCE già trovate in passato scrivendo PHP
generato su disco (065/068). Comportamento invariato per i 7 file che
già impostavano la proprietà (il blocco nuovo non fa nulla in quel
caso, `if (!$this->controller)` è già falso).

## Test

- `php -l`.
- Seminata una riga `cms_apicustom` reale per il permalink `hive`
  (puntava a `cms_settings`) e chiamata `api/hive` con `X-user` di un
  superadmin: prima di questo fix → 500 ("Undefined property
  $controller"); dopo → 200, `api_status: 1`.
- Dati e riga di test poi eliminati.

## Rischi e note — scoperta collegata, non corretta qui

**Verificando lo stesso permalink con un utente NON superadmin**
(`X-user` di un utente Standard di prova, creato e poi eliminato):
risposta 200 ma `"data": []` — **zero righe**, anche se la tabella ne
conteneva. Causa: `execute_api()` non chiama mai `cbInit()`/`cbLoader()`
su `$this->controller` (nemmeno per i 7 file che lo impostano già da
soli — la riga esiste ma è commentata: `//$this->controller->cbInit();`),
quindi le sue proprietà (`table`, `global_privilege`, `button_detail`)
restano ai default della classe base invece dei valori reali del
modulo. `ModuleHelper::can_list()` legge quei default (`table = null`,
`global_privilege = false`) e per un non-superadmin filtra via ogni
riga, sempre.

**Sembra un gap preesistente e più ampio**, indipendente dalla proprietà
mancante appena corretta: l'azione `list` del modulo API Generator
potrebbe restituire sempre zero righe per qualunque chiamante non
superadmin, a prescindere da quale dei 14 controller si chiami. Non
verificato se sia così anche in produzione (qui verificato solo con
dati di prova), e non corretto: chiamare `cbInit()` sul controller
cambierebbe comportamento anche per i 7 file "già a posto", un
cambiamento che nessuno ha chiesto e che andrebbe verificato con più
attenzione (potrebbe essere reso più permissivo o più restrittivo a
seconda del modulo, serve capire cosa succede oggi in produzione prima
di toccarlo).

## Rollback

`git revert` del commit — nessuna migration né dato coinvolto, solo la
classe base del controller API.
