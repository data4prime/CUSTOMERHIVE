# 063 - Modulo Menu Management: 3 bug reali corretti + test CRUD

- **Data**: 2026-09-01
- **Stato**: Completato
- **Area**: Bug fix + test
- **File/aree di codice coinvolte**:
  - `app/Helpers/MenuHelper.php`
  - `app/Http/Controllers/System/MenusController.php`
  - `app/Http/Controllers/System/CBController.php`
  - `tests/Feature/MenusCrudTest.php` (nuovo)
  - `tests/Concerns/SeedsCmsData.php`

## Contesto

Richiesta dall'utente un'analisi del modulo Menu Management in vista di
test CRUD, sullo stesso modello di Tenants/Groups/Privileges/Users
(vedi [060](060-groups-can-view-crash-standard-e-tenants-list-vuota.md)
e i test *CrudTest.php precedenti). L'analisi ha trovato 3 bug reali,
tutti corretti prima di scrivere i test (altrimenti li avrebbero
bloccati o mascherati).

## Bug 1: UrlGenerationException su ogni voce di menu modificabile

Gia' segnalato come "da investigare separatamente" nel commit di
migrazione a Laravel 13 (2a4dab24): `/admin/menu_management` crashava
con `UrlGenerationException` non appena esisteva almeno una voce di
menu modificabile.

Causa in `MenuHelper::menu_to_html()`:
```php
$href = "";
if (route("MenusControllerGetEdit", ["id" => $menu->id])) {
  //$href = route("MenusControllerGetEdit", ["id" => $menu->id]) . "?return_url=" . $return_url;
  $href = route("MenusControllerGetEdit") . "/" . $menu->id . "?return_url=" . $return_url;
}
```
La chiamata dentro l'`if` (corretta, con l'id) serve solo a un
controllo di verita' e il risultato viene scartato; quella usata per
costruire `$href` manca il parametro `id`, obbligatorio per la rotta
`admin/menu_management/edit/{id}`. La riga corretta era gia' presente
**commentata** subito sopra - sembra un refactor lasciato a meta'.

**Correzione**: usata la riga gia' corretta (quella commentata), tolta
la concatenazione manuale.

## Bug 2: crash creando la primissima voce di menu

