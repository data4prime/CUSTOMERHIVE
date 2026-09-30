# 157 - Grafico ad area: Query guidata, colore, piu' serie e aree impilate

- **Data**: 2026-09-29
- **Stato**: Completato
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/chartarea_v2.blade.php`
  - `resources/lang/en/crudbooster.php`, `resources/lang/it/crudbooster.php`

## Contesto

Seguito diretto di 156 (Grafico a barre): richiesta dell'utente di portare
sul Grafico ad area le stesse possibilita', con entrambe le modalita'
multi-serie (sovrapposte e impilate).

## Situazione prima

`chartarea_v2` era nello stesso stato di `chartbar_v2` prima di 156: solo
SQL libera, colore fisso `#3B5BDB`, una sola serie, testi del pannello
hardcoded in italiano.

## Situazione dopo

Riusa quanto introdotto in 156, senza codice nuovo condiviso: popup
`_source_config` (Query guidata + SQL libera; un widget salvato con la
sola SQL si riapre in modalita' SQL con la query intatta), colore della
prima serie, serie extra via `_multi_series_config`, normalizzazione dati
via `ChartSeriesBuilder`. Con piu' serie le aree sono sovrapposte
(default, utile per confronti es. anno su anno, il gradiente esistente ha
gia' opacita' ridotta); la casella "Aree impilate" (`config[stacked]`,
`chart.stacked`) le impila (utile per le parti di un totale). Due chiavi
lang nuove (EN/IT) per etichetta e aiuto della casella.

## Motivazione

Stessa esperienza di configurazione su linee, barre e aree, senza nuova
logica duplicata.

## Test

Widget sintetici (non salvati) su `mg_fatture` 2025 vs 2026 con "Mese
dell'anno" (154): SQL legacy senza `mode` (1 serie, colore di default
invariato), 2 serie sovrapposte, 2 serie impilate - label `01..12`
condivise, `stacked` corretto, valori numerici. Pannello di configurazione
renderizzato IT/EN: 1 blocco serie visibile su 4, nessun placeholder
`:noun` non sostituito. Nessun `chartarea_v2` salvato nel DB locale, quindi
nessuno snapshot di confronto. Non testato in browser (resa visiva della
sovrapposizione, JS del pannello). Suite di test non lanciata.

## Rischi e note

- Con aree sovrapposte, quelle davanti possono coprire quelle dietro (il
  gradiente e' trasparente ma da verificare a occhio con dati reali).
- Mesi senza dati a 0 (comportamento esistente): la serie 2026 scende a 0
  a fine anno, piu' evidente in un'area piena che in una linea.
- "Aree impilate" ha effetto solo con piu' di una serie.

## Rollback

Ripristinare `chartarea_v2.blade.php` e le due chiavi lang. Widget salvati
con `lines`/`stacked` perderebbero le serie extra. Nessuna migrazione.
