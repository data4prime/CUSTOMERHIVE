# 188 - Qlik apps: modale di eliminazione con scelta sugli item collegati

- **Data**: 2026-10-01
- **Stato**: Completato
- **Area**: Frontend / Backend
- **File/aree di codice coinvolte**:
  - `public/js/qlik_app_delete.js` (nuovo)
  - `public/css/theme.css`
  - `app/Http/Controllers/System/QlikAppController.php`
  - `resources/lang/{en,it}/crudbooster.php`

## Contesto

Eliminando un'app Qlik, gli item (fogli) collegati restavano orfani senza
modo di scegliere. La conferma era lo `swal` generico di CRUDBooster
(`CRUDBooster::deleteConfirm`), fuori dallo stile CustomerHive.

## Situazione prima

Cestino di riga e "Elimina selezionati" aprivano lo `swal` di default; il
`getDelete`/`postActionSelected` di `CBController` eliminava solo l'app.

## Situazione dopo

- Nella lista app Qlik il click su cestino di riga e su "Elimina
  selezionati" e' intercettato (fase di cattura) da `qlik_app_delete.js`, che
  apre una modale nello stile delle altre (`.ch-sync-*`, variante
  `--danger`): icona rossa, testo, riga con la casella "Elimina anche gli
  item Qlik collegati" (con il numero di item collegati; disabilitata se non
  ce ne sono), Annulla / Elimina.
- Casella spuntata: l'URL/form porta `delete_items=1`;
  `QlikAppController::hook_before_delete` elimina (soft delete, con la stessa
  cascata `ItemsAllowed`/`TenantsAllowed` di `AdminQlikItemsController`) gli
  item con `qlik_app_id` delle app eliminate. Non spuntata: come prima, solo
  l'app.
- La casella e il flag sono solo per il superadmin (gli item di un'app possono
  appartenere ad altri tenant); gli altri vedono la stessa modale senza casella
  e il server ignora `delete_items`.
- Nuova rotta `GET admin/qlik_apps/linked-items?ids=` per il conteggio.
- Se l'id/URL non si leggono dall'`onclick` del cestino, non si intercetta e
  resta la conferma standard.

## Motivazione

Dare la scelta esplicita e allineare la conferma allo stile della UI.
Limitata alle app Qlik: lo `swal` degli altri moduli non e' toccato.

## Test

`php -l`, rotta presente in `route:list`. Non provato a browser ne' con
eliminazioni reali (JS non controllato: nessun node disponibile): da
verificare a mano singolo, multiplo, con e senza casella.

## Rischi e note

- Il soft delete degli item segue `CBController::getDelete`; il log registra
  gli id degli item eliminati.
- Il bulk non riusa il `hook_before_delete` degli item: la cascata e' qui.

## Rollback

Rimuovere `qlik_app_delete.js`, il blocco in `cbInit`, `getLinkedItems`,
il corpo di `hook_before_delete` e le classi `.ch-confirm-*`/`--danger`.
