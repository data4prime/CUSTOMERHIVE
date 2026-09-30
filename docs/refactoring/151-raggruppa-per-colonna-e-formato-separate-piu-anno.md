# 151 - "Raggruppa per" separato in Colonna + Formato, aggiunta granularità Anno

- **Data**: 2026-09-29
- **Stato**: Completato e verificato con script PHP isolato + ispezione del sorgente compilato, non in browser su richiesta esplicita dell'utente
- **Area**: Statistic Builder / Backend + Frontend
- **File/aree di codice coinvolte**:
  - `app/Dashboards/DashboardDatasetRegistry.php`
  - `resources/views/crudbooster/statistic_builder/components/_query_builder_fields.blade.php`

## Contesto

Proposta discussa con l'utente e confermata prima di implementare
(stessa idea di 140, applicata al raggruppamento invece che alla
metrica): la select "Raggruppa per" (147) elenca ogni colonna data due
volte gia' composta ("Creato il (mese)", "Creato il (giorno)") - con piu'
colonne data nello stesso dataset la lista si affolla. In piu' mancava
"Anno" come granularita' (solo mese/giorno).

## Situazione prima

`buildDimensions()` genera per ogni colonna data due voci gia' composte
(`{col}:month`, `{col}:day`); la select "Raggruppa per" le mostra tutte
appiattite insieme alle colonne non-data (una voce sola ciascuna).
`execute()` gestisce solo i modificatori `month`/`day` nella
`DATE_FORMAT()`.

## Situazione dopo

**Backend** (`DashboardDatasetRegistry.php`), solo additivo:
- Nuova costante `DATE_DIMENSION_FORMATS` (day/month/year - "year" e'
  la novita' vera), usata sia da `buildDimensions()` (ora genera tre
  voci per colonna data invece di due) sia da `execute()`
  (`DATE_FORMAT($col, '%Y')` per il nuovo caso `'year'`).
- `optionsForFrontend()` espone due liste nuove per dataset,
  `dimension_columns` (una voce per colonna reale, con `type: 'date'`
  o `'text'` - le colonne data compaiono una sola volta anche se in
  `dimensions` hanno tre chiavi) e `dimension_formats` (le tre
  granularita', identiche per qualunque dataset). **Il formato di
  `config->group_by` non cambia**: resta `"colonna"` o
  `"colonna:formato"` esattamente come prima - zero rischio sui widget
  gia' configurati.

**Frontend** (`_query_builder_fields.blade.php`):
- "Raggruppa per" diventa due select, "Colonna" (tutte le colonne
  disponibili) e "Formato" (Giorno/Mese/Anno), quest'ultima visibile
  solo se la colonna scelta e' di tipo data (stesso meccanismo
  show/hide di Funzione+Colonna in 140). Un campo nascosto ricompone il
  valore vero (`composeGroupByValue()`), scomposto al caricamento
  (`populateGroupBySelects()`) per precompilare le due select dai
  widget gia' salvati - stessa idea di `composeMetricValue()`/
  `populateMetricSelects()`. Una colonna data appena scelta (nessun
  formato gia' salvato) parte da "Mese" come default, non dalla prima
  voce della lista.
- `populateSelect()` generalizzato per impostare anche `data-type`
  sulle opzioni quando presente (prima lo faceva solo
  `populateFilterColumnSelect()` con un ciclo scritto a mano,
  semplificato di conseguenza per riusare l'helper comune).

## Motivazione

Stessa motivazione di 140: lista piu' breve e leggibile, scalabile con
piu' colonne data nello stesso dataset, zero rischio sui dati gia'
salvati (formato di storage invariato). "Anno" era una granularita'
mancante e utile su un intervallo di dati lungo (i contratti/fatture di
test coprono gia' oltre un anno).

## Test

Non verificato in browser (regola esplicita dell'utente, vedi
`feedback_no_autonomous_browser_testing`). Verificato:
- script PHP isolato (bootstrap Laravel, eseguito e poi rimosso):
  `dimension_columns` per `mg_fatture` mostra 14 colonne una sola volta
  ciascuna (`created_at`/`updated_at`/`deleted_at` con `type: 'date'`,
  le altre `text`); `dimension_formats` mostra esattamente Giorno/Mese/
  Anno; `execute()` con `group_by: 'created_at:year'` (formato composto,
  come lo produce il campo nascosto) → **2025: 5.000, 2026: 36.305**
  (somma corretta, 5000+36305=41305, uguale al totale gia' verificato
  in 138);
- ispezione del sorgente COMPILATO (dato quanto successo in 144, non ci
  si fida del solo `php -l`): confermate le funzioni
  `composeGroupByValue()`/`populateGroupBySelects()` e il collegamento
  a `dataset.dimension_columns`/`dataset.dimension_formats`.

## Rischi e note

Nessuno noto - stesso pattern gia' validato in 140, stesso formato di
salvataggio.

## Rollback

`git diff` di `DashboardDatasetRegistry.php` e
`_query_builder_fields.blade.php` per tornare alla select "Raggruppa
per" singola con Mese/Giorno gia' composti (niente Anno).
