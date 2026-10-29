# 319 - Modelli Eloquent: relazioni additive (batch 2 del piano 317)

- **Data**: 2026-10-29
- **Stato**: Completato (98 test verdi nel container, vedi Test)
- **Area**: Architettura / Modelli
- **File/aree di codice coinvolte**:
  - `app/Menu.php` (`assignedTenants()`, `assignedGroups()`, `tenants_name()` senza N+1)
  - `app/Group.php` (`assignedTenants()`)
  - `app/User.php` (`tenantRecord()`, `primaryGroupRecord()`, `memberGroups()`)
  - `app/QlikItem.php` (`allowedGroupRecords()`, `allowedTenantRecords()`)
  - `tests/Feature/ModelRelationsTest.php` (nuovo, 8 test)
  - Piano di riferimento: `docs/refactoring/piani/317-models-standard-laravel.md` (sezione "Batch 2")

## Contesto

Secondo batch del piano di allineamento dei modelli agli standard Laravel,
dopo il batch 0-1 (test di caratterizzazione e igiene sul posto, doc
[317](317-modelli-eloquent-caratterizzazione-e-igiene.md)) e dopo il fix
ChatAI ([318](318-chatai-autorizzazioni-su-tabelle-dedicate.md)), che ha già
dato a `ChatAIConf` le sue relazioni. Il batch 2 introduce le relazioni
Eloquent mancanti, in modo solo additivo: nessun chiamante esistente viene
migrato. CustomerHive è in produzione e i controller custom dei clienti
possono usare i metodi attuali, quindi nulla viene rinominato o sostituito.

Il batch 2b (due fix con effetto visibile) è trattato a parte nel doc 320 e
non è coperto qui.

## Situazione prima

I modelli accedevano alle tabelle pivot e ai record collegati con metodi
"wrapper" scritti a mano (query dirette, array di id), senza relazioni
Eloquent:

- `Menu::tenants()` restituisce un array di id dal pivot `menu_tenants`
  (soft-deleted inclusi); `Menu::tenants_name()` faceva una query per ogni id
  (N+1) per comporre la stringa dei nomi.
- `Group::add_tenant()`, `User::tenant()` / `User::primary_group()`,
  `QlikItem::allowedGroups()` / `allowedTenants()` fanno lookup manuali.
- Non esisteva nessuna relazione per l'appartenenza di un utente ai gruppi
  (`users_groups`) né per i gruppi/tenant di un menu.

## Situazione dopo

