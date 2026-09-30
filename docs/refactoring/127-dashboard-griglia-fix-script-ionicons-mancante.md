# 127 - Dashboard a griglia: fix reale "l'icona non si vede" (script Ionicons v7 mai caricato)

- **Data**: 2026-09-28
- **Stato**: Completato e verificato in browser
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/builder_grid.blade.php`
  - `resources/views/crudbooster/statistic_builder/show_grid.blade.php`

## Contesto

Dopo il restyle dell'Indicatore KPI ([126](126-kpi-indicator-restyle-e-descrizione.md))
l'utente ha segnalato: "non mostra l'icona selezionata". Verificato in
browser (dashboard `test2`, id 7) prima di ipotizzare la causa.

## Situazione prima

Bug reale, pre-esistente e indipendente dal restyle di 126: `<ion-icon>`
(Ionicons v7, il web component usato dal rendering finito del widget,
vedi [053](053-smallbox-icon-searchable-color-picker.md)) richiede uno
script runtime (`ionicons.esm.js` + fallback `ionicons.js` da
`unpkg.com`) per essere registrato come custom element. Questo script è
caricato **solo** da `layout.blade.php` (il builder legacy ad aree
fisse). **Né `builder_grid.blade.php`** (pagina standalone, non estende
`admin_template`) **né `show_grid.blade.php`** (estende
`admin_template`, che carica solo il font CSS `ionicons.min.css`, non
il web component) lo caricano. Risultato: su ogni widget "Indicatore
KPI" creato/visto dalla nuova griglia, `<ion-icon>` restava un custom
element mai definito - nessuna icona veniva mai disegnata, qualunque
fosse il nome scelto nel picker.

Verificato in browser con `customElements.get('ion-icon')` → `undefined`
sulla pagina del builder a griglia prima del fix.

## Situazione dopo

Aggiunti gli stessi due `<script>` già presenti in `layout.blade.php`
(`ionicons.esm.js` con `type="module"` + `ionicons.js` con `nomodule`,
stessa versione `7.1.0`) - in `builder_grid.blade.php` direttamente in
`<head>`, in `show_grid.blade.php` tramite `@push('head')` (la pagina
estende `admin_template`, che espone `@stack('head')`).

## Motivazione

Fix minimo e mirato: stesso script, stessa versione, stesso posto
logico (head) di dove già funziona nel builder legacy - zero rischio di
introdurre un comportamento diverso tra le due modalità di builder.

## Test

Verificato in browser (dashboard `test2`, id 7, ambiente Docker
locale):
- Prima del fix: `customElements.get('ion-icon')` → `undefined` sulla
  pagina `builder_grid.blade.php`.
- Dopo il fix: `undefined` → definito (`class="... hydrated"` sull'
  elemento), confermato anche via network (i due script `unpkg.com`
  risultano caricati).
- **Scoperta una seconda causa, distinta, dello stesso sintomo**: anche
  con lo script caricato, il nome icona salvato nei widget esistenti
  (es. `ion-android-person`, scelto dal picker "Icon By Ionicons") **non
  è un nome valido per Ionicons v7** - `<ion-icon>` lo rende
  silenziosamente vuoto (nessun errore visibile, solo un
  `TypeError: Failed to fetch` in console per l'asset SVG non trovato).
  Confermato assegnando temporaneamente (solo lato client, mai salvato)
  un nome v7 valido (`person-circle`) allo stesso elemento: l'icona è
  comparsa correttamente, colorata con l'accent configurato dal restyle
  126. Questo è esattamente il gap già segnalato in
  [118](118-dashboard-griglia-icona-preview-select-ionicons.md)
  ("le icone del picker v2 non corrispondono al set v7") - **non
  risolto in questo intervento**, resta una limitazione nota.
- Creato e poi rimosso un widget di prova ("Contratti attivi", con
  descrizione, link e query SQL) sulla dashboard `test2` per verificare
  l'intero flusso salva/ricarica del campo `description` introdotto in
  126 - confermato che il testo torna intatto (non `[description]`
  grezzo) e che il form di configurazione ricarica tutti i valori
  salvati correttamente. Riga rimossa da `cms_statistic_components`
  (id 18) a fine verifica, dashboard di prova tornata allo stato
  originale.

## Rischi e note

- **Il problema di fondo segnalato dall'utente ("non mostra l'icona")
  non è completamente risolto**: senza lo script l'icona non compariva
  mai (causa 1, risolta qui); con lo script ma un nome v2 salvato,
  l'icona resta comunque vuota (causa 2, nota, non risolta). Per i
  widget **già configurati** con un nome v2 (es. `ion-android-person`),
  serve **riselezionare manualmente** un'icona nel picker con un nome
  che sia anche un'icona v7 valida, oppure aspettare un intervento
  dedicato su 118 (mappatura v2→v7, o sostituzione del picker con un
  set di icone v7 reali).
- Nessuna verifica ancora sulla vista pubblica (`show_grid.blade.php`)
  con un vero utente non-superadmin - il fix segue lo stesso pattern
  già verificato sul builder, ma il caricamento via `@push('head')`
  specificamente non è stato testato in questa sessione.

## Rollback

`git diff` sui due file per tornare allo stato precedente (icona mai
disegnata su nessun widget della griglia, indipendentemente dal nome
scelto).
