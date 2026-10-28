# 310 - Chat AI (finestra flottante): ridimensionabile

- **Data**: 2026-10-28
- **Stato**: Completato (solo compilazione Blade, non provato nel browser)
- **Area**: Frontend / Chat AI
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/chat3.blade.php` (stili degli handle)
  - `resources/views/crudbooster/admin_template_plugins.blade.php` (opzioni di `.resizable()`)

## Contesto

Richiesta: rendere ridimensionabile la finestra flottante della Chat AI.
Seguito dell'intervento 308 (fix dell'interfaccia della stessa finestra).

## Situazione prima

`admin_template_plugins.blade.php` chiamava già `$("#chatWindow").draggable(...).resizable()`,
ma il ridimensionamento non funzionava: jQuery UI aggiunge gli handle
(`.ui-resizable-handle`) e il loro posizionamento/cursore sta in
`jquery-ui.css`, che nelle pagine admin non viene caricato (lo include solo
`statistic_builder`). Gli handle restavano elementi senza stile, non
raggiungibili. In più gli handle di default sono solo `e, s, se`, poco utili
per una finestra ancorata a destra.

## Situazione dopo

- Handle su tutti i lati e angoli (`n, e, s, w, ne, se, sw, nw`), con stili
  limitati a `#chatWindow` in `chat3.blade.php` (colori solo via token
  `var(--ch-*)`); l'angolo in basso a destra ha una piccola impugnatura
  visibile.
- Dimensioni minime 280x260 px; massimo `100vw/100vh - 16px` (CSS).
- Nessun testo visibile nuovo, quindi nessuna chiave di traduzione.

## Motivazione

Si carica solo il CSS necessario per gli handle invece di tutto
`jquery-ui.css`, che cambierebbe l'aspetto di datepicker, tooltip e dialog in
tutte le pagine admin. Nessun `containment` sul resize: con elemento
`position: fixed` jQuery UI lo calcola male; i limiti sono dati dai `max-*` CSS.

## Test

Compilazione delle due viste Blade e `php -l` sul risultato: ok. NON provato
nel browser (trascinamento + ridimensionamento + tema scuro da verificare a
mano). Suite di test non lanciata.

## Rischi e note

- Da verificare a mano: ridimensionare dal lato sinistro (la finestra parte
  con `right: 0`) e il comportamento dopo un trascinamento.
- La dimensione non viene ricordata dopo il ricaricamento della pagina.
- `.chat-body` usa `height: calc(100% - 110px)`: segue il ridimensionamento
  ma presume header e footer di altezza ~110px in totale.

## Rollback

Ripristinare le due viste (`git checkout --` sui due file non basta se
contengono anche le modifiche non committate del 308: tornare a
`.resizable()` senza opzioni e togliere il blocco `#chatWindow
.ui-resizable-*` e `max-height`).
