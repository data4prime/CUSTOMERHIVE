# 023 - Helpers (1/N): NNHelper eliminato, ModuleHelperHelper e MyHelper spostati in App\Helpers

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Architettura / CRUDBooster
- **File/aree di codice coinvolte**:
  - `app/Helpers/ModuleHelperHelper.php`, `app/Helpers/MyHelper.php` (nuovi)
  - `packages/crocodicstudio/crudbooster/src/helpers/NNHelper.php` (eliminato)
  - `packages/crocodicstudio/crudbooster/src/helpers/{ModuleHelperHelper,MyHelper}.php` (eliminati, spostati)
  - `packages/crocodicstudio/crudbooster/src/CRUDBoosterServiceProvider.php`
  - `packages/crocodicstudio/crudbooster/src/helpers/{ChatAIHelper,GroupHelper,QlikHelper,UserHelper}.php`
  - `app/Http/Controllers/System/AdminQlikItemsController.php`
  - `packages/crocodicstudio/crudbooster/src/views/header.blade.php`
  - `config/app.php`

## Contesto

Primo lotto del percorso di uscita da `packages/.../helpers/` (14 file),
analizzato in chat il 2026-08-28: a differenza di `controllers/`, **nessun
controller custom cliente referenzia un helper per FQCN** — solo alias
globali (`CRUDBooster::`, `UserHelper::`, ecc. via `AliasLoader`) o, per i
6 helper non aliasati, FQCN usati solo da file già tracciati in questo
repo. Quindi qui **non serve nessuno shim `class_alias()` permanente**:
si sposta e si aggiornano direttamente i punti noti, senza lasciare
compatibilità legacy.

Scelto `App\Helpers` come nuovo namespace (non esiste una convenzione
Laravel builtin per gli helper; il progetto ha già `app/Classes/` e
`app/Services/` come precedenti).

Ordine di lavoro: dal più piccolo/isolato al più grande/centrale
(`CRUDBooster.php`, 80 KB e 101 metodi, per ultimo — stesso principio già
usato per `CBController`).

## Situazione prima

- `NNHelper.php`: **zero riferimenti in tutto il repo**, verificato due
  volte (prima nell'analisi generale, poi appena prima di questo
  intervento) — codice morto.
- `ModuleHelperHelper.php`: nessuna dipendenza interna da altri helper,
  referenziato per FQCN da 2 file (`AdminQlikItemsController.php`,
  `header.blade.php`).
- `MyHelper.php`: referenziato internamente (chiamate non qualificate,
  stesso namespace) da 4 altri helper (`ChatAIHelper`, `GroupHelper`,
  `QlikHelper`, `UserHelper`), aliasato globalmente
  (`AliasLoader::alias('MyHelper', ...)`), e referenziato per **FQCN
  diretto in `config/app.php`** (`'version' => \crocodicstudio\crudbooster\helpers\MyHelper::version()`)
  — caricato prima che l'`AliasLoader` sia attivo, quindi non poteva usare
  l'alias corto.

## Situazione dopo

- `NNHelper.php` cancellato, nessuna sostituzione.
- `ModuleHelperHelper` spostato in `App\Helpers\ModuleHelperHelper`
  (contenuto invariato). Aggiornati i 2 `use` che lo importavano.
- `MyHelper` spostato in `App\Helpers\MyHelper` (contenuto invariato).
  Aggiornati: la riga in `config/app.php`, la riga
  `AliasLoader::alias('MyHelper', ...)` in `CRUDBoosterServiceProvider`,
  e aggiunto `use App\Helpers\MyHelper;` nei 4 helper che lo chiamano
  internamente (prima risolvevano per nome nudo nello stesso namespace,
  ora serve l'import esplicito).

## Motivazione

Riduce la superficie di `packages/.../helpers/` partendo dai pezzi più
sicuri da validare il pattern (nessun impatto su codice esterno,
diversamente da `controllers/`) prima di affrontare l'helper centrale
`CRUDBooster.php`.

## Test

- `php -l` su tutti i file toccati/nuovi: nessun errore.
- `composer dump-autoload`.
- Verifica diretta: `class_exists('App\Helpers\MyHelper')` → vero;
  `App\Helpers\MyHelper::is_int('42')` → vero;
  `class_exists('crocodicstudio\crudbooster\helpers\NNHelper')` → falso
  (confermato sparito).
- Bootstrap completo dell'app: `config('app.version')` risolve senza
  errori attraverso la nuova classe (valore invariato rispetto a prima
  dello spostamento — stessa logica, stesso ambiente).
- `php artisan config:clear`, `php artisan route:list` (486 rotte,
  invariato).
- `curl` senza sessione su `/admin`, `/admin/groups`: 302, nessun 500.

## Rischi e note

- Restano da spostare: `GroupHelper`, `TenantHelper`, `CB`, `ChatAIHelper`,
  `LicenseHelper`, `MenuHelper`, `QlikHelper`, `ModuleHelper`,
  `UserHelper`, `Helper.php` (funzioni globali, cambio path del
  `require`), `CRUDBooster.php` per ultimo — vedi
  [`README.md`](README.md#roadmap-uscita-da-crudbooster-packages).

## Rollback

`git revert` del commit — ripristina i file nel pacchetto e tutti i
riferimenti, nessun impatto su codice esterno (nessuno shim da rimuovere).