Relazioni nuove, con nomi diversi da colonne e metodi esistenti (una
relazione omonima a una colonna sarebbe oscurata dall'attributo):

| Modello | Relazione | Tipo / pivot |
|---------|-----------|--------------|
| `Menu` | `assignedTenants()` | BelongsToMany `Tenant`, pivot `menu_tenants` (`menu_id`/`tenant_id`) |
| `Menu` | `assignedGroups()` | BelongsToMany `Group`, pivot `menu_groups` (`menu_id`/`group_id`) |
| `Group` | `assignedTenants()` | BelongsToMany `Tenant`, pivot `group_tenants` |
| `User` | `tenantRecord()` | BelongsTo `Tenant` via colonna `tenant` |
| `User` | `primaryGroupRecord()` | BelongsTo `Group` via colonna `primary_group` |
| `User` | `memberGroups()` | BelongsToMany `Group`, pivot `users_groups`, con `wherePivotNull('deleted_at')` (`UsersGroup` ha SoftDeletes) |
| `QlikItem` | `allowedGroupRecords()` | BelongsToMany `Group`, pivot `items_allowed` (`item_id`/`group_id`) |
| `QlikItem` | `allowedTenantRecords()` | BelongsToMany `Tenant`, pivot `tenants_allowed` (`item_id`/`tenant_id`) |

Le relazioni Qlik non filtrano il `deleted_at` del pivot, per restare
coerenti con i wrapper esistenti.

`Menu::tenants_name()` è stato riscritto per eliminare l'N+1: una sola query
`whereIn` con `keyBy('id')`, stessa iterazione sugli stessi id e stessa
logica di virgole. L'output è identico, **virgola finale compresa**.

Lasciati intatti di proposito: `Menu::tenants()`,
`QlikItem::allowedGroups()` / `allowedTenants()`, `Group::add_tenant()`,
`User::tenant()` / `User::primary_group()`. Le relazioni passano dai modelli
`Tenant`/`Group`, che escludono i soft-deleted, e quindi non riprodurrebbero
il risultato di oggi. `ChatAIConf` non è toccato (già fatto nel 318).

Nota sul piano: per `User` il piano nominava `groups()`; il nome realmente
usato è `memberGroups()` (piano aggiornato). La ricerca di `->groups` nel
grafo dà 0 risultati, ma 52 file PHP non sono indicizzati (probabilmente
controller dei clienti), quindi un nome così generico non è stato ritenuto
sicuro.

## Motivazione

- Dare ai modelli le relazioni standard di Laravel (eager loading, query
  leggibili) come base per i batch successivi.
- Togliere l'N+1 di `tenants_name()` senza cambiare l'output.
- Approccio additivo: nessun cambio di firma o di risultato per i chiamanti
  esistenti, compresi i controller dei clienti.
- Alternativa scartata: sostituire i wrapper con le relazioni. Cambierebbe il
  risultato quando Tenant/Group sono soft-deleted.

## Test

Verificato davvero (nel container, su `customerhive_testing`):

- Nuovo `tests/Feature/ModelRelationsTest.php`, 8 test: confronto tra le
  relazioni nuove e i metodi esistenti, caso soft-deleted, `tenants_name()`
  ("Alfa, Beta" / "" / "Alfa, " con virgola finale fotografata),
  `memberGroups()` con appartenenza cancellata.
- Insieme alla regressione (`MenuTenantsCharacterizationTest`,
  `ModuleStep1MenuDefaultsTest`, `QlikItemVisibilityTest`,
  `UserModelLookupsTest`, `QlikItemPublicAccessTest`, `MenusCrudTest`,
  `GroupsCrudTest`, `CrossModuleRelationsTest`, `ChatAIAuthorizationTest`):
  **98 test passati, 253 assert, nessun fallimento**.
- Review di `ch-reviewer`: nessun rilievo bloccante; tabelle e chiavi
  coincidono con migrazioni e pivot, nessuna collisione di nomi,
  `tenants_name()` identico nei casi limite (id duplicati, soft-deleted in
  testa/mezzo/coda, lista vuota).

Non verificato: i controller custom dei clienti (vedi Rischi), prove manuali
in browser.

## Rischi e note

Cambi di comportamento visibile: **nessuno**. Nessuna migrazione, nessuna
modifica a login, licenza, permessi o dati.

Rilievi del reviewer, da tenere presenti:

1. Le relazioni nuove escludono Tenant e Group soft-deleted, mentre i wrapper
   `tenants()` e `allowedTenants()` li includono: è il motivo per cui i
   wrapper non sono stati sostituiti. Chi migrerà i chiamanti dovrà
   decidere quale semantica volere.
2. `memberGroups()` filtra il `deleted_at` del pivot, mentre le relazioni
   Qlik no. Scelta non prevista dal piano, giustificata dal SoftDeletes di
   `UsersGroup`; può sorprendere.
3. La virgola finale di `tenants_name()` è mantenuta volutamente (output
   identico); eventuale correzione nel batch 2b.
4. Lacune di test minori: soft-deleted in testa/mezzo a `tenants_name()`,
   gruppi soft-deleted nelle relazioni a `Group`, id duplicati nella pivot.
   Da coprire nel 2b.
5. I 52 file PHP non indicizzati (probabili controller dei clienti) non sono
   verificabili rispetto ai nuovi nomi: da verificare prima di rilasciare ai
   clienti che hanno controller custom.
6. Pint segnala solo violazioni storiche su righe non toccate.

## Rollback

Solo codice e test, nessuna migrazione da annullare: `git revert` dei commit
del batch, oppure ripristino di `app/Menu.php`, `app/Group.php`,
`app/User.php`, `app/QlikItem.php` e rimozione di
`tests/Feature/ModelRelationsTest.php`. Attenzione: i file modello contengono
anche le modifiche del batch 1 (doc 317) e del 318, quindi il revert va
limitato ai commit del batch 2.
