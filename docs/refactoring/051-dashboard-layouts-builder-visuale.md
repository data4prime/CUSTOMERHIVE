# 051 - Dashboard Layouts: builder visuale al posto di TinyMCE

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Miglioramento UI/UX
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/DashboardLayoutController.php`
  - `resources/views/dashboard_layouts/builder.blade.php` (nuova)

## Contesto

Il campo "Code Layout" del modulo Dashboard Layouts (vedi analisi
precedente, non documentata come intervento numerato) era un editor
TinyMCE in cui l'admin scriveva/disegnava a mano l'HTML della griglia
della dashboard (righe/celle), con un id `areaN` assegnato
automaticamente a ogni cella al salvataggio
(`aggiungiIdAElemTd()`). Esperienza scomoda per un utente non tecnico.

Richiesta esplicita: sostituire l'esperienza di creazione/modifica con
qualcosa di più intuitivo, **senza toccare nient'altro** nel resto
dell'app (che consuma `code_layout` così com'è:
`StatisticBuilderController::getDashboard()`, `index.blade.php`, la
tabella `cms_statistic_components.area_name`), e tenendo conto che
esistono ambienti di produzione con layout già creati con il vecchio
editor.

## Situazione prima

- `cbInit()`: campo `code_layout` di tipo `'tinymce'`, nessuna
  view/controller dedicati per add/edit (form generico CBController).
- Al salvataggio, `hook_before_add()`/`hook_before_edit()` passavano
  l'HTML scritto a mano in `aggiungiIdAElemTd()`, che assegna
  `id="areaN" class="connectedSortable"` a ogni `<td>` senza id
  già esistente (i layout erano quindi tabelle HTML `<table><td>`).

## Situazione dopo

- **`getAdd()`/`getEdit()`/`postAddSave()`/`postEditSave()` overridati**
  in `DashboardLayoutController` (stesso pattern già usato da
  `PrivilegesController` per sostituire il form generico con una view
  su misura). Il resto del CRUD (lista, cancellazione, dettaglio)
  resta quello generico, non toccato.
- **Nuova view** `dashboard_layouts/builder.blade.php`: un builder
  visuale a righe/colonne — l'utente aggiunge righe, per ognuna sceglie
  quante colonne e quanto larga ciascuna (1-12, con preset rapidi tipo
  "2 uguali"/"3 uguali"/"1/3 + 2/3"), con anteprima proporzionale live
  (nessun HTML a vista). Il JS mantiene lo stato in un array e lo
  serializza in un campo nascosto (`layout_model`, JSON) inviato al
  salvataggio.
- **Generazione HTML lato server** (`buildCodeLayoutFromRequest()`):
  dal JSON ricevuto, ricostruisce
  `<div class='statistic-row row'><div id='areaN' class='col-sm-X connectedSortable'></div>...</div>`
  — **stessa identica struttura** già usata dal fallback hardcoded in
  `StatisticBuilderController::getDashboard()` quando un layout non è
  configurato. Nessuna modifica necessaria a chi consuma `code_layout`.
- **Compatibilità con i layout esistenti**: `getEdit()` prova a
  interpretare l'HTML esistente (`parseLayoutToGrid()`) per
  precompilare il builder. Riconosce solo la forma
  div/bootstrap-grid sopra; se il layout è nella vecchia forma a
  tabella (`<table><td>`) o ha una struttura diversa, **non tenta
  nessun reverse-engineering azzardato**: la pagina passa
  automaticamente a una modalità "Avanzato (HTML)" con una textarea
  pre-compilata con l'HTML originale intatto, editabile come prima
  (nessuna perdita, nessuna riconversione forzata). Un tab permette di
  passare comunque al builder visuale (sostituendo l'HTML esistente)
  se l'admin lo desidera.

Il vecchio meccanismo (`aggiungiIdAElemTd()`) resta in uso **solo**
per la modalità avanzata (sostituisce l'editor TinyMCE con una
textarea semplice, ma il trattamento dell'HTML al salvataggio è
identico a prima). `hook_before_add()`/`hook_before_edit()` non sono
più invocati (gli override diretti di `postAddSave()`/`postEditSave()`
non passano più dai metodi generici di `CBController` che li
chiamavano) — lasciati nel codice con un commento, non rimossi.

## Bug corretto en passant

Testando la modalità avanzata è emerso un bug pre-esistente in
`aggiungiIdAElemTd()`: con 2+ celle `<td>` senza id consecutive, il
regex che assegna gli id era scritto con un quantificatore *greedy*
(`(?:(?!id=).)*`), che finiva per consumare dal primo `<td>` fino
all'**ultimo** `</td>` dell'intera stringa in un solo match — collassando
più celle in una sola (le celle successive alla prima venivano perse).
Corretto rendendolo *lazy* (`*?`), così il match si ferma alla prima
`</td>` incontrata, una cella alla volta. Essendo l'unico punto del
codice che ancora invoca questa funzione (la modalità avanzata appena
introdotta), il fix è scoped esclusivamente a questo intervento.

## Motivazione

- Un builder a righe/colonne coincide con l'uso reale del campo (una
  griglia), è più veloce e non richiede conoscere HTML.
- Generare l'HTML lato server con lo stesso formato già usato altrove
  nell'app significa **zero modifiche** a `StatisticBuilderController`,
  alle view dello Statistic Builder, o alla tabella
  `cms_statistic_components` — il contratto di formato non cambia.
- La modalità avanzata (fallback automatico per layout non
  riconosciuti) evita di rompere o dover riconvertire a mano i layout
  già creati in produzione con il vecchio editor.

## Test

- `php -l` su controller e vista compilata (`Blade::compileString` +
  `php -l` sull'output): nessun errore di sintassi.
- `parseLayoutToGrid()` (via reflection, chiamata diretta):
  - HTML vuoto → `[]`.
  - HTML div/bootstrap-grid valido (2 righe, 6+6 e poi 12) →
    ricostruito correttamente come `[[6,6],[12]]`.
  - HTML legacy a tabella (`<table><td>`) → `null` (fallback avanzato).
  - HTML con markup imprevisto in mezzo (es. un `<p>` fuori da una
    riga) → `null`.
- `buildCodeLayoutFromRequest()` (via reflection, richiesta simulata):
  - Modalità builder, 3 righe (6+6 / 4+4+4 / 12) → HTML generato con 6
    id sequenziali (`area1`..`area6`), struttura corretta.
  - Modalità avanzata, 3 celle `<td>` senza id → tutte e 3 preservate
    con id sequenziali dopo il fix del regex (prima del fix: solo 1
    cella sopravviveva, le altre 2 sparivano).
- **Test end-to-end reale sul DB di sviluppo**: costruito l'HTML in
  modalità builder, inserito in `dashboard_layouts`, riletto e
  ri-parsato con `parseLayoutToGrid()` — round-trip esatto (stesso
  array di righe/colonne), poi riga di test cancellata.
- **Validazione**: submit senza `layoutname` → nessun insert nel DB
  (verificato il conteggio righe invariato), redirect con errore
  popolato in sessione (`errors->first('layoutname')`), stesso
  meccanismo `$errors` già usato da `privileges.blade.php`/
  `default.form.blade.php`.
- `curl` senza sessione su `/admin/dashboard_layouts`,
  `/admin/dashboard_layouts/add`, `/admin/dashboard_layouts/edit/1`:
  tutti 302 (redirect a login), nessun 500.

**Non verificato visivamente in browser** (il rendering completo della
pagina, incluso il builder JS, richiede una sessione admin reale —
stesso limite di testing isolato già incontrato più volte in questa
sessione, es. [043](043-privileges-form-come-standard.md#test)) —
lasciato al giro di test manuale dell'utente.

**Bug trovato dall'utente durante il primo test manuale**: il salvataggio
(`/admin/dashboard_layouts/edit-save/1`) falliva con
`Class "App\Http\Controllers\System\Schema" not found`. Causa: la
chiamata `Schema::hasColumn(...)` era scritta senza backslash iniziale
e senza un `use` esplicito — a differenza di `CBController.php`, questo
controller non importava `Illuminate\Support\Facades\Schema`, quindi
PHP risolveva `Schema` come classe relativa al namespace del
controller (`App\Http\Controllers\System\Schema`, inesistente) invece
che come facade globale. Corretto aggiungendo
`use Illuminate\Support\Facades\Schema;` (e già che c'ero,
`use Illuminate\Support\Facades\Validator;` al posto del
`\Validator::make(...)` con backslash, stesso problema evitato in modo
diverso). Nota: l'app ha anche un facade custom `App\Facades\Schema`
(alias globale `Schema` in `config/app.php`) con un `Blueprint`
custom, ma `CBController.php` stesso importa direttamente il facade
Laravel standard per questo stesso tipo di controllo — impostazione
identica qui per coerenza.

Ritestato dopo il fix: `Schema::hasColumn()` risolve correttamente,
`postEditSave()` end-to-end (chiamata diretta, non HTTP) scrive
`code_layout` nel formato atteso sul DB reale; ripristinato lo stato
originale della riga di test. `curl` su
`GET/POST /admin/dashboard_layouts/edit-save/1`: 405/302, nessun 500.

## Rischi e note

- `parseLayoutToGrid()` è deliberatamente conservativo: riconosce solo
  la forma esatta div/bootstrap-grid. Qualunque layout di produzione
  con una struttura anche solo leggermente diversa (classi extra,
  wrapper aggiuntivi) andrà in modalità avanzata invece di essere
  precompilato nel builder — comportamento sicuro (nessuna perdita di
  dati) ma da tenere a mente: la prima apertura in edit di un vecchio
  layout complesso mostrerà la textarea, non il builder.
- La modalità avanzata resta un editor HTML libero (nessuna
  sanitizzazione, invariato rispetto a prima) — i rischi di sicurezza
  già segnalati in precedenza su `code_layout` (nessun escaping in
  output, `global_privilege = true`) restano fuori scope per questo
  intervento, esplicitamente rimandati a un secondo momento.
- Non è stato aggiunto nessun guard su `hook_before_delete()` (resta
  vuoto): cancellare un layout ancora assegnato a una dashboard
  continua a ricadere sul fallback hardcoded silenzioso già esistente
  in `getDashboard()` — comportamento pre-esistente, non toccato.

## Rollback

`git revert` del commit — ripristina il form generico con TinyMCE e il
comportamento originale di `aggiungiIdAElemTd()` (incluso il bug del
regex greedy). Nessun impatto sui dati: la struttura di `code_layout`
salvata dal builder è compatibile con tutto ciò che la consumava prima.
