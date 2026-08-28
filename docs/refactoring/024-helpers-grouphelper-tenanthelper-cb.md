# 024 - Helpers (2/N): GroupHelper, TenantHelper, CB spostati in App\Helpers

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Architettura / CRUDBooster
- **File/aree di codice coinvolte**:
  - `app/Helpers/{GroupHelper,TenantHelper,CB}.php` (nuovi)
  - `packages/crocodicstudio/crudbooster/src/helpers/{GroupHelper,TenantHelper,CB}.php` (eliminati)
  - `packages/crocodicstudio/crudbooster/src/CRUDBoosterServiceProvider.php`
  - `app/Http/Controllers/System/AdminCmsUsersController.php`
  - `app/Console/Commands/UserExpiryNotification.php`
  - `app/Http/Controllers/System/CBController.php`
  - `packages/crocodicstudio/crudbooster/src/helpers/{ChatAIHelper,QlikHelper,LicenseHelper,CRUDBooster}.php`

## Contesto

Continua [023](023-helpers-nnhelper-moduleHelperhelper-myhelper.md).
Questi 3 helper hanno più accoppiamento interno dei precedenti — mappato
prima di muoversi:
- `GroupHelper` chiama `CRUDBooster::` (non ancora spostato) ed è chiamato
  da `ChatAIHelper`/`QlikHelper` (non ancora spostati).
- `TenantHelper` chiama `CRUDBooster::`/`ModuleHelper::` (non ancora
  spostati) ed è chiamato da `LicenseHelper` (non ancora spostato).
- `CB extends CRUDBooster` (non ancora spostato) ed è chiamato da
  `CRUDBooster.php` stesso (`CB::pk(...)` alla riga 1764).

Il pattern usato: spostare la classe, aggiungere un `use` esplicito verso
la vecchia posizione (`crocodicstudio\crudbooster\helpers\...`) per ciò
che questa classe chiama e non è ancora spostato, e aggiungere un `use`
esplicito verso la nuova posizione (`App\Helpers\...`) in ogni file **non
ancora spostato** che la chiamava per nome nudo nello stesso namespace.

## Situazione prima

I 3 file in `packages/crocodicstudio/crudbooster/src/helpers/`, tutti
chiamati per nome nudo (nessun `use`) da altri helper nello stesso
namespace, più aliasati globalmente (`GroupHelper`, `TenantHelper`) o
referenziati per FQCN esplicito (`CB`, entrambi via `use` nei file
chiamanti).

## Situazione dopo

- I 3 file spostati in `App\Helpers\{GroupHelper,TenantHelper,CB}`,
  contenuto invariato salvo gli import aggiunti:
  - `GroupHelper`: `use crocodicstudio\crudbooster\helpers\CRUDBooster;`
    (per le 2 chiamate `CRUDBooster::myId()`). Non serve import per
    `MyHelper::` — già in `App\Helpers`, stesso namespace.
  - `TenantHelper`: `use crocodicstudio\crudbooster\helpers\{CRUDBooster,ModuleHelper};`.
  - `CB`: `use crocodicstudio\crudbooster\helpers\CRUDBooster;` (per
    `extends CRUDBooster` e la chiamata interna).
- `AliasLoader`: `CB`/`GroupHelper`/`TenantHelper` ripuntati a
  `App\Helpers\...`.
- 3 file esterni con un `use ...` esplicito verso il vecchio FQCN,
  aggiornati al nuovo: `AdminCmsUsersController.php` (`GroupHelper`),
  `UserExpiryNotification.php` (`TenantHelper`), `CBController.php` (`CB`).
- 3 file **non ancora spostati** che chiamavano questi helper per nome
  nudo, ora con un `use App\Helpers\...;` esplicito aggiunto:
  `ChatAIHelper.php`/`QlikHelper.php` (`GroupHelper`), `LicenseHelper.php`
  (`TenantHelper`), `CRUDBooster.php` (`CB`).

## Motivazione

Prosegue lo svuotamento di `packages/.../helpers/`, gestendo esplicitamente
l'accoppiamento incrociato via `use` mirati invece di spostare tutto
insieme in blocco — permette di continuare un file (o pochi) alla volta
mantenendo ogni passo verificabile.

## Test

- `php -l` su tutti i 10 file toccati: nessun errore (una sola
  Deprecation notice preesistente in `CRUDBooster.php` riga 1530,
  parametro opzionale prima di uno obbligatorio — non introdotta da
  questo intervento).
- `composer dump-autoload`.
- Verifica diretta: `class_exists()` vero per i 3 nuovi FQCN;
  `get_parent_class(new App\Helpers\CB())` →
  `crocodicstudio\crudbooster\helpers\CRUDBooster` (corretto: `CRUDBooster`
  non ancora spostato in questa fase).
- `php artisan route:list`: 486 rotte, invariato.
- `curl` senza sessione su `/admin`, `/admin/users`: 302, nessun 500.

## Rischi e note

- Restano da spostare: `ChatAIHelper`, `LicenseHelper`, `MenuHelper`,
  `QlikHelper`, `ModuleHelper`, `UserHelper`, `Helper.php`, `CRUDBooster.php`
  (ultimo) — vedi [`README.md`](README.md#roadmap-uscita-da-crudbooster-packages).
  Ad ogni passo successivo va ripetuta la stessa verifica di
  accoppiamento (chi chiama chi per nome nudo) prima di spostare.

## Rollback

`git revert` del commit — ripristina i 3 file nel pacchetto e tutti i
riferimenti (alias, `use` aggiunti/modificati), nessun impatto su codice
esterno.
