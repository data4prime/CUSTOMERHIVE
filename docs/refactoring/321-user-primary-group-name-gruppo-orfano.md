# 321 - `current_user_primary_group_name()` con gruppo primario orfano

- **Data**: 2026-10-29
- **Stato**: Completato (test verdi nel container, non verificato nel browser)
- **Area**: Architettura / Modelli (batch 2b del piano 317)
- **File/aree di codice coinvolte**:
  - `app/Helpers/UserHelper.php` (`current_user_primary_group_name()`)
  - `tests/Feature/UserModelLookupsTest.php` (test rovesciato + caso nuovo)

## Contesto

Batch 2b del piano `docs/refactoring/piani/317-models-standard-laravel.md`.
Il [317](317-modelli-eloquent-caratterizzazione-e-igiene.md) aveva
fissato in un test di caratterizzazione che questo metodo solleva
un'eccezione con gruppo primario soft-deleted; il batch 2
([319](319-modelli-eloquent-relazioni-additive.md)) non lo aveva toccato.
Fix puntuale, un documento a sé come per il [320](320-menu-tenants-name-senza-virgola-finale.md)
(e simile per impostazione al [318](318-chatai-autorizzazioni-su-tabelle-dedicate.md)).

## Situazione prima

`UserHelper::me()->primary_group()->name` sollevava `ErrorException`
(`Attempt to read property "name" on null`) se l'utente **loggato** ha un
gruppo primario soft-deleted (`Group` usa SoftDeletes) o inesistente.
`cms_users.primary_group` è NOT NULL: l'orfano reale è un id che punta a un
record cancellato o inesistente, non NULL.

## Situazione dopo

`UserHelper::me()->primary_group()?->name ?? ''`. Con gruppo primario valido
il risultato è identico; con gruppo orfano restituisce `''`.

Valore di ripiego `''` scelto leggendo i tre chiamanti, tutti
`'default' => ...` di un campo select2 con `parent_select`
(`app/Helpers/ModuleHelper.php:861` e `:884`,
`app/Http/Controllers/System/AdminCmsUsersController.php:259`): la vista
`select2/component.blade.php` non legge `$form['default']` ma usa
`current_user_primary_group()` (l'id) per il JS, quindi `''` non crea
errori nuovi; il docblock dichiara `@return string`;
`AdminCmsUsersController` usa già `'default' => ''` per "nessun default".
Nessun altro chiamante (blade inclusi). Non toccati `User::primary_group()`
e `current_user_primary_group()` (il secondo restituisce ancora l'id grezzo).

**Cambio visibile**: con gruppo primario orfano, i form del campo
Group/Primary Group si renderizzano con default vuoto invece di sollevare
l'errore.

## Motivazione

Un errore non gestito per un dato orfano (gruppo cancellato dopo
l'assegnazione) è un difetto; `''` è già il valore con cui il codice
esprime "nessun default".

## Test

Eseguiti nel container su `customerhive_testing` (su richiesta del
chiamante) gli stessi file elencati nel [320](320-menu-tenants-name-senza-virgola-finale.md):
**100 passati, 254 assert, nessun fallimento**.

- Rovesciato di proposito (con commento nel codice) in
  `UserModelLookupsTest` il test che si aspettava `ErrorException`: ora
  `test_current_user_primary_group_name_con_gruppo_primario_orfano_restituisce_stringa_vuota`
  con `assertSame('', ...)`.
- Aggiunto il caso gruppo inesistente (id 999999).
- Invariato il caso gruppo valido.

Non verificato: rendering dei form nel browser.

## Rischi e note

- Review (ch-reviewer): nessun rilievo bloccante; il cambio visibile è
  quello dichiarato. Pint segnala solo violazioni storiche su righe non
  toccate. I file di test sono untracked: il reviewer non ha potuto
  confrontarli con una versione precedente.
- Nota del reviewer: il componente `select` (non coinvolto da questo
  fix) con default `''` mostrerebbe un'opzione vuota al posto del
  placeholder. Oggi i chiamanti usano select2, quindi non si manifesta;
  da tenere presente se un nuovo chiamante usasse `select`.
- Il caso cambia solo da errore a valore vuoto; il gruppo primario orfano
  resta comunque un dato incoerente (non corretto qui).

## Rollback

`git revert` del singolo fix (indipendente dal [320](320-menu-tenants-name-senza-virgola-finale.md)):
ripristinare in `app/Helpers/UserHelper.php` la riga
`return UserHelper::me()->primary_group()->name;` e il test in
`tests/Feature/UserModelLookupsTest.php`. Nessuna migrazione, nessun dato
toccato.
