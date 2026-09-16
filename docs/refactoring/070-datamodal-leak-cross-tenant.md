# 070 - Popup "datamodal": tenant admin vedeva record di altri tenant

- **Data**: 2026-09-16
- **Stato**: Completato (correzione applicata, test manuali non ancora eseguiti - vedi "Test")
- **Area**: Sicurezza + Bug fix
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/AdminGroupsController.php` (`members()`)
  - `app/Http/Controllers/System/AdminCmsUsersController.php` (`groups()`)

## Contesto

Segnalato dall'utente: nei campi "datamodal" (i popup di ricerca/selezione
usati per collegare due entità) un tenant admin vedeva record che non
dovrebbero essergli visibili, appartenenti ad altri tenant.

## Il problema

L'endpoint generico che alimenta tutti i popup datamodal,
`CBController::getModalData()` (`app/Http/Controllers/System/CBController.php:832`),
costruisce la query solo da `datamodal_table` + un `datamodal_where`
statico passato dal form del singolo controller (via `whereRaw`). Non
applica **nessuno scoping automatico per tenant**: è responsabilità di
ogni form valorizzare `datamodal_where` di conseguenza. Verificati tutti
gli usi di `datamodal_where` nel codice: due erano lasciati vuoti su
endpoint raggiungibili da un tenant admin (non solo da un superadmin).

1. **`AdminGroupsController::members()`** (riga 409) - popup "aggiungi
   membro" a un gruppo, tabella `cms_users`. La pagina che elenca i membri
   già esistenti filtra correttamente per tenant (riga 387-390:
   `->where('cms_users.tenant', ...)` se `UserHelper::isTenantAdmin()`),
   ma il popup di **ricerca per aggiungerne uno nuovo** no: un tenant
   admin vedeva nome/email di utenti di qualsiasi altro tenant e poteva
   selezionarli per aggiungerli al proprio gruppo.

2. **`AdminCmsUsersController::groups()`** (riga 455) - popup "aggiungi a
   un gruppo" per un utente, tabella `groups`. Stesso problema
   all'inverso: un tenant admin che modifica un utente vedeva tutti i
   gruppi di tutti i tenant, non solo i propri (i gruppi non hanno una
   colonna `tenant` diretta, l'appartenenza è nella tabella pivot
   `group_tenants`).

Entrambi gli endpoint richiedono solo `CRUDBooster::isRead()`, nessun
controllo `isSuperadmin()`.

## Verificato e SCARTATO come falso allarme

Altri usi di `datamodal_where` vuoto negli stessi tipi di popup
(`item_access_datamodal`, `item_tenant_datamodal`, `tenant_group_datamodal`,
`group_tenant_datamodal` in `AdminQlikItemsController`,
`AdminChatAIController`, `AdminTenantsController`,
`AdminGroupsController::tenant()`) sono tutti dietro
`CRUDBooster::isSuperadmin()` a monte del metodo: lì vedere record di
qualunque tenant è corretto (il superadmin gestisce l'assegnazione
tra tenant), non un bug.

## Correzione

In entrambi i punti, `datamodal_where` viene ora valorizzato
condizionalmente in base a `UserHelper::isTenantAdmin()`:

```php
// AdminGroupsController::members()
$datamodal_where = UserHelper::isTenantAdmin() ? 'tenant = ' . (int) UserHelper::tenant(CRUDBooster::myId()) : '';
```

```php
// AdminCmsUsersController::groups()
$datamodal_where = UserHelper::isTenantAdmin() ? 'id in (select group_id from group_tenants where tenant_id = ' . (int) UserHelper::current_user_tenant() . ')' : "";
```

Un superadmin continua a non avere alcun filtro (comportamento
preesistente, invariato). Il valore iniettato è sempre un intero già
noto lato server (id del tenant dell'utente loggato), non input utente:
nessun rischio di SQL injection nel `whereRaw`.

## Test

Non ho eseguito la suite di test né scritto test automatici per questo
intervento (in attesa di indicazione esplicita, come da prassi). Da
verificare manualmente prima del prossimo giro di test in dev:
- Login come tenant admin del Tenant A -> `groups/members/{group_id}` di
  un proprio gruppo -> il popup "aggiungi membro" deve mostrare solo
  utenti del Tenant A.
- Login come tenant admin del Tenant A -> `users/groups/{user_id}` di un
  proprio utente -> il popup "aggiungi a gruppo" deve mostrare solo
  gruppi assegnati al Tenant A.
- Login come superadmin -> stessi due popup -> devono continuare a
  mostrare rispettivamente tutti gli utenti e tutti i gruppi (nessuna
  regressione per il ruolo superadmin).

## Rischi e note

- Fix scoped ai due punti segnalati/verificati, non un audit completo di
  tutti i possibili usi futuri di `datamodal_where`: un nuovo form che
  collega a una tabella tenant-scoped dovrà ricordarsi di replicare lo
  stesso pattern (`getModalData()` non lo fa automaticamente).
- Nessuna migrazione, nessuna modifica a dati esistenti: solo la query
  del popup di ricerca cambia.

## Rollback

`git revert` del commit - ripristina `datamodal_where` vuoto in
entrambi i punti (torna a mostrare record di ogni tenant nei due
popup). **Sconsigliato**: reintroduce l'esposizione cross-tenant su
un'app in produzione.

Vedi anche [[project-production-clients]].
