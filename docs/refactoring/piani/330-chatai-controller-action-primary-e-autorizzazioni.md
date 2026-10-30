# Piano 330 — `AdminChatAIController`: Action per la regola "conf primary" e per le scritture di autorizzazione (batch A)

Stato: **approvato dalla persona il 2026-10-30, comprese le due parti**. Branch: `refactor/models`.
Autore: `/architect`, 2026-10-30. Destinatario: `ch-senior-dev` (non vede la
conversazione: questo file è autosufficiente). Origine: analisi di
`AdminChatAIController` nella sessione `/architect` del 2026-10-30. Segue il doc 329
(controllo `agent_id`) e il doc 328 (primo esempio di Action fuori da Menu/Group).
Il batch B (client della chat, le due `send_message*`) **non** è in questo piano.

## Obiettivo

**Parte 1 (refactoring).** Portare fuori dal controller le scritture che oggi vivono
dentro i metodi custom e gli hook, in cinque Action a un solo `handle()`, senza cambiare
nulla di visibile (**behavior-preserving**): stessi redirect, stesse righe a DB, stessi
permessi.

**Parte 2 (correzione, aggiunta per decisione della persona il 2026-10-30).** Far sì che
la chat ignori le conf ChatAI soft-deleted (vedi "Parte 2" sotto). **Cambia comportamento
visibile**, in modo dichiarato. Si fa **dopo** la parte 1, con test propri scritti prima,
così il refactoring resta verificabile come behavior-preserving da solo.

## Stato attuale (fatti verificati 2026-10-30, lettura del codice)

File: `app/Http/Controllers/System/AdminChatAIController.php`.

- **Regola "una sola conf primary"**, duplicata identica in `hook_after_add()` (r.311-327)
  e `hook_after_edit()` (r.362-378): `$record = ChatAIConf::find($id);` poi, se
  `$record->primary == 1`, `ChatAIConf::where('id', '!=', $id)->update(['primary' => 0])`.
  `hook_before_add/edit` (r.291-353) normalizzano `$postdata['primary']` (`"primary"` → 1,
  altro → 0): **restano dove sono** (modificano il `$postdata` del motore).
- **`ChatAIConf` usa `SoftDeletes`** (`app/ChatAIConf.php`): la query Eloquent della regola
  **esclude le conf soft-deleted**; quindi una conf cancellata che era primary resta con
  `primary = 1`. Per preservare il comportamento l'Action deve usare la query Eloquent
  (`ChatAIConf::where(...)`), **non** `DB::table()`. Nota correlata, **fuori perimetro**:
  `send_message()` legge `DB::table('chatai_confs')->where('primary', 1)->first()`, che
  non applica il soft delete (r.660). Se una conf primary cancellata resta a 1, può
  essere scelta al posto della nuova (`first()` senza ordinamento). Verificato che la
  colonna esiste: `MenusController.php:534` filtra già `chatai_confs.deleted_at IS NULL`
  e il modello ha `SoftDeletes`. **Non provato a runtime**: il test 4 la fissa e la
  correzione è la Parte 2. Stessa famiglia di difetto in `send_message_agent()`
  (r.823: `DB::table('chatai_confs')->where('id', $agent_id)->first()` senza filtro):
  un superadmin potrebbe usare una conf cancellata (un non superadmin no, perché
  `can_see_item` usa `find()` che la esclude).
- **Scritture di autorizzazione** (tutte con controllo `isSuperadmin` in testa, poi
  `MyHelper::is_int`, ecc.), rotte in `routes/web.php:66-73`:
  - `add_tenant($item_id)` (r.481-515): `request()->input('name')` = tenant, `safeRedirectTarget`
    su `ref_mainpath`/`return_url`, `!is_int($item_id) || !ChatAIConf::find($item_id)` →
    redirect `denied_access`; tenant vuoto/non int/inesistente (`DB::table('tenants')`) →
    `redirect($return_url.'/alert/1')`; `ChatAITenantAllowed::where(conf, tenant)->count() == 0`
    → `new ChatAITenantAllowed; ->chatai_conf_id; ->tenant_id; ->save()`; redirect.
  - `remove_tenant($item_id, $tenant_id)` (r.517-533): `is_int` su entrambi, poi
    `ChatAITenantAllowed::where(...)->delete()` (assegnato a `$data['delete']`, mai usato),
    `redirect('admin/chat_ai/tenant/'.$item_id)`.
  - `add_authorization($item_id)` (r.536-570) e `remove_authorization($item_id, $group_id)`
    (r.573-589): identici ai due sopra su `ChatAIGroupAllowed` / `groups`; redirect finale
    `admin/chat_ai/access/<item_id>`.
