# 145 - Widget Indicatore KPI: popup "Sorgente dati" più largo e colonna Operatore a larghezza fissa

- **Data**: 2026-09-29
- **Stato**: Completato e verificato con render diretto della vista, non in browser su richiesta esplicita dell'utente
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/smallbox.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/_query_builder_fields.blade.php`

## Contesto

Richiesta diretta dell'utente: nel popup "Sorgente dati", opzioni
dell'operatore come "negli ultimi N giorni" (introdotto in 138) non si
leggevano per intero.

## Situazione prima

- Popup allargato in 139 da 420px a 560px, ma la select Operatore
  (`.ch-builder-filter-operator`) aveva `flex: 1; min-width: 64px;` -
  divideva lo spazio in parti uguali con Colonna e Valore (`flex: 2`
  ciascuno), restando comunque la piu' stretta delle tre anche
  allargando il popup: allargare solo il popup non sarebbe bastato,
  Colonna e Valore si sarebbero presi la maggior parte dello spazio in
  piu'.

## Situazione dopo

- `.ch-source-modal`: 560px → 640px.
- `.ch-builder-filter-operator`: `flex: 0 0 160px` (larghezza fissa,
  non si allarga ne' si restringe) invece di `flex: 1; min-width: 64px`
  - garantisce spazio sufficiente per "negli ultimi N giorni"
  indipendentemente da quanto sono larghi Colonna/Valore.
- Colonna e Valore passano da `flex: 2` a `flex: 1` (si dividono lo
  spazio restante a meta') con `min-width: 0` esplicito (necessario
  perche' un elemento flex di default non si restringe sotto la
  larghezza del proprio contenuto - senza, una `<select>` con opzioni
  lunghe come "-- seleziona colonna --" avrebbe potuto far traboccare la
  riga anziche' adattarsi allo spazio rimasto).

## Motivazione

Fix mirato al sintomo esatto: la larghezza fissa sull'operatore risolve
il problema indipendentemente da quanto si allarga il popup in futuro
(non richiede piu' ritocchi accoppiati ogni volta che cambia la
larghezza del modale).

## Test

Non verificato in browser (regola esplicita dell'utente, vedi
`feedback_no_autonomous_browser_testing`) - **ma verificato stavolta
con un render diretto della vista** (non solo `php -l`, che nell'ultimo
intervento — 144 — non aveva rilevato il commento Blade mal chiuso):
`php artisan view:clear` + rendering di `smallbox.blade.php` per i
comandi `layout` e `showFunction` (nessun errore, HTML prodotto), piu'
ispezione diretta del file compilato per confermare che tutti e tre i
rami `if/elseif/elseif` (incluso `configuration`, dove vive il popup)
sono presenti e nell'ordine giusto.

## Rischi e note

Nessuno noto.

## Rollback

`git diff` di `smallbox.blade.php` e `_query_builder_fields.blade.php`
per tornare a popup 560px e colonna Operatore a `flex: 1`.
