# 189 - Qlik items: import selettivo dei fogli con anteprima

- **Data**: 2026-10-01
- **Stato**: Completato
- **Area**: Frontend / Backend
- **File/aree di codice coinvolte**:
  - `public/js/qlik_sync_modal.js`
  - `app/Services/QlikSync/QlikSyncService.php`, `QlikSyncUi.php`
  - `app/Http/Controllers/System/Concerns/HandlesQlikSyncStart.php`
  - `app/Http/Controllers/System/AdminQlikItemsController.php`
  - `app/QlikSyncRun.php`
  - `database/migrations/2026_10_01_100100_add_selected_sheet_ids_to_qlik_sync_runs.php`
  - `resources/lang/{en,it}/crudbooster.php`

## Contesto

Stessa logica del 187 (app) estesa agli item (fogli): invece di importarli
tutti, si vedono quelli presenti su Qlik e si scelgono.

## Situazione prima

La modale item chiedeva conf + app ("Tutte le app" o una) e importava tutti
i fogli dell'app (o di tutte).

## Situazione dopo

- Scelta conf e **una app**, la modale legge i fogli da Qlik
  (`GET admin/qlik_items/sync-preview?conf_id=&app_id=`, sola lettura) e li
  elenca ordinati con checkbox, ricerca, "Seleziona/Deseleziona visibili" e
  contatore; i gia' importati sono **spuntati** con tag "Gia' importato"
  (contano anche gli item creati a mano agganciabili per URL).
- "Importa selezionati (N)" avvia il run con `sheet_ids[]`; "Importa tutto"
  fa come prima (tutti i fogli dell'app scelta, o di tutte le app se
  "Tutte le app"). Con "Tutte le app" non c'e' elenco.
- `qlik_sync_runs.selected_sheet_ids` (JSON, nullable). `stageItemsApp`
  filtra i fogli sugli id scelti (riconvalidati contro Qlik).
- Mancanti: con selezione parziale `markMissingItems` a fine run e'
  disattivato e i fogli non piu' su Qlik si segnano `is_missing` per l'app
  sull'elenco completo (`markMissingSheetsOfApp`), cosi' i non selezionati
  non risultano mancanti. Senza selezione resta la logica di prima.
- La modale app e item e' ora ugualmente larga; nell'elenco dei fogli si
  vedono titolo e descrizione (la ricerca copre entrambi).
- **Fix driver SaaS** (`SaasQlikDriver::listSheets`): verificato sul tenant
  D4P (app Human Capital Management) che Qlik Cloud restituisce `qData.title`
  e `qData.description` come stringhe vuote e i valori veri in `qMeta`; il
  vecchio `??` non scattava sul vuoto e i fogli avrebbero preso l'id come
  titolo. Ora si usa il primo valore non vuoto (`qMeta` poi `qData`).

## Motivazione

Coerenza con le app e controllo su cosa entra nel catalogo.

## Test

`php -l`, migrazione applicata, rotta `sync-preview` presente. Non provato a
browser ne' su Qlik reale (il driver SaaS per i fogli usa WebSocket Engine,
l'on-prem il QRS: entrambi ancora non verificati su installazioni vere).

## Rischi e note

- L'anteprima fogli apre una sessione Engine per app (SaaS): lenta su app
  grandi; richiesta solo quando si sceglie l'app.
- Il rollback e il report dei run non cambiano.

## Rollback

Ripristinare i file sopra; la colonna `selected_sheet_ids` puo' restare.
