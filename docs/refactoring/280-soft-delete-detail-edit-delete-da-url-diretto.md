# 280 - Soft delete: detail/edit/delete da URL diretto non aprono piu' record eliminati

- **Data**: 2026-10-08
- **Stato**: Completato
- **Area**: CRUD / Soft delete
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/CBController.php` (`findActiveRow()` nuovo, `getDetail`, `getEdit`, `postEditSave`, `getDelete`)

## Contesto

Un record eliminato (soft delete, `deleted_at` valorizzato) spariva dalla
lista ma, inserendo l'id direttamente nell'URL (`.../detail/<id>`), veniva
comunque mostrato.

## Situazione prima

`getIndex`, `getDataTable`, `getModalData`, ecc. filtrano `deleted_at`, ma
`getDetail`, `getEdit`, `postEditSave` e `getDelete` caricavano il record con
`DB::table(...)->where(pk, $id)->first()` senza filtro. Un record eliminato
era quindi visualizzabile, modificabile e ri-eliminabile da URL.

## Situazione dopo

Nuovo `findActiveRow($id)`: stessa query ma con `whereNull(deleted_at)` se la
tabella ha la colonna. I quattro metodi lo usano; se il record non c'e' (o e'
eliminato) redirect alla lista del modulo con `crudbooster.missing_item`
(chiave gia' presente in en e it). Tabelle senza `deleted_at`: nessuna
differenza, tranne che un id inesistente ora da' il messaggio "elemento non
trovato" invece di un errore su `$row` nullo.

## Motivazione

Coerenza con le liste e chiusura di un accesso a dati considerati eliminati.

## Test

`php -l` OK. Suite non eseguita. Verifica manuale: eliminare un record, poi
aprire detail/edit con il suo id da URL → redirect alla lista con messaggio.

## Rischi e note

Cambia comportamento visibile (record eliminato: prima visibile, ora
redirect). Non coperti: controller che sovrascrivono `getDetail`/`getEdit`
(es. `DashboardLayoutController`, `AdminChatAIController`, controller custom
dei clienti) hanno la propria query senza filtro; da verificare caso per caso.
Anche la risposta `detail` delle API (`execute_api`) non e' stata controllata.
Un controller cliente che definisse un proprio `findActiveRow` andrebbe in
conflitto.

## Rollback

Ripristinare le quattro query `DB::table($this->table)->where($this->primary_key, $id)->first()`
e rimuovere `findActiveRow()`.
