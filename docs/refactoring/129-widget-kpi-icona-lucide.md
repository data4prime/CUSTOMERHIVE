# 129 - Widget Indicatore KPI: icona Lucide invece di Ionicons v7 da CDN

- **Data**: 2026-09-28
- **Stato**: Completato e verificato in browser
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/smallbox.blade.php`
  - `resources/views/crudbooster/statistic_builder/{builder_grid,index_,layout,show_grid}.blade.php`
  - `public/vendor/lucide/icons/` (2 SVG aggiunti: `shopping-cart`, `users`)

## Contesto

Seguito di [128](128-icone-lucide-reskin-font-awesome.md) (reskin Font
Awesome → Lucide): l'unico widget rimasto fuori era l'icona dell'Indicatore
KPI (Small Box), che usa `<ion-icon name="[icon]">` (Ionicons v7, caricato
da CDN esterno `unpkg.com`, mai vendorizzato — vedi backlog di
`README.md`). Richiesta esplicita dell'utente di convertire anche questa.

## Situazione prima

- Il widget renderizzava con `<ion-icon name="[icon]">` (Ionicons v7).
- Il picker icona nel form di configurazione ("Icon By Ionicons") generava
  l'elenco leggendo `vendor/crudbooster/ionic/css/ionicons.min.css`
  (**Ionicons v2**, classi tipo `ion-android-person`) — un formato di nomi
  completamente diverso e incompatibile con quello che il widget vero usa
  a runtime (Ionicons v7, nomi tipo `person-outline`). Bug gia' noto e
  documentato in [118](118-dashboard-griglia-icona-preview-select-ionicons.md):
  **qualunque icona scelta dal picker non veniva mai mostrata davvero nel
  widget**. Verificato sul DB locale: i due Small Box gia' configurati
  avevano `"icon":"ion-android-boat"` e `"icon":"ion-android-person"` —
  nessuno dei due un nome Ionicons v7 valido, icona sempre invisibile.
- L'anteprima icona nella tendina select2 (`formatIcon()`) usava un terzo
  formato ancora (`<i class="ion ion-xxx">`, font Ionicons v2) — quindi
  picker, anteprima e widget reale usavano **tre formati diversi** tra
  loro incompatibili.

## Situazione dopo

Stessa tecnica additiva di [128](128-icone-lucide-reskin-font-awesome.md),
adattata perche' qui non c'e' una classe font da reskinnare via CSS puro
(l'icona e' scelta dinamicamente per componente, il CSS da solo non puo'
interpolare un valore dentro `url()`):

- Markup: `<ion-icon name="[icon]">` → `<span class="lucide-icon"
  data-lucide-icon="[icon]"></span>`. Un piccolo script (gia' presente in
  questo file per altri scopi) legge l'attributo e imposta `mask-image`
  puntando a `public/vendor/lucide/icons/<nome>.svg` (stesso SVG
  vendorizzato in 128).
- Il picker ("Icona") ora legge l'elenco da `glob()` su
  `public/vendor/lucide/icons/*.svg` — stessa logica "l'elenco coincide
  sempre con cio' che e' davvero disponibile" gia' usata prima con
  Ionicons v2, ma puntata alla fonte giusta.
- L'anteprima in tendina (`formatIcon()`) usa la stessa tecnica a maschera
  del widget vero: **anteprima e risultato ora coincidono sempre**,
  a differenza di prima.
- Rimossi gli script Ionicons v7 da CDN (`unpkg.com`) dai 4 file che li
  caricavano (`builder_grid`, `index_`, `layout`, `show_grid`.blade.php):
  nessun altro punto del codice usa piu' `<ion-icon>` (verificato con
  grep), erano rimasti solo per questo widget.
- Corretto un bug trovato testando: `document.querySelector('#' +
  componentID + ' ...')` lanciava una `SyntaxError` perche' `componentID`
  e' un md5 esadecimale che spesso comincia per cifra (es.
  `3fe1c0b6...`) — non un selettore CSS `#id` valido senza escaping.
  Sostituito con `getElementById(componentID)` + `.querySelector(...)`
  sul risultato, che non ha questo limite.
- I 2 widget di test gia' configurati sul DB locale sono stati aggiornati
  a mano (`ion-android-boat`→`shopping-cart`, `ion-android-person`→`user`)
  per poter verificare la resa in browser — dato solo locale, non tocca
  nessun ambiente cliente.

Verificato in browser (dashboard griglia "test2", widget "Numero utenti"):
icona Lucide "user" visibile e correttamente colorata nella pastiglia
dell'Indicatore KPI.

## Motivazione

Stessa di [128](128-icone-lucide-reskin-font-awesome.md) (coerenza
visiva, autosufficienza da CDN esterni) piu' un beneficio aggiuntivo: **il
bug picker/anteprima/widget disallineati (118) si risolve come effetto
collaterale**, avendo un'unica fonte di verita' (i file vendorizzati) per
tutti e tre.

## Test

- Verifica visiva in browser (Docker locale): vista pubblica
  `statistic_builder/show/test2` — icona "Numero utenti" (Lucide `user`)
  renderizzata correttamente nella card.
- Console del browser controllata: nessun errore residuo riconducibile a
  questo intervento (l'unico errore rimasto, `$ is not defined` sullo
  script che nasconde i pulsanti azione in vista pubblica, e' preesistente
  e scollegato — vedi Rischi).
- Non verificato in questo giro: apertura del builder (drag&drop) per
  selezionare una nuova icona dal picker e vederne l'anteprima dal vivo —
  bloccata da un errore 500 preesistente su un URL di test non valido
  (route legacy vs griglia), non e' stato possibile isolarlo in tempo
  utile in questa sessione; il codice della select2/anteprima e' comunque
  lo stesso pattern gia' verificato funzionante nel widget renderizzato.
- Non eseguita la suite di test automatici (non richiesta esplicitamente).

## Rischi e note

- **Bug scollegato trovato durante la verifica, non corretto**: sulla
  vista pubblica della dashboard a griglia, lo script che dovrebbe
  nascondere i pulsanti Modifica/Elimina di ogni widget lancia
  `ReferenceError: $ is not defined` (jQuery non e' caricato in quel
  contesto) — quindi quei pulsanti restano visibili anche in vista
  pubblica. Preesistente, non introdotto ne' peggiorato qui. Da valutare
  come intervento a parte (aggiunto al backlog di `README.md`).
- Icone gia' salvate da eventuali dashboard con nomi Ionicons v2/v7 non
  validi restano senza icona visibile (mask su un file inesistente,
  nessun errore, solo pastiglia colorata vuota) — nessun impatto reale
  visto che questa funzionalita' e' stata introdotta oggi stesso (108-127),
  non ancora in produzione presso alcun cliente.

## Rollback

Ripristinare `<ion-icon name="[icon]"></ion-icon>` nel comando `layout` di
`smallbox.blade.php`, il blocco PHP che leggeva `ionicons.min.css` e i tag
`<script>` di Ionicons v7 nei 4 file di layout (vedi diff).
