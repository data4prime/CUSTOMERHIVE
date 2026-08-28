# 048 - Un tenant soft-deleted non deve bloccare la cancellazione del gruppo

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Bug fix
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/AdminGroupsController.php`

## Contesto

Segnalato dall'utente: `/admin/groups/tenant/2` (gruppo "Sales") non
mostra nessun tenant, ma il gruppo non si può comunque cancellare.

Causa: `group_tenants` ha ancora 1 riga che collega il gruppo 2 al
tenant id 2 ("test"), ma quel tenant è stato **soft-deleted**
(`tenants.deleted_at = 2026-08-28 11:44:56`). Il metodo
`AdminGroupsController::tenant()` filtra già i tenant soft-deleted
(`where('tenants.deleted_at', null)`, stesso filtro usato dal
datamodal "aggiungi tenant": `'datamodal_where' => 'deleted_at is
null'`), quindi la pagina correttamente non mostra nulla. Ma il
controllo `hook_before_delete()` introdotto in
[045](045-messaggio-cancellazione-gruppo-piu-specifico.md) contava
**tutte** le righe di `group_tenants` per il gruppo, senza verificare
se il tenant collegato fosse ancora attivo — quindi continuava a
bloccare la cancellazione per un'associazione ormai verso un tenant
non più esistente (agli occhi dell'utente, "fantasma").

Stesso pattern già visto in [046](046-groups-members-leftjoin-privilegio-orfano.md)/
[047](047-user-isSuperAdmin-crash-privilegio-orfano.md) (relazione
verso un record cancellato che il resto dell'app tratta come non più
esistente), ma con la differenza che qui la scelta corretta è opposta:
un tenant soft-deleted è deliberatamente "sparito" ovunque
nell'applicazione (non un dato ancora valido da recuperare come
l'utente orfano di 046), quindi il conteggio deve ignorarlo, non
mostrarlo.

## Situazione prima

```php
$tenants_count = GroupTenants::where('group_id', $id)->count();
```

Contava anche le righe che puntano a un tenant soft-deleted.

## Situazione dopo

```php
//esclude i tenant soft-deleted (tenants.deleted_at): la pagina
//"tenant" del gruppo li nasconde gia' (stesso filtro), quindi un
//gruppo il cui unico tenant associato e' stato cancellato non deve
//restare bloccato per un'associazione ormai fantasma
$tenants_count = GroupTenants::where('group_id', $id)
    ->join('tenants', 'tenants.id', '=', 'group_tenants.tenant_id')
    ->where('tenants.deleted_at', null)
    ->count();
```

Stesso filtro già usato da `tenant()` per decidere cosa mostrare:
adesso il conteggio usato dal blocco cancellazione e la pagina che
elenca i tenant sono coerenti.

## Motivazione

Il blocco cancellazione deve riflettere quello che l'utente vede
davvero nell'interfaccia: se la pagina tenant del gruppo è vuota, il
gruppo deve poter essere cancellato — altrimenti l'utente si trova
bloccato da una relazione che, a tutti gli effetti pratici
dell'applicazione (già nascosta ovunque un tenant cancellato
comparirebbe), non esiste più.

## Test

- `php -l`: nessun errore.
- Verifica sui dati reali: gruppo 2 → 1 riga in `group_tenants`
  (tenant_id 2), tenant 2 con `deleted_at` valorizzato. Conteggio con
  il nuovo filtro: **0** (prima: 1).
- `curl` senza sessione su `/admin/groups`, `/admin/groups/tenant/2`,
  `/admin/groups/delete/2`: tutti 302, nessun 500.

**Non verificato visivamente in browser** — lasciato al giro di test
manuale dell'utente.

## Rischi e note

- Il conteggio membri (`users_groups`) non necessitava dello stesso
  fix: il modello `UsersGroup` usa già `SoftDeletes` (trait Eloquent),
  quindi `UsersGroup::where(...)` esclude automaticamente le righe
  soft-deleted senza bisogno di un filtro esplicito. `GroupTenants`
  invece non ha soft-delete propria (nessuna colonna `deleted_at` sulla
  tabella pivot `group_tenants`): l'unico modo per sapere se
  un'associazione è "viva" è controllare se il tenant collegato lo è.

## Rollback

`git revert` del commit — ripristina il conteggio senza filtro sul
tenant, un tenant soft-deleted torna a bloccare la cancellazione del
gruppo.
