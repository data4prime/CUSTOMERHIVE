# 093 - `execute_api()`: chiamato `cbInit()` sul controller collegato per far funzionare i privilegi

- **Data**: 2026-09-24
- **Stato**: Completato (parziale, vedi Rischi e note)
- **Area**: Sicurezza / Bug fix
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/ApiController.php`

## Contesto

Seguito di [092](092-execute-api-controller-self-healing.md): verificando
il fix con un utente non superadmin, l'azione `list` restituiva sempre
`"data": []`, a prescindere dal controller collegato. Analisi approfondita
su richiesta dell'utente.

## Situazione prima

`execute_api()` chiama, a seconda dell'azione, `ModuleHelper::can_list()`/
`can_view()`/`can_add()`/`can_edit()`/`can_delete()` passando
`$this->controller` (il controller del modulo collegato) come "modulo".
Tutte iniziano con "il superadmin vede sempre tutto", poi per chiunque
altro leggono `$module->global_privilege` e chiamano
`CRUDBooster::isView()`/`isUpdate()`/ecc.

`$this->controller` non aveva **mai** eseguito `cbInit()` (c'era perfino
una riga già presente ma commentata, `//$this->controller->cbInit();` -
mai attiva). Di conseguenza le sue proprietà restavano ai default della
classe base (`global_privilege = false`, `table = null`) invece dei
valori veri configurati dal modulo collegato.

## Situazione dopo

Aggiunta una chiamata a `$this->controller->cbInit()`, una sola volta,
subito dopo aver risolto `$this->controller` (sia che l'abbia impostato
il file generato da solo, sia che l'abbia risolto il self-healing di
092) - rimossa anche la vecchia riga commentata equivalente, ridondante.

## Motivazione

Popolare le proprietà vere del modulo invece dei default della classe
base è comunque corretto indipendentemente da cosa serva più sotto -
`global_privilege`/`table`/`button_detail` riflettono ora la
configurazione reale, non un valore arbitrario.

## Test

Manuali sull'ambiente Docker locale (utenti/config di prova creati e poi
eliminati):
- `php -l`.
- Verificato dal vivo che `DashboardLayoutController->cbInit()` popola
  correttamente `global_privilege=true`/`table`/`button_detail` una
  volta chiamata (prima: default della classe base).
- Superadmin, permalink valido → invariato, 200.
- Permalink senza riga `cms_apicustom` → invariato, messaggio d'errore
  gestito (non un crash) - fix di 091 non intaccato da questo intervento.

## Rischi e note — il fix non risolve tutto, trovato un secondo problema

**Verificando con dati reali**, chiamare `cbInit()` non basta a far
funzionare i privilegi per il caso generale. Motivo, letto nel codice di
`ModuleHelper::can_list()`:

1. Il primo cancello (`!isView() && global_privilege==false`) **si
   supera** se `global_privilege=true` — su questo `cbInit()` aiuta
   davvero.
2. Ma **dopo** quel cancello, la funzione ritorna `true` solo se la
   riga soddisfa uno di pochi casi specifici e cablati: una tabella
   "manualmente generata" (`mg_*`, Module Generator) con lo stesso
   tenant/gruppo dell'utente **e** l'utente è tenant admin; oppure
   `$module->table == 'cms_users'`; oppure `$module->table == 'groups'`.
   **Per qualunque altra tabella** (es. `dashboard_layouts`, o qualunque
   modulo di sistema con `global_privilege=true` ma senza scoping per
   tenant/gruppo), non c'è nessun ramo che ritorni `true` — la funzione
   arriva sempre al `return false;` finale, **a prescindere da
   `global_privilege`**.

Verificato dal vivo: un utente Standard (non tenant admin) contro
`dashboard_layouts` (`DashboardLayoutController`, `global_privilege =
true` confermato dopo `cbInit()`) riceve comunque `"data": []`.

**In sintesi**: il fix di oggi corregge davvero il caso dei moduli
Module Generator (`mg_*`) con scoping tenant/gruppo per un tenant admin,
e i due casi speciali `cms_users`/`groups` — prima nemmeno quelli
funzionavano (mancava sempre `cbInit()`). Non risolve il caso di un
modulo generico con `global_privilege=true` e nessuno scoping per
tenant/gruppo: per quello, `can_list()`/`can_view()`/`can_edit()`/
`can_delete()`/`can_add()` sembrano mancare del tutto un ramo
equivalente a quello già presente nelle pagine CRUD admin normali (es.
`DashboardLayoutController::getAdd()`: `!isCreate() && global_privilege
== false` nega, altrimenti procede sempre) - un `global_privilege=true`
lì basta da solo, in `ModuleHelper` no.

**Non corretto**: aggiungere quel ramo mancante tocca `ModuleHelper`,
usato da **ogni** pagina CRUD admin dell'applicazione, non solo
dall'API Generator - un cambiamento con un raggio d'azione molto più
ampio di quanto chiesto oggi, da valutare a parte con più attenzione
(rischio di allargare l'accesso anche in punti dell'interfaccia admin
mai pensati per essere aperti a `global_privilege=true`).

## Rollback

`git revert` del commit — nessuna migration né dato coinvolto.
