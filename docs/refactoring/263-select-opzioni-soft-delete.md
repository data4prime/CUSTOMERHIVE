# 263 - Opzioni dei campi select: esclusi i record eliminati (soft delete)

- **Data**: 2026-10-07
- **Stato**: Completato (non provato a vista nel browser)
- **Area**: Form / type components
- **File/aree di codice coinvolte**: `CBController::getDataTable`, `type_components/select2/component.blade.php` (ramo `relationship_table`), `type_components/child/component.blade.php`

## Contesto

Le opzioni dei campi che leggono da un'altra tabella devono ignorare i record con `deleted_at` valorizzato.

## Situazione prima

Select, select2 (normale e ajax), radio, checkbox e datamodal gia' filtravano. Non filtravano: select a cascata (`getDataTable`, entrambi i rami), select2 multiplo con `relationship_table`, select dei campi `child`.

## Situazione dopo

I tre punti escludono i record eliminati se la tabella ha la colonna `deleted_at`. Liste e dettagli mostrano ancora il nome del record collegato anche se eliminato. Le opzioni da `dataquery` (SQL libero) restano a carico di chi le scrive.

## Test

`php -l`, `view:cache`. Test automatici non eseguiti.

## Rischi e note

Se un record punta a un'opzione poi eliminata, in modifica l'opzione non compare piu' e salvando il campo puo' svuotarsi (non affrontato).

## Rollback

Ripristinare i tre file.
