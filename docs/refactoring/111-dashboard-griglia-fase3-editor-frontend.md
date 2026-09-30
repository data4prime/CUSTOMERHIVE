# 111 - Dashboard a griglia libera, Fase 3: editor a griglia (frontend)

> **Aggiornamento 2026-09-28**: l'aggiunta widget descritta qui sotto
> come trascinamento dalla libreria (`GridStack.setupDragIn`) è stata
> sostituita da un click — il drag esterno è risultato inaffidabile in
> prova, segnalato dall'utente. Vedi
> [115](115-dashboard-griglia-fix-drop-area-vuota.md) e
> [116](116-dashboard-griglia-fix-aggiunta-widget-click.md).

- **Data**: 2026-09-28
- **Stato**: Completato
- **Area**: Statistic Builder / Dashboard / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/builder_grid.blade.php` (nuovo)
  - `resources/views/crudbooster/statistic_builder/components/_query_mode_toggle.blade.php` (nuovo)
  - `resources/views/crudbooster/statistic_builder/components/_query_builder_fields.blade.php` (nuovo)
  - `resources/views/crudbooster/statistic_builder/components/smallbox.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/table.blade.php`

## Contesto

Quarta fase del piano [`docs/piano-dashboard-griglia-libera.md`](../piano-dashboard-griglia-libera.md)
— la parte visibile del mockup approvato: griglia libera drag & resize,
palette widget, sidebar di configurazione **sempre inline, mai in una
modale** (decisione esplicita, a differenza del builder legacy che usa
`#modal-statistic`).

## Situazione prima

Il builder legacy (`index.blade.php`, incluso da `builder.blade.php`) usa
jQuery UI 1.11.4 sortable ad aree fisse, un widget per area, editing in
una modale Bootstrap. Il campo SQL dei widget "interrogano dati"
(smallbox/table) era l'unico modo di configurarne i dati.

## Situazione dopo

- `builder_grid.blade.php`: pagina standalone (NON estende il layout del
  builder legacy, che resta invariato) con gridstack.js 9 da CDN (stesso
  pattern di jQuery UI/Morris oggi — nessuna build step introdotta),
  palette a sinistra (drag esterno via `GridStack.setupDragIn`), griglia
  centrale, sidebar destra sempre popolata via `getEditComponent()` +
  injection diretta (mai modale). Autosave immediato su `grid.on('change')`
  (drag/resize) e sul drop dalla palette (`add-component` con
  `pos_x/pos_y/width/height`).
- `_query_mode_toggle.blade.php` + `_query_builder_fields.blade.php`:
  toggle "SQL libera / Query guidata" condiviso, incluso da smallbox/
  table (e chartline_v2/chartbar_v2, vedi 112) — la modalità guidata
  usa `DashboardDatasetRegistry` (108) con anteprima live
  (`dataset-preview`) prima di salvare.
- `smallbox.blade.php`/`table.blade.php`: campo SQL avvolto in un pannello
  toggle-abile, più il pannello "Query guidata"; il ramo `showFunction`
  di entrambi ora distingue `is_array($value)` (righe già risolte dal
  registry, modalità builder) da stringa (SQL letterale, comportamento
  identico a prima) — usati SIA dal builder legacy sia da quello a
  griglia, quindi il default `mode = 'sql'` assente mantiene il
  comportamento legacy byte-per-byte.

## Motivazione

Vedi piano: sidebar inline invece di modale, autosave invece di bozza+
salva, gridstack via CDN invece di una build step nuova.

## Test (manuale in browser, Chrome via automazione)

Verificato end-to-end su una dashboard di prova creata ad hoc (poi
eliminata) e su una dashboard clonata da dati reali (10 widget, poi
eliminata):
- apertura editor, selezione widget (click → sidebar), toggle SQL/
  guidata, popolamento dataset/metrica/raggruppamento, anteprima live,
  salvataggio e rilettura del valore calcolato nel widget, eliminazione
  widget — tutti confermati funzionanti tramite chiamate di rete reali e
  verifica diretta a DB;
- apertura di una dashboard **legacy** (`layout_mode='legacy_areas'`)
  dopo queste modifiche: renderizza esattamente come prima (nessuna
  regressione).

**Tre bug reali trovati e corretti durante questa verifica** (non
sarebbero emersi da un solo `php -l`):
1. `_query_builder_fields.blade.php`: `reset((array) (...))` — `reset()`
   richiede una variabile per riferimento, non un'espressione con cast;
   estratta una variabile reale prima di chiamarlo.
2. Stesso file: `{{ json_encode($componentID) }}` dentro un `<script>`
   — Blade **escapa** l'output di `{{ }}` (`"` → `&quot;`), che dentro
   uno `<script>` produce JS invalido; corretto in `{!! !!}` (mai
   `{{ }}` per iniettare JSON in uno script inline — stessa regola già
   in CLAUDE.md per le stringhe tradotte).
3. `builder_grid.blade.php`: usare `.grid-stack-item-content` sia come
   handle di drag di gridstack sia come target di un listener `click`
   non è affidabile (gridstack intercetta mousedown/mouseup); sostituito
   con una disambiguazione manuale mousedown+mouseup (soglia di
   movimento/tempo) per riconoscere un click da un drag.

**Non completamente verificato**: il click reale (via automazione
browser) sul bottone "Salva" della sidebar non ha sempre innescato
l'evento `submit` in questa sessione di test, mentre lo stesso form/
handler funziona correttamente quando l'evento viene innescato
programmaticamente (`.trigger('submit')`) — sembra una particolarità
dell'automazione browser usata per il test (coordinate/scaling del
viewport), non riprodotta cliccando altri bottoni della stessa sidebar,
ma non è stato possibile confermarlo con un click umano reale in questa
sessione. **Da riverificare con un click manuale in dev prima di
considerare la Fase 3 pienamente chiusa.**

## Rischi e note

- Palette limitata a 4 tipi (KPI/Tabella/Grafico a linee/Grafico a
  barre): "Distribuzione"/"SLA" del mockup non sono stati implementati
  in questa fase (nessun widget grafico a ciambella/gauge esiste nel
  sistema, legacy o nuovo) — scope ridotto consapevolmente.
- gridstack.js caricato da CDN: nessuna versione vendorizzata locale,
  stesso rischio di disponibilità già presente per jQuery UI/Morris/
  ApexCharts.

## Rollback

Rimuovere i quattro file nuovi; ripristinare smallbox.blade.php/
table.blade.php alla versione precedente (il branch `is_array($value)`
aggiunto è additivo e innocuo da solo, ma va tolto per pulizia).
