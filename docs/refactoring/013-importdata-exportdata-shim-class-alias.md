# 013 - ImportData/ExportData spostate (shim class_alias)

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Architettura / CRUDBooster
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/ImportData.php` (nuovo)
  - `app/Http/Controllers/System/ExportData.php` (nuovo)
  - `packages/crocodicstudio/crudbooster/src/controllers/ImportData.php` (ora uno shim)
  - `packages/crocodicstudio/crudbooster/src/controllers/ExportData.php` (ora uno shim)

## Contesto

Continua il percorso di [012](012-controller-motore-shim-class-alias.md):
delle 5 classi "motore", dopo `Controller` si spostano `ImportData`
(14 righe) ed `ExportData` (29 righe) — le due rimanenti più piccole.
A differenza di `Controller`, non sono basi di `CBController`/`ApiController`
ma classi standalone usate con Maatwebsite Excel (`ImportData implements
ToCollection`, `ExportData implements FromView, ShouldAutoSize`).

Verificato via grafo del codice (tokensave) prima di spostarle: **zero
riferimenti** a queste due classi in tutto il repo indicizzato (nessun
`new ExportData`/`new ImportData`, nessun caller sui rispettivi metodi).
Coerente con quanto già emerso in [006](006-controller-sistema-app-http-controllers-system.md):
il loro contratto reale è con i controller generati da interfaccia in
produzione, non versionati in questo repo — qui risultano irraggiungibili,
ma si procede comunque con lo shim per non rompere quell'uso esterno.

## Situazione prima

Entrambe le classi vivevano in
`packages/crocodicstudio/crudbooster/src/controllers/`, namespace
`crocodicstudio\crudbooster\controllers`, invariate rispetto all'originale
CRUDBooster.

## Situazione dopo

- Codice reale spostato, invariato, in `App\Http\Controllers\System\ImportData`
  e `App\Http\Controllers\System\ExportData`.
- I due vecchi file diventano shim a una riga:
  `class_alias(\App\Http\Controllers\System\ImportData::class, __NAMESPACE__ . '\ImportData');`
  (idem per `ExportData`).
- **Nessuna modifica** a `CBController.php`, che le importa con
  `use \crocodicstudio\crudbooster\controllers\ExportData;` /
  `use ...\ImportData;`: l'import punta all'FQCN legacy, che dopo l'alias
  risolve automaticamente alla nuova classe — stesso comportamento del
  pattern usato per `Controller`.

## Motivazione

Stesso pattern "strangler fig" di [012](012-controller-motore-shim-class-alias.md),
applicato alle prossime due classi in ordine di rischio crescente (nessun
uso interno al repo, quindi rischio anche minore di `Controller`).

## Test

- `php -l` su tutti e 4 i file: nessun errore di sintassi.
- Script diretto via `vendor/autoload.php` dentro il container Docker:
  istanziate `new crocodicstudio\crudbooster\controllers\ExportData(...)`
  e `new crocodicstudio\crudbooster\controllers\ImportData()` — entrambe
  risolvono a `App\Http\Controllers\System\*` (`get_class()`) e superano
  il check `instanceof` sulle interfacce Maatwebsite Excel
  (`FromView`/`ToCollection`).
- `php artisan route:list`: 486 rotte, nessun errore di autoload.

## Rischi e note

- Restano da spostare: `ApiController` (14 file esterni la estendono),
  poi `CBController` per ultima (~56 file esterni, la più grande) — vedi
  [`README.md`](README.md#roadmap-uscita-da-crudbooster-packages).
- Nessuna modifica a `composer.json` (stesso motivo di
  [012](012-controller-motore-shim-class-alias.md): `App\` già PSR-4 su `app/`).

## Rollback

`git revert` del commit — cambiamento puramente strutturale
(spostamento + alias), nessun comportamento a runtime modificato.
