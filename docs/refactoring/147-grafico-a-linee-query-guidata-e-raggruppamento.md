# 147 - Grafico a linee: sezione di configurazione completa (Query guidata + raggruppamento)

- **Data**: 2026-09-29
- **Stato**: Completato e verificato ispezionando il sorgente compilato, non in browser su richiesta esplicita dell'utente
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/_query_builder_fields.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/chartline_v2.blade.php`

## Contesto

Discusso con l'utente (proposta condivisa e confermata prima di
implementare): il widget "Grafico a linee" (`chartline_v2`) aveva una
sezione di configurazione ridotta al minimo - solo Nome e una textarea
SQL libera, niente Query guidata, niente filtri, niente pulsante
"Prova", a differenza dell'Indicatore KPI che nel frattempo (132-146) ha
accumulato tutta questa infrastruttura.

## Situazione prima

`chartline_v2.blade.php`, comando `configuration`: solo `config[name]` e
`config[sql]` (testo libero). `_query_builder_fields.blade.php` (Dataset/
Funzione+Colonna/Filtri/Prova, condiviso "di intenzione" fin dal 2023 -
il commento in testa lo definiva gia' "condiviso dai widget che
interrogano dati (smallbox, table, chartline_v2, chartbar_v2)") era in
pratica incluso solo da `smallbox.blade.php`. Il raggruppamento
(`group_by`) era gia' supportato per intero lato server
(`DashboardDatasetRegistry::execute()` lo accetta e restituisce righe
multiple; `renderComponentPayload()` lo passa gia' cosi' com'e' da
`config->group_by`) ma senza alcuna UI per impostarlo - rimosso
apposta in 133 perche' il solo Indicatore KPI (un valore, non una
serie) non ne aveva bisogno.

## Situazione dopo

- `_query_builder_fields.blade.php` accetta ora una variabile opzionale
  `$groupBySupported` (default `false`): se vera, mostra una select
  "Raggruppa per (opzionale)" popolata dalle dimensioni del dataset
  scelto (`dataset.dimensions`, gia' esposte da
  `DashboardDatasetRegistry::optionsForFrontend()`), con `name="config[group_by]"`
  diretto (nessuna composizione come per Funzione+Colonna, il valore e'
  gia' nel formato che `execute()` si aspetta). Il valore selezionato
  entra anche nell'anteprima "Prova" (`dataset-preview`). Per
  l'Indicatore KPI (che non passa `$groupBySupported`) il markup/JS
  della select restano del tutto assenti dal DOM - non solo nascosti -
  quindi nessun `config[group_by]` finisce mai nel suo salvataggio.
- `chartline_v2.blade.php`, comando `configuration`, riscritto per
  includere `_query_mode_toggle.blade.php` (toggle SQL libera/Query
  guidata) e `_query_builder_fields.blade.php` con
  `groupBySupported: true`, avvolgendo il proprio campo SQL in
  `<div id="ch-panel-sql-{{ $componentID }}">` con pulsante "Prova"
  (stesso pattern di `smallbox.blade.php`) - nessun cambio lato
  controller: `renderComponentPayload()` e `DashboardDatasetRegistry`
  sono gia' generici per qualunque widget, il ramo `showFunction` di
  `chartline_v2` gestiva gia' l'input array (righe multiple) prodotto
  dalla modalita' builder.
- Aggiunto anche un campo opzionale "Colore linea"
  (`config[color]`, default `#3B5BDB` come prima), usato ora al posto
  del colore hardcoded nella configurazione di ApexCharts.

## Motivazione

Porta il Grafico a linee alla pari con l'Indicatore KPI (stessa
Query guidata, stessi filtri con operatori tipizzati, stessa anteprima)
riusando l'infrastruttura gia' pronta invece di duplicarla - l'unico
pezzo mancante (il raggruppamento) era gia' pronto lato server, serviva
solo la UI.

## Test

Non verificato in browser (regola esplicita dell'utente, vedi
`feedback_no_autonomous_browser_testing`). Verificato:
- `php -l` su entrambi i file modificati;
- render diretto (script isolato, bootstrap Laravel, eseguito e poi
  rimosso) del comando `showFunction` di `chartline_v2` con righe multiple
  (simulando l'output di `execute()` in modalita' raggruppata) e un
  colore custom - nessun errore, colore applicato;
- ispezione del sorgente COMPILATO (dato quanto successo in 144 con un
  commento Blade mal chiuso, non ci si e' fidati del solo `php -l`):
  confermato che `_query_builder_fields.blade.php` genera la select
  "Raggruppa per" solo dentro `if($groupBySupported):`, che
  `chartline_v2` la passa (`compact('componentID','config','groupBySupported')`),
  e che `smallbox` (che non la passa) non ha alcun riferimento a
  `ch-builder-group-by` nel proprio output compilato - zero impatto sul
  widget KPI.

## Rischi e note

`chartbar_v2.blade.php` (Grafico a barre) ha lo stesso identico gap
(solo Nome + SQL libera) e potrebbe ricevere lo stesso trattamento in un
intervento futuro - non toccato qui, l'utente ha chiesto esplicitamente
solo il Grafico a linee.

## Rollback

`git diff` di `_query_builder_fields.blade.php` e
`chartline_v2.blade.php` per tornare alla configurazione minima
(solo Nome + SQL libera).
