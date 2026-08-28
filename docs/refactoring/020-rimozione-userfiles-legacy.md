# 020 - Rimossa packages/.../userfiles/ (stub di installazione ridondanti)

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Architettura / CRUDBooster
- **File/aree di codice coinvolte**:
  - `packages/crocodicstudio/crudbooster/src/CRUDBoosterServiceProvider.php`
  - `packages/crocodicstudio/crudbooster/src/userfiles/` (rimossa interamente)

## Contesto

Stesso schema di [019](019-rimozione-localization-legacy.md): verificato
(discussione in chat) che i 3 file in `userfiles/` sono tutti superflui,
per motivi diversi caso per caso:

- **`views/vendor/crudbooster/type_components/readme.txt`**: già
  pubblicato e **identico byte-per-byte** a
  `resources/views/vendor/crudbooster/type_components/readme.txt`
  (tracciato su git).
- **`controllers/CBHook.php`**: già pubblicato come
  `app/Http/Controllers/CBHook.php`, **tracciato su git** (eccezione al
  `.gitignore` di `app/Http/Controllers/*` introdotto in
  [006](006-controller-sistema-app-http-controllers-system.md) — file
  committato prima di quella regola) e **identico byte-per-byte** allo
  stub. Non morto: chiamato realmente in
  `AdminController::postLogin()` (`new \App\Http\Controllers\CBHook; $cb_hook_session->afterLogin();`),
  ma la copia viva è quella in `app/`, non quella nel pacchetto.
- **`controllers/AdminCmsUsersController.php`**: stub pre-refactoring (61
  righe, namespace `App\Http\Controllers` senza `System`), superato dalla
  versione reale in `App\Http\Controllers\System\AdminCmsUsersController`
  (517 righe, spostata in [006](006-controller-sistema-app-http-controllers-system.md)).
  Il `publishes()` condizionale che lo riguardava controllava
  `!file_exists(app_path('Http/Controllers/AdminCmsUsersController.php'))`
  — un path che **non esiste più** da quando quel controller vive in
  `System/`, quindi quel blocco era "armato": un
  `vendor:publish --tag=cb_user_controller` accidentale avrebbe ricreato
  un secondo `AdminCmsUsersController` generico direttamente in
  `app/Http/Controllers/`, in conflitto concettuale con quello vero.

## Situazione prima

`packages/crocodicstudio/crudbooster/src/userfiles/` con i 3 file sopra,
referenziati da 3 blocchi `publishes()` (uno incondizionato per il
readme, due condizionati su `file_exists()` per `CBHook`/`AdminCmsUsersController`)
in `CRUDBoosterServiceProvider::boot()`.

## Situazione dopo

- Cartella `userfiles/` cancellata interamente.
- Rimossi i 3 blocchi `publishes()` corrispondenti da
  `CRUDBoosterServiceProvider::boot()`.
- `app/Http/Controllers/CBHook.php` e
  `resources/views/vendor/crudbooster/type_components/readme.txt` non
  toccati: restano gli unici punti reali, invariati.

## Motivazione

Come [019](019-rimozione-localization-legacy.md): elimina copie
ridondanti che non erano solo inutili a runtime ma il cui unico scopo
dichiarato (seed per installazioni nuove) era già coperto dai file
tracciati in `app/`/`resources/`. In più, chiude una trappola latente:
il publish condizionato di `AdminCmsUsersController` sarebbe potuto
scattare per davvero (il suo guard era ormai sempre vero dopo
[006](006-controller-sistema-app-http-controllers-system.md)),
creando un controller duplicato in conflitto. Ora, se mai invocato,
`vendor:publish` fallisce semplicemente perché il tag non esiste più —
fallimento pulito invece di un duplicato silenzioso.

## Test

- `php -l` su `CRUDBoosterServiceProvider.php`: nessun errore.
- `php artisan route:list`: 486 rotte, invariato.
- `curl` senza sessione su `/admin`, `/admin/logs`: 302, nessun 500.
- Verifica diretta: `class_exists('App\Http\Controllers\CBHook')` → vero
  (la classe reale in `app/`, non toccata, autoload regolarmente).

## Rischi e note

- Nessuno noto: tutti e 3 i file erano o duplicati identici di file già
  tracciati e vivi altrove, o uno stub obsoleto il cui target reale non
  esiste più.

## Rollback

`git revert` del commit — ripristina la cartella e i 3 blocchi
`publishes()`, nessun impatto su `app/Http/Controllers/CBHook.php` o
`resources/views/...`.
