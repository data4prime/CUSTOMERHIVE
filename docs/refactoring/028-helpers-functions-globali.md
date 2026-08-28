# 028 - Helpers (6/N): funzioni globali spostate in app/Helpers/functions.php

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Architettura / CRUDBooster
- **File/aree di codice coinvolte**:
  - `app/Helpers/functions.php` (nuovo)
  - `packages/crocodicstudio/crudbooster/src/helpers/Helper.php` (eliminato)
  - `packages/crocodicstudio/crudbooster/src/CRUDBoosterServiceProvider.php`

## Contesto

Continua [023](023-helpers-nnhelper-moduleHelperhelper-myhelper.md)-[027](027-helpers-userhelper.md).
`Helper.php` non è una classe: dichiara funzioni globali senza namespace
(`g()`, `now()`, `rrmdir()`, `assetThumbnail()`, `assetResize()`,
`extract_unit()`, `min_var_export()`, `add_log_ch()`), per questo era
incluso con un `require` diretto in `CRUDBoosterServiceProvider::register()`
invece di essere autoloadato via PSR-4 (che gestisce solo classi/interfacce/
trait, non funzioni). Rinominato `functions.php` nella nuova posizione per
chiarezza (nella cartella `app/Helpers/` altrimenti popolata solo da
classi, il nome "Helper.php" avrebbe confuso).

## Situazione prima

`packages/crocodicstudio/crudbooster/src/helpers/Helper.php`, incluso con
`require __DIR__.'/helpers/Helper.php';`. Nessun altro punto nel repo
referenzia questo path (verificato).

## Situazione dopo

- Contenuto spostato invariato in `app/Helpers/functions.php`.
- `CRUDBoosterServiceProvider::register()`:
  `require __DIR__.'/helpers/Helper.php';` →
  `require app_path('Helpers/functions.php');`.

## Motivazione

Ultimo pezzo di `helpers/` spostabile senza toccare `CRUDBooster.php`
(prossimo e ultimo passo della serie).

## Test

- `php -l` su entrambi i file toccati: nessun errore.
- Bootstrap completo dell'app: `function_exists()` vero per `g`, `now`,
  `rrmdir`, `add_log_ch`.
- `php artisan route:list`: 486 rotte, invariato.
- `curl` senza sessione su `/admin`, `/admin/logs`: 302, nessun 500.

## Rischi e note

- Resta solo `CRUDBooster.php` (80 KB, 101 metodi) — ultimo pezzo della
  serie [helpers](README.md#roadmap-uscita-da-crudbooster-packages).

## Rollback

`git revert` del commit — ripristina `Helper.php` nel pacchetto e la
riga `require` originale.
