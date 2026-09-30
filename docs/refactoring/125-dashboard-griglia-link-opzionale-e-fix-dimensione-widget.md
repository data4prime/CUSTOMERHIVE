# 125 - Dashboard a griglia libera: link opzionale nell'Indicatore KPI e fix dimensione widget

- **Data**: 2026-09-28
- **Stato**: Completato e verificato in browser
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/smallbox.blade.php`
  - `resources/views/crudbooster/statistic_builder/builder_grid.blade.php`
  - `resources/views/crudbooster/statistic_builder/show_grid.blade.php`

## Contesto

Due richieste dell'utente dopo aver usato ancora il builder su dati
reali (`test2`, widget "Numero utenti").

## 1. Link dell'Indicatore KPI reso opzionale, con etichetta configurabile

**Situazione prima**: il campo "Link" era obbligatorio (`required`) e il
testo del pulsante era fisso ("Dettagli"), senza possibilità di
personalizzarlo.

**Situazione dopo**:
- Il campo "Link" non è più obbligatorio; se lasciato vuoto, il widget
  non mostra alcun pulsante/link in fondo alla card (prima un link
  vuoto sarebbe comunque comparso, puntando a `href=""`).
- Nuovo campo "Etichetta del link", libero, con `placeholder="Dettagli"`
  - se non compilato il testo resta "Dettagli" (comportamento
  preesistente), se compilato lo sostituisce.

La condizione `@if(!empty($config->link))` è valutata direttamente nel
render iniziale del layout (dove `$config` è già disponibile), non
tramite il meccanismo generico di sostituzione placeholder `[chiave]`
usato per `sql`/`name`/`icon`/`link` (quel meccanismo sostituisce un
placeholder solo se il valore è "truthy" - un'etichetta vuota avrebbe
lasciato `[link_label]` come testo letterale non sostituito nell'HTML).

**Test**: sul widget reale "Numero utenti" (dashboard `test2`,
`id_cms_statistics=7`) - link svuotato e salvato → verificato che il
pulsante scompare del tutto dalla vista pubblica; link e nuova
etichetta "Vai alla lista" impostati e salvati → verificato che compare
"Vai alla lista" al posto di "Dettagli". Verificato anche che il
salvataggio persiste dopo un ricaricamento completo della pagina.

## 2. Bug reale trovato e corretto: il widget non riempiva il contenitore

**Situazione prima**: su `show/test2` (e nello stesso modo nel
builder), il widget KPI appariva più piccolo della sua cella nella
griglia, lasciando spazio bianco sotto. Causa: `.border-box` (il div
wrapper comune a **tutti** i tipi di widget, non solo l'Indicatore
KPI - stesso markup in `smallbox`/`table`/`chartline_v2`/ecc.) non
aveva un'altezza esplicita, quindi restava alto quanto il suo
contenuto (`height: auto` di default per un `<div>` a blocco) invece di
riempire la cella (`.ch-grid-view-cell` nella vista pubblica,
`.grid-stack-item-content` nel builder). Gli elementi interni
(`.small-box`, `.card`) avevano già `height: 100%`, ma percentuale
riferita a un genitore (`.border-box`) che non aveva a sua volta
un'altezza definita - quindi non aveva alcun effetto pratico.

**Situazione dopo**: aggiunta la regola `.border-box { height: 100%;
box-sizing: border-box; }` sia in `builder_grid.blade.php` sia in
`show_grid.blade.php` (scoped `.ch-grid-view .border-box` in
quest'ultimo). Essendo il wrapper comune a ogni widget, la correzione
si applica a tutti i tipi (KPI, tabella, grafici, pannelli), non solo
all'Indicatore KPI segnalato.

**Test**: verificato sul widget "Numero utenti" (colore pieno, prima
con un bordo bianco visibile sotto la card, ora la card blu riempie
l'intera cella) sia nel builder sia nella vista pubblica, prima e dopo
un ricaricamento della pagina.

## Rischi e note

- Widget esistenti che avevano già un link configurato **continuano a
  mostrarlo** esattamente come prima (nessuna migrazione dati
  necessaria: il comportamento cambia solo per link vuoti, prima
  impossibili da salvare per via del `required` HTML lato client - un
  utente poteva comunque aver salvato un link vuoto bypassando la
  validazione client, es. da un JSON diretto: anche in quel caso il
  comportamento nuovo, nascondere il link, è quello corretto).
- Il fix di dimensione è puramente CSS (nessuna modifica ai dati),
  effetto collaterale positivo non richiesto esplicitamente: anche i
  widget non-KPI (tabella, grafici, pannelli) ora riempiono
  correttamente la loro cella, coerente con quanto segnalato
  dall'utente ("credo ci siano [problemi] per tutti").

## Rollback

`git diff` sui tre file per tornare al link sempre obbligatorio/
etichetta fissa e al bug di dimensione.
