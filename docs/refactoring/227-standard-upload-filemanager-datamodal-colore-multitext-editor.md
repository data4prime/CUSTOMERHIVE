# 227 - Standard per upload, filemanager, datamodal, colore, multitext ed editor

- **Data**: 2026-10-05
- **Stato**: Completato (da verificare a vista)
- **Area**: Frontend / UI standard
- **File/aree di codice coinvolte**:
  - `public/css/ch-components.css` (blocco "File, datamodal, colore, multitext, editor")
  - `public/js/ch-inputs.js`, `partials/ch_scripts.blade.php` (testi)
  - nuovi partial: `partials/ch_file`, `ch_datamodal_field`, `ch_datamodal_list`
  - `type_components/{upload,filemanager,color,multitext,datamodal}/component.blade.php`,
    `type_components/datamodal/browser.blade.php`,
    `default/datamodal_relation/{component,browser}.blade.php` (le 7 varianti `*_datamodal`)
  - `resources/lang/{en,it}/crudbooster.php` (`datamodal_choose`, `file_none_selected`)

## Contesto

Sesto passo dello standard campi (221-226). Mockup con tre proposte (`.scratch/mockup-altri.html`).
Scelte: **A** (campi allineati ai testuali) per file, colore, multitext ed editor;
**C** (riga come un select) per il datamodal, con la ricerca nella finestra.

## Situazione prima

- File: `<input type=file>` nativo (aspetto del browser); filemanager con pulsante blu "btn-primary".
- Datamodal: campo di testo di sola lettura + pulsante "Sfoglia Dati" (+ "apri modulo");
  popup con tabella e un pulsante "Seleziona" per riga; 7 varianti `*_datamodal` che ripetevano la stessa tabella.
- Colore: `<input type=color>` nativo. Multitext: righe con pulsanti Bootstrap, `name=" campo[]"` (spazio
  iniziale), valore interpolato nel JS senza escape e contatore delle righe che non contava quelle caricate.
- Editor: aspetto di default di summernote/tinymce/ckeditor.

## Situazione dopo

- **File (upload, filemanager, campi `input[type=file].form-control` scritti a mano)**: riquadro standard con
  "Scegli file" come addon a sinistra e il nome del file scelto accanto (`partials/ch_file`, `ch-inputs.js`);
  il vero `<input type=file>` resta figlio diretto della colonna del form, invisibile, e la label lo apre con
  `for=` (compatibile col codice che nasconde i campi con `input.parentNode`; la validazione `required` funziona). File già caricato:
  scheda con anteprima/icona, nome, "scarica" ed "elimina" (stesse azioni di prima).
  Nel filemanager il pulsante diventa l'addon (il plugin LFM usa lo stesso `id`/`data-input`); rimossa la seconda
  copia del pulsante "elimina" che compariva sotto.
- **Datamodal (C)**: riga come un select con la lente a destra (`partials/ch_datamodal_field`); clic o Invio/Spazio
  aprono la finestra; ✕ per svuotare (solo se non obbligatorio); link "apri modulo" come icona. `#<name> .input-id`
  / `.input-label` e `selectAdditionalData<name>` restano quelli di prima.
- **Finestra datamodal**: ricerca in alto (si invia da sola dopo 400 ms o con Invio, risultati lato server come
  prima), tabella in cui si sceglie **cliccando la riga** (niente colonna "Seleziona"), messaggio "nessun
  risultato", paginazione invariata. Lista unica in `partials/ch_datamodal_list` per `datamodal` e per le 7
  varianti.
- **Rifiniture della finestra** (dopo una prima verifica a vista): larghezza 640px (se non `large`), titolo
  da 16px, righe più alte (9px di padding, testo 14px), sfondo bianco dentro l'iframe, paginazione compatta
  con la pagina attiva in accent invece del blu Bootstrap. Ricerca verificata: "Roma" → 5 righe, focus e
  cursore restano nel campo dopo il ricaricamento.
- **Colore**: campione cliccabile + codice esadecimale sincronizzati (`ch-inputs.js`); il valore inviato resta
  quello dell'`<input type=color name=...>`.
- **Multitext**: una riga per voce nel riquadro standard, `+` / `✕` come pulsanti quadrati; valori sempre separati
  da `|`; nome del campo senza spazio, valore passato al JS con `json_encode`, limite di voci contato sulle righe
  presenti.
- **Editor**: bordo, raggio, barra e focus come i campi (solo CSS per summernote, tinymce, ckeditor).

## Motivazione

Un solo aspetto per i campi di scelta/inserimento rimasti, nei form e nei popup.

## Test

- Verificato in locale (Chrome): campo Contratto di `mg_fatture/edit/306` (apertura della finestra, 6 righe,
  scelta riga → id 69 e testo nel campo, classe `has-value`, finestra chiusa), campo Allegato di
  `mg_contratti/edit/71` (riquadro, nome del file scelto, `required` rispettato). Nessun errore in console.
  Compilazione di tutte le view (`view:cache`).
- **Non** verificati: filemanager (LFM), colore, multitext, editor, link "apri modulo", le varianti `*_datamodal`
  di gruppi/tenant, file già caricato; suite di test non eseguita.

## Rischi e note

- Il "Seleziona" del popup non c'è più: si clicca la riga. Chi usa il popup via tastiera usa Tab + Invio sulla riga.
- Multitext: il bug del contatore sistemato cambia il limite effettivo per i campi con valori già salvati
  (prima potevano superare `max_fields`).
- L'asterisco di "obbligatorio" di multitext compariva sempre (`isset`): ora solo se obbligatorio.
- Editor: CSS non verificato a vista sui tre motori.
- Fuori da questo intervento: `googlemaps`, `child`, `json`, `header`, `hidden`.

## Rollback

Ripristinare i file elencati (git) e rimuovere i tre partial.