- **Test esistenti**: `tests/Feature/ChatAIAuthorizationTest.php` (28 test, verdi il
  2026-10-30) copre `add_authorization`/`add_tenant` (riga creata una sola volta, id non
  validi senza righe, `return_url` esterno), `remove` (cancella solo righe ChatAI), solo
  superadmin, cascata alla cancellazione della conf. **Non copre**: la regola primary
  (nessun test la cita), il redirect finale delle `remove_*`, il redirect `/alert/1`.

## Perimetro

Dentro:
- `app/Actions/ChatAI/` (nuova cartella): `SetPrimaryChatAIConf`, `AllowTenantForChatAI`,
  `RevokeTenantForChatAI`, `AllowGroupForChatAI`, `RevokeGroupForChatAI`.
- `AdminChatAIController.php`: solo `hook_after_add`, `hook_after_edit`, `add_tenant`,
  `remove_tenant`, `add_authorization`, `remove_authorization`.
- Test nuovi (vedi sotto).

Dentro solo per la **Parte 2**: la singola query della conf in `send_message()` (r.660) e
quella in `send_message_agent()` (r.823), più il test nuovo `tests/Feature/ChatAIDeletedConfTest.php`.

Fuori (non toccare): `cbInit()`, `hook_before_*`, `hook_before_delete/after_delete`,
`access()`, `tenant()`, `getEdit`, `getDetail`, `content_view`, `getConf()`, il resto di
`send_message()`/`send_message_agent()` (cURL, sessione, storico: batch B), il trait
`SafeRedirectTarget`, le rotte, le viste, i modelli, `ChatAIHelper`, la regola primary
(non va fatta "pulire" i soft-deleted, nessuna migrazione/aggiornamento dati).

## Pattern scelto e perché

