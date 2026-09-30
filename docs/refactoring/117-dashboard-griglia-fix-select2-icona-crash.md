# 117 - Dashboard a griglia libera: fix, crash select2 sull'icona dello Small Box

- **Data**: 2026-09-28
- **Stato**: Completato e verificato in browser
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/smallbox.blade.php`

## Contesto

Segnalato dall'utente: cliccando la select "Icon By Ionicons" nella
sidebar del builder a griglia, il browser lanciava
`select2.full.js:4217 Uncaught TypeError: Cannot read properties of
undefined (reading 'top')`.

## Situazione prima

Lo script che inizializza select2 sulla select delle icone (dentro il
ramo `configuration` di `smallbox.blade.php`, condiviso da builder
legacy e builder a griglia, vedi
[111](111-dashboard-griglia-fase3-editor-frontend.md)) impostava sempre
`dropdownParent: $('#modal-statistic')`. Nel builder legacy quell'
elemento esiste (la modale); nella sidebar del builder a griglia
**non esiste affatto** — select2 riceve un `dropdownParent` vuoto/
inesistente e va in crash nel calcolarne la posizione.

## Situazione dopo

`dropdownParent` viene impostato solo se `#modal-statistic` esiste
davvero nella pagina; altrimenti select2 usa il comportamento di
default (dropdown agganciato al `<body>`), che nella sidebar a griglia
va benissimo (non c'è nessuno stacking context di modale da rispettare).

## Motivazione

Fix minimo e mirato: nessun cambio per il builder legacy (dove
`#modal-statistic` c'è sempre), il crash si presentava solo nel
contesto nuovo dove quell'elemento non esiste.

## Test

Verificato in browser su una dashboard di prova: aggiunto un widget
KPI, aperta la sua configurazione, aperta la select delle icone via
`select2('open')` — dropdown mostrato con tutte le 734 opzioni, nessun
errore in console. Dashboard di prova poi eliminata.

## Rischi e note

Nessuna nota aggiuntiva: fix isolato, nessun altro widget usa select2
in questo controller.

## Rollback

`git diff` di questo file per tornare al `dropdownParent` incondizionato
(ripristina il crash nel builder a griglia).
