# 130 - Dashboard a griglia (vista pubblica): rimosso il margine tra widget e cella

- **Data**: 2026-09-28
- **Stato**: Completato e verificato in browser
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/show_grid.blade.php`

## Contesto

Segnalato dall'utente su `statistic_builder/show/test2`: ogni widget
mostrava un margine visibile tra il proprio bordo e quello della cella
della griglia che lo contiene — piu' evidente sull'Indicatore KPI, che ha
una propria card bianca con bordo/ombra: si vedeva una card dentro
un'altra card, con uno scarto vuoto tra le due.

## Situazione prima

`.ch-grid-view-cell` (il contenitore di ciascun widget nella vista di
sola lettura) aveva `padding: 6px`, pensato per i widget senza sfondo
proprio (es. placeholder "Widget non configurato") che altrimenti
sarebbero rimasti "a galleggiare" senza alcun contorno visibile. Per i
widget con una card propria (Indicatore KPI) questo produceva l'effetto
card-dentro-card con margine visibile.

## Situazione dopo

`padding: 0` sulla cella. Verificato in browser sui 3 tipi di widget
presenti sulla dashboard di test ("test2"): Indicatore KPI (ora una sola
card, bordo singolo, nessun doppio contorno), Chart Area non configurato,
Small Box legacy — nessuno dei tre risulta con contenuto troppo a ridosso
del bordo arrotondato della cella (hanno gia' un padding interno proprio:
`.card-body`/`.kpi-indicator-card` per gli altri).

## Motivazione

Richiesta esplicita dell'utente; cambiamento visibile ma minimo (un solo
valore CSS), nessun rischio funzionale.

## Test

Verifica visiva in browser (Docker locale) sulla dashboard "test2" prima e
dopo la modifica, sui 3 widget presenti.

## Rischi e note

Non verificato su un widget Table/Chart con molte righe/dati: possibile
che in casi con molto contenuto serva un padding interno dedicato in
quello specifico componente (non in questa cella condivisa) — da
verificare se emerge in uso reale.

## Rollback

Rimettere `padding: 6px` su `.ch-grid-view-cell` in `show_grid.blade.php`.
