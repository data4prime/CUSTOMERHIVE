# 284 - API: licenza modulo, campi scrivibili e eliminazione allineati al pannello

- **Data**: 2026-10-08
- **Stato**: Completato (da verificare con chiamate reali)
- **Area**: API Generator / Sicurezza
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/ApiController.php` (`execute_api()`)
  - `app/Http/Middleware/EnforceModuleLicense.php` (`isModuleLicensed()`)

## Contesto

Confronto tra i controlli dell'API (`api/*`, `api2/*`) e quelli del pannello.
I permessi (`ModuleHelper::can_*`: ruoli, tenant, gruppo) erano gia' gli stessi;
emersero tre differenze.

## Situazione prima

1. **Licenza**: il pannello blocca i moduli a licenza (Qlik, ChatAI) con
   `EnforceModuleLicense`; le rotte API non passano da li'.
2. **Campi scrivibili**: in creazione/modifica ogni chiave del body finiva sul
   record, anche non dichiarata tra i parametri dell'endpoint (es. `tenant`,
   `group`, `deleted_at`, `created_by`).
3. **Eliminazione**: solo soft delete; il pannello scrive anche `deleted_by`,
   registra il log e lancia `hook_before_delete`/`hook_after_delete`.
   `created_by`/`updated_by` non erano scritti dall'API (il pannello si').

## Situazione dopo

1. Se la tabella dell'endpoint appartiene a un modulo a licenza non attiva,
   l'API risponde `api_status 0` con `crudbooster.module_not_licensed`.
   Logica condivisa: `EnforceModuleLicense::isModuleLicensed($modulePath)`.
2. Si scrivono solo i parametri dichiarati e attivi dell'endpoint (con colonna
   esistente; parametri a valore vuoto ignorati). `created_by`, `updated_by`,
   `deleted_by`, `deleted_at` e `created_at` non si accettano dal client: li
   imposta l'API (`created_by`/`updated_by` in creazione, `updated_by` in
   modifica, come `CBController`).
3. Eliminazione: `hook_before_delete` (se risponde con una Response l'operazione
   si ferma e l'API da' `failed`), log `log_delete`, `deleted_by` col soft
   delete, `hook_after_delete` a operazione riuscita.

## Motivazione

Stessa protezione e stessa tracciabilita' del pannello; nessun client puo'
scrivere colonne non esposte o dichiarare chi ha creato/modificato/eliminato.

## Test

`php -l` su ApiController e middleware. Letti i test esistenti
(`ApiExecuteTest`, solo list/detail): non dipendono dalle parti toccate.
Suite non eseguita; nessuna chiamata API reale di create/update/delete
provata.

## Rischi e note

- Cambia comportamento visibile: un client che inviava campi non dichiarati
  non li vede piu' scritti; chi mandava `created_by`/`deleted_at` viene
  ignorato. Gli endpoint vanno aggiornati dichiarando i parametri necessari.
- Parametri dichiarati con config `*` (esclusi dalla validazione) restano
  scrivibili se presenti nel body.
- Non toccati: hook di add/edit (`hook_before_add`, ...) non sono eseguiti
  dall'API; in modifica un `tenant`/`group` dichiarato puo' ancora spostare il
  record (come nel pannello, che controlla solo il record prima della
  modifica).
- Il controllo licenza usa `cms_moduls.path` dei moduli con quella tabella.

## Rollback

Ripristinare in `execute_api()` il ciclo "campi extra" (`foreach ($posts ...)`),
il ramo `delete` originale e rimuovere il blocco licenza; `isModuleLicensed()`
puo' restare.
