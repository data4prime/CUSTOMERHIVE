# 045 - Messaggio di blocco cancellazione gruppo più specifico (membri + tenant)

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: UI/UX
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/AdminGroupsController.php`
  - `resources/lang/it/crudbooster.php`
  - `resources/lang/en/crudbooster.php`

## Contesto

Segnalato dall'utente: eliminando il gruppo "test" da `/admin/groups`
compare il warning "Puoi eliminare solo gruppi senza membri!", ma
secondo l'utente il gruppo conteneva solo tenant assegnati, non membri
— messaggio percepito come sbagliato/fuorviante.

Verifica sui dati reali di questo ambiente: il gruppo "test" (id 3) ha
in realtà **1 riga in `users_groups`** (l'utente "test", id 4) **e 2
righe in `group_tenants`**. Il blocco era quindi corretto (c'è
davvero un membro), ma il messaggio generico non lo diceva — da qui la
percezione dell'utente che fosse "sbagliato".

Durante l'analisi è emerso anche un problema più concreto:
`hook_before_delete()` controllava **solo** `users_groups`, non
`group_tenants`. La tabella `group_tenants` ha però un vincolo FK reale
verso `groups` (`group_fk`, `DELETE_RULE = NO ACTION`, nessun cascade):
un gruppo con tenant assegnati ma **zero** membri avrebbe superato
indisturbato questo controllo applicativo per poi far fallire la query
`DELETE FROM groups ...` in `CBController::getDelete()` con un errore
SQL di vincolo di integrità non gestito (500), invece di un messaggio
comprensibile.

## Situazione prima

```php
public function hook_before_delete($id)
{
    $members_count = UsersGroup::where('group_id', $id)->count();
    if ($members_count > 0) {
        CRUDBooster::redirect(CRUDBooster::adminPath('groups'), trans('crudbooster.delete_not_empty_group'));
    }
}
```

Un solo controllo (membri), messaggio generico che non specifica cosa
sta effettivamente bloccando la cancellazione.

## Situazione dopo

```php
public function hook_before_delete($id)
{
    $members_count = UsersGroup::where('group_id', $id)->count();
    $tenants_count = GroupTenants::where('group_id', $id)->count();

    if ($members_count > 0 || $tenants_count > 0) {
        $parts = [];
        if ($members_count > 0) {
            $parts[] = trans_choice('crudbooster.group_relation_members', $members_count, ['count' => $members_count]);
        }
        if ($tenants_count > 0) {
            $parts[] = trans_choice('crudbooster.group_relation_tenants', $tenants_count, ['count' => $tenants_count]);
        }

        CRUDBooster::redirect(CRUDBooster::adminPath('groups'), trans('crudbooster.delete_group_has_relations', ['items' => implode(', ', $parts)]));
    }
}
```

Nuove chiavi di traduzione (`it`/`en`, uniche lingue che avevano già
`delete_not_empty_group`):
- `delete_group_has_relations`: "Non puoi eliminare questo gruppo: ha
  ancora :items assegnati."
- `group_relation_members` / `group_relation_tenants`: forme
  pluralizzate (`trans_choice`) per "N membro/i" e "N tenant".

Esempio reale (gruppo "test", id 3): **"Non puoi eliminare questo
gruppo: ha ancora 1 membro, 2 tenant assegnati."** — ora si vede
esplicitamente cosa c'è dentro, invece del generico "gruppi senza
membri".

## Motivazione

- Messaggio più utile: dice esattamente cosa sta bloccando la
  cancellazione (quanti utenti, quanti tenant), non solo "ci sono
  membri".
- Chiude anche il buco applicativo sui tenant: prima un gruppo con
  soli tenant assegnati (zero membri) avrebbe rischiato un errore SQL
  di vincolo di integrità non gestito invece di un messaggio pulito.

## Test

- `php -l` su controller e file di lingua: nessun errore.
- Verifica diretta sui dati reali: gruppo id 3 → 1 riga in
  `users_groups`, 2 righe in `group_tenants`.
- Confermato con `information_schema` che `group_tenants.group_id` ha
  un vincolo FK reale verso `groups.id` (`group_fk`, `DELETE_RULE =
  NO ACTION`) — nessun cascade automatico lato DB.
- Reso il messaggio via `trans()`/`trans_choice()` diretti (bootstrap
  applicazione, non HTTP): caso reale (1 membro + 2 tenant) →
  `"Non puoi eliminare questo gruppo: ha ancora 1 membro, 2 tenant
  assegnati."`; caso simulato solo-tenant (0 membri, 2 tenant) →
  `"Non puoi eliminare questo gruppo: ha ancora 2 tenant assegnati."`
  (il pezzo "membro/i" non compare quando il conteggio è 0, come
  atteso).
- `curl` senza sessione su `/admin/groups`, `/admin/groups/delete/3`:
  entrambi 302, nessun 500; conteggio righe `groups` invariato (4)
  dopo la chiamata.

**Non verificato visivamente in browser** — lasciato al giro di test
manuale dell'utente.

## Rischi e note

- Le nuove chiavi sono state aggiunte solo a `it`/`en`, le uniche
  lingue che avevano già `delete_not_empty_group` popolato (le altre
  6 lingue presenti in `resources/lang/` non hanno questa chiave:
  Laravel ricade sulla lingua di default se mancante).
- La vecchia chiave `delete_not_empty_group` resta nei file di lingua
  (non più referenziata da questo controller) per non rompere eventuali
  altri usi non ancora individuati — da rivedere in un secondo momento
  se risultasse davvero orfana.

## Rollback

`git revert` del commit — ripristina il controllo sul solo
`users_groups` e il messaggio generico precedente.
