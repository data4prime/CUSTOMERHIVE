# 337 - `Tenant`, `Group`, `Role`, `UsersGroup` spostati in `App\Models` (batch B3 del piano 337)

- **Data**: 2026-10-30
- **Stato**: Completato (8 test nuovi verdi, Larastan pulito; regressione sui test esistenti da lanciare con `/refactor-verify`)
- **Area**: Architettura / Modelli
- **File/aree di codice coinvolte**:
  - `app/Models/Tenant.php`, `Group.php`, `Role.php`, `UsersGroup.php` (spostati da `app/`)
  - `app/Support/legacy_crudbooster_aliases.php` (4 alias nuovi)
  - `app/Helpers/` (`UserHelper`, `TenantHelper`, `GroupHelper`, `ModuleHelper`), `app/Actions/Groups/AddTenantToGroup.php`
  - `app/Http/Controllers/System/` (`AdminController`, `AdminCmsUsersController`, `AdminGroupsController`, `AdminTenantsController`, `ModulsController`)
  - `app/ChatAIConf.php`, `app/Menu.php`, `app/QlikItem.php` (`use` espliciti)
  - 4 viste Blade (`users/profile`, `crudbooster/default/form_field`, `crudbooster/mass_edit/form_body`, `crudbooster/ordini/form_body`)
  - test: `TenantGroupModelMoveTest` (nuovo) + `use` aggiornati in 4 test esistenti

## Contesto

Dopo B1 (`User`, doc 336) la persona ha chiesto di spostare i modelli più complessi per primi e di
farlo come cluster, invece di uno alla volta: i quattro modelli sono accoppiati (`User` li usa tutti,
`Group` usa `Tenant`). Piano: `docs/refactoring/piani/337-modelli-in-app-models-cluster-tenant-group-role.md`
(approvato dalla persona). Deroga consapevole alla regola "un modello per batch" del piano 336.

## Situazione prima

I quattro modelli stavano in `App\` (`app/Tenant.php`, ...). 39 riferimenti `App\<Nome>` in 20 file.
`ChatAIConf`, `Menu` e `QlikItem` (ancora in `App\`) usavano `Tenant::class` / `Group::class` senza
`use`, perché nello stesso namespace.

## Situazione dopo

- I quattro file stanno in `app/Models/` (solo `namespace` cambiato, nessun metodo, relazione o tabella).
- Alias `App\Tenant`, `App\Group`, `App\Role`, `App\UsersGroup` in `legacy_crudbooster_aliases.php`:
  i controller custom dei clienti continuano a funzionare.
- Il codice del repo, le viste e i test usano i nomi nuovi; `Models/User.php` non ha più i `use` dei tre
  modelli (stesso namespace).
- `ChatAIConf`, `Menu`, `QlikItem` hanno `use App\Models\Tenant;` / `Group;` espliciti (da togliere quando
  si sposteranno in `App\Models`).
- Nessuna migration: l'unica colonna con un nome di classe nel DB locale è
  `personal_access_tokens.tokenable_type`, già gestita in B1.

## Motivazione

Standard Laravel (`App\Models`), un passo verso togliere il codice dalla root di `app/`. Alternativa
scartata: un modello per batch (più sicuro in astratto, ma i quattro sono accoppiati e il lavoro
è solo di namespace con alias).

## Test

- `tests/Feature/TenantGroupModelMoveTest.php` (nuovo, 8 test / 49 asserzioni): alias dei 4 modelli,
  tabelle e timestamp invariati, global scope SoftDeletes (positivo/negativo), relazioni di `Menu`/
  `QlikItem`/`ChatAIConf` verso `Tenant`/`Group` (il punto silenzioso), `Group::add_tenant()` e
  violazione dell'unique alla seconda chiamata. Scritto ed eseguito **prima** dello spostamento: 4 rossi
  (alias) e 4 verdi; dopo: 8 verdi.
- Larastan da cache vuota: nessun errore nuovo.
- `php -l` su tutti i file `.php` modificati: puliti. Ricerca a testo fisso dei vecchi nomi: restano
  solo gli alias, i test di alias e commenti storici in `docs/`.
- **Non eseguiti**: la suite completa e i test esistenti toccati (`ModelRelationsTest`,
  `ModelScopesTest`, `UserModelLookupsTest`, `ActionsTest`, `GroupsCrudTest`): li lancia la persona con
  `/refactor-verify`. Le 4 viste Blade non sono state renderizzate a mano (profilo utente, form ordini,
  `form_field`, mass edit).

## Rischi e note

- Scrivendo i test sono emersi due comportamenti già esistenti, **non modificati** e ora fotografati:
  `Tenant::delete()` (Eloquent) fallirebbe perché `tenants` non ha `updated_at` (Tenant ha i timestamp
  attivi); `Group::add_tenant()` non controlla i duplicati e la seconda chiamata lancia `QueryException`.
- `AdminController` usa `Tenant::where('domain_name', ...)` nei flussi di login per dominio: un `use`
  sbagliato bloccherebbe il login, quindi vale la pena un giro manuale di login per tenant.
- Prima di aggiornare un cliente reale: riconfermare sul suo DB che non ci siano colonne `*able_type`
  oltre a `personal_access_tokens`, e rigenerare l'autoload di Composer.
- Hook Pint segnala stile storico nei file toccati: ignorato (righe non scritte da me).
- Nessun reviewer lanciato (spostamento di namespace behavior-preserving, come B1).
- Il Bash di questo ambiente consuma i backslash: dopo un `sed` inline il namespace di `Role` e
  `UsersGroup` era `AppModels` (corretto subito con Edit, lezione già in `lezioni.md`).

## Rollback

Rimettere i 4 file in `app/` con il namespace `App`, togliere i 4 alias, ripristinare i `use` e i
riferimenti (il diff li elenca). Nessun dato toccato, nessuna migration da annullare.
