# 015 - CBController spostata (shim class_alias) — ultima classe motore

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Architettura / CRUDBooster
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/CBController.php` (nuovo, 2369 righe)
  - `packages/crocodicstudio/crudbooster/src/controllers/CBController.php` (ora uno shim)

## Contesto

Ultimo passo della serie [012](012-controller-motore-shim-class-alias.md)/
[013](013-importdata-exportdata-shim-class-alias.md)/[014](014-apicontroller-shim-class-alias.md):
`CBController` è la classe motore più grande (2369 righe) e la più
referenziata — `extends CBController` per FQCN letterale da ~56 controller
CRUD generati da interfaccia (fuori da questo repo in produzione). È la
base di tutte le operazioni CRUD standard (`getIndex`, `getAdd`,
`postAddSave`, `getEdit`, `postEditSave`, export/import, datatable/modal
di relazione, ecc.).

## Situazione prima

Viveva in `packages/crocodicstudio/crudbooster/src/controllers/CBController.php`,
namespace `crocodicstudio\crudbooster\controllers`, `extends Controller`
(già spostata in [012](012-controller-motore-shim-class-alias.md)).

## Situazione dopo

- Codice reale spostato in `App\Http\Controllers\System\CBController`.
  Per un file di questa dimensione, lo spostamento **non è stato ritrascritto
  a mano**: copiato con `cp` a livello di filesystem e poi modificata solo
  la riga del namespace, per eliminare qualunque rischio di errore di
  trascrizione su 2369 righe. Verificato con un diff automatico riga per
  riga contro l'originale (escludendo la riga del namespace): **zero
  differenze di contenuto**. Unica differenza tecnica rilevata: il file
  copiato ha terminatori di riga LF invece dei CRLF dell'originale —
  irrilevante (nessun heredoc/nowdoc nel file, quindi nessuna stringa
  dipende dal tipo di fine riga; `.gitattributes` ha comunque `text=auto`,
  che normalizza a LF nel repository indipendentemente da questo).
- `extends Controller` (nome nudo) risolve a `App\Http\Controllers\System\Controller`,
  stesso namespace, nessuna modifica necessaria.
- `use \crocodicstudio\crudbooster\controllers\ExportData;` e
  `use \crocodicstudio\crudbooster\controllers\ImportData;` (usati in
  `postExportData()` per `Excel::download(new ExportData(...))`) lasciati
  invariati: risolvono attraverso gli shim di
  [013](013-importdata-exportdata-shim-class-alias.md), stesso comportamento.
- `use App\Http\Controllers\System\LogsController;` — import preesistente,
  ora un self-import (la classe importata è nello stesso namespace di
  destinazione): legale in PHP, nessun errore.
- Il vecchio file diventa uno shim a una riga:
  `class_alias(\App\Http\Controllers\System\CBController::class, __NAMESPACE__ . '\CBController');`

## Motivazione

Chiude la serie delle 5 classi motore iniziata in
[006](006-controller-sistema-app-http-controllers-system.md)/[012](012-controller-motore-shim-class-alias.md):
tutto il codice reale del pacchetto CRUDBooster vendorizzato è ora fuori da
`packages/`, in `App\Http\Controllers\System`, con gli FQCN legacy ancora
risolvibili per i moduli generati in produzione grazie agli shim
`class_alias()`.

## Test

Trattato con la massima cautela per via delle dimensioni e del numero di
controller dipendenti:
- Diff automatico riga per riga (`diff` su una copia con la riga 3
  rimossa da entrambi i file): **zero differenze**, oltre alla riga del
  namespace.
- `php -l` su entrambi i file: nessun errore di sintassi.
- Script diretto via `vendor/autoload.php` dentro il container Docker:
  - `get_parent_class(new crocodicstudio\crudbooster\controllers\CBController())`
    → `App\Http\Controllers\System\Controller` (corretto: `CBController`
    estende `Controller`).
  - `get_parent_class(new App\Http\Controllers\System\AdminGroupsController())`
    → `App\Http\Controllers\System\CBController` (uno dei ~56 controller
    CRUD che estendono `CBController` per FQCN legacy, risolve
    correttamente attraverso l'alias).
- `php artisan route:list`: 486 rotte, nessun errore di autoload (invariato
  rispetto a prima dell'intervento).
- `curl` (senza sessione) su `/admin`, `/admin/groups`, `/admin/logs`,
  `/admin/users`: tutte 302 (redirect al login, comportamento atteso),
  nessun 500.

**Da fare**: verifica manuale via browser di un giro CRUD completo (list →
add → edit → delete, export/import) lasciata all'utente — questa classe
gestisce l'intero flusso CRUD e i test automatici sopra non lo esercitano
a fondo (solo autoload e routing).

## Rischi e note

- Con questo intervento si chiude la "Roadmap uscita da CRUDBooster
  (packages/)" per le classi motore — vedi
  [`README.md`](README.md#roadmap-uscita-da-crudbooster-packages). Restano
  fuori scope: l'helper `CRUDBooster` (84 metodi, alias globale) e gli
  asset statici (~90% dei file del pacchetto, lavoro UI/UX separato).
- Nessuna modifica a `composer.json` (stesso motivo degli interventi
  precedenti della serie).
- La verifica manuale end-to-end di un flusso CRUD completo resta a carico
  dell'utente (vedi sopra).

## Rollback

`git revert` del commit — cambiamento puramente strutturale
(spostamento + alias), nessun comportamento a runtime modificato secondo
tutte le verifiche automatiche eseguite.
