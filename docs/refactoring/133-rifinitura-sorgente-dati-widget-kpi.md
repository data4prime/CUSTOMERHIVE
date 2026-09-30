# 133 - Rifinitura pannello Sorgente dati del widget KPI

- **Data**: 2026-09-28
- **Stato**: Completato e verificato in browser
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `app/Dashboards/DashboardDatasetRegistry.php`
  - `app/Http/Controllers/System/StatisticBuilderController.php`
  - `resources/views/crudbooster/statistic_builder/components/smallbox.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/_query_builder_fields.blade.php`

## Contesto

Lista di rifiniture puntuali richieste dall'utente dopo aver usato il
popup "Sorgente dati" introdotto in [132](132-widget-kpi-sezioni-apribili-e-popup-sorgente.md).

## Situazione prima → dopo

- **Dataset**: select nativa nell'ordine "curati poi tabelle" di
  `DashboardDatasetRegistry::all()`, non ricercabile → **ordinata
  alfabeticamente** (`optionsForFrontend()`, `usort` per label) e
  **ricercabile** (select2, stessa libreria gia' usata per l'icona).
  Nuovo helper condiviso `window.__chEnsureSelect2()` (era duplicato
  identico nello script dell'icona): chi lo chiama per primo carica
  select2 una sola volta, l'altro aspetta lo stesso caricamento invece di
  iniettare un secondo `<script src>` in corsa.
- **Metrica**: generava sum/avg/min/max per ogni colonna numerica/data →
  **solo "Conteggio righe"** per adesso (`buildMetrics()` semplificata,
  rimossa la costante `NUMERIC_TYPES` diventata inutilizzata).
- **Raggruppa per**: rimosso dalla UI (`_query_builder_fields.blade.php`)
  - il backend (`dimensions`, `execute()`) resta invariato e riusabile se
    serve reintrodurlo, semplicemente non piu' raggiungibile da nessun
    campo del form.
- **"Prova" anche su SQL libera**: prima il pulsante esisteva solo nel
  pannello Query guidata. Aggiunto un secondo pulsante nel pannello SQL
  libera, con un nuovo endpoint dedicato
  `StatisticBuilderController::postSqlPreview()` (superadmin-only, stessa
  sostituzione delle sessioni e stessa lettura "prima riga, primo valore"
  gia' usate al render vero in `showFunction` - nessun rischio nuovo:
  chi puo' salvare quella SQL la fa gia' girare ad ogni caricamento del
  widget, un pulsante "prova adesso" non apre nulla che non fosse gia'
  accessibile).
- **Campo SQL**: etichetta "Count (SQL QUERY)" → **"Query"**, tolto il
  testo di aiuto sotto la textarea.
- **Riepilogo "Sorgente dati"**: prima una riga unica ("Modalità attuale:
  **X** — Tabella: Y", il nome tabella non in grassetto e con "Tabella:"
  duplicato perche' la label generata gia' inizia per "Tabella: ") → **due
  righe separate**, `Modalità attuale: **X**` e `Tabella: **Y**` (senza
  duplicare "Tabella:", tolto con una regex sulla label prima di
  mostrarla), entrambe in grassetto sul valore. La riga "Tabella" compare
  solo se un dataset e' stato davvero selezionato (prima compariva anche
  col placeholder "-- seleziona --").
- **Pulsante "Configura sorgente"**: centrato orizzontalmente, con un
  margine sotto prima del pulsante "Salva" generale.

Verificato in browser (Docker locale, dashboard "test2"): dataset in
ordine alfabetico con casella di ricerca (verificato che `.select2('open')`
apra un elenco che parte con "Attività di sistema", poi "Tabella:
Contratti", "Tabella: Dashboard layouts", "Tabella: Fatture"...); Metrica
con solo "Conteggio righe"; "Raggruppa per" assente; "Prova" su SQL libera
con `select count(*) as c from mg_contratti` → **Risultato 1** (uguale a
`SELECT COUNT(*) FROM mg_contratti`); "Prova" su Query guidata con
dataset "Tabella: Fatture" → **Conteggio righe 1** (uguale a
`SELECT COUNT(*) FROM mg_fatture`); riepilogo aggiornato correttamente su
due righe in grassetto; pulsante "Configura sorgente" centrato.

## Motivazione

Vedi richiesta utente: dataset piu' semplici da trovare in un elenco che
cresce con lo schema reale (131), interfaccia piu' snella (una sola
metrica per adesso, niente raggruppamento), simmetria tra le due
modalita' (Prova disponibile in entrambe), e un riepilogo piu' leggibile
nella sidebar.

## Test

Verifica end-to-end in browser con dati reali, non solo lettura del
codice - vedi dettaglio sopra. Alcune interazioni (apertura dei menu a
tendina select2 via click) non sono risultate azionabili dall'automazione
browser usata per il test (limite dello strumento, non del codice:
`$(...).select2('open')` funziona correttamente, e la stessa select gia'
in produzione risponde al click reale di un utente); verificate quindi
programmaticamente via console del browser dove il click automatizzato
non bastava. Non eseguita la suite di test automatici (non richiesta
esplicitamente).

## Rischi e note

- `postSqlPreview()` esegue `DB::select()` sulla stringa SQL ricevuta
  cosi' com'e', stessa fiducia gia' accordata al campo `config[sql]`
  esistente (superadmin-only, gia' eseguito ad ogni caricamento del
  widget) - nessuna nuova superficie di rischio, solo un modo piu' comodo
  di testare la stessa cosa prima di salvare.
- Il helper `window.__chEnsureSelect2` e' condiviso ma definito dentro
  `_query_builder_fields.blade.php`: se in futuro un widget include
  quella select senza mai includere questo partial, l'helper non esiste
  e va copiato o estratto altrove.

## Rollback

`git diff` dei 4 file elencati sopra. Nessuna migrazione dati coinvolta:
`config[dataset]`/`config[metric]` gia' salvati restano validi (i valori
"count"/"table:..." non sono cambiati, solo l'elenco di opzioni proposto
nel form).
