# 148 - Sezione "Sorgente dati" in popup condiviso tra tutti i widget

- **Data**: 2026-09-29
- **Stato**: Completato e verificato ispezionando il sorgente compilato, non in browser su richiesta esplicita dell'utente
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/_source_config.blade.php` (nuovo)
  - `resources/views/crudbooster/statistic_builder/components/smallbox.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/chartline_v2.blade.php`

## Contesto

Richiesta diretta dell'utente subito dopo aver visto la sezione
"Sorgente dati" del Grafico a linee (147): voleva lo stesso trattamento
gia' usato per l'Indicatore KPI (popup dedicato invece che inline
nella sidebar) applicato in modo uniforme, con il campo Dataset ordinato
alfabeticamente e ricercabile - quest'ultimo era gia' cosi' (select2 +
`optionsForFrontend()` gia' ordina alfabeticamente), mancava solo la
modale.

## Situazione prima

Il popup "Sorgente dati" (riepilogo + pulsante "Configura sorgente" +
overlay con toggle SQL libera/Query guidata) esisteva solo dentro
`smallbox.blade.php` (introdotto in 132/133/139), interamente scritto
li' - markup, CSS e JS. `chartline_v2.blade.php` (147) aveva ricevuto la
Query guidata completa ma SENZA popup: tutto inline nella sidebar.

## Situazione dopo

Estratto tutto il popup (markup del `<details>` "Sorgente dati" +
overlay/modale + CSS + JS di apertura/chiusura/riepilogo/pulse) in un
nuovo partial condiviso, `_source_config.blade.php`, parametrizzato:
- `$sqlLabel`, `$sqlPlaceholder`, `$sqlHelp` (testo del campo SQL libera,
  diverso tra KPI - "Query", senza aiuto - e grafico - "SQL Query
  (colonne label e value)", con placeholder ed help specifici)
- `$groupBySupported` (passato pari pari a `_query_builder_fields.blade.php`,
  vedi 147)

`smallbox.blade.php` e `chartline_v2.blade.php` ora si limitano a un
`@include('..._source_config', compact(...))` con i rispettivi
parametri, senza piu' duplicare markup/CSS/JS - `smallbox.blade.php` ha
perso ~130 righe di codice ora nel partial condiviso.

## Motivazione

La modale rendeva gia' bene per il KPI (132/133/139/145); estenderla a
tutti i widget invece di riscriverla per ognuno evita la duplicazione
che si sarebbe altrimenti ripetuta a ogni nuovo widget "a query"
(chartbar_v2 in futuro, se richiesto).

## Test

Non verificato in browser (regola esplicita dell'utente, vedi
`feedback_no_autonomous_browser_testing`). Verificato con render diretto
(script isolato, bootstrap Laravel, eseguito e poi rimosso) del comando
`layout` di `smallbox` (invariato, OK) e ispezionando il sorgente
COMPILATO (dato quanto successo in 144, non ci si fida del solo `php -l`
quando si toccano commenti Blade - qui il nuovo partial ne ha 6, tutti
bilanciati `{{--`/`--}}`) dei comandi `configuration` di entrambi i
widget: confermato che `smallbox` include `_source_config` con solo
`componentID`/`config` (gli altri parametri prendono i default), che
`chartline_v2` la include con `groupBySupported`/`sqlLabel`/
`sqlPlaceholder`/`sqlHelp` valorizzati, e che il partial condiviso
contiene davvero l'overlay/modale e gli include di
`_query_mode_toggle`/`_query_builder_fields`.

## Rischi e note

`chartbar_v2.blade.php` non e' stato toccato (stesso gap di prima di
147, mai richiesto esplicitamente) - riceverebbe lo stesso trattamento
con un `@include` analogo, nessun altro lavoro da rifare visto che tutta
la logica ora vive in `_source_config.blade.php`.

## Rollback

`git diff` di `smallbox.blade.php` e `chartline_v2.blade.php`, ed
eliminazione di `_source_config.blade.php`, per tornare al popup
duplicato solo nel KPI e al pannello inline nel grafico.
