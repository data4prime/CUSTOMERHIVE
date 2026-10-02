# 216 - `select2/info.json`: documentare `datatable_orig`, rimuovere `info_.json`

- **Data**: 2026-10-02
- **Stato**: Completato
- **Area**: Module generator / pulizia
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/default/type_components/select2/info.json`
  - `resources/views/crudbooster/default/type_components/select2/info_.json` (rimosso)

## Contesto

Dall'analisi dei `type_components`: la cartella `select2` conteneva due
descrittori, `info.json` e un residuo `info_.json` con due attributi in più.

## Situazione prima

`info.json` (letto da `ModulsController::getTypeInfo()` e dai passi 4 del module
generator) non elencava `datatable_orig`, mentre `select2/component.blade.php`,
`component_detail.blade.php` e `CBController` (righe ~1640–1660, salvataggio
many-to-many) lo leggono davvero. `info_.json` aveva anche
`datatable_exception`, che **nessun file** del progetto legge. Nessun codice
referenziava `info_.json`.

## Situazione dopo

- `info.json`: aggiunto `datatable_orig` ("original datatable|original column[|original key]")
  tra gli attributi opzionali.
- `info_.json`: eliminato. `datatable_exception` non è documentato perché non ha
  effetto.

## Motivazione

Il descrittore deve riflettere gli attributi che il componente supporta; un
file con suffisso `_` non riferenziato è solo confusione.

## Test

JSON valido (decodificato con PHP nel container). Non verificato il passo 4 del
wizard nel browser.

## Rischi e note

**Cambia l'interfaccia del module generator**: nel passo 4 (anche nel wizard v2,
se legge `info.json`) i campi `select2` mostrano ora un'opzione in più,
`datatable_orig`. Prima era impostabile solo a mano nel controller.

## Rollback

`git checkout` dei due file.
