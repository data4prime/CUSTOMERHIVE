# 046 - Members del gruppo: utente con privilegio orfano non deve sparire

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Bug fix
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/AdminGroupsController.php`

## Contesto

Seguito diretto di [045](045-messaggio-cancellazione-gruppo-piu-specifico.md):
l'utente ha segnalato che il messaggio "1 member assigned" mostrato
provando a cancellare il gruppo "test" non corrisponde a quanto si
vede nella pagina "Members" del gruppo, che risulta vuota.

Causa: l'utente "test" (`cms_users` id 4, riga presente in
`users_groups` per il gruppo id 3) ha `id_cms_privileges = 3`. Durante
i test della sessione precedente (vedi [044](044-privileges-delete-bloccato-e-messaggio-perso.md))
è stato cancellato proprio il privilegio id 3 ("naemee"), lasciando
quel riferimento orfano. `AdminGroupsController::members()` usa un
`join()` (INNER JOIN) su `cms_privileges`: un utente il cui privilegio
non esiste più non ha nessuna riga corrispondente, quindi l'INNER JOIN
lo esclude silenziosamente dalla lista — anche se in `users_groups` è
ancora un membro a tutti gli effetti. Il controllo di blocco
cancellazione gruppo (045), che conta direttamente `users_groups`
senza join, invece lo vede correttamente: da qui la discrepanza.

Deciso con l'utente: **non** toccare il dato (l'utente resta senza un
privilegio valido) e **non** aggiungere un guard in
`PrivilegesController::getDelete()` per ora — l'unica correzione
richiesta è che un utente così non debba sparire dalla vista.

## Situazione prima

```php
$data['members'] = DB::table('users_groups')
    ->where('users_groups.group_id', $group_id)
    ->where('users_groups.deleted_at', null)
    ->join('cms_users', 'cms_users.id', '=', 'users_groups.user_id')
    ->join('cms_privileges', 'cms_privileges.id', '=', 'cms_users.id_cms_privileges');
```

## Situazione dopo

```php
$data['members'] = DB::table('users_groups')
    ->where('users_groups.group_id', $group_id)
    ->where('users_groups.deleted_at', null)
    ->join('cms_users', 'cms_users.id', '=', 'users_groups.user_id')
    ->leftJoin('cms_privileges', 'cms_privileges.id', '=', 'cms_users.id_cms_privileges');
```

Con `leftJoin`, un utente con `id_cms_privileges` orfano compare
comunque in lista, con la colonna "Privilege" vuota invece di sparire.
La vista (`resources/views/groups/members.blade.php`) già stampa
`{{$member->privilege}}` con `{{ }}`, che su `null` produce
semplicemente una cella vuota — nessuna modifica necessaria lì.

## Motivazione

Un INNER JOIN su una relazione facoltativa/potenzialmente incoerente
(un privilegio può essere cancellato indipendentemente dagli utenti
che lo referenziano, non essendoci un guard lato `PrivilegesController`)
nasconde silenziosamente dati reali. Chi guarda la lista membri deve
vedere tutti i membri effettivi, anche quelli con un riferimento rotto,
altrimenti conteggi (come quello del messaggio di blocco cancellazione
gruppo) e visualizzazione non corrispondono.

## Test

- `php -l`: nessun errore.
- Query replicata a mano con `leftJoin`: l'utente "test" (id 4, gruppo
  3) ora compare nel risultato con `privilege` a stringa vuota, dove
  prima (con `join`) il risultato era un array vuoto.
- `curl` senza sessione su `/admin/groups`, `/admin/groups/members/3`:
  entrambi 302, nessun 500.

**Non verificato visivamente in browser** — lasciato al giro di test
manuale dell'utente.

## Rischi e note

- Non risolve la causa a monte (privilegi cancellabili senza
  controllare se ci sono utenti assegnati): per scelta esplicita
  dell'utente, resta così com'è per ora. Se in futuro si vuole evitare
  di ricreare questo tipo di orfano, va aggiunto un guard in
  `PrivilegesController::getDelete()` analogo a quello già fatto per i
  gruppi (045).
- Stesso pattern (`join` su una tabella potenzialmente disallineata)
  non trovato altrove: `AdminTenantsController::members()` non fa join
  con `cms_privileges`, quindi non è affetto dallo stesso problema.

## Rollback

`git revert` del commit — ripristina l'INNER JOIN, l'utente con
privilegio orfano torna a sparire dalla lista membri.
