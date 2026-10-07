# 222 - Standard unico per i campi radio (controllo segmentato + radio tonda)

- **Data**: 2026-10-05
- **Stato**: Completato (da verificare a vista)
- **Area**: Frontend / UI standard
- **File/aree di codice coinvolte**:
  - `public/css/ch-components.css` (blocco "Radio")
  - `public/css/theme.css` (rimossi `.export-seg*` e `.mg-opt-seg*`)
  - `resources/views/crudbooster/partials/ch_radio.blade.php` (nuovo)
  - `resources/views/crudbooster/default/type_components/radio/component.blade.php`
  - `default/table.blade.php`, `module_generator/step4.blade.php`, `privileges.blade.php`,
    `setting.blade.php`, `qlik_sync/run.blade.php`

## Contesto

Seguito di [221](221-standard-campo-checkbox-switch-e-checkbox-quadrata.md) per
i radio. Mockup con tre proposte (`.scratch/mockup-radio.html`); scelta la
**B, controllo segmentato** (segmenti in una barra, quello selezionato bianco
e "sollevato").

## Situazione prima

- `radio/component.blade.php`: tre blocchi di HTML duplicati
  (`dataenum`, `datatable`, `dataquery`), stile Bootstrap 3 (`radio-inline`).
- Bug visibili: nel ramo `datatable` un `echo "$name"` di debug stampava il nome del campo
  sopra ogni opzione; il ramo `dataquery` aveva markup con `div` non bilanciati e
  usava `$val` non definito; label non escapati.
- Tre segmentati scritti a mano con stili propri (`.export-seg`, `.mg-opt-seg`,
  gruppi `btn-group` + `btn-check`), più radio native sparse.

## Situazione dopo

- **Un solo stile** in `ch-components.css`:
  - `.ch-seg` (+ `.ch-seg-sm`, `.ch-seg-block`) = segmentato B;
  - ogni `input[type=radio]` "nudo" → radio tonda (18px, punto accent, stati hover/focus/disabled/errore);
  - i vecchi `.btn-group` con `.btn-check` radio assumono lo stesso aspetto del segmentato (solo CSS, markup invariato).
- **Un solo markup** in `partials/ch_radio.blade.php`: sceglie da solo
  segmentato (2-8 opzioni, etichette ≤ 24 caratteri e ≤ 70 in totale) oppure elenco di radio tonde;
  `rd_mode` (`seg`/`list`) per forzare.
- `radio/component.blade.php` raccoglie le opzioni e usa il partial. Rimossi l'`echo "$name"`
  e il markup rotto di `dataquery`; label escapati. `name`, `value`, default di creazione
  (prima opzione se c'è `validation`) e `data-val` invariati.
- Migrati al partial / a `.ch-seg`: formato e orientamento export, opzioni del generatore (step 4),
  "Super privilege" nei privilegi, radio non yes/no dei Settings, annullamento sync Qlik (elenco).
- Settings yes/no: restano switch (intervento 221).
- Restano radio tonde in automatico: `child` (celle di tabella), Google Maps.

## Motivazione

Un solo posto per aspetto e markup; sparisce la duplicazione dei tre segmentati.
Il segmentato regge male molte opzioni: per questo il fallback automatico alla radio tonda.

## Test

- `php artisan view:cache` (compilazione di tutte le view) e render del componente nei
  casi: `dataenum` con 3 opzioni (segmentato), etichetta lunga e 6 opzioni (elenco),
  `dataquery` disabilitato, nessuna opzione. Verificato l'escape.
- **Non** verificato a vista né con la suite di test.

## Rischi e note

- Cambia l'aspetto per tutti i clienti (voluto); il salvataggio non cambia.
- Da controllare a vista: export (PDF/XLS/CSV con icone), step 4 del generatore,
  gruppi `btn-group` del generatore (icone, `btn-sm`) e dei token API (`api_tokens/add`,
  `api_generator`), privilegi, Settings.
- I gruppi `btn-group` sono ristilizzati via `:has()` (browser moderni).
- Le card di scelta di step 5 (`cfg-style-radio`, `d-none`) non sono toccate.

## Rollback

Ripristinare i file elencati (git) e rimuovere `ch_radio.blade.php`.
