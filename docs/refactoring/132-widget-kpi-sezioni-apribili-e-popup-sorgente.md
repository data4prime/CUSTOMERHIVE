# 132 - Widget KPI: pannello di configurazione in sezioni apribili + popup per la sorgente

- **Data**: 2026-09-28
- **Stato**: Completato e verificato in browser
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/smallbox.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/_query_mode_toggle.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/_query_builder_fields.blade.php`

## Contesto

Richiesta dell'utente dopo aver usato il pannello di configurazione del
widget Indicatore KPI (introdotto/esteso in 128/129/131): i pulsanti non
seguivano lo stile della pagina, e il modulo era un unico blocco lungo.
Chiesto: (1) pulsanti in stile con il resto della pagina, (2) il form
diviso in 4 sezioni apribili/chiudibili (Nome+descrizione, Colore+icona,
Link+etichetta, Sorgente), (3) la sorgente dati configurata in un popup
con un pulsante "Prova" e un "Salva" che chiude il popup.

## Situazione prima

- Il toggle "SQL libera/Query guidata" (`_query_mode_toggle.blade.php`) e
  il pulsante "Anteprima" (`_query_builder_fields.blade.php`) usavano
  classi Bootstrap (`btn btn-sm btn-primary/btn-default`, icona
  `fa fa-eye`) - **ma il builder a griglia (`builder_grid.blade.php`) non
  carica ne' Bootstrap ne' Font Awesome** (pagina standalone, vedi 128):
  questi pulsanti comparivano completamente privi di stile, a differenza
  del resto della pagina (`.ch-btn`, colori/font propri).
- Il form era un'unica sequenza lunga di campi, tutti sempre visibili.
- La sezione "Sorgente dati" (toggle + campi SQL/query guidata) era
  inline nella sidebar, mescolata cogli altri campi.

## Situazione dopo

- **Pulsanti**: `.ch-btn`/`.ch-btn-active` (stessi nomi/colori gia'
  definiti in `builder_grid.blade.php`) al posto delle classi Bootstrap,
  ridefiniti pero' in un `<style>` **auto-contenuto dentro
  `smallbox.blade.php`** (non nel foglio di stile globale del builder a
  griglia): il comando `configuration` e' una risposta Blade indipendente
  da `layout`, iniettata sia nella sidebar del builder a griglia sia nella
  modale Bootstrap del builder legacy (`#modal-statistic`,
  `index_.blade.php`) - stili definiti altrove non sarebbero arrivati in
  entrambi i contesti. "Anteprima" rinominato "Prova" (icona `fa fa-eye`
  tolta, stesso motivo: Font Awesome non e' caricato su questa pagina).
- **4 sezioni apribili/chiudibili** (`<details>/<summary>` nativi, nessun
  JS aggiuntivo per l'apertura/chiusura): Nome e descrizione (aperta di
  default), Colore e icona, Link, Sorgente dati (aperta di default).
- **Sorgente dati in popup**: la sezione mostra solo un riepilogo
  ("Modalità attuale: SQL libera" / "Query guidata — Tabella: X") e un
  pulsante "Configura sorgente". I campi veri (toggle, SQL o dataset/
  metrica/filtro, pulsante "Prova") restano nello stesso `<form>` (letti
  da `.serialize()` al Salva generale della sidebar, invariato) ma
  **spostati via JS dentro il popup** appena la pagina li carica - nessuna
  richiesta di salvataggio separata per il popup: il suo pulsante "Salva"
  chiude la finestra e aggiorna il riepilogo, il salvataggio vero resta
  quello unico gia' esistente in fondo alla sidebar. Il riepilogo si
  aggiorna anche in tempo reale mentre il popup e' aperto (cambio modalita'/
  dataset), non solo alla chiusura.

Verificato in browser (Docker locale, builder a griglia): le 4 sezioni si
aprono/chiudono correttamente: apertura del popup, cambio a "Query
guidata", selezione dataset "Tabella: Contratti", "Prova" → "Conteggio
righe 1" (verificato uguale a `SELECT COUNT(*) FROM mg_contratti`),
chiusura del popup con "Salva" → il riepilogo nella sidebar mostra
correttamente "Query guidata — Tabella: Contratti".

## Motivazione

Vedi richiesta utente: coerenza visiva con il resto della pagina (i
pulsanti Bootstrap non stilizzati stonavano) e un form piu' lungo diviso
in sezioni logiche invece di un blocco unico, con la configurazione più
delicata (la sorgente dati) isolata in un popup dedicato invece che
mescolata al resto.

## Test

Verifica end-to-end in browser con dati reali (non solo lettura del
codice): apertura/chiusura delle 4 sezioni, apertura del popup, cambio
modalità, selezione dataset, "Prova" con valore confrontato al DB reale,
chiusura del popup, aggiornamento del riepilogo. Non eseguita la suite di
test automatici (non richiesta esplicitamente).

## Rischi e note

- **Incidente durante il test, da segnalare**: nella sessione di verifica
  in browser, 3 widget di prova sulla dashboard "test2" (usata anche in
  [131](131-query-guidata-dataset-da-schema-reale.md)) sono stati
  **eliminati per errore** - non da un bug del codice, ma da click
  dell'automazione browser finiti sul pulsante "×" di eliminazione di un
  widget (sovrapposto in alto a destra alla card, coordinate cambiate tra
  uno screenshot e l'altro per via del layout dinamico). Dati di test
  locali, non dati di un cliente (`docs/piano-testing-manuale.md`: "il DB
  lo pulisce l'utente") - impatto nullo sul codice/produzione, ma vale la
  pena saperlo se quella dashboard di test sembra "svuotata".
- Le sezioni "Colore e icona" e "Link" partono chiuse: se un domani si
  nota che vengono aperte quasi sempre, si puo' cambiare il default
  aggiungendo `open` al loro `<details>`.
- Stile duplicato tra questo file e `builder_grid.blade.php` (stessi
  colori/nomi di classe `.ch-btn` ecc.) per lo stesso motivo per cui ogni
  widget ha gia' il proprio `<style>` per l'aspetto del comando `layout` -
  scelta deliberata di auto-contenimento, non un errore di copia-incolla:
  se in futuro emerge un terzo widget con lo stesso bisogno, vale la pena
  estrarre un partial CSS condiviso invece di duplicare una terza volta.
- Non ancora applicato ad altri widget che condividono
  `_query_mode_toggle.blade.php`/`_query_builder_fields.blade.php`
  (`table`, `chartline_v2`, `chartbar_v2`, `chartarea_v2`): hanno ancora
  il loro form flat, senza sezioni ne' popup. Stesso pattern riusabile se
  richiesto in seguito.

## Rollback

`git diff` dei 3 file elencati sopra. I campi restano identici (stessi
`name="config[...]"`), quindi anche tornando alla versione precedente le
dashboard/i widget già salvati continuano a funzionare senza modifiche ai
dati.
