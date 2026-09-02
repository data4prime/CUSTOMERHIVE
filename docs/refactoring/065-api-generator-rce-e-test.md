# 065 - API Generator: RCE autenticata corretta + null-safety + 19 test

- **Data**: 2026-09-02
- **Stato**: Completato (parziale - vedi "Rischi e note")
- **Area**: Sicurezza + Bug fix + test
- **File/aree di codice coinvolte**:
  - `app/Helpers/CRUDBooster.php`
  - `app/Http/Controllers/System/ApiCustomController.php`
  - `tests/Feature/ApiCustomCrudTest.php` (nuovo)

## Contesto

Richiesta dall'utente un'analisi del modulo API Generator (`ApiCustomController`
+ `CRUDBooster::generateAPI()`) in vista di test CRUD, sullo stesso modello
dei moduli precedenti. L'analisi ha trovato una vulnerabilità seria - non
un bug di comportamento come negli interventi precedenti, ma una **Remote
Code Execution autenticata** - discussa esplicitamente con l'utente prima
di intervenire, dato il progetto in produzione con clienti reali (vedi
[[project-production-clients]]).

## Il problema: due bug che si sommano

**Punto A - nessun controllo di privilegio** (deliberatamente NON corretto
in questo intervento, vedi "Rischi e note"): il middleware `CBBackend`
verifica solo "sei loggato, non sei bloccato" - nessun controllo di
privilegio/modulo a livello di rotta. `ApiCustomController::getIndex()`/
`getGenerator()`/`getEditApi()` hanno un check esplicito
`CRUDBooster::isSuperadmin()`, ma `postSaveApiCustom()`, `getDeleteApi()`,
`getGenerateScreetKey()`, `getStatusApikey()`, `getDeleteApiKey()` non ce
l'hanno: un utente con **qualunque privilegio**, anche "Standard" senza un
solo permesso assegnato, può chiamarli.

**Punto B - generazione di codice PHP concatenando input utente non
sanificato** (corretto in questo intervento): `postSaveApiCustom()` scrive
su disco un vero controller Laravel (`app/Http/Controllers/Api...Controller.php`),
che diventa immediatamente un file autoloaded (PSR-4, nessun deploy
necessario). Il sorgente PHP di quel file veniva costruito incollando
`$table_name`/`$permalink`/`$method_type` **grezzi dentro literal PHP a
doppi apici**:
```php
$this->table       = "' . $table_name . '";
```
Un valore con una virgoletta rompeva il literal e permetteva di iniettare
PHP arbitrario. Anche a parte le virgolette, un literal a doppi apici in
PHP **interpola sempre** il contenuto (`$qualcosa` al suo interno viene
valutato) - un problema distinto dalle virgolette, che un semplice
"escape delle virgolette" (`addslashes()`) non avrebbe chiuso del tutto.

Sommando A e B: **un utente con il privilegio più basso possibile poteva
eseguire codice PHP arbitrario sul server con una singola richiesta HTTP**
a `/admin/api_generator/save-api-custom`.

## Correzione (punto B)

