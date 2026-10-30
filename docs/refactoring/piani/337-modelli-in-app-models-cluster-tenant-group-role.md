# Piano 337 — Modelli in `app/Models/`, batch B3: `Tenant`, `Group`, `Role`, `UsersGroup`

Stato: **approvato ed eseguito (doc 337)**. Branch: `refactor/models`.
Autore: `/architect`, 2026-10-30. Destinatario: l'implementatore (non vede la
conversazione: questo file è autosufficiente). Origine: dopo B1 (`User`, piano 336) la persona ha
scelto di spostare **insieme** il cluster tenant/permessi, partendo dai modelli più complessi,
invece di un modello per batch. **Deroga consapevole** alla regola "un modello per batch" del
piano 336: i quattro modelli sono accoppiati (`User` li usa tutti, `Group` usa `Tenant`) e si
verificano meglio insieme; per contenere il rischio i passi interni sono comunque ordinati e
isolati (vedi "Passi").

## Obiettivo

Spostare `App\Tenant`, `App\Group`, `App\Role`, `App\UsersGroup` in `App\Models\`, con alias di
compatibilità per i controller custom dei clienti, **senza cambiare comportamento**: nessun
metodo, relazione, tabella o scope cambia.

## Fatti verificati (2026-10-30)

- `Tenant` (tabella `tenants`, `SoftDeletes`, `$fillable`), `Group` (`groups`, `SoftDeletes`,
  relazione `assignedTenants` verso `Tenant`, wrapper `add_tenant()` → Action `AddTenantToGroup`),
  `Role` (`cms_privileges`), `UsersGroup` (`users_groups`, `SoftDeletes`, senza timestamp, scope
  `ofUser`/`ofGroup`). Tutti 10-31 righe.
- **Riferimenti `App\<Nome>` nel repo: 39 in 20 file** (ricerca a testo, 2026-10-30):
  - codice: `Models/User.php` (3 `use`), `Actions/Groups/AddTenantToGroup.php`, `Helpers/` (`UserHelper`
    4, `TenantHelper` 2, `GroupHelper` 2, `ModuleHelper` 1), `Controllers/System/`
    (`AdminGroupsController` 6, `AdminCmsUsersController` 5, `AdminController` 2,
    `AdminTenantsController` 1, `ModulsController` 1);
  - viste Blade: `users/profile.blade.php` (2: `\App\Tenant::find`, `\App\Group::find`),
    `crudbooster/default/form_field.blade.php`, `crudbooster/mass_edit/form_body.blade.php`,
    `crudbooster/ordini/form_body.blade.php` (1 ciascuna, `\App\Group::find`);
  - test: `ActionsTest`, `ModelScopesTest`, `ModelRelationsTest`, `GroupsCrudTest` (solo un commento),
    `UserModelLookupsTest`.
  - Nessun riferimento in `config/`, `routes/`, `database/`, `bootstrap/`, `composer.json`,
    `phpstan*.neon`, file `.sql`/`.yml`.
- **Colonne polimorfiche**: nel DB locale (`customerhive`, `customerhive_testing`) l'unica colonna
  `*able_type` è `personal_access_tokens.tokenable_type`, già gestita in B1 e che non riguarda questi
  quattro modelli. **Nessuna migration di dati necessaria.** (Da riconfermare sul DB di un cliente
  reale prima di aggiornarlo: la persona può verificarlo con la query
  `SELECT table_name, column_name FROM information_schema.columns WHERE column_name LIKE '%able_type'`.)
- **Dipendenze "silenziose" nello stesso namespace** (il rischio principale di questo batch): tre
  modelli ancora in `App\` usano `Tenant::class`/`Group::class` **senza `use`**, perché finora
  erano nello stesso namespace: `ChatAIConf` (righe 47, 55), `Menu` (34, 41, 81), `QlikItem` (23, 31).
  Dopo lo spostamento risolverebbero `App\Tenant`/`App\Group`: grazie all'alias continuerebbero a
  funzionare **per caso**, ma `Menu`/`QlikItem`/`ChatAIConf` vanno comunque corretti con un
  `use App\Models\Tenant;`/`Group;` esplicito (altrimenti il giorno che l'alias viene tolto si
  rompono le relazioni). Quando i loro batch li sposteranno in `App\Models`, quel `use` va
  rimosso (stesso namespace).
- Gli altri riferimenti non qualificati (`Tenant::`, `Group::`, `UsersGroup::`, `Role::`) stanno
  in file che hanno già il `use` o in `Models/User.php` (che dopo lo spostamento è nello stesso
  namespace e non ha più bisogno dei `use`).
- L'alias si aggiunge in `app/Support/legacy_crudbooster_aliases.php` (già caricato da
  `composer.json`, nessuna modifica a `composer.json`). `User` ha già il suo (batch B1).

## Perimetro

Dentro:
1. Spostare i 4 file in `app/Models/` (solo `namespace App\Models;` + gli `use` necessari;
   `Group` mantiene `use App\Actions\Groups\AddTenantToGroup;`; `Tenant` in `Group` non richiede
   più `use`, stesso namespace).
2. 4 `class_alias(..., 'App\<Nome>')` nella sezione "Modelli spostati in App\Models".
3. Aggiornare i riferimenti del repo (elenco sopra) al nuovo nome: `use`, `\App\X` inline, Blade,
   test. Non toccare i commenti storici in `docs/refactoring/`.
4. Aggiungere `use App\Models\Tenant;` / `use App\Models\Group;` in `ChatAIConf`, `Menu`,
   `QlikItem` (vedi sopra).
5. Rimuovere i `use App\Group;`, `use App\Role;`, `use App\Tenant;` da `Models/User.php` (stesso
   namespace dopo lo spostamento).

Fuori (non toccare): metodi/relazioni/tabelle dei modelli, la rinomina al singolare
(`UsersGroup` → `UserGroup`), le pivot (`GroupTenants`, `MenuGroups`, …: batch successivo),
`Menu`, `Modules`, Qlik, ChatAI, `Clienti`/`Ordini`/`Righe` (piano 317), `CBController`/`CRUDBooster::`,
controller custom dei clienti.

## Pattern e perché

Spostamento con alias di compatibilità, come B1 (ADR A17): il vecchio FQCN continua a funzionare
per i controller custom dei clienti (`use App\Tenant;`, `instanceof \App\Group`, …), il codice del
repo usa il nuovo. Qui **non serve la migration** di B1 perché nessun dato salvato nel DB contiene
il nome di queste classi.

## Passi

1. **Test di caratterizzazione prima** (vedi sotto): scriverli ed eseguirli sul codice attuale; i
   test di alias sono rossi fino al passo 3 (dichiararlo nel docblock e pulirlo al passo 3).
2. Spostare **uno alla volta in questo ordine**, con `php -l` dopo ciascuno: `Role` e `UsersGroup`
   (nessuna dipendenza tra i modelli), poi `Tenant`, poi `Group` (dipende da `Tenant`). Per
   ciascuno: spostare il file, aggiungere l'alias, aggiornare i riferimenti di quel modello.
3. Aggiungere i `use` espliciti in `ChatAIConf`, `Menu`, `QlikItem`; ripulire i `use` di `User`.
4. Ricerca a testo fisso finale (`App\\Tenant`, `App\\Group`, `App\\Role`, `App\\UsersGroup`,
   anche in `resources/views/`): devono restare solo l'alias, i commenti storici in `docs/` e i
   test di alias.
5. Pint `--test` sui soli file toccati, Larastan (perimetro `app/Helpers` e `Controllers/System`)
   anche da cache vuota (`phpstan clear-result-cache`): la baseline può citare questi nomi.
6. A fine batch: documento in `docs/refactoring/` (numero libero al momento, README), aggiornare
   `mappa.md` e la riga di `pattern-nel-repo.md`, post-mortem in `lezioni.md`; ADR A17 resta valida
   (nota: B3 fatto con alias, nessuna migration).

## Test di caratterizzazione

Esistono già e sono la regressione: `ModelRelationsTest`, `ModelScopesTest`, `UserModelLookupsTest`,
`ActionsTest`, `GroupsCrudTest`, `UsersCrudTest`, i test di login/tenant. Da aggiungere in
`tests/Feature/TenantGroupModelMoveTest.php`:

1. **Alias** per ciascuno dei 4: `class_exists('App\X')`, `new \App\X instanceof \App\Models\X`,
   i due `::class` risolvono alla stessa classe reale (rosso fino al passo 2).
2. **Tabella e comportamento invariati** (verdi anche prima): `getTable()` = `tenants`/`groups`/
   `cms_privileges`/`users_groups`; `UsersGroup` senza timestamp; soft delete attivo su
   `Tenant`/`Group`/`UsersGroup` (positivo) e assente su `Role`.
3. **Relazioni dei modelli ancora in `App\`** (il rischio silenzioso): `ChatAIConf::tenants()`/
   `groups()`, `Menu::tenants()`/`groups()`, `QlikItem::tenants()`/`groups()` restituiscono
   modelli `App\Models\Tenant`/`App\Models\Group` (positivo con dati veri; negativo: un gruppo non
   assegnato non compare — stesso setup).
4. **`Group::add_tenant()`** (wrapper usato dai controller custom): assegna il tenant; la
   seconda chiamata con lo stesso tenant lancia `QueryException` (unique `group_id, tenant_id`,
   nessun controllo duplicati: comportamento attuale, da non cambiare).
5. Nota emersa scrivendo i test: `Tenant::delete()` (Eloquent) fallirebbe oggi perché `tenants`
   non ha `updated_at`; il test sul soft delete marca `deleted_at` via DB e verifica solo il global
   scope. Non si corregge in questo batch (cambierebbe comportamento).

Regola (lezione 2026-10-29): ogni negativo ha il suo positivo con lo stesso setup. Regola
(lezione 2026-10-30): i test si scrivono **ed eseguono sul codice vecchio** prima di spostare.
Se un test fa DDL o `TRUNCATE`, tearDown che ripulisce (lezione doc 324).

## Vincoli

- Behavior-preserving. Nessun metodo/relazione cambia; nessun testo UI nuovo.
- I vecchi nomi devono continuare a funzionare finché ogni cliente non è migrato.
- Nessun commit/push, nessuna suite completa in autonomia (la lancia la persona con
  `/refactor-verify`; solo i file toccati, `full` a fine fase).
- Il fatto che l'alias nasconda un `use` dimenticato è voluto: la ricerca finale (passo 4) è la rete
  di sicurezza.

## Impatto sui clienti

Nessuno atteso, a condizione che il processo di update rigeneri l'autoload di Composer (già
richiesto per ogni classe nuova). Il codice custom che usa `App\Tenant`/`Group`/`Role`/`UsersGroup`
continua a funzionare tramite alias. **Cambia comportamento visibile? No.**

## Rischio

Medio. È il nucleo dell'isolamento tra tenant e dell'autorizzazione, ma la modifica è solo di
namespace. Punti di attenzione: (1) i tre modelli ancora in `App\` che usano `Tenant`/`Group` senza
`use` (coperti dal test 3); (2) le viste Blade con `\App\Group::find` (4 punti) vanno aggiornate
una per una: un errore si vedrebbe solo caricando quelle pagine (profilo, form ordini, mass edit,
form_field); (3) `AdminController` ha 7 usi di `Tenant::where(...)` nei flussi di login/redirect per
tenant: il `use` va aggiornato, un errore bloccherebbe il login per dominio; (4) con
`composer dump-autoload --classmap-authoritative` in produzione serve il dump dopo l'update.

## Rollback

Rimettere i 4 file in `app/`, togliere i 4 alias, ripristinare i riferimenti e i `use` (il diff li
elenca). Nessun dato toccato, nessuna migration da annullare.

## Decisioni aperte (per la persona)

- Nessuna: ordine (cluster insieme), assenza di migration e alias sono già decisi. Da confermare
  solo sul DB di un cliente reale la query `*able_type` sopra, prima del suo aggiornamento.
