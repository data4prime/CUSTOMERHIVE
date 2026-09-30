# 115 - Dashboard a griglia libera: fix, area di drop nulla su griglia vuota

- **Data**: 2026-09-28
- **Stato**: Completato (parzialmente verificato, vedi "Rischi e note")
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/builder_grid.blade.php`

## Contesto

Segnalato dall'utente: trascinando un widget dalla libreria a sinistra
nella griglia "non succede nulla". Bug reale nel builder a griglia
([111](111-dashboard-griglia-fase3-editor-frontend.md)).

## Situazione prima

`.grid-stack` (l'elemento `#ch-grid-canvas`, il vero bersaglio
"droppable" di gridstack) non aveva un'altezza minima esplicita: senza
widget al suo interno collassava a un'altezza di 0px. La zona grigia
visibile sotto/intorno era solo il padding del contenitore
`.ch-canvas`, non l'elemento `.grid-stack` vero e proprio — un drop in
quella zona non raggiungeva mai il bersaglio giusto.

## Situazione dopo

`.grid-stack` ha ora `min-height: calc(100vh - 140px)`: la superficie di
drop copre sempre l'area visibile del canvas, con o senza widget già
presenti.

## Motivazione

Trovato ispezionando `document.elementFromPoint()` nel punto di drop
durante il test: restituiva `.ch-canvas` (il contenitore) invece di
`#ch-grid-canvas` (la griglia vera), confermando che il drop cadeva
fuori bersaglio.

## Test

Verificato che dopo la correzione `elementFromPoint()` nello stesso
punto risolve correttamente `#ch-grid-canvas`. **Non verificato
end-to-end con un trascinamento reale**: né l'automazione browser
disponibile in questa sessione né una sequenza di eventi mouse
sintetici costruita a mano sono riusciti a far scattare l'evento
`mouseenter` che gridstack usa per riconoscere "sto trascinando sopra
la griglia" (`dropped` non si attiva in nessuno dei due casi) —
limite noto e diffuso delle automazioni browser con librerie di
drag&drop basate su eventi mouse nativi (non HTML5 drag-and-drop), non
solo di questo progetto. Il meccanismo sottostante di gridstack risulta
comunque correttamente collegato (`ddElement`/`ddDraggable` presenti,
soglia di movimento superata, helper con `pointer-events:none` corretto).

## Rischi e note

- **Da riconfermare con un trascinamento reale (mouse fisico) prima di
  considerare il problema dell'utente pienamente risolto** — il fix
  applicato è concreto e necessario, ma potrebbe non essere l'unica
  causa se il problema persiste.
- Se persiste anche con un mouse reale, i prossimi indizi utili sono:
  appare un'anteprima ("ghost") del widget che segue il cursore durante
  il trascinamento? La cella diventa evidenziata quando si passa sopra
  la griglia? Questo aiuterebbe a capire se il problema è ancora
  l'area di drop o qualcos'altro nella catena gridstack.

## Rollback

`git diff` di questo file per rimuovere il `min-height` (torna al bug
originale).