- `CRUDBooster::generateAPI()`: `$table_name`/`$permalink`/`$method_type`
  ora passano per `var_export()` invece del literal a doppi apici.
  `var_export()` produce un literal PHP a **singoli** apici correttamente
  escapato, che non interpola mai nulla - l'unica difesa davvero solida per
  incollare un valore arbitrario dentro sorgente PHP generato (a
  differenza di `addslashes()` + doppi apici, che chiude solo il
  breakout via virgolette ma non l'interpolazione).
- Stessa correzione nel ramo "modifica" di `postSaveApiCustom()` (il
  `preg_replace()` che riscrive un controller già su disco). Lì è emerso
  un **secondo bug indipendente**: la stringa di *replacement* di
  `preg_replace()` ha una sua sintassi speciale (`$1`, `\1`, `$0` vengono
  sostituiti con i gruppi catturati) - un valore con `$` o `\` produceva
  una sostituzione diversa da quella attesa, a prescindere
  dall'iniezione nel file generato. Risolto passando a
  `preg_replace_callback()`, il cui valore di ritorno viene inserito
  letteralmente.
- `$controllerName` (finisce nel **nome classe** e nel **nome file** del
  controller generato, non dentro una stringa PHP - `var_export()` lì non
  aiuta) sanificato a `[A-Za-z0-9_]`: chiudeva sia l'injection nel nome
  classe sia il **path traversal** nel nome file (un permalink con `/` o
  `..` scriveva il file fuori da `app/Http/Controllers/`). Un nome classe
  PHP valido è comunque solo lettere/cifre/underscore, quindi non toglie
  nulla a un permalink che produceva già un nome funzionante.

## Bug minori corretti nello stesso intervento

- `getDeleteApi($id)`: nessun controllo null - un `$id` inesistente
  crashava con 500 (`$row->controller` su null). Ora risponde
  `{"status": 0}`.
- `postSaveApiCustom()`, ramo creazione: se il file del controller esiste
  già ma non c'è una riga `cms_apicustom` corrispondente (file orfano),
  `->first()->id` crashava con 500. Ora redirect gestito.
- I due `CRUDBooster::redirectBack()` in `postSaveApiCustom()` (permalink
  duplicato, controller non trovato) sostituiti con
  `CRUDBooster::redirect(CRUDBooster::referer(), ...)`. **Non è stata
  toccata `redirectBack()` stessa** (resta `exit()`-based, usata anche da
  2 Blade view Qlik - lasciata da parte deliberatamente anche
  nell'intervento [063](063-menu-management-bug-e-test-crud.md)): si è
  solo spostato questi 2 punti su un helper già return-based
  (`CRUDBooster::redirect()`, refactorizzato in
  [064](064-settings-bug-e-test-crud.md)), sbloccando la testabilità di
  quei 2 rami senza rischiare l'embedding Qlik.

## Verificato e SCARTATO come falso allarme

`ApiCustomController.php` importa `use CRUDbooster;` (minuscolo) invece di
`use CRUDBooster;` come il resto dell'app - PHP non è case-sensitive sui
nomi di classe importati con `use`, quindi risolve comunque alla stessa
classe (`config/app.php` alias `'CRUDBooster' => App\Helpers\CRUDBooster::class`).
Solo un'inconsistenza di stile preesistente, nessun impatto funzionale -
non toccata.

## Test

`tests/Feature/ApiCustomCrudTest.php` (nuovo, 19 test, tutti passano):

- **Regressione RCE** (6 test): creazione e modifica con valori pericolosi
  in tabella/permalink/method_type - verificano che il valore finisca nel
  file generato SOLO tramite `var_export()` (mai come literal a doppi
  apici grezzo), incluso un test specifico per il bug `$1`/`\1` di
  `preg_replace()`.
- **Path traversal** (1 test): permalink con `../../` non scrive file
  fuori da `app/Http/Controllers/`.
- **Null-safety** (2 test): `getDeleteApi()` su id inesistente, creazione
  con file orfano collidente.
- **Swap `redirectBack()` → `redirect()`** (2 test): permalink duplicato,
  controller non trovato - ora testabili via HTTP simulato.
- **Caratterizzazione endpoint più semplici** (7 test): `cms_apikey`
  (generazione, stato, cancellazione, lista), inclusa la regressione del
  fix su `CRUDBooster::valid()` (condiviso con Settings, vedi
  [064](064-settings-bug-e-test-crud.md)) e i 3 controlli di accesso già
  esistenti (`getIndex`/`getGenerator`/`getEditApi`).
- **Caratterizzazione del punto A** (1 test): dimostra esplicitamente che
  `postSaveApiCustom()` è raggiungibile con successo da un utente Standard
  senza alcun permesso assegnato - fissa il comportamento ATTUALE, così da
  accorgersi (test che inizia a fallire) il giorno in cui verrà introdotto
  un controllo qui.

A differenza di Settings (che scrive in `public/storage/`), qui i file
generati/modificati finiscono **dentro l'albero sorgente vero e proprio**
(`app/Http/Controllers/`, tracciato da git): ogni test traccia i file che
crea, ripuliti in `tearDown()` più uno sweep di sicurezza su qualunque
`*PhpunitTest*.php` rimasto (verificato che non lasci residui sul
filesystem del container dopo l'esecuzione).

Suite completa eseguita in Docker: 137/137 test passano (118 precedenti +
19 nuovi), nessuna regressione.

## Rischi e note

- **Il punto A (nessun controllo di privilegio) resta deliberatamente non
  corretto**, deciso esplicitamente con l'utente: "il punto A lo lasciamo
  stare per adesso, sarà da riprendere in futuro". Il modulo API Generator
  è quindi oggi ancora raggiungibile - per creare/modificare/cancellare
  API generate e chiavi API - da qualunque utente autenticato,
  indipendentemente dal privilegio. La correzione del punto B (questo
  intervento) elimina comunque il vettore più grave (RCE), ma il gap di
  autorizzazione resta un rischio reale da trattare con priorità alla
  ripresa del lavoro su questo modulo.
- **Scoperta ma NON corretta, fuori scope**: `generateAPI()` costruisce lo
  `use` statement del controller generato come
  `"App\Http\Controllers\\".$controller` (per tabelle "manually
  generated", prefisso `mg_`) - ma dopo l'uscita da CRUDBooster (vedi
  roadmap in [README](README.md)) quasi tutti i controller di sistema
  vivono in `App\Http\Controllers\System\`, non nella root. Il ramo per
  tabelle non "manually generated" punta invece a
  `crocodicstudio\crudbooster\controllers\`, namespace che **non esiste
  più** nel repo (pacchetto rimosso interamente). La feature di
  generazione API sembra quindi già rotta end-to-end per l'uso reale
  indipendentemente da questo intervento (i test scritti qui verificano
  la sicurezza della generazione/scrittura file, non che l'API generata
  sia poi effettivamente richiamabile). Da investigare separatamente se
  questo modulo viene rimesso in uso.
- `getColumnTable()` (introspezione colonne tabella per il form del
  generator) non è coperta da test - bassa rilevanza per la sicurezza,
  fuori scope.

## Rollback

`git revert` del commit - ripristina la RCE, i 2 bug null-safety e rimuove
i test. **Sconsigliato**: la RCE è un rischio reale su un'app in
produzione, un rollback andrebbe accompagnato da un piano per rimediare
altrimenti (es. disabilitare temporaneamente il modulo).

Vedi anche [[project-production-clients]], [064](064-settings-bug-e-test-crud.md).
