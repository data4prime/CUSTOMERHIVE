# 178 - Widget Qlik: modalità "foglio" e select ordinati/ricercabili (Fasi 4, 7)

- **Data**: 2026-09-30
- **Stato**: Completato
- **Area**: Qlik / Widget / UI
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/qlikwidget.blade.php`
  - `app/Http/Controllers/System/StatisticBuilderController.php` (`mashupSheet`)
  - `resources/views/mashup_sheet.blade.php` (nuova)
  - `routes/web.php` (`mashup-sheet/{componentID}`)
  - `app/Services/QlikSync/QlikSyncUi.php` (`sheetItemsByApp`)
  - `QlikAppController.php`, `AdminQlikItemsController.php` (campo conf → `select2`)
  - `resources/lang/{en,it}/crudbooster.php` (`qlik_widget_*`)

## Contesto

Fasi 4 e 7 di [`../piano-qlik-sync-app-items.md`](../piano-qlik-sync-app-items.md):
i select devono essere in ordine alfabetico e ricercabili, e il widget Qlik
deve poter mostrare un foglio importato (item) oltre al master object di oggi.

## Situazione prima

- Widget Qlik: si sceglie un'app (`config[mashups]`) e un **master object**
  (`config[object]`, elencato dal browser con la Capability API) mostrato con
  `vis.show()`. Non mostra fogli.
- Select "conf" di app e item: `select` semplice (nel form app con un
  `relationship_table` inutile), ordinato per id.

## Situazione dopo

- **Modalità aggiuntiva "foglio"** (chiave `config[qlik_mode]`: `master_object`
  | `sheet`; non `mode`, già usata da altri widget). **I widget esistenti non
  hanno la chiave e si comportano esattamente come prima.** In modalità
  foglio: app da `qlik_apps` (tutte, in ordine alfabetico, ricercabili) e fogli
  dagli item con `qlik_app_id` = app scelta (item senza app non compaiono).
  Config salvata in `config[item]`.
- **Rendering**: iframe verso la nuova pagina minimale `/mashup-sheet/{componentID}`
  (login obbligatorio, guard licenza Qlik) che fa il login a Qlik con lo stesso
  JS degli item (`qliksaas_login.js` / `qlik_op_jwt_login.js`) e incorpora l'URL
  dell'item. On-premise senza utente Qlik associato: messaggio invece del
  redirect con `exit` dell'helper.
- **Form di configurazione**: selettore di modalità; app ordinate e filtrabili;
  il select dei master object non viene inviato né blocca il salvataggio in
  modalità foglio; cambiando app si aggiornano i fogli.
- **Select ricercabili**: campo "conf" dei form app e item passato a `select2`
  (ricerca + ordine alfabetico per `confname`), senza `relationship_table`;
  nella modale di sincronizzazione elenchi filtrabili.

## Motivazione

Modalità aggiuntiva e non sostitutiva: nessun rischio per i widget già in
uso; il master object resta disponibile. Il widget non applica `can_see_item`
al rendering (come la modalità attuale non controlla l'app): la visibilità è
quella della dashboard, i dati sono comunque limitati dall'identità Qlik.

## Test

- Rendering del layout con dati finti: widget legacy (iframe `/mashup/`), senza
  config (stato vuoto), foglio con item (iframe `/mashup-sheet/`), foglio senza
  item (stato vuoto). Configurazione: form presente, app in ordine
  alfabetico (indipendente da maiuscole), modalità preselezionata corretta.
- Lint PHP, compilazione Blade di widget e `mashup_sheet`, rotta registrata.
- **NON verificato**: il comportamento del JS di configurazione nel browser e il
  login/embedding contro un Qlik reale.

## Rischi e note

- Il salvataggio del widget scrive anche `qlik_mode` sui widget legacy (valore
  `master_object`): innocuo.
- Passando da "foglio" a "master object" il select oggetti si ricarica dal
  loader esistente (iframe `mashup-objects`).
- Il campo "conf" in `select2`: stesso valore singolo, ma da verificare a mano
  add/edit di app e item (salvataggio e pre-selezione in modifica).

## Rollback

Ripristinare i file elencati da git; i widget in modalità foglio tornano a
non mostrare nulla (la chiave `qlik_mode` viene ignorata dalla versione
precedente, che mostra lo stato "non configurato" o il master object).
