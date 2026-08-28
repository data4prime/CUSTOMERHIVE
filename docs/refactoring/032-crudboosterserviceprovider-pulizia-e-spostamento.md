# 032 - CRUDBoosterServiceProvider ripulito e spostato in App\Providers

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Architettura / CRUDBooster
- **File/aree di codice coinvolte**:
  - `app/Providers/CRUDBoosterServiceProvider.php` (nuovo)
  - `packages/crocodicstudio/crudbooster/src/CRUDBoosterServiceProvider.php` (eliminato)
  - `app/Providers/AppServiceProvider.php`
  - `config/app.php`

## Contesto

Ultimo file rimasto con logica attiva in `packages/.../src/` oltre a
`views/`/`assets/`. Analisi prima di agire:
- 3 delle 4 registrazioni manuali di provider di terze parti
  (`Barryvdh\DomPDF\ServiceProvider`, `Maatwebsite\Excel\ExcelServiceProvider`,
  `Intervention\Image\ImageServiceProvider`) sono **duplicati morti**:
  verificato nel `composer.json` di ciascun pacchetto vendor che dichiara
  un blocco `extra.laravel` (provider + alias) per l'auto-discovery di
  Laravel, e che questo progetto non disabilita l'auto-discovery
  (nessun `extra.laravel.dont-discover` nel `composer.json` root). Solo
  `unisharp/laravel-filemanager` **non** supporta l'auto-discovery — quella
  registrazione è l'unica reale.
- Gli alias `PDF`/`Excel` risultavano già registrati **anche** in
  `config/app.php` (righe preesistenti, non toccate da questo intervento)
  — tripla ridondanza con l'auto-discovery e con l'`AliasLoader` del
  provider.
- Il singleton `'crudbooster' => true` non è mai letto da nessuna parte
  nel repo — morto.

## Situazione prima

`packages/crocodicstudio/crudbooster/src/CRUDBoosterServiceProvider.php`:
`loadViewsFrom`/`publishes` (path relativi via `__DIR__`), `require` delle
funzioni globali, singleton morto, 4 registrazioni manuali di provider
(3 ridondanti), 8 alias custom via `AliasLoader::alias()`.

## Situazione dopo

- **Nuovo** `App\Providers\CRUDBoosterServiceProvider`: solo
  `loadViewsFrom`/`publishes` (ora con `base_path('packages/crocodicstudio/crudbooster/src/...')`
  invece di `__DIR__`, dato che il provider non vive più accanto a
  `views/`/`assets/`) e la registrazione di
  `Unisharp\Laravelfilemanager\LaravelFilemanagerServiceProvider` (l'unica
  reale). Rimossi: le 3 registrazioni ridondanti, il singleton morto, il
  `require` delle funzioni globali (spostato), l'intero blocco
  `AliasLoader` (i suoi 8 alias spostati in `config/app.php`).
- `AppServiceProvider::register()`: aggiunto
  `require app_path('Helpers/functions.php');` (era in
  `CRUDBoosterServiceProvider::register()`).
- `config/app.php`:
  - `providers`: `crocodicstudio\crudbooster\CRUDBoosterServiceProvider::class`
    → `App\Providers\CRUDBoosterServiceProvider::class` (stessa posizione
    nell'elenco).
  - `aliases`: aggiunti gli 8 alias custom (`CRUDBooster`, `CB`,
    `GroupHelper`, `QlikHelper`, `ModuleHelper`, `TenantHelper`,
    `UserHelper`, `MyHelper`) con la sintassi `::class` già usata nel
    resto del file, subito dopo `PDF`/`Excel` preesistenti.

## Motivazione

Elimina superficie morta (3 registrazioni + 1 singleton mai necessari) e
converge sul meccanismo standard di Laravel per gli alias (`config('app.aliases')`,
letto internamente dallo stesso `AliasLoader` — comportamento identico,
niente più chiamate programmatiche in un provider). Chiude l'ultimo pezzo
di logica non-view/asset rimasto nel pacchetto vendorizzato.

## Test

- `php -l` su tutti e 3 i file toccati: nessun errore.
- `php artisan config:clear` + `composer dump-autoload`.
- Bootstrap completo dell'app: `class_exists('App\Providers\CRUDBoosterServiceProvider')`,
  `class_exists('CRUDBooster')`, `class_exists('UserHelper')` (alias
  risolti tramite `config('app.aliases')`, non più `AliasLoader::alias()`
  manuale) → tutti veri; `function_exists('g')` → vero (funzioni globali
  ora richieste da `AppServiceProvider`); `class_exists('PDF')`,
  `class_exists('Excel')` → veri (auto-discovery, invariato).
- `php artisan route:list`: 486 rotte, invariato; **17 rotte
  `laravel-filemanager` ancora presenti** (conferma che l'unica
  registrazione manuale rimasta funziona).
- `view()->exists('crudbooster::header')` → vero (conferma che
  `loadViewsFrom` con il nuovo `base_path(...)` risolve correttamente,
  invece del vecchio `__DIR__`).
- `curl` senza sessione: `/admin` → 302, `/admin/login` → 200,
  `/admin/groups` → 302, nessun 500.

## Rischi e note

- Le 3 registrazioni manuali rimosse erano confermate ridondanti tramite
  ispezione dei `composer.json` dei pacchetti vendor, non per assunzione
  — se in futuro uno di questi pacchetti perdesse il supporto
  all'auto-discovery in un aggiornamento, andrebbe ripristinata la
  registrazione manuale per quello specifico pacchetto.
- Con questo, `packages/crocodicstudio/crudbooster/src/` contiene solo
  `assets/`, `fonts/`, `views/` — nessun file PHP con logica applicativa
  residua, solo asset statici e template Blade (fuori scope, lavoro
  UI/UX).

## Rollback

`git revert` del commit — ripristina il vecchio provider nel pacchetto,
la riga in `config/app.php` → `providers`, e rimuove le 8 righe aggiunte
in `config/app.php` → `aliases` e la riga in `AppServiceProvider`.
