# 140 - Query guidata: campo Metrica separato in Funzione + Colonna

- **Data**: 2026-09-29
- **Stato**: Completato (verificato con script PHP isolato, non in browser su richiesta esplicita dell'utente)
- **Area**: Statistic Builder / Backend + Frontend
- **File/aree di codice coinvolte**:
  - `app/Dashboards/DashboardDatasetRegistry.php`
  - `resources/views/crudbooster/statistic_builder/components/_query_builder_fields.blade.php`

## Contesto

Proposta discussa con l'utente e confermata prima di implementare
(vedi conversazione): la select "Metrica" del pannello Query guidata,
dopo l'estensione di 138 (Somma/Media/Minimo/Massimo per colonna
numerica), elenca ogni combinazione funzione+colonna gia' composta come
voce unica - per `mg_fatture` sono 36 voci ("Conteggio righe", "Somma
Importo", "Media Importo", ..., "Somma Id", "Media Tenant", ...), lista
lunga e con parecchie combinazioni poco sensate (aggregare `id`/`tenant`/
`group`). Con tabelle piu' larghe la lista cresce linearmente col numero
di colonne numeriche × 4.

## Situazione prima

`_query_builder_fields.blade.php`: un'unica select "Metrica"
(`name="config[metric]"`), popolata da `dataset.metrics` -
`DashboardDatasetRegistry::buildMetrics()` genera direttamente le
etichette composte ("Somma Importo") per ogni coppia aggregazione/
colonna.

## Situazione dopo

**Backend** (`DashboardDatasetRegistry.php`), solo additivo:
- `buildMetrics()` salva ora anche `column_label` (etichetta della sola
  colonna, es. "Importo") accanto a `label` (la versione composta, es.
  "Somma Importo") per ogni metrica su colonna numerica - serve a
  costruire la lista "Colonna" senza dover spezzare la label composta
  lato JS.
- `optionsForFrontend()` espone due liste nuove per dataset, derivate
  da `metrics` (che resta invariato, ancora presente in output):
  `metric_functions` (una voce per ogni aggregazione distinta tra le
  metriche del dataset, incluso `count` - per i dataset curati che
  definiscono solo `count` risulta una lista di una sola voce) e
  `metric_columns` (le colonne numeriche disponibili, le stesse per
  qualunque funzione tra Somma/Media/Minimo/Massimo perche'
  `buildMetrics()` le genera tutte e quattro per ogni colonna numerica -
  basta un'unica lista, non serve rifiltrarla per funzione).
- **Il formato di `config->metric` non cambia**: resta `"count"` o
  `"aggregazione:colonna"` (es. `"sum:importo"`), esattamente come prima
  - `execute()` non tocca nulla, zero rischio sui widget gia' salvati.

**Frontend** (`_query_builder_fields.blade.php`):
- La select "Metrica" diventa due select, "Funzione" (5 voci al massimo:
  Conteggio/Somma/Media/Minimo/Massimo) e "Colonna" (solo le colonne
  numeriche del dataset scelto) - "Colonna" resta nascosta quando la
  funzione scelta e' "Conteggio righe" (non ha bisogno di una colonna,
  aggrega sempre sulla chiave primaria, invariato da sempre).
- Un campo nascosto (`input type="hidden" name="config[metric]"`)
  ricompone il valore vero da Funzione+Colonna
  (`composeMetricValue()`), aggiornato a ogni cambio di una delle due
  select - lo stesso identico formato salvato prima della UI a select
  singola, cosi' non serve alcuna migrazione dei widget gia'
  configurati: al caricamento il valore salvato viene scomposto
  (`populateMetricSelects()`, split su `:`) per precompilare le due
  select, simmetrico a `composeMetricValue()`.
- Il pulsante "Prova" (anteprima) usa `composeMetricValue()` al posto
  del vecchio `.val()` sulla select singola, stesso endpoint invariato.

## Motivazione

Riduce una lista di N×4+1 voci (spesso con combinazioni poco
significative tipo "Somma Id") a due select brevi e indipendenti (max 5
+ N voci), piu' leggibile e scalabile su tabelle con molte colonne
numeriche - zero cambi al formato di salvataggio/esecuzione, quindi zero
rischio sui widget gia' configurati (comportamento server-side
identico, solo la UI di scelta e' cambiata).

## Test

Non verificato in browser (regola esplicita dell'utente, vedi
`feedback_no_autonomous_browser_testing`). Verificato con uno script PHP
isolato (bootstrap Laravel, eseguito e poi rimosso) contro il DB di
sviluppo reale:
- `mg_fatture`: 5 funzioni (count/sum/avg/min/max) e 9 colonne numeriche
  elencate correttamente;
- `admin_users` (dataset curato, solo `count`): 1 sola funzione, colonne
  vuote - come atteso;
- `execute()` con `metric: "sum:importo"` (formato composto, come lo
  produce il campo nascosto) → **41.305**, uguale al valore gia'
  verificato in 138 con gli stessi dati - conferma che il formato di
  esecuzione non e' cambiato;
- `execute()` con `metric: "count"` → **27** (totale fatture di test),
  corretto.

`php -l` su entrambi i file modificati. Da confermare visivamente
dall'utente: aprire un widget KPI in Query guidata, verificare che
scegliendo "Conteggio righe" il campo Colonna sparisca, che scegliendo
un'altra funzione compaia con le sole colonne numeriche, e che un
widget gia' configurato con una metrica composta (es. "Somma Importo")
riapra le due select gia' precompilate correttamente.

## Rischi e note

Nessuno noto - cambio isolato alla sola UI di selezione, nessun impatto
sul formato dati o sull'esecuzione della query.

## Rollback

`git diff` di `DashboardDatasetRegistry.php` e
`_query_builder_fields.blade.php` per tornare alla select "Metrica"
singola con le combinazioni gia' composte.
