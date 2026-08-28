# 030 - CBBackend e CBAuthAPI spostati in App\Http\Middleware — middlewares/ non esiste più

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Architettura / CRUDBooster
- **File/aree di codice coinvolte**:
  - `app/Http/Middleware/{CBBackend,CBAuthAPI}.php` (nuovi)
  - `packages/crocodicstudio/crudbooster/src/middlewares/` (rimossa interamente)
  - `packages/crocodicstudio/crudbooster/src/routes.php`
  - `config/lfm.php`

## Contesto

Dopo la rimozione di `CBBackend__.php` (morto) in
[018](018-commands-middlewares-validations-cleanup.md), restavano solo i
2 middleware vivi: `CBBackend` (gate su ogni richiesta area admin —
`Auth::guest()`, lock screen, redirect dashboard) e `CBAuthAPI`
(`CRUDBooster::authAPI()` sulle rotte `api/*`). Entrambi referenziati per
FQCN letterale, non per alias, applicati inline nelle definizioni di
rotta invece che tramite gli alias di `app/Http/Kernel.php`
(`$routeMiddleware` non li contiene).

Verificato con una ricerca su tutto il repo (non solo `app/`/`routes/`,
anche `config/`): **`config/lfm.php`** (config pubblicata di
`unisharp/laravel-filemanager`) applica anch'essa `CBBackend` alle
proprie rotte — riferimento che una ricerca ristretta a `app`/`resources`/`routes`
avrebbe perso.

## Situazione prima

I 2 file in `packages/crocodicstudio/crudbooster/src/middlewares/`,
namespace `crocodicstudio\crudbooster\middlewares`. Applicati per FQCN in
3 punti: `routes.php` (2× `CBBackend`, 1× `CBAuthAPI`) e `config/lfm.php`
(1× `CBBackend`).

## Situazione dopo

- I 2 file spostati in `App\Http\Middleware\{CBBackend,CBAuthAPI}`
  (namespace standard Laravel, stessa cartella di
  `SetUserPreferredLanguage.php`/`EncryptCookies.php`/ecc.), contenuto
  invariato — `use CRUDBooster;` bare non necessitava modifiche (risolve
  già tramite l'alias globale, indipendente dal namespace del file).
- `middlewares/` rimossa interamente (cartella vuota).
- Aggiornati i 3 riferimenti in `routes.php` e 1 in `config/lfm.php`.

## Motivazione

Ultimo pezzo di logica "viva" rimasto in `packages/.../src/` oltre a
`views/`/`assets/` (fuori scope, UI/UX). Nessun contratto FQCN esterno da
proteggere (i middleware sono applicati centralmente in `routes.php`, non
referenziati dai controller custom dei clienti).

## Test

- Grep di conferma: zero riferimenti al vecchio namespace in tutto il
  repo.
- `php -l` su tutti e 4 i file toccati: nessun errore.
- `composer dump-autoload`; `class_exists()` vero per entrambe le nuove
  classi.
- `php artisan route:list`: 486 rotte, invariato.
- `curl` senza sessione su `/admin`, `/admin/groups`, `/admin/logs`:
  tutti 302 — **a differenza delle verifiche sugli helper/view, qui il
  302 è generato proprio da `CBBackend::handle()` (`Auth::guest()`)**,
  quindi il test esercita davvero il codice appena spostato, non solo il
  routing.

## Rischi e note

- Nessuno noto. Con questo, gli unici contenuti rimasti in
  `packages/crocodicstudio/crudbooster/src/` sono `assets/`, `fonts/`,
  `views/`, `CRUDBoosterServiceProvider.php`, `routes.php` — tutti fuori
  scope per un ulteriore refactoring backend (asset/view sono lavoro
  UI/UX; il service provider e le rotte sono il punto di aggancio finale
  e restano nel pacchetto per costruzione).

## Rollback

`git revert` del commit — ripristina la cartella e i 4 riferimenti.
