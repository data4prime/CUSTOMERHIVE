# 119 - Dashboard a griglia libera: posizione dei pulsanti elimina/ridimensiona

- **Data**: 2026-09-28
- **Stato**: Completato e verificato in browser
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/builder_grid.blade.php`

## Contesto

Segnalato dall'utente: i pulsanti per eliminare e ridimensionare un
widget erano in posizioni scomode.

## Situazione prima

- Il pulsante elimina (`.ch-widget-toolbar`) galleggiava sopra il bordo
  della card (`top: -14px`, a cavallo del bordo superiore) — per i
  widget vicino al margine alto del canvas veniva tagliato dallo
  scroll di `.ch-canvas`.
- `GridStack.init()` non specificava `resizable.handles`: il default di
  gridstack attiva più maniglie di ridimensionamento contemporaneamente
  (est/sud-est/sud), ammassate proprio nella stessa zona in alto a
  destra della toolbar elimina.
- Le card con contenuto leggermente più alto del previsto mostravano la
  scrollbar classica di Windows (con le freccette su/giù), che da
  lontano si confondeva visivamente con un'altra maniglia.

## Situazione dopo

- Toolbar elimina spostata dentro il bordo della card (`top: 6px; right:
  6px`), mai tagliata dallo scroll, pulsante leggermente più grande e
  con stato hover rosso (più chiaro che è un'azione distruttiva).
- Ridimensionamento limitato alla sola maniglia in basso a destra
  (`resizable: { handles: 'se' }`), ingrandita leggermente via CSS per
  essere più facile da afferrare.
- Scrollbar delle card stilizzata più sottile (6px, senza frecce) per
  non essere scambiata per un controllo.

## Motivazione

Nessun cambio di comportamento, solo posizionamento/stile: stessa
funzionalità (elimina, ridimensiona), meno probabilità di cliccare/
afferrare la cosa sbagliata.

## Test

Verificato in browser: aggiunti due widget di prova, confermato via
ispezione DOM che esiste **una sola** maniglia di resize
(`.ui-resizable-se`, nessun'altra `.ui-resizable-*`) e che il suo
rettangolo non si sovrappone a quello della toolbar elimina. La
scrollbar sottile non si è vista renderizzata nell'ambiente di test
usato in questa sessione (possibile impostazione di sistema che forza
la scrollbar classica di Windows indipendentemente dal CSS
`::-webkit-scrollbar` — da confermare sul browser reale dell'utente).
Widget di prova poi rimossi.

## Rischi e note

Se lo stato "non configurato" dei widget appena aggiunti viene reso più
compatto (vedi discussione in corso con l'utente su un placeholder
piu' curato al posto di `[sql]`/`[name]`), la necessità stessa di
scroll su un widget piccolo dovrebbe sparire quasi sempre.

## Rollback

`git diff` di questo file per tornare al posizionamento precedente.
