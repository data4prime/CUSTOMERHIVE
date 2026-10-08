# 281 - API update: la risposta contiene il record appena modificato

- **Data**: 2026-10-08
- **Stato**: Completato
- **Area**: API Generator
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/ApiController.php` (`execute_api()`, ramo `save_edit`)

## Contesto

L'endpoint di update rispondeva solo `api_status`/`api_message`: il client
doveva rifare una chiamata `detail` per vedere i nuovi valori.

## Situazione prima

Dopo `->update($row_assign)` la risposta era `{"api_status":1,"api_message":"success"}`.

## Situazione dopo

Dopo l'update il record viene riletto e unito alla risposta, con gli stessi
criteri del `detail`: solo i campi di risposta configurati
(`$responses_fields`), percorsi di upload trasformati con `asset()`. Le chiavi
di sistema (`api_status`, `api_message`, ...) non vengono sovrascritte da
colonne omonime. Vale per `api/*` e `api2/*`. `save_add` non e' toccato.

### Aggiunta (stesso giorno): anche la creazione

Anche `save_add` restituisce ora il record appena creato (stessi criteri:
solo campi di risposta configurati, upload con `asset()`), oltre a `id` che
resta come prima. La logica e' stata estratta in `mergeSavedRow()` e usata da
creazione e modifica. Se la creazione fallisce (`id` vuoto) la risposta e'
invariata. Rollback: togliere la chiamata a `mergeSavedRow()` nel ramo
`save_add`.

## Motivazione

Evita una seconda chiamata e restituisce lo stato reale del record dopo il
salvataggio (inclusi default/trigger/`updated_at`).

## Test

`php -l` OK. Suite non eseguita. Verifica manuale: update di un record →
risposta con i campi aggiornati.

## Rischi e note

Cambia la risposta visibile (campi in piu'); i client esistenti che ignorano
chiavi sconosciute non sono impattati. Se nessun campo di risposta e'
configurato per l'endpoint, la risposta resta invariata. Una colonna che si
chiama come una chiave di sistema non appare nella risposta.

## Rollback

Rimuovere il blocco "Restituisce il record appena modificato" nel ramo
`save_edit`.
