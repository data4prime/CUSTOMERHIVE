# 062 - Pulsante "Add member" al posto di "Add group" in Tenants > Group

- **Data**: 2026-09-01
- **Stato**: Completato
- **Area**: Bug fix (UI)
- **File/aree di codice coinvolte**:
  - `resources/views/tenants/group.blade.php`

## Contesto

Segnalato dall'utente: su `/admin/tenants/group/{id}` (la pagina che
associa dei Group a un Tenant) il pulsante di submit del form dice
"Add member", ma qui si sta aggiungendo un **Group**, non un membro
(utente).

Causa: `tenants/group.blade.php` è stata copiata dalla view analoga
`groups/members.blade.php` (che associa Users a un Group, dove "Add
member" è corretto) senza aggiornare l'etichetta del pulsante.

## Situazione prima

```blade
<input type="submit" name="submit" value='{{trans("crudbooster.button_add_member")}}'
  class='btn btn-success'>
```

## Situazione dopo

```blade
<input type="submit" name="submit" value='{{trans("crudbooster.button_add_group")}}'
  class='btn btn-success'>
```

La chiave di traduzione `crudbooster.button_add_group` esisteva già
(en: "Add group", it: "Aggiungi gruppo") in entrambi i file di lingua,
mai usata da nessuna view: nessuna modifica ai file di lingua
necessaria.

## Motivazione

Etichetta del pulsante deve riflettere l'azione reale del form
(aggiungere un gruppo al tenant), non quella copiata dalla view
sorgente.

## Test

- `php -l` sulla view: nessun errore.
- `php artisan view:clear`: cache delle view compilate svuotata.
- Non toccata `groups/members.blade.php` (stesso pattern, ma lì "Add
  member" è corretto): verificato che l'occorrenza cambiata sia solo
  quella in `tenants/group.blade.php`.

## Rischi e note

- Nessuno: cambio isolato a una singola view, nessuna chiave di
  traduzione nuova, nessun impatto su altri moduli.

## Rollback

`git revert` del commit - ripristina `button_add_member` in questa view.
