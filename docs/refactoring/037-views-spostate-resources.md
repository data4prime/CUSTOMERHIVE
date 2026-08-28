# 037 - views/ spostata in resources/views/crudbooster/ — packages/.../src/ ora vuota

- **Data**: 2026-08-28
- **Stato**: Completato (verifica manuale in browser lasciata all'utente)
- **Area**: Architettura / CRUDBooster
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/` (nuovo, 221 file: 223 - 2 morti)
  - `packages/crocodicstudio/crudbooster/src/views/` (rimossa interamente)
  - `app/Providers/CRUDBoosterServiceProvider.php`
  - 9 file dentro `resources/views/crudbooster/` con path hardcoded aggiornati

## Contesto

Ultimo pezzo di `packages/crocodicstudio/crudbooster/src/`. A differenza
degli spostamenti precedenti (dove tutti i riferimenti incrociati usavano
il namespace `crudbooster::`, quindi indifferenti alla posizione fisica),
qui è emerso un rischio reale: **9 file usano un path assoluto
hardcoded** (`base_path('packages/crocodicstudio/crudbooster/src/views/default/type_components/'.$type.'/...')`)
dentro un `file_exists()` per decidere se includere l'asset/component di
un tipo di campo form (checkbox, select, upload, ecc.). Il meccanismo è a
due livelli:
1. Esiste il default del pacchetto per questo `$type`? Se sì, include
   `crudbooster::default.type_components.{type}.asset` (via namespace).
2. Altrimenti esiste un override utente in
   `resource_path('views/vendor/crudbooster/type_components/{type}/asset.blade.php')`
   (meccanismo di estensione separato, indipendente, oggi inutilizzato —
   solo un `readme.txt` lì dentro)? Se sì, include quello.
3. **Nessun `@else` finale**: se nessuno dei due esiste, non viene
   incluso nulla — silenziosamente, nessun errore.

Spostare `views/` senza aggiornare quei 9 file avrebbe fatto fallire
sempre il controllo (1), facendo sparire silenziosamente gli
asset/component JS-CSS di **ogni tipo di campo, su ogni form
dell'applicazione** (datepicker, select2, upload, ecc.) — nessun 500,
nessun errore visibile, solo funzionalità mancante.

Durante l'analisi trovati anche 2 file morti con lo stesso path
hardcoded, mai referenziati da nessuna parte:
`mass_edit/form_body.blade_.php` (doppia estensione — nome non valido per
Blade) e `menus/form_body_old.blade.php`.

## Situazione prima

`packages/crocodicstudio/crudbooster/src/views/` (223 file), namespace
`crudbooster::` registrato con
`loadViewsFrom(base_path('packages/crocodicstudio/crudbooster/src/views'), 'crudbooster')`.

## Situazione dopo

- Copiata l'intera cartella in `resources/views/crudbooster/` (a livello
  filesystem, non riscritta a mano — 223 file, troppi per una
  ritrascrizione sicura).
- Eliminati i 2 file morti invece di copiarli.
- Nei 9 file vivi rimasti, sostituite le **16 occorrenze** (18 totali
  meno le 2 nei file morti) di
  `base_path('/?packages/crocodicstudio/crudbooster/src/views/default/type_components/...')`
  con `resource_path('views/crudbooster/default/type_components/...')` —
  stesso stile già usato dal controllo "override utente" adiacente nello
  stesso file.
- `CRUDBoosterServiceProvider::boot()`:
  `loadViewsFrom(base_path('packages/.../views'), 'crudbooster')` →
  `loadViewsFrom(resource_path('views/crudbooster'), 'crudbooster')`.
- `packages/crocodicstudio/crudbooster/src/views/` cancellata. **`packages/crocodicstudio/crudbooster/src/` è ora una cartella vuota**
  (nessun file rimasto).

## Motivazione

Ultimo passo dell'uscita da `packages/`. La scelta di `resources/views/crudbooster/`
(non `resources/views/vendor/crudbooster/`) è stata decisa esplicitamente
dall'utente — nessuna necessità del prefisso `vendor/`, dato che quelle
view non sono più "codice di un pacchetto esterno" ma parte integrante
del progetto.

## Test

- Diff ricorsivo (`diff -rq`) tra la vecchia e la nuova cartella:
  **esattamente e solo i 9 file attesi risultano diversi**, i 2 file
  morti risultano presenti solo nella vecchia posizione, tutti gli altri
  212 file identici byte-per-byte.
- Compilazione isolata (`BladeCompiler::compileString()` + `php -l`) dei
  9 file modificati: tutti OK.
- **Verifica diretta del meccanismo a rischio**:
  `file_exists(resource_path('views/crudbooster/default/type_components/select/asset.blade.php'))`
  → vero; `view()->exists('crudbooster::default.type_components.select.asset')`
  → vero — conferma che la catena `file_exists()` → `@include()` funziona
  davvero, non solo che il file esiste.
- `view()->exists()` su 7 view chiave (`header`, `sidebar`,
  `admin_template`, `default.index`, `default.form`, `license_modal`,
  `export`): tutte trovate.
- `php artisan view:clear` + `route:list`: 486 rotte, invariato.
- `curl` senza sessione: `/admin` → 302, `/admin/login` → 200,
  `/admin/groups` → 302, nessun 500.

**Non verificato** (lasciato all'utente, come da richiesta esplicita di
fare un test completo prima del commit): un giro reale in browser che
crei/modifichi un modulo con diversi tipi di campo (select, upload,
datepicker, ecc.) per confermare visivamente che gli asset JS/CSS si
carichino — le verifiche sopra confermano che il meccanismo *risolve*
correttamente, non sostituiscono un controllo visivo end-to-end.

## Rischi e note

- **`packages/crocodicstudio/crudbooster/src/` è ora completamente
  vuota**. Non ho rimosso `packages/crocodicstudio/` né il mapping PSR-4
  `crocodicstudio\crudbooster\` in `composer.json` — sono un passo
  successivo distinto (comporterebbe anche verificare che nessun altro
  file nel repo referenzi ancora quel namespace, cosa non ancora fatta in
  questo intervento), da affrontare solo su richiesta esplicita.
- Il meccanismo di override utente (`resources/views/vendor/crudbooster/type_components/`)
  non è stato toccato, resta dov'era, indipendente da questo spostamento.

## Rollback

`git revert` del commit — ripristina la cartella nel pacchetto (inclusi
i 2 file morti) e il vecchio `loadViewsFrom()`.
