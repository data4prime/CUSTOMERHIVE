# 049 - Crash sul dettaglio per un campo "select" con FK orfana

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Bug fix
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/default/type_components/select/component_detail.blade.php`

## Contesto

Segnalato dall'utente: `/admin/users/detail/4` crasha con
`Attempt to read property "name" on null` nella vista
`type_components/select/component_detail.blade.php`.

Causa: il campo "Privilege" del modulo Users è un tipo `select` con
`'datatable' => 'cms_privileges,name'` (vedi
`AdminCmsUsersController::cbInit()`). L'utente "test" (id 4) ha
`id_cms_privileges = 3`, ma quel privilegio è stato cancellato (vedi
[044](044-privileges-delete-bloccato-e-messaggio-perso.md)/
[046](046-groups-members-leftjoin-privilegio-orfano.md)/
[047](047-user-isSuperAdmin-crash-privilegio-orfano.md) — stessa serie
di conseguenze dell'utente "test" rimasto orfano per scelta esplicita
dell'utente). Il template generico faceva:

```php
echo CRUDBooster::first($table, ['id' => $value])->$field;
```

Se `$value` non corrisponde a nessuna riga (FK orfana), `first()`
ritorna `null` e l'accesso a `->$field` crasha. Questo componente è
condiviso da **qualunque** modulo con un campo `select` a
`'datatable'` (non solo Users/Privileges): stesso rischio ovunque un
riferimento punti a un record cancellato.

## Situazione prima

```php
if (isset($form['datatable'])) {
    $datatable = explode(',', $form['datatable']);
    $table = $datatable[0];
    $field = $datatable[1];
    echo CRUDBooster::first($table, ['id' => $value])->$field;
}
```

## Situazione dopo

```php
if (isset($form['datatable'])) {
    $datatable = explode(',', $form['datatable']);
    $table = $datatable[0];
    $field = $datatable[1];
    $related = CRUDBooster::first($table, ['id' => $value]);
    echo $related ? $related->$field : '';
}
```

Una FK orfana ora mostra semplicemente una cella vuota invece di far
crashare l'intera pagina di dettaglio.

## Motivazione

Coerente con la linea già seguita in
[046](046-groups-members-leftjoin-privilegio-orfano.md)/
[047](047-user-isSuperAdmin-crash-privilegio-orfano.md): l'app permette
di cancellare un privilegio anche se ancora referenziato da qualche
utente (nessun guard aggiunto per scelta esplicita dell'utente), quindi
ogni punto che risolve quel riferimento deve tollerare che non esista
più — qui in un componente condiviso da tutti i campi `select` con
`datatable`, non specifico a un singolo modulo.

## Test

- Compilazione Blade (`BladeCompiler::compileString`) + `php -l` sul
  file compilato: nessun errore.
- Reso il componente direttamente con `view(...)->render()`:
  - `$value = 3` (privilegio cancellato, caso reale utente "test"):
    output vuoto, nessuna eccezione (prima: crash).
  - `$value = 1` (Super Administrator, privilegio esistente): output
    `"Super Administrator"` — nessuna regressione sul caso normale.
- `curl` senza sessione su `/admin/users`, `/admin/users/detail/4`,
  `/admin/users/detail/1`: tutti 302, nessun 500.

**Non verificato visivamente in browser** — lasciato al giro di test
manuale dell'utente.

## Rischi e note

- Fix a livello di componente condiviso: si applica a qualunque modulo
  usi un campo `select` con `'datatable'` puntato a un record ormai
  cancellato, non solo alla pagina Users.
- Non tocca il componente form/edit (`component.blade.php`), che non
  ha lo stesso rischio: un valore senza opzione corrispondente
  semplicemente non risulta "selected", senza errori.

## Rollback

`git revert` del commit — ripristina l'accesso diretto senza controllo
null, il crash torna a presentarsi per qualunque campo select con FK
orfana.
