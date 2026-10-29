# 322 - Prime Action del repo e local scope (batch 3 del piano 317)

- **Data**: 2026-10-29
- **Stato**: Completato (123 test verdi nel container, vedi "Test")
- **Area**: Architettura / Modelli
- **File/aree di codice coinvolte**:
  - Nuovi: `app/Actions/Menus/AssignDefaultMenuTenant.php`,
    `app/Actions/Menus/AssignDefaultMenuGroup.php`,
    `app/Actions/Groups/AddTenantToGroup.php`
  - Modelli: `app/Menu.php`, `app/Group.php`, `app/UsersGroup.php`
  - Controller di sistema: `MenusController`, `ModulsController`,
    `AdminGroupsController`, `AdminChatAIController`,
    `AdminQlikItemsController`, `QlikItemsController`,
    `AdminCmsUsersController`
  - Helper/servizi: `app/Helpers/GroupHelper.php`, `app/Helpers/UserHelper.php`,
    `app/Services/QlikSync/QlikSyncRollback.php`
  - Test nuovi: `tests/Feature/ActionsTest.php`,
    `tests/Feature/ModelScopesTest.php`

## Contesto

Terzo batch del piano
[`piani/317-models-standard-laravel.md`](piani/317-models-standard-laravel.md)
(sezione "Batch 3"), dopo i test di caratterizzazione e l'igiene dei modelli
([317](317-modelli-eloquent-caratterizzazione-e-igiene.md)), le autorizzazioni
ChatAI ([318](318-chatai-autorizzazioni-su-tabelle-dedicate.md)), le relazioni
additive ([319](319-modelli-eloquent-relazioni-additive.md)) e le due correzioni
del batch 2b ([320](320-menu-tenants-name-senza-virgola-finale.md),
[321](321-user-primary-group-name-gruppo-orfano.md)). Introduce le **prime
Action del repo** (nuovo pattern in `app/Actions/`) e i **local scope** su
`Menu` e `UsersGroup`. Gli scope da fare (gruppi A e B) sono stati scelti dalla
persona; mai global scope.

## Situazione prima

- Tre azioni "crea la riga pivot di default" vivevano come metodi dei modelli:
  `Menu::assign_default_tenant()`, `Menu::assign_default_group()`,
  `Group::add_tenant()`. Ognuna crea una riga in `menu_tenants`, `menu_groups`,
  `group_tenants` con `save()`; chiamate da `MenusController` (`hook_after_add`,
  ~742), `ModulsController` (`postStep1`, ~882-883) e `AdminGroupsController`
  (`hook_after_add`, ~333).
- Le stesse condizioni `where` si ripetevano in molti punti: `name='Dashboard'
  and is_active=1 and is_dashboard=1` su `Menu`, `path = 'qlik_items/content/'.
  $itemId` su `Menu`, `user_id`/`group_id` su `UsersGroup` (soft-delete globale).

## Situazione dopo

**Parte 1 - Action.** Tre classi, una per azione, un solo `handle()`, senza
stato e senza dipendenze da `UserHelper`, sessione o richiesta (gli id li passa
il chiamante):

- `App\Actions\Menus\AssignDefaultMenuTenant::handle(Menu $menu, $tenantId)`
- `App\Actions\Menus\AssignDefaultMenuGroup::handle(Menu $menu, $groupId)`
- `App\Actions\Groups\AddTenantToGroup::handle(Group $group, $tenantId)`

Gli id sono **volutamente senza tipo**: `UserHelper::current_user_tenant()` e
`current_user_primary_group()` non hanno tipo di ritorno e un `int` avrebbe
cambiato il valore salvato. Le Action creano le stesse righe di prima con
`save()`, senza controllo di duplicati: le tre pivot hanno un unique composito
(`menu_tenant_unique`, `menu_group_unique`, `group_tenant_unique`), quindi un
secondo insert lancia ancora `QueryException`. I metodi dei modelli restano come
**wrapper sottili con docblock** perché i controller custom dei clienti (non
visibili) potrebbero chiamarli. I tre chiamanti nei controller di sistema
usano ora le Action; i metodi omonimi `add_tenant` di `AdminChatAIController`,
`AdminGroupsController`, `AdminQlikItemsController` non sono toccati.

**Parte 2 - Local scope.**

- `Menu::scopeActiveDashboard` (`name='Dashboard' and is_active=1 and
  is_dashboard=1`): usato in `AdminChatAIController` (~775) e
  `AdminQlikItemsController` (~566, ma dentro un commento `/* */`: nessun
  effetto).
- `Menu::scopeQlikItemContent($itemId)` (`path = 'qlik_items/content/'.$itemId`):
  `AdminQlikItemsController` (~558), `QlikItemsController` (~31),
  `QlikSyncRollback` (~111, con `->exists()`).
