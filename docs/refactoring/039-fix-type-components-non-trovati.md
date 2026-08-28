# 039 - Fix: "non è stato trovato il tipo di componente" su moduli con view dedicate

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Bug fix (regressione da [037](037-views-spostate-resources.md))
- **File/aree di codice coinvolte**:
  - `resources/views/{chat_ai,qlik_items,tenants,users}/form_body.blade.php`
  - `resources/views/{chat_ai,qlik_items,tenants,users}/form_detail.blade.php`
  - `app/Http/Controllers/System/ModulsController.php`

## Contesto

Segnalato dall'utente: su `/admin/tenants/edit/5`, i campi di tipo
`text`/`upload` mostravano "non è stato trovato il tipo di componente"
invece del componente vero.

Causa: in [037](037-views-spostate-resources.md) erano stati corretti i
path assoluti hardcoded (`base_path('packages/crocodicstudio/crudbooster/src/views/default/type_components/...')`)
solo nei 9 file **dentro** `packages/crocodicstudio/crudbooster/src/views/`.
Ma lo stesso identico pattern era stato copia-incollato anche in **8 view
dedicate** di 4 moduli (`chat_ai`, `qlik_items`, `tenants`, `users`), che
vivono in `resources/views/` — **fuori dalla cartella scansionata in
quell'intervento**, quindi mai trovate. Trovato inoltre lo stesso
problema, in forma leggermente diversa, in `ModulsController.php`
(`glob()` per elencare i tipi disponibili nel generatore di moduli, e
`file_get_contents()` per il loro `info.json`) — path anch'esso ormai
inesistente.

A differenza dei file nel pacchetto, queste 8 view **hanno un `@else`
esplicito** che mostra il messaggio d'errore invece di sparire in
silenzio — motivo per cui il problema è stato visibile subito qui,
mentre nei file già corretti in 037 sarebbe stato silenzioso (nessun
`@else`, verificato con un test diretto sul meccanismo in quell'occasione).

## Situazione prima

16 occorrenze in 8 file (`base_path('/?packages/crocodicstudio/crudbooster/src/views/default/type_components/'.$type.'/{asset,component,component_detail}.blade.php')`)
+ 2 in `ModulsController.php` (`glob()`/`file_get_contents()` sullo
stesso path), tutte puntanti a una cartella non più esistente.

## Situazione dopo

- Le 16 occorrenze nelle 8 view sostituite con
  `resource_path('views/crudbooster/default/type_components/...')`
  (stesso stile già usato in 037).
- `ModulsController.php`: `glob()` e `file_get_contents()` ripuntati allo
  stesso nuovo path.
- Scansione dell'intero repo (non solo `resources/views/`) per
  `crocodicstudio/crudbooster`: restano solo 2 commenti storici
  (innocui, in `RouteServiceProvider.php` e
  `legacy_crudbooster_aliases.php`), nessun altro riferimento funzionale.

## Motivazione

Corregge una regressione reale introdotta in 037, causata da una ricerca
scope-limitata (solo dentro il pacchetto) invece che sull'intero repo.

## Test

- `php -l` su tutti i 9 file toccati: nessun errore.
- Compilazione isolata (`BladeCompiler::compileString()` + `php -l`) delle
  8 view: tutte OK.
- **Verifica diretta** (non solo sintattica): `file_exists()` per
  `text`/`upload` (asset e component) → tutti trovati; `glob()` sul nuovo
  path → 34 tipi elencati correttamente (prima: 0, cartella inesistente);
  `file_get_contents()` su `info.json` di un tipo → contenuto letto
  correttamente (prima: fallirebbe).
- `php artisan view:clear` (svuota la cache Blade compilata, che
  conteneva ancora il vecchio path) + `route:list`: 486 rotte, invariato.
- `curl` senza sessione su `/admin`, `/admin/tenants`: 302, nessun 500.

## Rischi e note

- **Lezione per interventi futuri simili**: quando si cerca "chi
  referenzia un path che sto per spostare", la ricerca va fatta
  sull'intero repo, non solo nella cartella che si sta spostando — moduli
  applicativi possono avere copie ridondanti dello stesso pattern altrove
  (qui, 4 moduli con `form_body`/`form_detail` dedicati invece di usare
  quelli condivisi).
- Non ancora verificato se esistono **altre** view dedicate (oltre a
  questi 4 moduli) con lo stesso pattern ma un `type` diverso da
  `text`/`upload` che l'utente non ha ancora provato — la ricerca
  esaustiva sopra dovrebbe averle già tutte incluse, ma vale la pena
  tenerlo presente durante il resto del giro di test manuale.

## Rollback

`git revert` del commit — ripristina i vecchi path (che tornerebbero a
rompersi, essendo comunque già rotti prima di questo fix).
