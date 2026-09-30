# 149 - Fix: campo Dataset del popup rotto/non ricercabile (CSS select2 mancante)

- **Data**: 2026-09-29
- **Stato**: Completato e verificato ispezionando il sorgente compilato, non in browser su richiesta esplicita dell'utente
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/_query_builder_fields.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/smallbox.blade.php`

## Contesto

Segnalato dall'utente subito dopo 148: nel popup "Sorgente dati" il
campo Dataset "si vede due volte e non è ricercabile".

## Situazione prima

Il foglio di stile di select2
(`vendor/crudbooster/assets/select2/dist/css/select2.min.css`) veniva
caricato con un `<link>` scritto a mano solo dentro
`smallbox.blade.php`, per il picker dell'icona Lucide (`#smallbox-icon-select`)
- un dettaglio specifico del widget KPI, non del pannello Query guidata.
`_query_builder_fields.blade.php` inizializza pero' un SECONDO select2
(sul campo Dataset) senza mai caricare quel CSS lui stesso: per
`smallbox.blade.php` funzionava comunque (il link c'era, messo li' per
un altro motivo), ma per `chartline_v2.blade.php` (che ha ricevuto la
Query guidata in 147 e non ha mai avuto un'icona da scegliere, quindi
nessun motivo per avere quel link) il Dataset si inizializzava via JS
come select2 ma **senza stile**: privo di CSS, select2 renderizza un
contenitore non formattato accanto/sopra la select nativa nascosta,
dando l'impressione di un campo duplicato e rotto (niente ricerca
funzionante in pratica).

## Situazione dopo

Il `<link>` di select2 si sposta dentro `_query_builder_fields.blade.php`
stesso (dove il select2 del Dataset viene davvero creato), cosi' ogni
widget che include quel partial lo carica esattamente una volta a
prescindere da cos'altro include. Rimosso il `<link>` duplicato da
`smallbox.blade.php` (restava comunque valido per l'icona, ma ridondante
ora che la pagina lo carica gia' tramite `_query_builder_fields.blade.php`,
incluso da `_source_config.blade.php` poco sopra nello stesso form).

Confermato anche (nessun cambio necessario, gia' cosi'): il Dataset resta
ordinato alfabeticamente lato server
(`DashboardDatasetRegistry::optionsForFrontend()`, `usort` per label).

## Motivazione

Fix del sintomo esatto segnalato, spostando la dipendenza CSS nel file
che la usa davvero invece che in un file widget-specifico che capitava
solo per un altro motivo di caricarla anche lui.

## Test

Non verificato in browser (regola esplicita dell'utente, vedi
`feedback_no_autonomous_browser_testing`). Verificato ispezionando il
sorgente COMPILATO (script isolato, bootstrap Laravel, eseguito e poi
rimosso): `select2.min.css` compare esattamente una volta, nel file
compilato di `_query_builder_fields.blade.php` - assente dal compilato
di `smallbox.blade.php` (rimosso correttamente) ma comunque presente sulla
pagina finale tramite l'include (`_source_config` → `_query_builder_fields`),
per entrambi i widget.

## Rischi e note

Nessuno noto - `chartbar_v2.blade.php` (se in futuro ricevesse lo stesso
trattamento di 147/148) erediterebbe automaticamente il CSS corretto
tramite lo stesso include, senza bisogno di ricordarsene di nuovo.

## Rollback

`git diff` di `_query_builder_fields.blade.php` e `smallbox.blade.php`
per tornare al link duplicato solo nel KPI (e mancante nel grafico).
