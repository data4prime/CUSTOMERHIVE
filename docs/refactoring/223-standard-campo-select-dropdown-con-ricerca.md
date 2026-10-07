# 223 - Standard unico per i campi select (dropdown con ricerca)

- **Data**: 2026-10-05
- **Stato**: Completato (da verificare a vista)
- **Area**: Frontend / UI standard
- **File/aree di codice coinvolte**:
  - `public/css/ch-components.css` (blocco "Select2 / Select standard")
  - `resources/views/crudbooster/partials/ch_select2_assets.blade.php` (nuovo)
  - `type_components/select/{asset,component}.blade.php`, `type_components/select2/{asset,component}.blade.php`
  - `resources/views/crudbooster/setting.blade.php`
  - `resources/lang/{en,it}/crudbooster.php` (`select_no_results`)

## Contesto

Terzo passo dello standard campi (221 checkbox, 222 radio). Mockup con tre proposte
(`.scratch/mockup-select.html`); scelta la **B, dropdown con ricerca**: campo chiuso con
chevron, pannello con ricerca, spunta sulla voce selezionata, voci con descrizione/icona.

## Situazione prima

- `type select`: `<select>` nativo (pannello del sistema operativo, nessuna ricerca).
- `type select2`: select2 4.0.2 con aspetto proprio (voce evidenziata in accent pieno, freccia di default).
- `select2/component`: residui di debug (`echo $enum;` e virgolette/`;` letterali stampati
  accanto alle opzioni `dataenum`); label delle opzioni non escapati.

## Situazione dopo

- **Il motore resta select2** (già presente e usato in ~20 punti: ajax, destroy/reinit,
  parent select, widget): riscritto non il motore ma **l'aspetto**, in `ch-components.css`:
  campo con chevron, pannello arrotondato con ricerca, hover grigio, selezionata in accent-soft
  con spunta, messaggio "nessun risultato", disabilitata ed errore.
- `partials/ch_select2_assets` (con `@once`) carica select2 una volta sola e definisce
  `window.chSelect(el, opts)`: larghezza piena, ricerca solo con 8+ voci, `<option data-desc>`
  e `data-icon` per voci con descrizione/icona; auto-init per `<select data-ch-select>`.
- `type select` ora usa `chSelect` (prima nativo); i due `asset.blade.php` includono il partial.
- Settings: i `select` usano `data-ch-select`.
- Escape di value/label delle opzioni in `select` e `select2`; rimossi i residui di debug in `select2`.
- Nuova chiave `select_no_results` (en/it).

## Motivazione

Una sola resa per ogni select dei form, senza riscrivere le integrazioni select2 esistenti
(riscrivere il motore sarebbe stato il cambio più rischioso e meno utile).

## Test

- `php artisan view:cache` e render dei componenti `select` e `select2` con `dataenum`
  (escape verificato). **Non** verificato a vista né con la suite di test.

## Rischi e note

- Cambia l'aspetto per tutti i clienti (voluto). `name`/`value` inviati invariati.
- `type select` con `parent_select`: le opzioni vengono ricaricate via `.html()` + `change`,
  select2 le rileva; da provare a vista.
- **Non toccati** i `<select>` nativi di builder/generatori e viste di servizio (module
  generator, statistic builder, api generator, filtri e popup della lista, profilo,
  import): restano nativi (con lo stile di `.form-select`). Per passarli allo standard basta
  includere `partials/ch_select2_assets` nella pagina e aggiungere `data-ch-select` al `<select>`.
- Escape dei label: label con HTML voluto ora vengono mostrati come testo.

## Rollback

Ripristinare i file elencati (git) e rimuovere `ch_select2_assets.blade.php`.
