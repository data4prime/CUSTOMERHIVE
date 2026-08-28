# 047 - Crash su utente con privilegio orfano (isSuperAdmin/isTenantAdmin)

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Bug fix
- **File/aree di codice coinvolte**:
  - `app/User.php`

## Contesto

Conseguenza diretta di [046](046-groups-members-leftjoin-privilegio-orfano.md):
dopo aver cambiato l'INNER JOIN in LEFT JOIN in
`AdminGroupsController::members()`, l'utente "test" (orfano, privilegio
cancellato) torna visibile nella lista membri del gruppo — ma
visitando `/admin/groups/members/3` compare l'errore:

```
Attempt to read property "is_superadmin" on null
(View: /var/www/html/resources/views/groups/members.blade.php)
```

Catena della chiamata: la vista, per ogni membro, chiama
`UserHelper::icon($member->id)` per l'avatar → `User::find($id)->photo()`
→ se l'utente non ha una foto propria, `photo()` chiama
`$this->isSuperAdmin()`/`isTenantAdmin()` per scegliere un'icona di
default in base al ruolo → questi due metodi facevano
`Role::find($my_role_id)->is_superadmin` **senza controllare se
`Role::find()` ha trovato qualcosa**. Per l'utente "test", il cui
`id_cms_privileges` punta a un privilegio ormai cancellato,
`Role::find()` ritorna `null` e l'accesso alla proprietà crasha.

Bug latente pre-esistente, mai emerso finora perché nessun percorso
di codice prima d'ora mostrava un utente con privilegio orfano.

## Situazione prima

```php
public function isTenantAdmin()
{
  $my_role_id = $this->id_cms_privileges;
  return Role::find($my_role_id)->is_tenantadmin == 1;
}

public function isSuperAdmin()
{
  $my_role_id = $this->id_cms_privileges;
  return Role::find($my_role_id)->is_superadmin == 1;
}
```

## Situazione dopo

```php
public function isTenantAdmin()
{
  $my_role_id = $this->id_cms_privileges;
  $role = Role::find($my_role_id);
  return $role && $role->is_tenantadmin == 1;
}

public function isSuperAdmin()
{
  $my_role_id = $this->id_cms_privileges;
  $role = Role::find($my_role_id);
  return $role && $role->is_superadmin == 1;
}
```

Un utente senza un privilegio valido ora è trattato semplicemente come
"nessun permesso speciale" (non superadmin, non tenantadmin) invece di
far crashare qualunque pagina che tenti di determinarne il ruolo.

## Motivazione

Coerente con la decisione dell'utente in [046](046-groups-members-leftjoin-privilegio-orfano.md#situazione-prima):
un utente con privilegio cancellato resta nel sistema (nessuna pulizia
dati, nessun guard in `PrivilegesController::getDelete()` per ora) e
**deve essere visualizzabile**. Questo però richiede che ogni punto del
codice che interroga il ruolo di un utente sia tollerante a un
riferimento rotto — `isSuperAdmin()`/`isTenantAdmin()` sono i due punti
centrali (usati da `UserHelper::isSuperAdmin()`/`isTenantAdmin()`,
quindi da `photo()`, dalla sidebar, da `ModuleHelper`, ecc.), quindi il
fix va fatto qui e si propaga a tutti i chiamanti.

## Test

- `php -l`: nessun errore.
- Test diretto: `App\User::find(4)->isSuperAdmin()` e
  `->isTenantAdmin()` ora ritornano `false` (prima: eccezione);
  `->photo()` ritorna l'icona utente di default;
  `UserHelper::icon(4)` idem.
- Non-regressione: `App\User::find(1)` (Super Administrator, privilegio
  id 1 esistente) → `isSuperAdmin()` ritorna ancora `true`.
- `curl` senza sessione su `/admin/groups`, `/admin/groups/members/3`:
  entrambi 302, nessun 500.
- Route count invariato (490).

**Non verificato visivamente in browser** — lasciato al giro di test
manuale dell'utente. Il rendering isolato via script (senza sessione
reale) di `groups/members.blade.php` non è stato possibile fino in
fondo per lo stesso limite ambientale già documentato in
[043](043-privileges-form-come-standard.md#test) (`CRUDBooster::mainpath()`/
`getCurrentModule()` richiedono una richiesta HTTP reale con rotta
corrente risolta) — limite pre-esistente, non legato a questo fix.

## Rischi e note

- Fix a livello di modello (`App\User`): si propaga automaticamente a
  tutti i chiamanti esistenti di `isSuperAdmin()`/`isTenantAdmin()`
  (sidebar, `ModuleHelper`, `UserHelper::can_do_on_user()`, ecc.), non
  solo alla pagina membri gruppo.
- Comportamento invariato per qualunque utente con un privilegio
  valido; cambia solo per un utente orfano, che prima crashava e ora
  viene trattato come privo di permessi speciali.

## Rollback

`git revert` del commit — ripristina l'accesso diretto senza controllo
null, il crash torna a presentarsi per qualunque utente con privilegio
cancellato.
