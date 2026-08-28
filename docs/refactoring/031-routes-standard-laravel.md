# 031 - routes.php spostato in routes/crudbooster.php (RouteServiceProvider)

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Architettura / CRUDBooster
- **File/aree di codice coinvolte**:
  - `routes/crudbooster.php` (nuovo)
  - `packages/crocodicstudio/crudbooster/src/routes.php` (eliminato)
  - `packages/crocodicstudio/crudbooster/src/CRUDBoosterServiceProvider.php`
  - `app/Providers/RouteServiceProvider.php`

## Contesto

Ultimo pezzo di logica "viva" in `packages/.../src/` oltre a `views/`/`assets/`
(fuori scope UI/UX). Il file era incluso con un `require __DIR__.'/routes.php';`
diretto in `CRUDBoosterServiceProvider::boot()`, fuori dal meccanismo
standard di Laravel (`RouteServiceProvider`).

**Punto delicato analizzato prima di agire**: l'ordine di registrazione
delle rotte. `App\Providers\RouteServiceProvider` è registrato in
`config/app.php` **prima** di `CRUDBoosterServiceProvider` (riga 192 vs
197) — quindi oggi le rotte di `routes.php` si registrano **dopo** quelle
di `routes/web.php` (che contiene già ~40 rotte extra sotto lo stesso
prefisso `admin/...`). Spostare il file dentro `RouteServiceProvider::map()`
senza attenzione avrebbe potuto cambiare quest'ordine, con rischio di far
vincere una rotta diversa in caso di sovrapposizione.

## Situazione prima

`packages/crocodicstudio/crudbooster/src/routes.php` (163 righe, 4 blocchi
`Route::group()` autosufficienti: API generator dinamico, upload/docs,
login/licenza, routing dinamico dei moduli da `cms_moduls`), incluso via
`require` in `CRUDBoosterServiceProvider::boot()`. Ultima riga,
`$controllers_base_path = '\crocodicstudio\crudbooster\controllers\\';`,
già segnalata come dead code in
[006](006-controller-sistema-app-http-controllers-system.md).

## Situazione dopo

- Contenuto spostato in `routes/crudbooster.php`, identico (verificato
  con diff, uniche differenze whitespace di fine riga), **rimossa la
  riga morta** `$controllers_base_path`.
- `app/Providers/RouteServiceProvider.php`: nuovo metodo
  `mapCrudboosterRoutes()` che fa solo `require base_path('routes/crudbooster.php');`
  — **senza avvolgerlo in un `Route::group()` esterno**, perché ogni
  blocco nel file gestisce già i propri middleware/namespace/prefix.
  Chiamato in `map()` **per ultimo**, dopo `mapApiRoutes()`/`mapWebRoutes()`,
  per preservare esattamente l'ordine di registrazione di prima.
- `CRUDBoosterServiceProvider::boot()`: rimossa la riga
  `require __DIR__.'/routes.php';`.

## Motivazione

Le rotte dell'applicazione passano ora tutte dal punto di ingresso
standard Laravel (`RouteServiceProvider` + cartella `routes/`) invece che
da un `require` nascosto dentro il `boot()` di un package vendorizzato —
più facile da trovare, più coerente con il resto del progetto.

## Test

- Diff automatico (whitespace-insensitive): contenuto identico a parte la
  riga morta rimossa.
- `php -l` su tutti e 3 i file toccati: nessun errore.
- `php artisan route:clear` + `route:list`: **486 rotte, stesso numero
  esatto di prima** in tutte le verifiche precedenti della sessione.
- Verificate esplicitamente 4 rotte definite nel file spostato:
  `getLogin`/`postLogin` (`admin/login`), `apiDocumentation`
  (`api-documentation`), `fileControllerPreview` (`uploads/...`) — tutte
  presenti, puntano ai controller giusti.
- `curl` senza sessione: `/admin` → 302, `/admin/login` → 200 (pagina di
  login, corretto), `/admin/groups` → 302, `/admin/logs` → 302,
  `/api-documentation` → 200. Nessun 500.

## Rischi e note

- L'ordine di registrazione delle rotte è stato preservato deliberatamente
  (blocco crudbooster ancora dopo `routes/web.php`), ma non è stato
  possibile fare un confronto rotta-per-rotta contro uno snapshot "prima"
  (il vecchio file era già stato cancellato quando è stato fatto il
  confronto) — il conteggio totale (486, invariato in tutta la sessione)
  e le rotte con nome verificate sono la prova usata al suo posto.
- Con questo, `packages/crocodicstudio/crudbooster/src/` contiene solo
  `assets/`, `fonts/`, `views/`, `CRUDBoosterServiceProvider.php` — tutto
  il resto (controllers/, helpers/, commands/, configs/, database/,
  localization/, userfiles/, validations/, middlewares/, routes.php) è
  stato spostato o eliminato in questa sessione.

## Rollback

`git revert` del commit — ripristina il vecchio file, il `require` nel
service provider, e rimuove `mapCrudboosterRoutes()` da
`RouteServiceProvider`.
