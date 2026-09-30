# 112 - Dashboard a griglia libera, Fase 4: nuovi widget grafico (ApexCharts)

- **Data**: 2026-09-28
- **Stato**: Completato (scope ridotto: solo linee/barre)
- **Area**: Statistic Builder / Dashboard
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/chartline_v2.blade.php` (nuovo)
  - `resources/views/crudbooster/statistic_builder/components/chartbar_v2.blade.php` (nuovo)

## Contesto

Quinta fase del piano [`docs/piano-dashboard-griglia-libera.md`](../piano-dashboard-griglia-libera.md):
grafici moderni per i widget raggiungibili solo dalla nuova griglia,
senza toccare i widget legacy (Morris.js/Raphael, abbandonati dal ~2014).

## Situazione prima

`chartline.blade.php`/`chartbar.blade.php`/`chartarea.blade.php`
disegnano con Morris.js, supportano multi-serie (colonna `area_name`
separata da `;`), usati sia dal builder legacy sia (indirettamente,
tramite conversione, vedi 109) da dashboard convertite alla griglia —
ma senza Morris caricato nella nuova pagina.

## Situazione dopo

Due nuovi `component_name` (`chartline_v2`/`chartbar_v2`), partial
dedicati che caricano **ApexCharts da CDN** solo al bisogno (nessuno
script statico nel `<head>`, caricato lazy dal primo widget che lo
richiede). Supportano entrambe le modalità (SQL libera e query guidata,
stesso toggle di 111), ma **una sola serie** (colonne `label`/`value`),
a differenza del multi-serie legacy — scelta di scope deliberata.

## Motivazione

Nuovi `component_name` invece di sostituire in-place `chartline`/
`chartbar`: zero rischio per le dashboard legacy esistenti, che
continuano a usare gli stessi partial Morris di sempre.

## Test

Verificato che i due nuovi partial compilano (`php -l`) e seguono lo
stesso contratto `command` (layout/configuration/showFunction) degli
altri widget. Il rendering visivo del grafico ApexCharts stesso **non è
stato verificato in browser** in questa sessione (il test manuale di
111 ha usato solo widget `smallbox` per la verifica end-to-end) — da
fare come prossimo passo prima di considerare questa fase pienamente
verificata.

## Rischi e note

- Multi-serie non supportato (gap noto, vedi sopra).
- Ogni istanza di questi widget richiede/carica ApexCharts
  indipendentemente (con dedup lato client se lo script è già presente/
  in caricamento) — leggermente ridondante con più widget sulla stessa
  dashboard, non ottimizzato in questa fase.
- Nessun widget "Distribuzione"/gauge (vedi 111, rischi).

## Rollback

Rimuovere i due file; togliere le due voci dalla palette in
`builder_grid.blade.php` (già minime, nessun'altra dipendenza).
