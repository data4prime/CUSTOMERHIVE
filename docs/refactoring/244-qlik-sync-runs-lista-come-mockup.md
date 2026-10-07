# 244 - Lista "Sincronizzazioni Qlik" allineata al mockup

- **Data**: 2026-10-07
- **Stato**: Completato (verificato a vista nel browser locale)
- **Area**: Frontend / Qlik
- **File/aree di codice coinvolte**:
  - `resources/views/qlik_sync/runs.blade.php`

## Contesto

La pagina `admin/qlik_apps/sync-runs` era rimasta con lo stile vecchio rispetto
al mockup "Confronto UI CustomerHive" (colonna "Proposta"): tabella a strisce con
bordi, badge di stato quadrati a colore pieno, pulsante di dettaglio `btn-info`.

## Situazione prima

`.box > .box-body` con `table table-striped table-bordered`; stato e barra di
avanzamento con colori esadecimali scritti in linea (`#198754`, `#dc3545`...);
badge con `style` in linea; pulsante dettaglio `btn btn-info btn-sm`.

## Situazione dopo

- Contenitore `table-responsive rel-table` (card arrotondata, intestazione
  "a pillola", righe con separatore e hover: già definito in `ch-ui2.css`).
- Stato come `ch-pill ch-pill-dot ch-pill-{ok|bad|blue|warn|gray}`; stesso stile
  per il badge "annullata/rollback".
- Barra di avanzamento sottile con colore da token (`--ch-success`, `--ch-danger`,
  `--ch-blue`, `--ch-warning`, `--ch-text-muted`) al posto degli hex.
- Pulsante dettaglio `btn btn-sm btn-secondary`; testi secondari in `text-secondary`.
- Nessun CSS nuovo, nessun testo UI nuovo (stesse chiavi `trans()`), nessun
  cambio di logica o di dati.

## Motivazione

Coerenza con lo standard UI (token `var(--ch-*)`, niente colori a mano) e con il
mockup approvato; riuso di componenti già presenti invece di CSS ad hoc.

## Test

Verifica a vista su `localhost:8080/admin/qlik_apps/sync-runs` con i 10 run
esistenti (completati e falliti): aspetto coerente col mockup. Non verificati:
stati `running`/`queued`/`cancelling` (nessun run in quello stato in locale), tema
scuro, pagina di dettaglio.

## Rischi e note

Solo markup/classi. La pagina di dettaglio (`run.blade.php`) ha ancora lo stile
vecchio (badge in linea, `table-striped`): da allineare in un intervento a parte,
se serve.

## Rollback

Ripristinare `resources/views/qlik_sync/runs.blade.php` dalla versione precedente
(git).