**Action** (un'operazione, un solo `handle()`), come `app/Actions/Groups/AddTenantToGroup.php`
(doc 322) e `GenerateApiToken` (doc 328): operazioni di scrittura oggi dentro il controller.
Niente sessione, richiesta, `UserHelper` o `CRUDBooster` dentro l'Action; niente stato. Il
controller tiene: controllo `isSuperadmin`, validazione degli id (`is_int`, `exists`),
`safeRedirectTarget`, redirect. Si istanziano con `new` (ADR A14: niente `app()`).

Firme proposte (id senza tipo dove oggi arrivano come stringa, come negli altri Action):

```php
// App\Actions\ChatAI
class SetPrimaryChatAIConf   { public function handle(ChatAIConf $conf): void }
class AllowTenantForChatAI   { public function handle(ChatAIConf $conf, $tenantId): void }
class RevokeTenantForChatAI  { public function handle($confId, $tenantId): void }
class AllowGroupForChatAI    { public function handle(ChatAIConf $conf, $groupId): void }
class RevokeGroupForChatAI   { public function handle($confId, $groupId): void }
```

- `SetPrimaryChatAIConf::handle()`: se `$conf->primary == 1` (confronto lasco come oggi),
  `ChatAIConf::where('id', '!=', $conf->id)->update(['primary' => 0])`. I due hook fanno
  `$record = ChatAIConf::find($id); (new SetPrimaryChatAIConf())->handle($record);`.
- `Allow*`: stessa logica di oggi, **count poi insert** (`where(...)->count() == 0` → nuovo
  record con `chatai_conf_id` e `tenant_id`/`group_id`, `save()`); non cambiare in
  `firstOrCreate` (altro comportamento sotto concorrenza). `$tenantId`/`$groupId` senza tipo
  per salvare lo stesso valore di prima.
- `Revoke*`: `ChatAI*Allowed::where('chatai_conf_id', $confId)->where(...)->delete()`;
  nel controller si toglie l'assegnazione morta `$data['delete'] =`.

## Passi

1. **Test di caratterizzazione prima di toccare il codice** (vedi sotto); devono essere
   verdi sul codice attuale.
2. Creare le 5 Action.
3. Sostituire i corpi nei 6 metodi del controller; togliere gli `use` diventati inutilizzati.
4. `php -l` sui file toccati e Pint `--test` solo sui file nuovi/toccati (A8).
5. **Parte 2**, solo dopo i passi 1-4: scrivere `ChatAIDeletedConfTest` (rossi attesi 1-3),
   poi le due modifiche di query e la guardia in `send_message()`; `php -l` e Pint.
   Tenere i due interventi ben distinguibili nel diff (parte 1: Action e metodi
   sostituiti; parte 2: solo le due query e la guardia).

## Test di caratterizzazione (per rischio decrescente; tutti verdi sul codice attuale)

Setup come `ChatAIAuthorizationTest` (`registerAdminModules()`, `registerAdminModule('chat_ai',
'AdminChatAIController', 'chatai_confs')`, `fakeActiveChatAILicense()`, `seedConf`,
`allowTenant`, `allowGroup`). Per la regola primary i test passano **dall'HTTP**
(`POST admin/chat_ai/add-save` e `edit-save/{id}`, con `primary = "primary"` come il
checkbox): leggere `cbInit()` (r.30-242) per i campi obbligatori. Se l'HTTP del CRUD
risultasse impraticabile, fallback dichiarato nel doc: chiamare direttamente
`hook_after_add($id)`/`hook_after_edit($id)` sul controller (non `app()` con FormRequest:
qui non vale il divieto A14, ma non usare `app()` per Action).

1. **Primary via add**: esiste A con `primary=1`; aggiungere B con checkbox primary →
   A.primary=0, B.primary=1.
2. **Primary via edit**: A primary, B no; modificare B con primary → A=0, B=1.
3. **Senza checkbox**: aggiungere/modificare senza `primary` → le altre conf non cambiano
   e la conf resta `primary=0`. Caratterizzare anche cosa succede quando la chiave
   `primary` manca del tutto (`hook_before_*` la leggono senza `isset`): documentare il
   comportamento reale, non correggerlo.
4. **Conf soft-deleted**: A primary con `deleted_at` valorizzato; B diventa primary →
   A resta `primary=1` (la query Eloquent la esclude). Fissa il comportamento attuale.
5. **Redirect delle `remove_*`**: `remove_tenant` → `admin/chat_ai/tenant/<id>`,
   `remove_authorization` → `admin/chat_ai/access/<id>`; id non int → `denied_access`.
6. **Redirect `/alert/1`** di `add_tenant`/`add_authorization` con tenant/gruppo vuoto,
   non int o inesistente (nessuna riga creata). Con un caso positivo affiancato nello
   stesso setup (lezione ChatAI 2026-10-29).
7. **Test delle Action** (`tests/Feature/ChatAIActionsTest.php`, MySQL vero come `ActionsTest`):
   `SetPrimaryChatAIConf` con `primary=1` e `primary=0`; `Allow*` crea una riga e non
   duplica; `Revoke*` cancella solo la riga indicata (altre conf e altro tipo intatti).

`ChatAIAuthorizationTest` e `ChatAISendMessageAgentTest` restano come regressione.
I test non vengono lanciati dall'architect: li lancia la persona con `/refactor-verify`.

## Parte 2 — Conf soft-deleted ignorate dalla chat (cambio visibile, dopo la parte 1)

Modifica (solo queste due righe, nient'altro nei metodi):

1. `send_message()`: la query diventa `DB::table('chatai_confs')->where('primary', 1)->whereNull('deleted_at')->first()`.
   Subito dopo, se non c'è nessuna conf, rispondere come fa già il metodo per l'errore del
   token — `echo json_encode(["message" => trans('crudbooster.missing_item')]); return;` —
   (oggi, senza conf primary, è un 500 per `$chatai_conf->url` su `null`). Chiave già
   esistente in en e it: nessun testo nuovo.
2. `send_message_agent()`: la query diventa `...->where('id', $agent_id)->whereNull('deleted_at')->first()`.
   La guardia `missing_item` del doc 329 copre già il caso "nessuna conf".

Effetto visibile, da dichiarare nel doc: (a) dopo aver cancellato la conf primary e averne
impostata un'altra, la chat fluttuante usa la nuova (prima poteva usare quella cancellata);
(b) se si cancella la primary e **non** se ne imposta un'altra, la chat fluttuante risponde
con il messaggio `missing_item` (prima continuava a usare la conf cancellata); (c) un
superadmin non può più usare una conf cancellata tramite `agent_id`. Nessun dato cambia.

Test (scritti **prima**, rossi sul codice di dopo la parte 1; setup e cURL come
`ChatAISendMessageAgentTest`: url `http://127.0.0.1:9/`, passphrase di 40 caratteri; leggere
`ChatAIHelper::getToken()` per costruire una conf con token valido e una senza):

1. **Primary cancellata, nessun'altra primary** → `send_message` risponde `missing_item`,
   nessuna riga in `chat_ai_history`. *Rosso prima della parte 2.*
2. **Due conf primary, la più vecchia (id minore) soft-deleted** → `send_message` usa la
   nuova. Per distinguerle: la cancellata senza token valido (risposta `chatai_token_error`),
   l'attiva con token valido e url irraggiungibile (messaggio di errore di rete); atteso
   il secondo. *Rosso prima.*
3. **Superadmin con `agent_id` di una conf cancellata** → `missing_item`, nessuna riga di
   storico. *Rosso prima.*
4. **Casi positivi affiancati**: conf primary attiva e `agent_id` di conf attiva
   (superadmin) → la richiesta arriva a `saveChatHistory` (1 riga). *Verde prima e dopo.*
5. `ChatAISendMessageAgentTest` e `ChatAIAuthorizationTest` restano verdi.

## Vincoli

- Nessun testo UI nuovo (nessuna chiave i18n); nessuna rotta, firma o nome di metodo
  pubblico del controller cambiato (ogni metodo pubblico è una rotta).
- Gli hook mantengono nome e firma (API verso i controller dei clienti: ADR/mappa).
- Niente `app(...)`/`resolve()` nel perimetro Larastan; le Action si istanziano con `new`.
- `ChatAIConf` resta in `App\` (ADR A11); non toccare i modelli.
- Nessun commit/push, nessuna suite lanciata in autonomia, nessun accesso a server remoti.

## Impatto sui clienti

Nessuno per la parte 1. La parte 2 ha gli effetti visibili elencati sopra, solo sulla chat
ChatAI. Controller di sistema non ereditato dai clienti; nessuna proprietà né hook cambia
firma; ChatAI non è attivo in produzione (doc 300).

## Rischio

Basso. Punti di attenzione: (1) usare la query Eloquent nell'Action per non perdere
l'esclusione dei soft-deleted; (2) `Allow*` resta count-poi-insert; (3) il controller
continua a fare le verifiche di `exists` sui tenant/gruppi prima di chiamare l'Action.

## Rollback

Ripristinare i 6 metodi dal diff e cancellare `app/Actions/ChatAI/` e i test nuovi.
Nessuna migrazione, nessun dato toccato.

## Dopo il batch

Reviewer (i18n atteso "n/a") → correzioni → `ch-doc-writer` (doc 330) → post-mortem in
`docs/architettura/lezioni.md`; aggiornare `pattern-nel-repo.md` (Action in
`app/Actions/ChatAI/`) e `mappa.md` (conteggio Action). Il doc 330 deve distinguere le due
parti (la 1 behavior-preserving, la 2 correzione con cambio visibile) e dire se la parte 2
è stata provata sul vecchio comportamento o solo dedotta.
