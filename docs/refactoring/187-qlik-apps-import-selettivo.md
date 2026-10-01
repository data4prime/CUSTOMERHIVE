# 187 - Qlik apps: import selettivo con elenco a checkbox

- **Data**: 2026-10-01
- **Stato**: Completato
- **Area**: Frontend / Backend
- **File/aree di codice coinvolte**:
  - `public/js/qlik_sync_modal.js`
  - `app/Services/QlikSync/QlikSyncService.php`, `QlikSyncUi.php`
  - `app/Http/Controllers/System/Concerns/HandlesQlikSyncStart.php`
  - `app/Http/Controllers/System/QlikAppController.php`
  - `app/QlikSyncRun.php`
  - `database/migrations/2026_10_01_100000_add_selected_app_ids_to_qlik_sync_runs.php`
  - `resources/lang/{en,it}/crudbooster.php`

## Contesto

La sync app (docs 176-178) importava sempre tutte le app della conf Qlik.
Serve poter scegliere quali importare. Segue il 186 (stato e data ultimo
import in lista).

## Situazione prima

Il bottone "Sincronizza da Qlik" apriva una modale con la sola scelta della
conf; `stageApps` faceva `upsertApp` su tutto il risultato di `listApps()`.

## Situazione dopo

- Scelta la conf, la modale (modalita' app) legge le app da Qlik
  (`GET admin/qlik_apps/sync-preview`, sola lettura) e le mostra con
  checkbox, ricerca, "Seleziona/Deseleziona visibili" e contatore. Le app
  gia' importate partono **spuntate** e hanno il tag "Gia' importata".
- Bottoni: "Importa selezionate (N)" (spento a 0) e "Importa tutto" (come
  prima, nessuna selezione).
- `QlikSyncService::start()` ha il parametro opzionale `$selectedAppIds`,
  salvato in `qlik_sync_runs.selected_app_ids` (testo JSON, nullable;
  NULL = tutte). `stageApps` filtra `listApps()` sugli id scelti (quindi la
  selezione e' riconvalidata contro Qlik) e `total` conta le selezionate.
- `markMissingApps` con selezione parziale decide "mancante" sull'elenco
  completo di Qlik (appid non piu' presente), non sulle app elaborate: le
  non selezionate non vengono segnate mancanti. Senza selezione resta la
  logica di prima.
- Modalita' item invariata.

## Motivazione

Evitare di popolare il catalogo con app non volute. Il flusso a due passi
(anteprima poi import) evita di toccare il driver.

## Test

`php -l` su PHP e migrazione, migrazione applicata in locale, rotta
`sync-preview` presente in `route:list`. JS non eseguito/controllato (nessun
node disponibile) e non provato a browser su Qlik reale: da verificare a mano
(selezione, ricerca, import selezionate/tutto, app non selezionate non
segnate mancanti).

## Rischi e note

- L'anteprima fa una chiamata a Qlik a ogni cambio conf; con cataloghi molto
  grandi puo' essere lenta.
- Dopo il deploy riavviare i worker (`queue:restart`, gia' nella CI su dev).
- Migrazione additiva, colonna nullable.

## Rollback

Ripristinare i file sopra e `php artisan migrate:rollback --step=1` per la
colonna (non necessario: e' ignorata se non usata).
