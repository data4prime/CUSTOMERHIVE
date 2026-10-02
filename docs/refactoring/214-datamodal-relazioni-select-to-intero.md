# 214 - Popup `*_datamodal` delle relazioni: `select_to` forzato a intero

- **Data**: 2026-10-02
- **Stato**: Completato
- **Area**: Sicurezza
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/default/type_components/{group_members,group_items,group_tenant,item_access,item_tenant,tenant_group,user_groups}_datamodal/browser.blade.php`

## Contesto

Emerso dall'analisi dei `type_components` (inventario e duplicazione dei sette
tipi `*_datamodal`). Il popup di scelta di ogni relazione (utenti-gruppi,
gruppi-membri, gruppi-item, gruppi-tenant, item-gruppi, item-tenant,
tenant-gruppi) riceve l'id del record padre dalla query string
(`?select_to=...`, generata da `component.blade.php` con
`urlencode($form['datamodal_select_to'])`).

## Situazione prima

Ognuno dei sette `browser.blade.php` concatenava il valore direttamente nel
testo SQL di una `whereRaw` usata per escludere le righe già collegate:

```php
->whereRaw('group_tenants.group_id = '.Request::get('select_to').' AND ...')
```

Poiché la URL del popup è raggiungibile da qualunque utente autenticato che
apra `.../modal-data?type=<tipo>_datamodal&select_to=...`, il valore non era
affatto controllato: **SQL injection** (es. `select_to=0 OR 1=1` o una
subquery).

## Situazione dopo

Nelle sette `whereRaw` il valore è `(int) Request::get('select_to')`. Per un id
numerico legittimo la query è identica a prima. Gli altri usi di `select_to`
nei browser (`UserHelper::tenant(...)`, `GroupTenants::where('group_id', ...)`)
passavano già da binding e non sono stati toccati.

## Motivazione

Chiude un'iniezione SQL con una modifica minima e indipendente dal
consolidamento successivo (215). Alternativa scartata: binding `?` nelle
`whereRaw` — equivalente, ma con tre `whereRaw` multi-riga avrebbe richiesto
di toccare anche la forma degli argomenti; il cast intero basta perché gli id
sono sempre numerici.

## Test

- Lint e render dei sette browser nel container Docker con dati reali del DB
  locale (`select_to=1`): tutti rendono la tabella senza errori.
- Non verificato nel browser, né con un `select_to` malevolo (nessun test
  automatico aggiunto).

## Rischi e note

- Cambia comportamento solo per `select_to` non numerico: prima errore SQL o
  iniezione, ora viene letto come `0` (nessuna esclusione dalla lista).
- Non coperti da questo intervento: `getModalData` passa il parametro `where`
  in `whereRaw` (è il meccanismo dichiarato di `datamodal_where`), e
  `columns` arriva dalla query string; sono da valutare a parte.

## Rollback

Ripristinare i sette `browser.blade.php` (togliere il `(int)`); vedi anche 215,
che ha riscritto gli stessi file.
