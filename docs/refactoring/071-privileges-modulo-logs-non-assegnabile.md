# 071 - Privileges: il modulo Logs non era assegnabile a nessun ruolo

- **Data**: 2026-09-16
- **Stato**: Completato (correzione applicata in `PrivilegesController`; l'abilitazione effettiva per il ruolo Tenant Admin va fatta dall'utente da UI - vedi "Rischi e note")
- **Area**: Bug fix
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/PrivilegesController.php` (`getAdd()`, `getEdit()`)

## Contesto

Seguito a [070](070-datamodal-leak-cross-tenant.md): l'utente ha segnalato
che `LogsController` (modulo "Log User Access", tabella `cms_logs`) ha già
uno scoping per tenant (`hook_query_index()` filtra i log ai soli utenti
del proprio tenant per `UserHelper::isTenantAdmin()`), ma un tenant admin
che prova ad aprirlo ottiene "Sorry, you are not allowed to access this
area." - e dalla pagina Privileges non trova modo di assegnargli il
permesso.

## Il problema

`PrivilegesController::getAdd()` (riga 80-96) e `getEdit()` (riga
199-208) costruiscono l'elenco dei moduli mostrati come checkbox nella
pagina di gestione di un privilegio/ruolo con un filtro molto ristretto:

```php
$moduls = DB::table("cms_moduls")
    ->where('is_protected', 0)
    ->where('table_name', 'like', config('app.module_generator_prefix') . '%')
    ->orWhere('table_name', 'groups')
    ->orWhere('table_name', 'cms_users')
    ->orWhere('table_name', 'cms_menus')
    ...
```

Compaiono solo: i moduli creati con Module Generator (prefisso
configurato) e tre eccezioni esplicite (`groups`, `cms_users`,
`cms_menus`). Il modulo Logs ha `is_protected = 1`
(`database/seeders/CmsModulsSeeder.php:135`, `table_name = 'cms_logs'`) e
non era tra le eccezioni: **non compariva mai** nella UI di Privileges,
per nessun ruolo. Senza una riga in `cms_privileges_roles` con
`is_read`/`is_visible = 1` per quel modulo, il controllo di accesso
generico di `CBController` nega l'ingresso - da cui il messaggio "not
allowed to access this area", indipendentemente dal fatto che
`LogsController` gestisca correttamente il tenant admin a livello di
query.

Aggravante notata in `postEditSave()` (riga 252): ad ogni salvataggio di
un privilegio, **tutte** le righe `cms_privileges_roles` di quel
privilegio vengono cancellate e ricreate solo dai moduli presenti nel
form. Un'eventuale riga `cms_logs` inserita a mano nel DB sarebbe stata
cancellata al primo salvataggio successivo di quel ruolo da UI.

## Correzione

Aggiunta la stessa eccezione già usata per `groups`/`cms_users`/`cms_menus`,
in entrambi i metodi:

```php
->orWhere('table_name', 'cms_users')
->orWhere('table_name', 'cms_menus')
->orWhere('table_name', 'cms_logs')
```

Il modulo Logs ora compare nella pagina Privileges e può essere
abilitato/disabilitato per qualsiasi ruolo, sfruttando lo scoping per
tenant già presente in `LogsController::hook_query_index()`.

## Test

Non eseguiti (né suite né test manuale in ambiente), come da prassi
concordata. Da verificare manualmente:
- Privileges → ruolo Tenant Admin → il modulo "Log User Access" ora
  compare tra le checkbox; spuntare Visible/Read e salvare.
- Login come quel tenant admin → `/admin/logs` accessibile, e mostra solo
  i log degli utenti del proprio tenant (comportamento già presente,
  invariato da questo fix).
- Login come superadmin → Privileges continua a funzionare come prima
  per gli altri moduli (nessuna riga rimossa dall'elenco esistente).

## Rischi e note

- Il fix rende il modulo **assegnabile**, non lo assegna automaticamente
  a nessun ruolo: l'utente deve comunque andare su Privileges → Tenant
  Admin → abilitare Logs e salvare perché l'accesso sia effettivo.
- Non toccata la query di join tra `where`/`orWhere`/`whereNull` in
  `getAdd()`: per come Laravel compone le clausole, l'`AND deleted_at is
  null` finale si lega solo all'ultima condizione OR (ora `cms_logs`,
  prima `cms_menus`), non a tutte e quattro le eccezioni esplicite -
  comportamento preesistente, non introdotto né aggravato da questo
  intervento (nessuna di queste tabelle usa in pratica soft delete sui
  moduli di sistema).
- Non esteso ad altri moduli "di sistema" con `is_protected = 1` (es.
  Tenants, Qlik Items, Settings, la stessa Privileges): fix scoped al
  modulo segnalato.

## Rollback

`git revert` del commit - il modulo Logs torna a non essere assegnabile
da nessuna pagina Privileges (nessun impatto su dati già salvati in
`cms_privileges_roles`, se un ruolo lo aveva già abilitato tramite questo
fix il permesso resta a DB ma non sarà più modificabile da UI finché non
si riapplica il fix).

Vedi anche [070](070-datamodal-leak-cross-tenant.md).
