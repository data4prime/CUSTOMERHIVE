# 012 - Prima classe "motore" spostata: Controller (shim class_alias)

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Architettura / CRUDBooster
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/Controller.php` (nuovo)
  - `packages/crocodicstudio/crudbooster/src/controllers/Controller.php` (ora uno shim)

## Contesto

Prosegue il lavoro "portare fuori da `packages/` tutto quello che si può,
rispettando gli standard Laravel", iniziato in
[006](006-controller-sistema-app-http-controllers-system.md). Lì erano state
spostate le 21 "schermate" senza contratto esterno; restavano le 5 classi
"motore" (`Controller`, `CBController`, `ApiController`, `ExportData`,
`ImportData`), usate in `extends` per FQCN letterale da 70 file nel repo —
in gran parte controller generati da interfaccia in produzione, mai presenti
in questo repo, quindi non modificabili qui.

Si inizia da `Controller` (15 righe) perché nessun file esterno la estende
per FQCN diretto (verificato via grep): è solo la base interna di
`CBController` e `ApiController`. Rischio quasi nullo, adatta a validare il
meccanismo dello shim prima di applicarlo alle classi grosse (`CBController`,
2369 righe, estesa da ~56 controller; `ApiController`, 901 righe, estesa da
14).

## Situazione prima

`packages/crocodicstudio/crudbooster/src/controllers/Controller.php`,
namespace `crocodicstudio\crudbooster\controllers`, conteneva la classe reale
(solo `extends Illuminate\Routing\Controller`, corpo vuoto — i `use` dei
trait Laravel erano già commentati, dead code preesistente).

## Situazione dopo

- Classe reale spostata in `App\Http\Controllers\System\Controller`
  (rimossi anche i commenti dei trait mai attivati, dead code).
- Il vecchio file diventa uno shim di 5 righe:
  `class_alias(\App\Http\Controllers\System\Controller::class, __NAMESPACE__ . '\Controller');`
  — l'FQCN legacy resta risolvibile per chi lo estende da fuori.
- **Nessuna modifica** a `CBController.php`/`ApiController.php`: entrambi
  fanno `extends Controller` (nome nudo, stesso namespace), che dopo
  l'alias risolve automaticamente alla nuova classe. Non serve importarla
  per FQCN — anzi, andrebbe evitato: `CBController.php` importa già
  `use Illuminate\Support\Facades\App;`, un `use App\Http\Controllers\System\Controller;`
  nello stesso file avrebbe introdotto un'ambiguità inutile sul nome breve
  `Controller` importato vs. quello di namespace. Lo shim rende il
  cambiamento invisibile a questo livello, com'è lo scopo del pattern.

## Motivazione

Stesso pattern "strangler fig" già validato per l'auth guard
([001](001-auth-guard-additivo-fase-1.md)): spostare il codice reale,
lasciare un alias che mantiene l'FQCN legacy risolvibile, zero impatto su
chi lo estende da fuori. Iniziare dalla classe più piccola e meno
referenziata riduce il rischio del primo tentativo del meccanismo su
questa specifica gerarchia di classi.

## Test

Nessuna suite PHPUnit dedicata (nessun test esistente esercita queste
classi motore direttamente). Verificato con mezzi leggeri, dentro il
container Docker locale:
- `php -l` su entrambi i file: nessun errore di sintassi.
- `php artisan route:list`: 486 rotte, nessun errore fatale di autoload
  (esercita l'intera gerarchia `CBController`/`ApiController` → tutti i
  controller CRUD e API generati).
- Script diretto via `vendor/autoload.php`:
  `get_parent_class(new crocodicstudio\crudbooster\controllers\CBController())`
  → `App\Http\Controllers\System\Controller` — conferma che l'alias
  risolve correttamente e la catena di ereditarietà arriva alla nuova
  classe.

**Da fare**: verifica manuale via browser lasciata all'utente (nessun
comportamento a runtime dovrebbe cambiare, ma non sostituisce un giro
sull'app reale).

## Rischi e note

- Restano da spostare con lo stesso pattern: `ImportData`/`ExportData`
  (piccole, nessun `extends` esterno trovato), poi `ApiController` (14
  file esterni), poi `CBController` per ultima (~56 file esterni, la più
  grande e rischiosa) — vedi
  [`README.md`](README.md#roadmap-uscita-da-crudbooster-packages).
- Nessuna modifica a `composer.json`: `App\` è già mappato PSR-4 su
  `app/`, non serve `composer dump-autoload` per un file nuovo in una
  radice già coperta.

## Rollback

`git revert` del commit — cambiamento puramente strutturale
(spostamento + alias), nessun comportamento a runtime modificato.
