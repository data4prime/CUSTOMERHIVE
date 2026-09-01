# 060 - Crash liste Groups per utente Standard e lista Tenants sempre vuota per non-superadmin

- **Data**: 2026-09-01
- **Stato**: Parzialmente completato (vedi Stato per dettagli)
- **Area**: Bug fix / caratterizzazione
- **File/aree di codice coinvolte**:
  - `app/Helpers/ModuleHelper.php`
  - `tests/Feature/CrossModuleRelationsTest.php` (nuovo)
  - `tests/Concerns/SeedsCmsData.php`

## Contesto

Trovato scrivendo test sulle RELAZIONI tra i moduli Tenants/Groups/
Privileges/Users (dopo i CRUD test per singolo modulo), non da una
segnalazione dell'utente. Per verificare l'isolamento per tenant nelle
liste con un attore non superadmin, e' stato necessario un nuovo helper
di test (`SeedsCmsData::actingAsTenantUser()`) che simula un login
Tenantadmin/Standard popolando `admin_privileges_roles` in sessione come
fa `AdminController::postLogin()` - fino ad ora tutti i test usavano solo
`actingAsSuperadmin()`, quindi questa parte del codice non era mai stata
esercitata.

Il filtro per tenant nelle liste admin **non** vive dove ci si
aspetterebbe (una `WHERE` nella query di `CBController::getIndex()` -
quella riga esiste ma e' commentata, vedi `getIndex()` linea ~282), ma
riga-per-riga DOPO il fetch, in `ModuleHelper::can_view()` (chiamata da
`getIndex()` prima di renderizzare ogni riga, linea ~576: `if
(!ModuleHelper::can_view($this, $row)) { unset(...); continue; }`).

### Bug 1 (crash reale, corretto in questa sessione)

`ModuleHelper::get_group_id()` e `get_tenant_id()`, ramo `groups`,
chiamate da `can_view()` per OGNI riga mostrata a un attore non
superadmin:

```php
// get_group_id()
$entity_group = DB::table("group_tenants")->where('group_id', $row->id)->where('group_id',UserHelper::current_user_tenant() )->first()->group_id;

// get_tenant_id()
$entity_tenant = DB::table("group_tenants")->where('group_id', $row->id)->where('tenant_id',UserHelper::current_user_tenant() )->first()->tenant_id;
```

Due problemi distinti:

1. In `get_group_id()` il secondo `where` filtra per errore ancora su
   `group_id` invece che su `tenant_id` (copia-incolla dal ramo sopra) -
   la query richiede che `group_id` sia contemporaneamente uguale
   all'id della riga E al tenant dell'attore, il che non e' mai vero
   nella pratica.
2. Entrambi i metodi, per questo ramo, non controllano se `first()` ha
   trovato qualcosa prima di leggere `->group_id`/`->tenant_id` -
   a differenza dei rami `qlik_confs`/`qlik_apps` subito sopra, che
   gia' fanno `if ($entity_group) { ... }` prima di dereferenziare.

**Perche' non era mai emerso prima**: per un **Tenantadmin**,
`AdminGroupsController::hook_query_index()` filtra gia' a livello SQL
(join su `group_tenants` per il tenant dell'attore), quindi
`can_view()` non vede mai una riga di un tenant estraneo e il percorso
che crasha non viene mai raggiunto. Un **Standard** (ne' superadmin ne'
tenantadmin) non ha invece nessun filtro SQL equivalente
(`hook_query_index()` filtra solo `if (UserHelper::isTenantAdmin())`):
la prima volta che un simile attore lista `/admin/groups` con gruppi di
piu' di un tenant in tabella, la riga del gruppo NON del proprio tenant
fa crashare `can_view()` con un 500 ("Attempt to read property
'group_id'/'tenant_id' on null").

### Bug 2 (solo documentato, non corretto)

`can_view()`/`get_tenant_id()` non hanno **nessun ramo** per la tabella
`tenants` (solo `cms_users`, `groups` e i moduli Qlik sono gestiti
esplicitamente). Per qualunque riga di questo modulo, `get_tenant_id()`
ritorna il valore di fallback `true`, che non puo' mai combaciare con
nessuno dei controlli espliciti in `can_view()`: il risultato e' che un
attore non superadmin (tenantadmin incluso) non vede **nessun** tenant
in lista, nemmeno il proprio. Nessun 500, solo una lista vuota - a
differenza del bug 1, deciso con l'utente di **non** intervenire per
ora: comportamento solo caratterizzato da un test, non corretto.

## Situazione prima

Vedi blocchi di codice sopra (Bug 1).

## Situazione dopo

```php
// get_group_id()
if ($module->table == "groups") {
  $entity_group = DB::table("group_tenants")->where('group_id', $row->id)->where('tenant_id', UserHelper::current_user_tenant())->first();
  if ($entity_group) {
    $entity_group = $entity_group->group_id;
  }
}

// get_tenant_id()
if ($module->table == "groups") {
  $entity_tenant = DB::table("group_tenants")->where('group_id', $row->id)->where('tenant_id', UserHelper::current_user_tenant())->first();
  if ($entity_tenant) {
    $entity_tenant = $entity_tenant->tenant_id;
  }
}
```

Stesso pattern di null-safety gia' usato per `qlik_confs`/`qlik_apps`
nello stesso file. Con questo fix, per un gruppo che non appartiene al
tenant dell'attore, `entity_group`/`entity_tenant` ricadono sul default
`true` di `ModuleHelper::get_group_id()`/`get_tenant_id()` (nessun
crash) - la visibilita' finale del gruppo e' comunque decisa
correttamente piu' sotto in `can_view()` da un controllo indipendente
(`GroupTenants::where('group_id', $row->id)->where('tenant_id',
current_user_tenant())->count() > 0`), che non dipende da queste due
variabili per il ramo Standard.

Bug 2: nessuna modifica al codice di produzione.

## Motivazione

- Bug 1 e' un crash reale e riproducibile per un percorso di codice
  legittimo (utente Standard con visibilita' sul modulo Groups): decisa
  con l'utente la correzione immediata, stesso standard "zero
  regressioni" applicato al resto della sessione.
- Bug 2 e' un comportamento anomalo (lista vuota) ma non un crash;
  deciso con l'utente di limitarsi a documentarlo e a coprirlo con un
  test di caratterizzazione, senza decidere ora se un tenantadmin
  dovrebbe invece vedere almeno il proprio tenant - e' una scelta di
  prodotto, non solo un bug fix meccanico come il primo.

## Test

`tests/Feature/CrossModuleRelationsTest.php` (nuovo, 8 test, tutti
passano) copre:

- Isolamento per tenant nella lista Users per un tenantadmin (via
  `can_view()`, non via query SQL).
- Lista Tenants vuota per un tenantadmin (caratterizzazione Bug 2).
- Isolamento per tenant nella lista Groups per tenantadmin (via SQL +
  `can_view()` ridondanti) e per Standard (via solo `can_view()`,
  prima del fix crashava con 500 - regressione da non reintrodurre).
- Cancellazione di una privilege in uso (comportamento gia' deciso in
  044/046/047: nessun guard, utente orfano resta senza permessi
  speciali senza crash).
- Cancellazione di un tenant con un Group associato ma senza utenti:
  riesce, lascia una riga `group_tenants` orfana (nessun cascade -
  comportamento noto/analogo a 046/047, non corretto).
- Cambio privilege di un utente esistente: non tocca la sua
  appartenenza ai gruppi.
- Creazione di gruppi da attori di tenant diversi: nessuna
  contaminazione tra le rispettive righe `group_tenants`.

Suite completa: 68/68 test passano (60 precedenti + 8 nuovi), nessuna
regressione.

`SeedsCmsData::actingAsTenantUser()` (nuovo helper): autentica il test
come utente non superadmin, popolando `cms_privileges_roles` e la
sessione `admin_privileges_roles` con la stessa forma prodotta da
`AdminController::postLogin()` - necessario perche' `CRUDBooster::
isView()`/`isRead()` per un non superadmin leggono da li' per decidere
se il modulo e' accessibile.

## Rischi e note

- La cancellazione di un tenant con Group associati ma senza utenti
  (lasciando una riga `group_tenants` orfana) e' un gap simile nello
  spirito a quelli di 046/047, ma non ancora discusso/deciso con
  l'utente in questa sessione al di la' della sua caratterizzazione via
  test - se in futuro si osservano problemi legati a righe
  `group_tenants` orfane, ripartire da li'.
- Il Bug 2 (lista Tenants vuota per non-superadmin) resta aperto per
  una eventuale decisione di prodotto futura.

## Rollback

`git revert` del commit - ripristina i due `where('group_id', ...)` /
l'assenza di null-check in `get_group_id()`/`get_tenant_id()`, il crash
per un attore Standard su `/admin/groups` con gruppi multi-tenant
torna a presentarsi.
