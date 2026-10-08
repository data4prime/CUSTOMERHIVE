# 282 - Generatore API: endpoint di creazione senza created_by/updated_by/deleted_by

- **Data**: 2026-10-08
- **Stato**: Completato
- **Area**: API Generator
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/ApiCustomController.php` (`postBulkCreate`)

## Contesto

Seguito di 276 ("Crea endpoint per..."). Le colonne `created_by`,
`updated_by`, `deleted_by` sono scritte dal sistema e non devono comparire
tra i parametri dell'endpoint di creazione.

## Situazione prima

L'endpoint `<tabella>_create` generato aveva come parametri tutte le colonne
tranne `id`, quindi anche le tre colonne `*_by`.

## Situazione dopo

I parametri di `create` escludono `id`, `created_by`, `updated_by`,
`deleted_by`. Gli altri quattro endpoint non cambiano.

## Motivazione

Un client non deve poter dichiarare chi ha creato/modificato/eliminato un
record: lo decide il sistema.

## Test

`php -l` OK. Non eseguito in browser.

## Rischi e note

- Vale solo per gli endpoint generati d'ora in poi: quelli gia' creati vanno
  ritoccati nell'editor (o eliminati e rigenerati).
- Il filtro riguarda i parametri dichiarati; `execute_api()` accetta ancora
  campi extra inviati nel body (`$posts`), quindi un client potrebbe
  comunque inviare `created_by`. Non toccato qui.
- `update` (che espone `updated_by`) e `list` non sono stati modificati.

## Rollback

Rimettere `->where('name', '!=', 'id')` nei parametri di `save_add`.
