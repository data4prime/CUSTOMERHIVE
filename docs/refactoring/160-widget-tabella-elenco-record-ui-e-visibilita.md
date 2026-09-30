# 160 - Widget Tabella "Elenco record": configurazione e visibilità (Fasi 3 e 4)

- **Data**: 2026-09-30
- **Stato**: Completato (verifica lato server e di rendering; non provato in browser)
- **Area**: Statistic Builder / Sicurezza
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/table.blade.php`
  - `.../components/_records_list_fields.blade.php` (nuovo)
  - `.../components/_source_config.blade.php`, `_query_mode_toggle.blade.php` (parametri opzionali)
  - `app/Http/Controllers/System/StatisticBuilderController.php`
  - `app/Dashboards/DashboardDatasetRegistry.php`, `DatasetAccessScope.php`
  - `resources/lang/{en,it}/crudbooster.php` (chiavi `statistic_builder_records_*`)
  - piano: `docs/piano-widget-tabella-elenco-record.md`; nucleo: 158, 159

## Contesto

Fasi 3 (configurazione guidata) e 4 (il widget sparisce a chi non ha
accesso) del piano. Il nucleo di sicurezza (158, 159) era già stato rivisto.

## Situazione prima

`table.blade.php` aveva solo la SQL libera, nel form diretto, senza il
popup "Sorgente dati" usato dagli altri widget. Nota: il piano dava per
esistente una "Query guidata a 2 colonne" nel widget Tabella; nella UI non
esisteva (il template sapeva solo renderizzare righe `{label, value}` se
ricevute, ma nessun pannello le configurava).

## Situazione dopo

- **Popup "Sorgente dati" nel widget Tabella** (`_source_config`): SQL libera
  (come prima, stessa textarea e stesso salvataggio) oppure "Elenco record".
  La Query guidata non è offerta sulla Tabella.
- **Pannello "Elenco record"** (`_records_list_fields`): tabella (solo dataset
  `mg_*` con modulo, flag `records` in `dataset-options`), colonne
  (checkbox; alla scelta di una tabella si preselezionano le prime 5),
  ordina per + direzione, record per pagina (10/25/50/100), filtri (stesso
  formato `config[filters][N][column|operator|value]`). Salva in config:
  `mode='records'`, `dataset`, `columns[]`, `order_by`, `order_dir`,
  `page_length`, `filters`.
- **Anteprima "Prova"** nuovo endpoint `records-preview`
  (`postRecordsPreview`, solo superadmin, max 10 righe). Gira come
  superadmin: mostra tutte le righe senza scoping, e il testo accanto al
  pulsante lo dice.
- **Render** (`renderComponentPayload`): con `mode='records'` chiama
  `executeRows()` con le colonne/filtri/ordinamento salvati; `table.blade.php`
  mostra intestazioni dalle etichette e `pageLength` dalla config
  (tetto 500 righe, paginazione lato client).
- **Una `sql` scalare rimasta in config non viene mai eseguita** in modalità
  `records` (prima il ciclo sulle chiavi la eseguiva se `mode` era diverso da
  `builder`): aggiunto `records` alla condizione che la salta.
- **Fase 4**: `renderComponentPayload()` restituisce `null` se il widget è
  una Tabella `records` e l'utente non può leggere la tabella
  (`DatasetAccessScope::canRead()`) o il dataset non esiste. I 3 punti
  d'uso: griglia in sola lettura e lista bulk scartano il widget
  (`->filter()->values()`, le posizioni degli altri restano), la vista del
  singolo componente restituisce layout vuoto e config nulla. Nessuna config
  del widget finisce nel payload.
- Testi nuovi solo via `trans('crudbooster.statistic_builder_records_*')` in
  EN e IT (in JS passati con `json_encode(trans(...))`).

## Motivazione

Permette di costruire una tabella senza scrivere SQL, con ruoli e permessi
rispettati lato server a ogni render (la config salvata non è mai
considerata affidabile).

## Test

Script temporaneo nel container Docker (nessuna suite lanciata), con dati di
prova in transazione annullata (0 righe residue):

- Render del widget come superadmin: 2 intestazioni, 3 righe, `pageLength: 25`,
  nessun `[sql]` residuo, la `sql` scalare `select 1/0` **non** eseguita.
- Utente standard t9 g7: 1 riga; tenant admin t9: 3 righe; utente senza
  `is_read` (tolto nella transazione): payload `NULL`; dataset sconosciuto
  per non superadmin: `NULL`.
- Widget in SQL libera: rendering invariato.
- `dataset-options`: `records` true per `table:mg_fatture`, false per
  `admin_users`.
- Form di configurazione della Tabella: pannello e pulsante "Elenco record"
  presenti, niente pulsante Query guidata, `page_length` selezionato.
- `records-preview`: risultati corretti; colonna `password` -> 422; utente
  non superadmin -> 403.
- Form di configurazione di smallbox, chartline_v2, chartbar_v2, chartarea_v2:
  né pannello né pulsante "Elenco record", SQL e Query guidata presenti come prima.
- `php -l` ok su tutti i file PHP toccati.

**Non verificato**: il comportamento in browser (popup, toggle, checkbox,
filtri, DataTables) — il JS è stato scritto ma non eseguito. Va provato a
mano (vedi sotto). Test automatici per ruolo: Fase 5.

## Rischi e note

- **Comportamento visibile che cambia**: nel widget Tabella la SQL libera non è
  più direttamente nel form ma nel popup "Sorgente dati" (come negli altri
  widget); il link "view table" del testo d'aiuto è diventato testo semplice.
  I widget Tabella già salvati (senza `mode`) si riaprono in SQL libera.
- Il codice delle righe filtro è duplicato da `_query_builder_fields` (volutamente,
  vedi commento nel partial): debito tecnico da unificare in futuro.
- Il toggle di modalità è gestito due volte (idempotente) se un widget
  includesse entrambi i partial; oggi nessuno lo fa.
- Il widget nascosto lascia un buco nella griglia (accettato per la v1).
- Le eccezioni di `executeRows()` in render mostrano il messaggio (in italiano)
  dentro il widget, come già fa la modalità `builder`.
- Da provare a mano: creare un widget Tabella in "Elenco record" su
  `mg_fatture`, salvarlo, vederlo come superadmin e come utenti di ruoli/tenant
  diversi (scenario di `piano-testing-manuale.md`), togliere `is_read` a un
  ruolo e verificare che il widget scompaia.

## Seguito: paginazione ignorata nel builder a griglia (2026-09-30)

Segnalato dall'utente: con "Record per pagina" = 10 il builder
(`/admin/statistic_builder/builder/21`) mostrava tutte le righe. Causa:
`builder_grid.blade.php` è una pagina standalone che carica solo jQuery e
gridstack, senza DataTables; lo script del widget chiamava `$.fn.DataTable`
(indefinito) e andava in errore, lasciando la tabella HTML piena (il server
manda fino a 500 righe, la paginazione è lato client). Preesistente: valeva
anche per le Tabelle in SQL libera nel builder. La vista di sola lettura
(`show_grid`) estende `admin_template`, che carica DataTables, e non era
affetta. Corretto caricando in `builder_grid.blade.php` DataTables core (JS +
CSS senza integrazione Bootstrap, stessa versione 1.10.7). Verificato solo
che la vista compili e includa i file; non provato in browser (layout di
ricerca/selettore pagina senza Bootstrap da controllare a occhio).

Stesso sintomo nella dashboard di sola lettura
(`/admin/statistic_builder/show/<slug>`), causa diversa: li' il widget e'
nel contenuto della pagina, ma jQuery e DataTables sono caricati dopo (in
fondo, `admin_template_plugins`), quindi lo script di `table.blade.php`
girava con `$` non ancora definito. Corretto facendo riprovare lo script
ogni 50ms (max ~5s) finche' `jQuery` e `$.fn.DataTable` non esistono; nel
builder parte subito. Non provato in browser. Nota: il piccolo script
"nascondi i pulsanti modifica/elimina" in coda al template usa anch'esso `$`
con lo stesso problema di ordine (preesistente, non toccato).

## Rollback

Ripristinare `table.blade.php` (textarea SQL diretta), rimuovere il partial
`_records_list_fields`, i parametri opzionali in `_source_config` /
`_query_mode_toggle`, il ramo `records`, `recordsParams()`,
`isRecordsWidgetHidden()`, `postRecordsPreview()` e i `null`-check nei 3
punti d'uso di `StatisticBuilderController`, il flag `records` in
`optionsForFrontend()`, `moduleTables()`, e le chiavi lingua
`statistic_builder_records_*`.
