# 225 - Standard campi (checkbox, radio, select, testuali) esteso a ogni punto dell'app

- **Data**: 2026-10-05
- **Stato**: Completato (da verificare a vista)
- **Area**: Frontend / UI standard
- **File/aree di codice coinvolte**:
  - `public/js/ch-select.js` (nuovo), `public/js/ch-inputs.js`
  - `resources/views/crudbooster/partials/ch_head.blade.php`, `ch_scripts.blade.php`, `ch_select2_assets.blade.php`
  - `public/css/ch-components.css`
  - `type_components/child/asset.blade.php`, `module_generator/template.blade.php`
  - `type_components/money/component.blade.php` (prefisso `€` di default)

## Contesto

Gli interventi 221-224 hanno uniformato i type component. Restavano fuori i campi
**scritti a mano** nelle viste (profilo, impostazioni, lista/filtri/export, builder,
generatori): select nativi, password senza occhio, numeri senza stepper, `input-group`
con stile vecchio. Richiesta: i campi dei type component sono "quelli dell'app", quindi
devono valere ovunque.

## Situazione prima

Lo standard valeva solo dove una vista usava il type component (o i partial `ch_check`,
`ch_radio`, `ch_input`). Select2 si caricava solo se un type component lo richiedeva.

## Situazione dopo

- **Select ovunque**: select2 (CSS in `ch_head`, JS in `ch_scripts`) è caricato in tutte le
  pagine; `public/js/ch-select.js` potenzia **ogni `<select>`** (anche scritti a mano e
  creati dopo da JS, con attesa di 200 ms per lasciare la precedenza a chi inizializza da sé):
  - esclusi: select2 già inizializzati o con classe `.select2`, `[multiple]`, `[size]` e
    `[data-ch-native]` (opt-out);
  - dropdown appeso alla modale/dialog che contiene il campo (ricerca funzionante nei popup);
  - larghezza piena per i campi nascosti (popup) e per `.form-control`; variante compatta
    (`.ch-select-compact`) per `.input-sm`/`-sm`/campi non `form-control`;
  - `$(select).val(x)` aggiorna anche la grafica (select2 4.0 non lo faceva);
  - ricerca nel pannello da 4 voci in su.
- **Password, numeri, textarea scritti a mano**: `ch-inputs.js` li avvolge nel riquadro
  standard (occhio, stepper ▲▼, contatore se `maxlength`), esclusi `[data-ch-native]`,
  `.form-control-sm` e numeri dentro tabelle; osserva anche i campi aggiunti dopo.
- **`input-group` legacy** con addon (`.input-group-text`): stesso riquadro del mockup via CSS.
- Rimossi i caricamenti duplicati di `select2.full.min.js` (asset del `child`, template del
  module generator); `ch_select2_assets` è ora vuoto (compatibilità).
- `money`: prefisso `€` di default (anche nel mockup).

## Regressione trovata e corretta (qlik_confs/add)

Select2 notifica la scelta con un evento **jQuery** (`$el.trigger('change')`), che non raggiunge i
listener registrati con `addEventListener('change')`: lo script del controller `QlikConfController`
(visibilità dei campi in base a Type/Auth) smise di reagire dopo la scelta. Correzione in
`ch-select.js`: su un select potenziato `.trigger('change')` diventa un evento nativo (arriva sia ai
listener nativi sia a quelli jQuery, una volta sola ciascuno). Compatibilità aggiunta anche per il codice
che nasconde un campo con `input.parentNode.parentNode.classList.add('hidden')`: se la colonna del form è
nascosta, la label della riga sparisce con lei (`ch-components.css`). Il campo file (227) mantiene
l'`<input type=file>` come figlio diretto della colonna proprio per questo.

## Motivazione

Una sola resa per ogni campo in tutta l'app, senza dover convertire a mano ogni vista
(e senza lavoro sui moduli dei clienti).

## Test

- Verificato a vista in locale (Chrome, sessione già loggata): Profilo (4 select e 4 password
  potenziati), lista `mg_fatture` (selettore righe compatto, popup filtri con 14 select:
  ricerca e spunta funzionanti, dropdown dentro la modale), generatore moduli step 1, form
  `mg_contratti`/`mg_fatture`. Nessun errore in console. **Non** eseguita la suite di test.
- Non verificate: builder statistiche, api generator, import, pagine dei widget.

## Rischi e note

- I select creati da JS con la propria `.select2(opts)` dopo oltre 200 ms potrebbero trovare il
  select già potenziato (select2 4.0 ignora un secondo init): in tal caso aggiungere
  `data-ch-native` o la classe `select2` al `<select>`.
- Gli stepper sui numeri scritti a mano cambiano il DOM (wrapper): codice che usa
  `.parent()` dell'input va controllato; `data-ch-native` esclude un campo.
- Pagine senza `ch_scripts` (pubbliche) restano con select nativi.
- Non coperti: `date`/`datetime`/`time`, `upload`, `datamodal`, `color`, editor (da fare).

## Rollback

Togliere select2 e `ch-select.js` da `ch_scripts`, il link da `ch_head`, e ripristinare i
file elencati (git).
