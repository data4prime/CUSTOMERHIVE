# 174 - Griglia libera: i widget grafici legacy non si vedevano dopo la conversione

- **Data**: 2026-09-30
- **Stato**: Completato (non verificato nel browser)
- **Area**: Statistic Builder
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/builder_grid.blade.php`
  - `resources/views/crudbooster/statistic_builder/show_grid.blade.php`

## Contesto

Prova manuale della conversione "Griglia libera" (vedi 109/114) su una
dashboard `legacy_areas` con 9 widget (smallbox, panelarea, 2 chartarea,
2 panelcustom, 2 chartline, 1 chartbar): dopo la conversione il builder a
griglia mostrava solo 3 widget.

## Situazione prima

- La conversione era corretta: nel DB tutti i widget avevano
  `pos_x/pos_y/width/height` validi e senza sovrapposizioni;
  `getListComponentsGrid()` restituiva tutti i 9 payload, nessun errore nel log.
- I widget "classici" `chartarea`/`chartline`/`chartbar` disegnano con
  `Morris.Area/Line/Bar` (Raphael). Morris era caricato solo da
  `statistic_builder/index.blade.php` (builder/vista legacy); `builder_grid` e
  `show_grid` non lo caricavano (Raphael arriva gia' da
  `admin_template_plugins` solo nella vista di sola lettura).
- In `builder_grid`, `loadComponents()` faceva `forEach(addExistingWidget)`:
  `.html()` esegue lo `<script>` del widget, `new Morris.*` lanciava
  `ReferenceError` dentro `$(function(){})` e l'eccezione interrompeva il
  `forEach` - il primo widget Morris restava vuoto e tutti i successivi non
  venivano mai aggiunti.
- Nella vista di sola lettura (`show_grid`) gli script sono isolati per widget:
  i widget comparivano, i grafici Morris restavano vuoti.

## Situazione dopo

- `builder_grid`: caricati CSS Morris, Raphael e Morris (stessi CDN/versioni
  di `index.blade.php`); `addExistingWidget` chiamato in un `try/catch` per
  widget, con `console.error`, cosi' un widget rotto non nasconde gli altri.
- `show_grid`: aggiunto `@push('bottom')` con CSS e JS di Morris (Raphael gia'
  presente dal template admin).

## Motivazione

Una dashboard legacy con grafici classici, convertita alla griglia, deve
mostrare tutti i widget. Scartata la conversione automatica `chart*` ->
`chart*_v2`: cambia il formato della config e quindi il comportamento visibile.

## Test

Nessun test automatico eseguito. Verificato: lato server tutti i payload si
renderizzano (script di prova in locale); le due viste compilano
(`Blade::compileString` + `php -l`). **Non verificato nel browser**: da provare
sulla dashboard "Test conversione legacy 2" (slug `test-conversione-legacy-2`,
creata in locale ancora in `legacy_areas`).

## Rischi e note

- Aggiunge 2 richieste CDN (Morris, Raphael) alle pagine a griglia, come gia'
  fa il builder legacy.
- Altri script di widget che dipendano da librerie non caricate nelle viste a
  griglia resterebbero rotti allo stesso modo (ora non piu' bloccanti in
  builder, per il try/catch).

## Rollback

Ripristinare i due file blade alla versione precedente (`git checkout` dei
due path). Nessuna migration, nessun dato toccato.
