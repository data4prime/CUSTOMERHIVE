# 137 - Dashboard a griglia: fix spaziatura interna widget diversa tra builder e vista pubblica

- **Data**: 2026-09-29
- **Stato**: Completato (verificato leggendo/calcolando la geometria, non in browser su richiesta esplicita dell'utente)
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/show_grid.blade.php`

## Contesto

Segnalato dall'utente: nel widget Indicatore KPI lo spazio tra la
descrizione e il link ("Vai alla lista") e' molto maggiore nella
dashboard pubblica (`/admin/statistic_builder/show/test2?m=9`) rispetto
al builder (`/admin/statistic_builder/builder/7`), pur essendo lo stesso
widget con la stessa altezza in righe di griglia. Il footer del link usa
`margin-top: auto` (flexbox, vedi `smallbox.blade.php`) per restare
ancorato in fondo alla card: quanto spazio "vuoto" finisce sopra di lui
dipende da quanta area di contenuto e' disponibile nella cella.

## Situazione prima

Le due pagine calcolano l'altezza di un widget con formule diverse per
uno stesso valore "altezza in righe" (H) e `cellHeight`/`grid-auto-rows`
di 40px in entrambe:

- **Builder** (`builder_grid.blade.php`): GridStack.init() con
  `cellHeight: 40, margin: 8`. GridStack fissa l'altezza dell'item a
  `H * 40` px e poi INSETA `.grid-stack-item-content` di 8px su ogni
  lato (margine, non aggiunto tra le righe interne che l'item occupa) -
  altezza contenuto = `40H - 16`. In piu' quella stessa regola ha
  `padding: 4px`, altri 4px per lato -> area di contenuto reale
  `40H - 24`.
- **Vista pubblica** (`show_grid.blade.php`, prima di questo fix): CSS
  Grid puro con `gap: 16px` sul contenitore. Una cella che copre H righe
  include anche i gap TRA quelle righe dentro la propria altezza (a
  differenza del margine di GridStack) - altezza cella =
  `H*40 + (H-1)*16 = 56H - 16`, con `padding: 0` -> area di contenuto
  reale `56H - 16`.

Per H=2 (altezza di default del widget KPI): builder = `40*2-24 = 56px`,
pubblica (prima) = `56*2-16 = 96px` - quasi il doppio, tutto scaricato
come spazio vuoto sopra il footer del link dal `margin-top: auto`.

## Situazione dopo

`show_grid.blade.php` riproduce ora la stessa geometria di GridStack
invece di un modello diverso (gap) che nominalmente sembra equivalente
ma non lo e' per celle che coprono piu' righe:
- il contenitore passa a `gap: 0` (nessuno spazio incluso nella singola
  cella che copre piu' righe);
- `.ch-grid-view-cell` guadagna `margin: 8px` (stesso inset di
  GridStack, applicato PRIMA della cella invece che come gap tra celle)
  e `padding: 4px` (stesso padding di `.grid-stack-item-content` nel
  builder, prima era `padding: 0`).

Lo spazio visivo TRA due widget adiacenti resta 16px come prima (8px di
margine di ciascuno dei due lati che si toccano) - cambia solo l'area di
contenuto disponibile dentro ciascun widget, ora identica al builder per
qualunque altezza H (non solo per il caso segnalato).

## Motivazione

Fix strutturale a livello di griglia (non un ritocco mirato al solo CSS
del footer dell'Indicatore KPI): allineare la formula geometrica delle
due pagine fa si' che QUALUNQUE widget (non solo smallbox) abbia la
stessa area di contenuto disponibile nelle due viste, per qualunque
altezza scelta - una toppa solo sul CSS del footer avrebbe richiesto lo
stesso ragionamento rifatto per ogni futuro widget con layout
sensibile allo spazio verticale disponibile.

## Test

Non verificato in browser (regola esplicita dell'utente, vedi
`feedback_no_autonomous_browser_testing`). Verificato ricavando le due
formule (`40H-24` builder, `56H-16` pubblica-prima) dal codice sorgente
di GridStack 9 (CSS + JS caricati da CDN, letti via fetch per confermare
che l'inset di `margin` e' applicato come `top/right/bottom/left` su
`.grid-stack-item-content` e che l'altezza dell'item e'
`rowSpan * cellHeight` senza margine aggiuntivo) e dalle regole CSS
attuali di `builder_grid.blade.php`/`show_grid.blade.php`; dopo il fix
le due formule coincidono (`40H-24` in entrambe). Da confermare
visivamente dall'utente ricaricando la dashboard pubblica.

## Rischi e note

Effetto collaterale atteso: il bordo esterno della griglia pubblica
guadagna ora un margine di 8px verso i bordi del contenitore (prima i
widget nella prima riga/colonna toccavano il bordo del contenitore senza
alcuno spazio) - coerente con GridStack, che applica lo stesso inset di
8px anche ai widget in prima riga/colonna nel builder, quindi e' un
allineamento voluto, non un effetto indesiderato.

## Rollback

`git diff` di `show_grid.blade.php` per tornare al modello basato su
`gap` (spaziatura interna diversa dal builder).
