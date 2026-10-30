# 338 - Pivot/autorizzazioni e `Modules` spostati in `App\Models`

- **Data**: 2026-10-30
- **Stato**: Completato (156 test verdi dall'implementatore; poi `/refactor-verify` su 23 file: 319 verdi, 1468 asserzioni; suite completa da lanciare)
- **Area**: Architettura / Modelli
- **File/aree di codice coinvolte**:
  - `app/Models/` (9 file nuovi: `GroupTenants`, `MenuTenants`, `MenuGroups`, `ModuleTenants`,
    `TenantsAllowed`, `ItemsAllowed`, `ChatAIGroupAllowed`, `ChatAITenantAllowed`, `Modules`);
    i 9 vecchi `app/<Nome>.php` eliminati
  - `app/Support/legacy_crudbooster_aliases.php` (9 alias)
  - `use`/riferimenti in `app/Actions/{ChatAI,Groups,Menus}/`, `app/Helpers/{ModuleHelper,TenantHelper}.php`,
    7 controller di `Controllers/System/`, la Blade `group_members_datamodal/browser.blade.php`
  - `use` espliciti in `app/Menu.php`, `app/QlikItem.php`, `app/ChatAIConf.php`
  - `tests/Feature/PivotModelMoveTest.php` (nuovo)
  - Piano: `piani/338-modelli-in-app-models-pivot-e-modules.md`

## Contesto

Quarto batch di spostamento dei modelli in `App\Models\` (ADR A17), dopo `User` (336) e il cluster
`Tenant`/`Group`/`Role`/`UsersGroup` (337). La persona ha scelto di spostare in un unico batch le
8 tabelle pivot/autorizzazioni e `Modules`, lasciando per dopo `Menu`, `ChatAIConf`, il cluster Qlik,
`Log`/`AccessLog` e `Clienti`/`Ordini`/`Righe` (non di sistema).

## Situazione prima

I 9 modelli stavano in `app/` con namespace `App`: classi di 12-17 righe, senza metodi né relazioni
(`$table`, `$timestamps = false`; `Modules` con `SoftDeletes`). Tre modelli rimasti in `App\`
(`Menu`, `QlikItem`, `ChatAIConf`) li usavano senza `use`, per lo stesso namespace.

## Situazione dopo

I 9 modelli sono in `App\Models\` (solo il namespace cambia), con `class_alias` sui vecchi nomi per i
controller custom dei clienti. `Menu`, `QlikItem` e `ChatAIConf` hanno `use App\Models\...` espliciti
(da togliere quando si sposteranno a loro volta). Riferimenti qualificati aggiornati, compresi i due
inline `\App\ItemsAllowed` in `AdminGroupsController` (righe 590/599) e `\App\GroupTenants` nella Blade.
Rimossi 4 import mai usati (`MenusController`→`MenuTenants`, `AdminModuleHelperController`→
`ItemsAllowed`/`TenantsAllowed`, `TenantHelper`→`Modules`). **Nessun cambio visibile.** Nessuna migration:
l'unica colonna `*able_type` del DB locale è `personal_access_tokens.tokenable_type` (già gestita in 336).

## Motivazione

Allineare i modelli alla convenzione Laravel (`app/Models`) per batch piccoli, senza rompere i clienti
che li citano per nome (alias). Deroga consapevole alla regola "un modello per batch": sono modelli
minimali e si verificano bene insieme.

## Test

- Nuovo `PivotModelMoveTest` (22 test): alias per ciascuno dei 9, tabella e `timestamps=false` dei pivot,
  soft delete di `Modules`, `Menu::tenants`, `QlikItem::allowed*`, `ChatAIConf::allowedGroups/Tenants`
  (positivo e negativo sullo stesso setup; quest'ultimo non era coperto) e render della Blade
  `group_members_datamodal/browser`. Sul codice vecchio: 9 alias rossi, 13 verdi.
- Dopo: 22/22 verdi; con la regressione del piano 14 file / 156 test / 467 asserzioni verdi.
- Verifiche statiche: `php -l`, `composer dump-autoload`, Larastan da cache vuota senza errori, Pint
  `--test` pulito sui file nuovi. Ricerca finale a testo fisso (anche rifatta indipendentemente dalla
  sessione principale): il vecchio nome resta solo negli alias e nei test di alias.
- `/refactor-verify` (su richiesta della persona) su 23 file (i precedenti più i test che toccano le tabelle
  dei 9 modelli, tra cui i due ChatAI non ancora eseguiti): **319 verdi, 1468 asserzioni, 0 falliti**
  (713 s). Test scelti cercando i nomi di tabella in `tests/`, non con `tokensave_affected` (l'indice
  serve `dev`); suite completa non lanciata.
- **Non verificato**: la query `*able_type` su un DB di un cliente reale;
  `composer --classmap-authoritative`. Nessun reviewer lanciato (modifica di solo namespace).

## Rischi e note

- Il vecchio nome continua a funzionare solo grazie all'alias: un `use` dimenticato non si vede.
- Pint segnala violazioni storiche (CRLF, tab, `use \`) sui file legacy toccati; non riformattati per
  non gonfiare il diff.
- Le 8 chiamate `GroupTenants` in `ModuleHelper` non hanno un test dedicato (coperte da `GroupsCrudTest`
  e `CrossModuleRelationsTest`).
- In produzione serve `composer dump-autoload` dopo l'update.

## Rollback

Rimettere i 9 file in `app/` con namespace `App`, togliere i 9 alias, ripristinare riferimenti e `use`
(incluso gli import rimossi). Nessun dato toccato, nessuna migration.
