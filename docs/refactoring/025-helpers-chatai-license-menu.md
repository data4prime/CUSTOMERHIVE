# 025 - Helpers (3/N): ChatAIHelper, LicenseHelper, MenuHelper spostati in App\Helpers

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Architettura / CRUDBooster
- **File/aree di codice coinvolte**:
  - `app/Helpers/{ChatAIHelper,LicenseHelper,MenuHelper}.php` (nuovi)
  - `packages/crocodicstudio/crudbooster/src/helpers/{ChatAIHelper,LicenseHelper,MenuHelper}.php` (eliminati)
  - `packages/crocodicstudio/crudbooster/src/helpers/CRUDBooster.php`
  - 9 controller in `app/Http/Controllers/System/` (`AdminChatAIController`, `AdminModuleHelperController`, `GetLicense`, `AdminCmsUsersController`, `AdminController`, `AdminGroupsController`, `AdminQlikItemsController`, `AdminTenantsController`, `MenusController`, `StatisticBuilderController`)
  - `resources/views/chat_ai/view.blade.php`
  - 5 view del pacchetto (`header.blade.php`, `sidebar.blade.php`, `license_modal.blade.php`, `statistic_builder/components/qlikwidget.blade.php`, `statistic_builder/layout.blade.php`)

## Contesto

Continua [023](023-helpers-nnhelper-moduleHelperhelper-myhelper.md)/[024](024-helpers-grouphelper-tenanthelper-cb.md).
Questi 3 helper avevano il numero più alto di punti esterni da aggiornare
finora — non tutti tramite un singolo `use` per file: alcune view Blade
richiamano l'FQCN **inline**, senza import (`@if(crocodicstudio\crudbooster\helpers\LicenseHelper::isActiveQlik())`),
quindi ogni occorrenza andava sostituita singolarmente, non un solo punto
per file.

Accoppiamento interno mappato prima di muoversi:
- `ChatAIHelper` chiama `CRUDBooster::`/`UserHelper::` (non ancora
  spostati) ed è chiamato da `CRUDBooster.php` (`ChatAIHelper::can_see_item()`).
- `LicenseHelper` chiama `UserHelper::countUsers()` (non ancora spostato);
  nessun helper lo chiama internamente.
- `MenuHelper` chiama `CRUDBooster::`/`UserHelper::` (non ancora spostati)
  ed è chiamato da `CRUDBooster.php` (`MenuHelper::parse_path_for_*`).

## Situazione prima

I 3 file in `packages/.../helpers/`. Riferimenti esterni noti:
- `ChatAIHelper`: 3 file (2 `use` + 1 FQCN inline in una view).
- `LicenseHelper`: 14 file — 9 controller/comandi con `use` pulito, 5 view
  Blade con **7 occorrenze inline** senza `use` (`header.blade.php` x1,
  `sidebar.blade.php` x3, `qlikwidget.blade.php` x2,
  `statistic_builder/layout.blade.php` x1; `license_modal.blade.php` ha
  invece un `use` pulito).
- `MenuHelper`: 3 file — 1 `use` pulito (`MenusController.php`) + 2 FQCN
  inline (`sidebar.blade.php`, e `sidebar.blade copy.php`).

## Situazione dopo

- I 3 file spostati in `App\Helpers\{ChatAIHelper,LicenseHelper,MenuHelper}`,
  contenuto invariato salvo import aggiunti (`CRUDBooster`/`UserHelper`
  dal vecchio namespace, dove necessario).
- Tutti i riferimenti esterni noti aggiornati al nuovo FQCN, sia i `use`
  sia le 7 occorrenze inline nelle view.
- `CRUDBooster.php`: aggiunto `use App\Helpers\{ChatAIHelper,MenuHelper};`
  per le sue chiamate interne.
- **Non toccato** (fuori scope, notato come backlog):
  `packages/crocodicstudio/crudbooster/src/views/sidebar.blade copy.php`
  — file con lo spazio nel nome, **non referenziato da nessuna vista
  reale** (verificato: nessun `view('...')` lo richiama), stesso tipo di
  file morto/di backup già visto con `CBBackend__.php` ([006](006-controller-sistema-app-http-controllers-system.md)).
  Contiene ancora il vecchio FQCN di `MenuHelper`, irrilevante essendo
  irraggiungibile.

## Motivazione

Prosegue lo svuotamento di `helpers/`. Ha richiesto più attenzione dei
lotti precedenti per la presenza di FQCN inline nelle view (senza un
singolo punto di import da correggere).

## Test

- `php -l` su tutti i file PHP toccati (nuovi/modificati): nessun errore
  (stessa deprecation preesistente in `CRUDBooster.php`, riga spostata da
  1530 a 1532 per le 2 righe di `use` aggiunte — non un problema nuovo).
- Verifica sintattica delle 6 view Blade toccate: compilate singolarmente
  con `Illuminate\View\Compilers\BladeCompiler::compileString()` e il PHP
  risultante passato a `php -l` — tutte e 6 senza errori (necessario
  perché un `curl` con redirect 302 non arriva a renderizzare le view,
  essendo il redirect generato dal middleware prima del render).
- Grep di conferma: zero riferimenti al vecchio namespace per i 3 helper
  in tutto il repo, salvo il file morto già noto.
- `composer dump-autoload`; `class_exists('App\Helpers\{ChatAIHelper,LicenseHelper,MenuHelper}')`
  → vero per tutti e 3; vecchio FQCN di `ChatAIHelper` → confermato non
  più esistente.
- `php artisan route:list`: 486 rotte, invariato.
- `curl` senza sessione su `/admin`, `/admin/groups`, `/admin/logs`: 302,
  nessun 500.

## Rischi e note

- `sidebar.blade copy.php`: stesso tipo di file morto di `CBBackend__.php`,
  da confermare ed eventualmente eliminare in un intervento a parte
  (fuori scope qui).
- Restano da spostare: `QlikHelper`, `ModuleHelper`, `UserHelper`,
  `Helper.php`, `CRUDBooster.php` (ultimo) — vedi
  [`README.md`](README.md#roadmap-uscita-da-crudbooster-packages).

## Rollback

`git revert` del commit — ripristina i 3 file nel pacchetto e tutti i
riferimenti (compresi i 7 punti inline nelle view), nessun impatto su
codice esterno.
