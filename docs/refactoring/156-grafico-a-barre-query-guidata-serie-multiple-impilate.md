# 156 - Grafico a barre: Query guidata, colore, piu' serie e barre impilate

- **Data**: 2026-09-29
- **Stato**: Completato
- **Area**: Statistic Builder / Backend + Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/chartbar_v2.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/chartline_v2.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/_multi_series_config.blade.php` (nuovo)
  - `app/Dashboards/ChartSeriesBuilder.php` (nuovo)
  - `resources/lang/en/crudbooster.php`, `resources/lang/it/crudbooster.php`

## Contesto

Richiesta diretta dell'utente dopo 152/154/155 sul Grafico a linee: portare
sulle barre le stesse possibilita' (in particolare il confronto tra anni,
una serie per anno), piu' l'opzione di barre impilate, ed estrarre in un
partial condiviso il blocco "serie extra" invece di copiarlo.

## Situazione prima

`chartbar_v2` aveva solo SQL libera (textarea `label`/`value`), colore fisso
`#3B5BDB`, una sola serie. Il blocco "Altre linee" e tutto l'algoritmo di
unione label/serie di `showFunction` vivevano dentro `chartline_v2`. Il
ramo `config->lines` di `renderComponentPayload()` era gia' generico
(non legato a `chartline_v2`).

## Situazione dopo

- `chartbar_v2`: Query guidata + SQL libera nel popup condiviso
  `_source_config` (un widget salvato con la sola `sql`, senza `mode`, si
  riapre in modalita' SQL con la query intatta), colore della prima serie,
  fino a 4 serie extra, checkbox "Barre impilate" (`config[stacked]`,
  `chart.stacked` di ApexCharts). Con piu' serie le barre sono affiancate.
- `_multi_series_config.blade.php`: blocco serie extra estratto da
  `chartline_v2` (markup + JS "Aggiungi/Rimuovi" + filtro degli slot vuoti
  di 155), con testi da `trans()` EN/IT parametrizzati sul sostantivo
  ("linea/linee" per le linee, "serie" per le barre).
- `ChartSeriesBuilder::build()`: normalizzazione del valore del widget
  (SQL grezza / righe piatte / `{name,color,rows}`), unione label, palette,
  estratta da `chartline_v2` e riusata da entrambi. Nessuna modifica al
  controller ne' al salvataggio.
- Nelle barre i valori numerici (stringhe da SUM MySQL) sono castati a
  numero prima di passarli ad ApexCharts (sicuro per lo stacking); nelle
  linee resta com'era.

## Motivazione

Stessa esperienza di configurazione su linee e barre, un solo punto dove
vive la logica di serie invece di due copie da tenere allineate. Scartato
per ora: `chartarea_v2` (stessi limiti, da trattare a parte).

## Test

- Snapshot dell'HTML renderizzato di tutti i grafici salvati nel DB locale
  (`chartline`, `chartbar`, `chartline_v2` id 29) prima e dopo il refactor:
  identici byte per byte (nessun `chartbar_v2` salvato in locale).
- Render di `chartbar_v2` sintetici (non salvati): SQL legacy senza `mode`
  (1 serie, colore di default invariato), builder con 2 serie affiancate e
  impilate su `mg_fatture` 2025 vs 2026 con "Mese dell'anno" (154): label
  `01..12` condivise, `stacked` true/false corretto.
- Render del pannello di configurazione di entrambi i widget (IT/EN):
  1 blocco visibile su 4 con 1 serie extra compilata e 1 slot vuoto.
- Non testato in browser (JS "Aggiungi/Rimuovi", popup, rendering
  ApexCharts). Suite di test non lanciata.

## Rischi e note

- Il partial e l'helper sono comuni: un errore li' tocca linee e barre.
  Mitigato dallo snapshot identico del widget linee esistente.
- Nelle linee cambiano solo i testi del pannello: ora passano da `trans()`
  (EN/IT); in italiano restano le stesse parole ("Altre linee (opzionale)",
  "Linea 2", "+ Aggiungi linea"), il testo d'aiuto e' stato leggermente
  esteso (menziona il confronto tra anni).
- "Barre impilate" ha effetto solo con piu' di una serie.
- Label del pannello ancora hardcoded italiano dove gia' lo erano
  (placeholder `_empty_widget_state`, "Sorgente dati", ecc.).

## Rollback

Ripristinare `chartbar_v2.blade.php` e `chartline_v2.blade.php`, eliminare
`_multi_series_config.blade.php` e `ChartSeriesBuilder.php`. Nessuna
migrazione. Widget barre salvati con `lines`/`stacked` perderebbero le
serie extra.
