# 052 - Dashboard Layouts: rimossa la modalità avanzata, preview nel dettaglio

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Miglioramento UI/UX
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/DashboardLayoutController.php`
  - `resources/views/dashboard_layouts/builder.blade.php`
  - `resources/views/dashboard_layouts/detail.blade.php` (nuova)

## Contesto

Seguito diretto di [051](051-dashboard-layouts-builder-visuale.md).
Due richieste esplicite dell'utente dopo aver visto il builder:

1. Togliere del tutto la modalità "Avanzato (HTML)" (il tab con la
   textarea di fallback per layout non riconosciuti dal builder).
2. Nella pagina di dettaglio, mostrare un'anteprima visiva del layout
   invece del rendering generico di default.

## Situazione prima

- `getEdit()` calcolava `legacy_html` e passava alla vista un flag per
  mostrare la textarea HTML grezza quando `parseLayoutToGrid()` non
  riconosceva la struttura esistente.
- `buildCodeLayoutFromRequest()` aveva un ramo `layout_mode === 'advanced'`
  che passava l'HTML grezzo per `aggiungiIdAElemTd()`.
- La vista aveva due tab ("Builder visuale" / "Avanzato (HTML)") con
  toggle JS.
- `getDetail()` **non era overridato**: usava il form-detail generico
  di `CBController` (`crudbooster::default.form_detail`), che per un
  campo `tinymce` mostra l'HTML/testo grezzo del campo, non un preview
  visivo della griglia.

## Situazione dopo

- **Rimossa la modalità avanzata**: niente più tab, niente più
  textarea, niente più `legacy_html`/`layout_mode` nel controller e
  nella vista. `getEdit()` ora, se il layout esistente non è
  riconosciuto da `parseLayoutToGrid()` (ritorna `null`), mostra
  semplicemente il builder con una griglia di default (`[[12]]`) — se
  l'admin salva, l'HTML originale (qualunque esso fosse) viene
  sostituito da quello generato dal builder. Se l'admin non salva,
  l'HTML esistente resta intatto (viene sovrascritto solo al submit).
- **`getDetail($id)` overridato** in `DashboardLayoutController`, con
  una nuova view `dashboard_layouts/detail.blade.php`: mostra il nome
  del layout e un **preview reale** — l'HTML di `code_layout` viene
  renderizzato così com'è (stesso HTML che finirebbe su una vera
  dashboard), dentro un contenitore con CSS che dà a ogni
  `.connectedSortable` un bordo tratteggiato, uno sfondo chiaro e
  un'etichetta "Area: <id>" (via `content: 'Area: ' attr(id)` in CSS,
  nessun JS necessario). Se `code_layout` è vuoto, mostra un messaggio
  invece di un riquadro vuoto.

## Motivazione

- La modalità avanzata era pensata come rete di sicurezza per non
  perdere layout di produzione complessi, ma introduceva complessità
  (due modalità, toggle, textarea HTML) che l'utente ha valutato non
  necessaria per l'obiettivo originale (un'esperienza semplice, senza
  HTML a vista). Rimossa per scelta esplicita, consapevole del
  tradeoff (vedi Rischi e note).
- Un preview visivo nel dettaglio è molto più utile del rendering
  generico del campo (che avrebbe mostrato l'HTML come testo/tinymce
  read-only): permette di vedere a colpo d'occhio la struttura della
  griglia senza dover aprire il builder in modifica.

## Test

- `php -l` sul controller e compilazione Blade (`Blade::compileString`
  + `php -l` sull'output) di entrambe le view: nessun errore.
- `buildCodeLayoutFromRequest()` (via reflection): generazione HTML
  invariata senza `layout_mode` in input (usa sempre `layout_model`).
- **`getDetail()` reso con dati reali**: impostato temporaneamente un
  layout a 2 aree (6+6) sulla riga di test, verificato che l'output
  contenga il contenitore `dl-preview` e le due aree con i rispettivi
  id (`area1`, `area2`); ripristinato lo stato precedente della riga
  subito dopo.
- `curl` senza sessione su `/admin/dashboard_layouts`,
  `/admin/dashboard_layouts/add`, `/admin/dashboard_layouts/edit/1`,
  `/admin/dashboard_layouts/detail/1`: tutti 302, nessun 500.
- Route count invariato (513).

**Non verificato visivamente in browser** — lasciato al giro di test
manuale dell'utente.

## Rischi e note

- **Tradeoff esplicitamente accettato dall'utente**: senza modalità
  avanzata, un layout di produzione con una struttura non riconosciuta
  da `parseLayoutToGrid()` (es. ancora nella vecchia forma
  `<table><td>`, o con markup personalizzato) non è più editabile
  "com'era" — aprendolo in modifica il builder parte da una griglia
  vuota di default, e **salvando** si perde l'HTML originale
  (sostituito dalla nuova griglia). Nessun rischio finché non si
  salva: la sola apertura della pagina di modifica non tocca il dato.
- Il preview nel dettaglio stampa l'HTML memorizzato senza escaping
  (`{!! !!}`) — stesso livello di rischio già presente e discusso in
  precedenza per questo campo (nessuna sanitizzazione, `global_privilege
  = true`), non introdotto né aggravato da questo intervento.
- `aggiungiIdAElemTd()` resta nel codice (usata solo dagli hook
  `hook_before_add()`/`hook_before_edit()`, già segnalati come non più
  invocati in [051](051-dashboard-layouts-builder-visuale.md)) — non
  rimossa, resta dead code innocuo.

## Rollback

`git revert` del commit — ripristina la modalità avanzata (tab +
textarea) e il rendering generico della pagina di dettaglio.