- `UsersGroup::scopeOfUser($userId)` e `scopeOfGroup($groupId)` (SoftDeletes
  globale invariato): sostituita solo la condizione iniziale lasciando intatta
  la catena, in `GroupHelper` (24, 47, 84, 94), `UserHelper` (229, 439),
  `AdminCmsUsersController` (1323, 1452), `AdminGroupsController` (59, 371, 479,
  515; il 515, `remove_member`, convertito dopo il rilievo del reviewer).

SQL e bindings sono identici in ogni punto (verificato da reviewer e dal test).

**Non fatti per scelta**: gruppo C (`GroupTenants::where(...)` ripetuto 5+3
volte in `ModuleHelper::can_list/view/edit/add/delete`: sono i controlli di
permesso per tenant, rischio medio-alto) e gruppo D (`Tenant::where('domain_name',
...)` x7 in `AdminController`: login/MFA, sotto la migrazione "strangler fig"
A2). Non toccati anche `MenuHelper.php:72` (`parent_id`, non ripetuto) e i punti
di `MenusController` (costruzione/parsing del path, non query identiche).

**Comportamento visibile: nessun cambio.**

## Motivazione

Le Action isolano la scrittura in un punto testabile senza dipendere da
sessione/richiesta e danno al repo un primo esempio del pattern; gli scope
eliminano condizioni duplicate e danno un nome al significato ("dashboard
attiva", "contenuto del Qlik item"). Si è scelto di non usare mai global scope
(effetti nascosti su tutte le query) e di lasciare i wrapper nei modelli per non
rompere i controller dei clienti. Gruppi C e D scartati per ora per il rischio.

## Test

Eseguiti nel container su `customerhive_testing`:

- Nuovi `tests/Feature/ActionsTest.php` (8 test: le tre Action, i wrapper,
  secondo insert -> `QueryException`, wrapper `Menu::assign_default_*` con
  utente loggato) e `tests/Feature/ModelScopesTest.php` (7 test: confronto
  `toSql()`/bindings fra scope e query esplicita, `activeDashboard` su 4 menu,
  `qlikItemContent`, `UsersGroup` con soft-deleted e `withTrashed()`, `delete()`
  via scope = soft delete).
- Regressione insieme: `ModelRelationsTest`, `GroupsCrudTest`,
  `CrossModuleRelationsTest`, `UsersCrudTest`, `MenusCrudTest`,
  `ModuleStep1MenuDefaultsTest`, `MenuTenantsCharacterizationTest`,
  `UserModelLookupsTest`, `QlikItemVisibilityTest`, `QlikItemPublicAccessTest`,
  `ChatAIAuthorizationTest`: **123 passati, 314 assert, nessun fallimento**.
- Larastan (`phpstan analyse` su `app/Helpers` e `Controllers/System`): nessun
  errore nuovo. `php -l` pulito.
- Pint `--test` (solo informativo): Action e test nuovi passano; i file storici
  segnalano violazioni su righe non toccate. Avvisi git LF->CRLF su alcuni file
  (autocrlf, innocuo).

Non verificato: i file non indicizzati (vedi sotto) e prove manuali da browser.

## Rischi e note

Esito della review (`ch-reviewer`): nessun rilievo bloccante né strutturale su
Action e scope; behavior-preserving confermato.

- **Dubbio minore (`TypeError`)**: se `Menu::find()`/`Group::find()` restituisce
  `null`, prima si aveva `Error` "Call to a member function ... on null", ora
  `TypeError` sul parametro tipizzato. Sempre un 500 non catturato; cambia solo
  il messaggio, non visibile all'utente.
- Il punto `AdminGroupsController:515` (`remove_member`) era stato dimenticato
  ed è stato convertito dopo la review.
- I controller custom dei clienti non sono toccati e i wrapper restano per
  loro. La ricerca dei chiamanti è limitata ai file indicizzati: 52 PHP non
  indicizzati (probabili controller dei clienti) non sono verificabili e restano
  com'erano; gli scope sono additivi, i `where` espliciti continuano a
  funzionare.
- Gruppi C e D degli scope rimandati (vedi sopra).

## Rollback

Nessuna migrazione né dato coinvolto: `git revert` dei commit del batch (solo
codice e test). Le due parti sono separabili, ma toccano in parte gli stessi
file (`Menu.php`, `Group.php`, `UsersGroup.php`, `AdminGroupsController.php`),
quindi un revert parziale va fatto a mano su quei file. Ripristinando le
chiamate originali ai metodi dei modelli basta eliminare `app/Actions/` e i
due test nuovi.
