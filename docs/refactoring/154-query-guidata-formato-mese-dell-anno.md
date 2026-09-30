# 154 - Query guidata: formato "Mese dell'anno" per confrontare anni

- **Data**: 2026-09-29
- **Stato**: Completato
- **Area**: Statistic Builder / Backend
- **File/aree di codice coinvolte**:
  - `app/Dashboards/DashboardDatasetRegistry.php`
  - `resources/lang/en/crudbooster.php`, `resources/lang/it/crudbooster.php`

## Contesto

Richiesta diretta dell'utente: un Grafico a linee (widget 18, componente
`chartline_v2`) con due linee, una per i mesi del 2025 e una per i mesi del
2026, sovrapposte sugli stessi mesi. Il widget unisce le label distinte tra
le linee (`chartline_v2.blade.php`), quindi per sovrapporle le label devono
coincidere. Vedi anche 152 (piu' linee) e 153 (operatori sulle date).

## Situazione prima

Il "Raggruppa per" di una colonna data offriva solo Giorno / Mese / Anno
(`DATE_DIMENSION_FORMATS`), tutti con label che includono l'anno
(`DATE_FORMAT(col, '%Y-%m')` per "Mese"). Filtrando un anno diverso per
linea, le label restavano `2026-01..2026-12` contro `2025-01..2025-12`: 24
categorie, ogni linea a 0 dove manca l'altra. L'unica strada era la SQL
libera. La label "Mese" era inoltre fuorviante (in realta' anno-mese).

## Situazione dopo

- Nuovo formato `month_of_year` ("Mese dell'anno"): label `01..12`
  (`DATE_FORMAT(col, '%m')`), uguale per tutti gli anni. Config salvato:
  `group_by = "created_at:month_of_year"`.
- "Mese" rinominato "Anno-mese" (solo etichetta: il valore salvato resta
  `month`). Etichette dei formati ora via `trans()` EN/IT.
- Aggiunto anche ai due dataset curati (`admin_users`, `system_activity`),
  che elencano le dimensioni a mano.

## Motivazione

Permette di fare da Query guidata il confronto tra anni (una linea per
anno, ciascuna con filtro data sull'anno e raggruppamento "Mese dell'anno").
Scartati "Mese-anno"/"Anno-mese" come nuove voci: contengono comunque l'anno,
non risolvono il problema.

## Test

Verifica mirata con `php -l` e rigenerazione del widget 18 in Docker (vedi
sotto per l'esito). Suite di test non lanciata (solo su richiesta).

## Rischi e note

- Additivo: `day`/`month`/`year` producono le stesse label di prima, i widget
  gia' salvati non cambiano. Visibile solo il testo "Mese" -> "Anno-mese".
- I mesi senza dati in una linea vengono disegnati a 0 (comportamento
  esistente di `chartline_v2`, non toccato).
- Le label sono `01..12` numeriche, non nomi dei mesi.

## Rollback

Ripristinare `DashboardDatasetRegistry.php` e le due chiavi lang; nessuna
migrazione. Eventuali widget salvati con `month_of_year` smetterebbero di
funzionare (dimensione fuori whitelist).