`MenusController::hook_before_add()`:
```php
$id = (Menu::orderby('id', 'desc')->first()->id) + 1;
```
Su una `cms_menus` completamente vuota (es. subito dopo
un'installazione pulita, o se qualcuno cancella tutte le voci),
`first()` torna `null` e `->id` crasha ("Attempt to read property
'id' on null").

**Correzione**: stesso pattern di null-safety gia' usato altrove in
questa sessione (`sidebarDashboard()`, `get_group_id()`/`get_tenant_id()`
in [060](060-groups-can-view-crash-standard-e-tenants-list-vuota.md)):
```php
$lastMenu = Menu::orderby('id', 'desc')->first();
$id = ($lastMenu ? $lastMenu->id : 0) + 1;
```

## Bug 3: CBController::getDelete() - variabile inesistente sul ramo "accesso negato"

Bug preesistente nel controller BASE condiviso da tutti i moduli CRUD
(Tenants, Groups, Users, Menus, ecc.), non specifico di Menu
Management - scoperto scrivendo il test sul permesso di cancellazione
di un tenantadmin:

```php
if (!ModuleHelper::can_delete($this, $row)) {
    CRUDBooster::insertLog(trans("crudbooster.log_try_delete", [
        'name' => $module->{$this->title_field},   // $module non e' mai definita qui
        'module' => CRUDBooster::getCurrentModule()->name
    ]));
    return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
}
```
**Qualunque tentativo di cancellazione bloccato da `ModuleHelper::
can_delete()`, su qualunque modulo CBController, crashava con un 500**
invece di mostrare "accesso negato" - mai emerso prima perche' tutti i
test precedenti usavano solo un attore superadmin (per cui
`can_delete()` ritorna sempre true, bypassando questo ramo).

**Correzione**: `$module` -> `$row` (il dato corretto e' gia' letto
cosi' qualche riga sotto, per il log della cancellazione riuscita).

## Comportamento trovato e SOLO caratterizzato (non corretto)

`MenusController::hook_before_edit()` ricostruisce sempre `path` dai
campi specifici del `type` (Module/Statistic/Qlik/Agent AI) prima di
un controllo pensato per preservare il suffisso `?m=<id>` aggiunto in
creazione:
```php
$path = explode('?', $postdata['path'])[0] ?? '';
$args = explode('?', $postdata['path'])[1] ?? '';
$key = explode('=', $args)[0] ?? '';
if ($key == 'm') {
    $postdata['path'] = $path . '?m=' . $id;
}
```
Il `path` appena ricostruito per questi 4 tipi non contiene mai un
`?` (es. `'users'`, `'statistic_builder/show/slug'`), quindi `$key`
non e' mai `'m'` e il blocco non scatta: **il parametro `?m=` sparisce
ad ogni modifica di una voce di questi tipi**, anche se la modifica
non cambia il `type`. Comportamento non deciso con l'utente in questa
sessione (a differenza dei 3 bug sopra, dove la correzione era
inequivocabile) - solo documentato e coperto da un test di
caratterizzazione (`test_modifica_di_un_menu_di_tipo_module_perde_il_parametro_m`).
Se in futuro emergono problemi di rendering (fullpage/fillcontent) su
voci Module/Statistic dopo una modifica, ripartire da qui.

## CRUDBooster::redirectBack() - NON toccata

`MenusController::hook_before_edit()` usava anche
`CRUDBooster::redirectBack()` per la regola "non puoi disattivare
l'unica voce impostata come dashboard". A differenza di
`CRUDBooster::redirect()`/`CBController::validation()` (gia'
rifattorizzate da `exit()` a `return` in una sessione precedente),
`redirectBack()` esce ancora con `exit()` in ogni ramo - e non e'
stata toccata qui: e' usata anche da `QlikHelper::getJWTTokenOP()`,
chiamata a sua volta da 2 **Blade view** (`mashup.blade.php`,
`mashup_objects.blade.php`), dove un semplice `return` non basta a
fermare il rendering a meta' - servirebbe un meccanismo diverso, non
valutato in questa sessione per non rischiare di rompere l'embedding
Qlik via mashup (funzionalita' reale, fuori scope Qlik di questa
sessione).

**Soluzione mirata**: nel solo `hook_before_edit()` di
`MenusController`, sostituita quella chiamata con
`CRUDBooster::redirect()` (gia' testabile, stesso pattern
hook->Response degli altri controller):
```php
return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.cannot_disable_dashboard'), 'error');
```
Unica differenza visibile: si torna alla root admin invece che alla
pagina precedente (stesso comportamento gia' presente altrove in
questo controller per i casi di accesso negato). Nessun altro dei 9
punti di chiamata di `redirectBack()` nel resto del codebase e' stato
toccato.

## Test

`tests/Feature/MenusCrudTest.php` (nuovo, 14 test, tutti passano):
lista (regressione bug 1), creazione prima voce su tabella vuota
(regressione bug 2), creazione per type URL/Module/Statistic (verifica
costruzione di `path`), `hook_after_add` assegna il tenant di un
tenantadmin, modifica base, caratterizzazione del comportamento
`?m=` sopra, regola dashboard-unica (regressione del fix mirato su
`redirectBack()`), cancellazione orfanizza i figli invece di
cancellarli a cascata, cancellazione rimuove le associazioni
privilege, un tenantadmin non puo' toccare un menu condiviso tra piu'
tenant (regressione bug 3 - lo stesso test aveva scoperto il crash),
sincronizzazione delle pivot table privileges/tenants/groups in
creazione, riordino/re-parenting via `postSaveMenu()` (drag-and-drop).

`tests/Concerns/SeedsCmsData.php`:
- Nuovo helper `seedMenu()` (stesso pattern di `seedTenant()`/
  `seedGroup()`).
- `actingAsSuperadmin()`/`actingAsTenantUser()` ora impostano anche
  `admin_privileges` in sessione (mancava: `AdminController::
  postLogin()` la popola sempre, ma nessun test l'aveva mai
  impostata prima d'ora perche' nessun hook testato finora dipendeva
  da `CRUDBooster::myPrivilegeId()`). Cambio additivo, non ha
  impattato nessuno dei 77 test preesistenti.

Suite completa: 91/91 test passano (77 precedenti + 14 nuovi), nessuna
regressione.

## Rischi e note

- Il comportamento "?m= sparisce alla modifica" e il gap "nessuna
  validazione server-side della profondita' massima di annidamento"
  (notato in analisi, non testato) restano aperti per una eventuale
  decisione di prodotto futura.
- Il bug 3 (`CBController::getDelete()`) e' nel controller condiviso:
  la correzione si propaga automaticamente a tutti i moduli
  CBController-based, non solo a Menu Management.

## Rollback

`git revert` del commit - ripristina tutti e 3 i bug e la chiamata a
`redirectBack()` in `hook_before_edit()`.
