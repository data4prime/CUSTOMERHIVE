# 014 - ApiController spostata (shim class_alias)

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Architettura / CRUDBooster
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/ApiController.php` (nuovo, 901 righe)
  - `packages/crocodicstudio/crudbooster/src/controllers/ApiController.php` (ora uno shim)

## Contesto

Continua [012](012-controller-motore-shim-class-alias.md)/[013](013-importdata-exportdata-shim-class-alias.md):
delle 5 classi "motore", `ApiController` è la penultima (901 righe),
`extends Controller` (già spostata) ed è a sua volta estesa per FQCN
letterale da 14 controller custom (`Api*Controller` in
`app/Http/Controllers/`, generati da interfaccia, gitignored) — motore
delle rotte API custom definite in `cms_apicustom`.

## Situazione prima

Viveva in `packages/crocodicstudio/crudbooster/src/controllers/ApiController.php`,
namespace `crocodicstudio\crudbooster\controllers`.

## Situazione dopo

- Codice reale spostato, invariato salvo il namespace, in
  `App\Http\Controllers\System\ApiController`. Diff riga per riga contro
  la versione precedente: nessuna differenza di sostanza, solo 4 righe con
  spazi finali rimossi (whitespace, nessun impatto sul comportamento).
- `extends Controller` (nome nudo) risolve automaticamente a
  `App\Http\Controllers\System\Controller` — stessa classe, stesso
  namespace, nessun `use` aggiuntivo necessario.
- Import `use CRUDBooster;`, `use ModuleHelper;`, `use UserHelper;`,
  `use Session;` (nomi nudi, risolvono al namespace radice tramite alias
  Laravel/globali) lasciati invariati: la risoluzione non dipende dal
  namespace del file che li importa.
- Import inutilizzati preesistenti (`use App\Http\Controllers\System\AdminCmsUsersController;`,
  `use App\Http\Controllers\AdminSmartphonesController;`, mai referenziati
  nel corpo della classe) lasciati intoccati — dead code preesistente,
  fuori scope per un intervento di puro spostamento.
- Il vecchio file diventa uno shim a una riga:
  `class_alias(\App\Http\Controllers\System\ApiController::class, __NAMESPACE__ . '\ApiController');`

## Motivazione

Stesso pattern "strangler fig" di [012](012-controller-motore-shim-class-alias.md)/[013](013-importdata-exportdata-shim-class-alias.md).
Penultimo passo prima di `CBController` (l'ultima, e più grande/rischiosa,
delle 5 classi motore).

## Test

- `php -l` su entrambi i file: nessun errore di sintassi.
- Diff automatico riga per riga tra il vecchio file (da git HEAD) e il
  nuovo (namespace normalizzato per il confronto): solo differenze di
  whitespace finale su 4 righe.
- Script diretto via `vendor/autoload.php`:
  `get_parent_class(new crocodicstudio\crudbooster\controllers\ApiController())`
  → `App\Http\Controllers\System\Controller` (corretto: `ApiController`
  estende `Controller`, non è essa stessa la classe finale).
- `php artisan route:list`: 486 rotte, nessun errore di autoload.

## Rischi e note

- Ultimo pezzo rimanente: `CBController` (2369 righe, ~56 file esterni la
  estendono) — vedi [`README.md`](README.md#roadmap-uscita-da-crudbooster-packages).
  Sarà l'intervento con il diff più grande e il rischio maggiore della
  serie, da trattare con la massima cautela (revisione a più passate del
  diff, non solo verifica sintattica/di routing).
- Nessuna modifica a `composer.json` (stesso motivo dei due interventi
  precedenti).

## Rollback

`git revert` del commit — cambiamento puramente strutturale
(spostamento + alias), nessun comportamento a runtime modificato.
