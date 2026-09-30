# 142 - Dashboard a griglia: rimosso il padding interno tra widget e contenitore (builder e vista pubblica)

- **Data**: 2026-09-29
- **Stato**: Completato (non verificato in browser, su richiesta esplicita dell'utente)
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/builder_grid.blade.php`
  - `resources/views/crudbooster/statistic_builder/show_grid.blade.php`

## Contesto

Richiesta diretta dell'utente: eliminare il margine visibile tra un
widget (es. `.kpi-indicator-card`, che ha gia' il proprio bordo/sfondo/
ombra) e la card generica che lo contiene, sia nel builder che nella
dashboard pubblica. Stesso problema gia' risolto una volta per la sola
vista pubblica in 130 ("rimosso il padding della cella che creava un
margine/doppio bordo visibile"); il padding era pero' rientrato oggi
stesso in 137, che lo aveva impostato a `4px` in `show_grid.blade.php`
per farlo combaciare con quello (preesistente, mai toccato finora) di
`builder_grid.blade.php`, allineando cosi' l'area di contenuto
disponibile tra le due pagine. L'utente segnala ora lo stesso margine
visibile in ENTRAMBE le pagine, non solo nella pubblica.

## Situazione prima

- `builder_grid.blade.php`: `.grid-stack-item-content { padding: 4px }`
  (preesistente, non toccato da 137).
- `show_grid.blade.php`: `.ch-grid-view-cell { padding: 4px }`
  (impostato da 137 per allinearsi al builder).

## Situazione dopo

`padding: 0` in entrambe le regole. Il `margin: 8px` di
`.ch-grid-view-cell` (che riproduce l'inset di GridStack, vedi 137)
resta invariato: e' lo spazio TRA un widget e l'altro, non tra un widget
e il proprio contenitore - non e' quello segnalato dall'utente. La
geometria allineata da 137 (altezza contenuto = `righe*40 - 2*margin`)
resta identica in entrambe le pagine: si toglie solo il termine
`- 2*padding`, in entrambe contemporaneamente, quindi le due formule
restano uguali fra loro (prima `40H-24`, ora `40H-16`).

## Motivazione

Richiesta diretta dell'utente. Il padding creava un secondo bordo/ombra
visibile a ridosso di quello proprio del widget (es. l'Indicatore KPI,
che ha gia' bordo/sfondo/ombra via `.kpi-indicator-card`) - toglierlo fa
si' che il bordo del widget coincida con quello della cella che lo
contiene invece di lasciarci un piccolo margine bianco in mezzo.

## Test

Non verificato in browser (regola esplicita dell'utente, vedi
`feedback_no_autonomous_browser_testing`). Verificato solo `php -l` su
entrambi i file. Da confermare visivamente dall'utente: aprire sia il
builder sia la dashboard pubblica di una dashboard a griglia e
verificare che il bordo di ogni widget non abbia piu' un margine
visibile verso il bordo della propria cella.

## Rischi e note

Effetto collaterale atteso e voluto: il bordo/sfondo/ombra della card
generica (`.grid-stack-item-content`/`.ch-grid-view-cell`) e quello del
widget stesso (dove il widget ne ha uno proprio, es. l'Indicatore KPI)
ora si toccano direttamente invece di avere 4px di spazio bianco tra
loro - dato che condividono raggio e colore del bordo molto simili, il
risultato visivo atteso e' un bordo unico leggermente piu' spesso, non
due bordi visibilmente separati.

## Rollback

`git diff` di `builder_grid.blade.php` e `show_grid.blade.php` per
tornare al padding di 4px in entrambe le pagine.
