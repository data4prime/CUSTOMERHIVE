# 267 - Export: nel dialogo compaiono tutte le colonne del modulo

- **Data**: 2026-10-07
- **Stato**: Completato
- **Area**: Lista / Export
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/CBController.php` (`getIndex`, nuovo `exportExtraColumns`)
  - `resources/views/crudbooster/default/table.blade.php` (dialogo Export)

## Contesto

Nel dialogo di esportazione, la sezione "Colonne" mostrava solo le colonne
della lista del modulo. Serve poter scegliere tra tutte le colonne del modulo
e spuntare solo quelle da esportare.

## Situazione prima

`$columns` (dialogo) = `$this->columns_table`, cioè solo le colonne visibili in
lista. `export.blade.php` filtra `$response['columns']` con `columns[]`, quindi
non poteva esportare nulla che non fosse già in lista.

## Situazione dopo

`getIndex()` calcola `export_columns` = colonne della lista + colonne extra
(campi del form con colonna in tabella, poi le altre colonne della tabella;
esclusi `deleted_at`, `password`, `remember_token` e i tipi senza colonna).
Il dialogo elenca tutte, tutte spuntate di default (come prima). In
esportazione (`index_return`) le sole colonne extra spuntate vengono aggiunte a
`$columns_table`, quindi alla query e all'output; i campi form con
`datatable` usano la join per mostrare l'etichetta invece dell'id, le colonne
di sistema (created_by, tenant...) la ottengono dal `systemJoin` esistente.

## Motivazione

Più flessibilità senza toccare la lista né il formato dell'export per le
colonne già presenti. Nessun testo UI nuovo (etichette da form/colonne).

## Test

`php -l` sul controller. Non verificato a vista né con la suite di test.

## Rischi e note

Le colonne extra senza campo form hanno come etichetta il nome colonna
umanizzato (non tradotto). Il default "tutte spuntate" ora esporta anche le
colonne extra: comportamento visibile cambiato rispetto a prima.

## Rollback

Ripristinare i due file (nessuna migrazione).
