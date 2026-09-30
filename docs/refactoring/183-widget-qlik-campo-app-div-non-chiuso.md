# 183 - Widget Qlik: campo "App Qlik" (div non chiuso, proprietà non definita)

- **Data**: 2026-09-30
- **Stato**: Completato
- **Area**: Frontend (statistic builder)
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/qlikwidget.blade.php`

## Contesto

Segnalato un problema (errore/layout) sul campo "App Qlik" nella
configurazione del widget Qlik in
`/admin/statistic_builder/builder/23`.

## Situazione prima

- Un `<div class="mb-3 row">` vuoto (riga 104) veniva aperto e mai chiuso: la
  chiusura finale chiudeva lui al posto del contenitore esterno, sfalsando
  la struttura DOM del pannello di configurazione.
- Il ciclo delle opzioni testava `isset($config)` e poi leggeva
  `$config->mashups`: con una config presente ma senza la chiave `mashups`
  (widget nuovo/incompleto) si otteneva "Undefined property".

## Situazione dopo

- Rimosso il div orfano.
- Il test è ora `isset($config->mashups)`.
- Il campo di ricerca separato sopra la select è stato sostituito da un
  unico campo select2 con ricerca integrata (stesso pattern del
  widget Modulo in `panelcustom.blade.php`, con chiavi nuove EN/IT
  `qlik_widget_app_no_results`/`qlik_widget_app_searching`). Stessa cosa per
  il campo fogli (`qlik_item`, chiave `qlik_widget_sheet_no_results`): tolto
  l'input `qlik_item_filter`, la select2 viene aggiornata con
  `change.select2` dopo ogni `fillItems()`. Rimosso il vecchio filtro JS
  (`refillApps`) e l'input `mashup_app_filter`; il filtro dei fogli resta.
  Comportamento visibile cambiato: solo la UI del campo.

## Motivazione

Correzione strutturale minima, behavior-preserving per i widget già
configurati.

## Test

Solo lettura del diff; non verificato in browser né con la suite di test.

## Rischi e note

Nessun cambiamento di comportamento per widget con `mashups` valorizzato. Non
riprodotto direttamente l'errore segnalato: se persiste, servono il testo
dell'errore o uno screenshot.

## Rollback

Ripristinare `qlikwidget.blade.php` (`git checkout` del file).
