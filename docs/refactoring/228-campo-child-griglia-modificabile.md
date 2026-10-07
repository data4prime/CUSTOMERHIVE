# 228 - Campo child: griglia modificabile

- **Data**: 2026-10-05
- **Stato**: Completato (da verificare a vista)
- **Area**: Frontend / UI standard
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/default/type_components/child/component.blade.php` (riscritto)
  - `public/css/ch-components.css` (blocco "Campo child")
  - `resources/lang/{en,it}/crudbooster.php` (`child_add_row`, `child_total`)

## Contesto

Ultimo dei type component di scelta/inserimento (221-227). Mockup con tre proposte
(`.scratch/mockup-child.html`); scelta la **B, griglia modificabile**: niente mini-form, le celle sono i campi.

## Situazione prima

Due schede: un mini-form (un campo per colonna, con i suoi id `<name><colonna>`) con "Aggiungi alla tabella"
e, sotto, la tabella delle righe con modifica/elimina. ~600 righe di Blade/JS che ripetevano a mano il codice
di ogni tipo di campo; a ogni riga aggiunta si copiava il valore del mini-form in input nascosti.

## Situazione dopo

- **Una sola tabella**: ogni riga è una riga della tabella figlia, le celle sono i campi (testo, numero,
  textarea, select, radio come segmentato, datamodal come riga con la lente, upload con scegli/anteprima, hidden)
  e si modificano sul posto. "Aggiungi riga" aggiunge una riga vuota in fondo (da un `<template>`), il cestino
  elimina (con la stessa conferma di prima), messaggio "nessun dato" quando è vuota.
- **Contratto di salvataggio invariato**: ogni colonna produce un input `name="<slug-label>-<colonna>[]"` per
  riga, nello stesso ordine (CBController legge quegli array); anche le validazioni `<slug>-<colonna>` valgono.
  Le colonne `hidden` stanno nella cella delle azioni di ogni riga.
- **Formule** (`'formula' => "[qta] * [prezzo]"`): calcolate nella riga mentre si digita (stesso testo della formula,
  `[colonna]` letto nella riga). **Nuovo**: `'sum' => true` su una colonna mostra il totale in fondo.
- **datamodal**: una finestra per colonna condivisa dalle righe; `datamodal_select_to` riempie le altre colonne
  della *stessa riga* (per `name` di colonna). **upload**: un file nascosto per colonna, stesso caricamento via
  AJAX e stessi controlli di dimensione/estensione di prima.
- **select collegati** (`parent_select`): le opzioni si ricaricano nella stessa riga.
- Le colonne `required` usano l'attributo HTML `required`: una riga aggiunta e lasciata vuota blocca il salvataggio
  (prima il controllo avveniva solo alla pressione di "Aggiungi alla tabella").
- Ogni cella usa i campi standard (select2, numeri, switch) e il resto dello stile 221-227.

## Motivazione

Meno codice duplicato, inserimento rapido di molte righe (Tab tra le celle), totali in tempo reale, un solo punto
di manutenzione.

## Test

- `view:cache` e render in locale su `users/edit/5` (child "Qlik Users": 4 colonne, datamodal + testo): griglia
  presente, 2 righe aggiunte con "Aggiungi riga", nomi degli input corretti (`qlikusers-<colonna>[]`),
  scelta di un record nel datamodal di una riga → id `3` e testo "D4P SAAS" nella riga giusta.
- Verificata la **sintassi** dello script generato per un child di prova con tutti i tipi di colonna
  (text, number, formula, sum, radio, select, upload, datamodal, hidden): nessun errore.
- **Non** verificato a vista: formule, somme, radio, upload, select collegati, salvataggio completo.
  Suite di test non eseguita.

## Rischi e note

- **Gli id `<name><colonna>` non esistono più** (con più righe non possono essere unici): script dei moduli dei
  clienti che li usano (`$('#mg_righeprezzo')`) vanno adattati (selettore `[data-col="prezzo"]` dentro la riga).
- Le nuove righe vanno in fondo (prima in cima); le righe già salvate restano in ordine di id decrescente.
- Una riga vuota con colonne obbligatorie impedisce il salvataggio finché non si compila o si elimina.
- Colonne molto larghe (datamodal, upload) stanno in celle strette: per child con molte colonne valutare la
  variante a schede (C del mockup).

## Rollback

Ripristinare `child/component.blade.php` e la parte CSS (git).
