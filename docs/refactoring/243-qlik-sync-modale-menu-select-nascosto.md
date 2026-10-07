# 243 - Modale "Sincronizza da Qlik": menu del select configurazione invisibile

- **Data**: 2026-10-07
- **Stato**: Completato (verificato a vista nel browser locale)
- **Area**: Frontend / Qlik
- **File/aree di codice coinvolte**:
  - `public/css/theme.css` (blocco `.ch-sync-*`)
  - letti, non modificati: `public/js/ch-select.js`, `public/js/qlik_sync_modal.js`

## Contesto

Segnalazione: dalla lista `admin/qlik_apps`, nella modale "Sincronizza le app da
Qlik", scegliendo la configurazione il menu non mostrava nulla.

## Situazione prima

`ch-select.js` trasforma in select2 ogni `<select>` dell'app. Il menu di select2
viene appeso al `.modal`/`dialog` che contiene il campo, altrimenti al `<body>`.
La modale di sincronizzazione è un overlay proprio (`.ch-sync-overlay`,
`z-index: 100000`), non un `.modal` né un `dialog`: il menu finiva nel `<body>`
con `z-index: auto`, cioè dietro l'overlay. Dati e JS erano corretti (cambiando
il valore via JS la lista delle 42 app si caricava).

## Situazione dopo

Regola `body > .select2-container { z-index: 100001; }` in `theme.css`, accanto
agli stili della modale: il menu select2 appeso al body sta sopra l'overlay.

## Motivazione

Fix minimo e behavior-preserving: non tocca `ch-select.js` (usato in tutta
l'app) né la struttura della modale. Alternativa scartata: far riconoscere
`.ch-sync-dialog` a `dropdownParent`, che metterebbe il menu dentro un contenitore
con `overflow: hidden` e richiederebbe di verificare il posizionamento.

## Test

Provata la regola dal vivo nel browser su `localhost:8080/admin/qlik_apps`:
il menu si apre sopra la modale con le due voci. Nessun test automatico.

## Rischi e note

La regola vale per tutti i menu select2 appesi al body, ora sopra z-index
100000: nessun altro overlay del progetto è sopra quel valore (non verificato
in modo esaustivo). Nessun testo UI nuovo.

## Rollback

Rimuovere la regola `body > .select2-container` da `public/css/theme.css`.
