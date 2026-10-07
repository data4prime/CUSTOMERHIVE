# 245 - Dettaglio "Sincronizzazione Qlik n. N" allineato al mockup

- **Data**: 2026-10-07
- **Stato**: Completato (verificato a vista nel browser locale)
- **Area**: Frontend / Qlik
- **File/aree di codice coinvolte**:
  - `resources/views/qlik_sync/run.blade.php`

## Contesto

Seguito di [244](244-qlik-sync-runs-lista-come-mockup.md): la lista era stata
allineata al mockup "Confronto UI CustomerHive", la pagina di dettaglio di un run
(`admin/qlik_apps/sync-runs/{id}`) no.

## Situazione prima

Intestazione della card con badge a colore pieno in linea, elenco `dl` con
etichette in grassetto, barra di avanzamento con hex in linea, link "Torna alle
sincronizzazioni" come testo sottolineato, tabella dei record a strisce con bordi
dentro un `.box`.

## Situazione dopo

- Intestazione card: tipo + pillola di stato (`ch-pill ch-pill-dot`, anche per
  "annullata/rollback").
- Dati del run in righe etichetta (maiuscolo, grigio) / valore, con separatore;
  barra di avanzamento da token (`--ch-*`).
- Link di ritorno come pulsante `btn btn-secondary btn-sm`.
- Titolo "Record" sopra una tabella `rel-table` (come la lista, 244); azioni colorate
  con token, Qlik ID in monospazio grigio.
- Invariati: logica, controller, pulsanti annulla/rollback, dialog di conferma,
  ricarica automatica con run attivo. Nessun CSS né testo UI nuovo.

## Motivazione

Coerenza con lo standard UI (token `var(--ch-*)`, componenti già presenti) e con
il mockup.

## Test

Verifica a vista sul run 36 (completato, un record "Invariati"). Non verificati:
run attivo (pulsante Annulla), run con rollback possibile (pulsante Ripristina e
dialog), run con errore (`alert-danger`), tema scuro.

## Rischi e note

Solo markup/classi. I dialog `qs-cancel` e `qs-rollback` hanno ancora stile in
linea: fuori da questo intervento.

## Rollback

Ripristinare `resources/views/qlik_sync/run.blade.php` dalla versione precedente (git).
