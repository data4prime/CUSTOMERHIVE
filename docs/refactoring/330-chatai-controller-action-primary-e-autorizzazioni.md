# 330 - AdminChatAIController: Action per regola primary e scritture di autorizzazione + conf soft-deleted ignorate dalla chat

- **Data**: 2026-10-30
- **Stato**: Completato (test eseguiti il 2026-10-30: 58 verdi, 199 asserzioni)
- **Area**: Architettura / Action + Chat AI
- **File/aree di codice coinvolte**:
  - `app/Actions/ChatAI/SetPrimaryChatAIConf.php` (nuovo)
  - `app/Actions/ChatAI/AllowTenantForChatAI.php` (nuovo)
  - `app/Actions/ChatAI/AllowGroupForChatAI.php` (nuovo)
  - `app/Actions/ChatAI/RevokeTenantForChatAI.php` (nuovo)
  - `app/Actions/ChatAI/RevokeGroupForChatAI.php` (nuovo)
  - `app/Http/Controllers/System/AdminChatAIController.php`
  - `tests/Feature/ChatAIPrimaryAndRedirectsTest.php`,
    `tests/Feature/ChatAIActionsTest.php`,
    `tests/Feature/ChatAIDeletedConfTest.php` (nuovi)
  - Piano: `docs/refactoring/piani/330-chatai-controller-action-primary-e-autorizzazioni.md`

L'intervento ha **due parti distinte**, approvate insieme ma da leggere
separatamente: la **Parte 1** è un refactoring *behavior-preserving*, la
**Parte 2** è una correzione che **cambia comportamento visibile** (solo sulla
chat ChatAI).

## Contesto

Origine: analisi di `AdminChatAIController` nella sessione `/architect` del
2026-10-30. È il **batch A** della serie sul controller (dopo il doc 329, batch
0: controllo `agent_id` in `send_message_agent`). Il **batch B** (client della
chat, cioè il resto delle due `send_message*`) **non è fatto**. Il doc 328 è il
precedente per le Action fuori da Menu/Group. Decisione ADR A16: `send_message()`
usa la conf primary per tutti, voluto.

Le scritture sul DB stavano dentro metodi custom e hook del controller; la
parte 2 è nata dall'analisi della stessa area (le conf soft-deleted non erano
escluse dalle query della chat).

## Situazione prima

- **Regola "una sola conf primary"** duplicata identica in `hook_after_add()` e
  `hook_after_edit()`: `ChatAIConf::find($id)` e, se `primary == 1`,
  `ChatAIConf::where('id', '!=', $id)->update(['primary' => 0])`. `ChatAIConf` usa
  `SoftDeletes`, quindi la query Eloquent esclude le conf cancellate: una conf
  primary cancellata resta con `primary = 1`.
- **Scritture di autorizzazione** dentro `add_tenant`, `add_authorization`,
  `remove_tenant`, `remove_authorization` (count-poi-insert per le add, delete
  per le remove; in `remove_*` il risultato era assegnato a `$data['delete']`,
  mai usato).
- `send_message()` leggeva `DB::table('chatai_confs')->where('primary', 1)->first()`
  senza filtro sul soft delete e senza ordinamento; senza conf primary
  rispondeva con un 500 (`->url` su `null`).
- `send_message_agent()` leggeva `DB::table('chatai_confs')->where('id', $agent_id)->first()`
  senza filtro sul soft delete: un superadmin poteva usare una conf cancellata
  (un non superadmin no, perché `can_see_item` usa `find()`).
- Nessun test copriva la regola primary né i redirect finali delle `remove_*`
  né il redirect `/alert/1`.

## Situazione dopo

### Parte 1 - refactoring (behavior-preserving)

Cinque Action a un solo `handle()` in `app/Actions/ChatAI/`, istanziate con
`new` (ADR A14):

- `SetPrimaryChatAIConf::handle(ChatAIConf $conf)`: se `primary == 1`, azzera
  le altre. Usa la **query Eloquent**, quindi i soft-deleted restano esclusi
  (invariato: una conf primary cancellata resta `primary = 1`).
- `AllowTenantForChatAI::handle(ChatAIConf $conf, $id)` e
  `AllowGroupForChatAI::handle(ChatAIConf $conf, $id)`: **count poi insert**
  come prima (non `firstOrCreate`).
- `RevokeTenantForChatAI::handle($confId, $id)` e
  `RevokeGroupForChatAI::handle($confId, $id)`: delete della riga indicata.

In `AdminChatAIController`:

- `hook_after_add`/`hook_after_edit` fanno `find` +
  `(new SetPrimaryChatAIConf())->handle($record)`.
- `add_tenant`, `add_authorization`, `remove_tenant`, `remove_authorization`
  usano le Action; il controller tiene controllo superadmin, `is_int`,
  `find`/`exists`, `safeRedirectTarget` e redirect.
- Tolta l'assegnazione morta `$data['delete']`.
- Il `find` della conf avviene ora solo se `is_int` è vero (stesso esito di
  prima).
- Nomi e firme dei metodi pubblici e degli hook invariati (ogni metodo pubblico
  è una rotta; gli hook sono API verso i controller dei clienti).

### Parte 2 - correzione con cambio visibile

- `send_message()`: query `DB::table('chatai_confs')->where('primary', 1)->whereNull('deleted_at')`;
  se non c'è conf risponde JSON `{"message": trans('crudbooster.missing_item')}`
  (prima: 500). Chiave già esistente in en e it, nessun testo UI nuovo.
- `send_message_agent()`: aggiunto `->whereNull('deleted_at')` alla query
  dell'agente; il caso "nessuna conf" è coperto dalla guardia `missing_item` del
  doc 329.

**Effetti visibili** (solo sulla chat ChatAI):

- (a) Dopo aver cancellato la primary e averne impostata un'altra, la chat
  fluttuante usa la nuova (prima poteva usare la cancellata: `first()` senza
  ordinamento).
- (b) Cancellando la primary **senza** impostarne un'altra, la chat risponde
  `missing_item` (prima continuava a usare la conf cancellata).
- (c) Un superadmin non può più usare una conf cancellata tramite `agent_id`.

Nessun dato cambia, nessuna pulizia dei soft-deleted.

**La Parte 2 è dedotta dal codice, non provata a runtime sul vecchio
comportamento.** La colonna `deleted_at` è verificata da
`MenusController.php:534` (filtra già `chatai_confs.deleted_at IS NULL`) e dal
`SoftDeletes` del modello.

## Motivazione

- Parte 1: togliere dal controller scritture duplicate (regola primary in due
  hook) e rendere le operazioni testabili isolatamente, nello stesso schema di
  `AddTenantToGroup` (322) e `GenerateApiToken` (328). Nessuna riga a DB,
  redirect o permesso cambia.
- Parte 2: la chat non deve usare conf che l'utente ha cancellato; il 500 senza
  primary diventa un messaggio gestito. Si è fatta **dopo** la parte 1, con test
  propri, per tenere il refactoring verificabile come behavior-preserving da solo.
- Alternative scartate: `firstOrCreate` nelle Allow (altro comportamento sotto
  concorrenza); `DB::table()` in `SetPrimary` (avrebbe azzerato anche i
  soft-deleted, cambiando comportamento nella parte 1); pulizia dei dati dei
  soft-deleted (fuori perimetro, nessuna migrazione).

## Test

**Test nuovi scritti, NON eseguiti** (li lancia la persona con `/refactor-verify`):

- `tests/Feature/ChatAIPrimaryAndRedirectsTest.php` (11, non 14 come riportato dal senior dev): regola primary via HTTP
  `add-save`/`edit-save` con e senza checkbox, conf soft-deleted che resta
  `primary = 1`, redirect delle `remove_*`, redirect `/alert/1` con caso positivo
  affiancato.
- `tests/Feature/ChatAIActionsTest.php` (7): le 5 Action.
- `tests/Feature/ChatAIDeletedConfTest.php`: casi 1-3 **attesi rossi sul codice
  precedente alla parte 2** (primary cancellata senza altre -> `missing_item`; due
  primary con la più vecchia cancellata -> usa la nuova; superadmin con `agent_id`
  cancellato -> `missing_item`); caso 4 positivo, verde prima e dopo.
- Regressione: `ChatAIAuthorizationTest` e `ChatAISendMessageAgentTest`, **non
  rieseguiti dopo il 330**.

**Verifiche effettivamente fatte**: `php -l` ok su tutti i file nuovi e sul
controller; Pint `--test` PASS sui file nuovi; sul controller solo stile storico
(tab, righe vuote tra gli `use`), non toccato. Review di `ch-reviewer`: nessun
rilievo bloccante, solo suggerimenti sui test.

**Esito dei test (2026-10-30, su richiesta della persona)**: `ChatAIPrimaryAndRedirectsTest`
(11), `ChatAIActionsTest` (7), `ChatAIDeletedConfTest` (4) e, come regressione,
`ChatAIAuthorizationTest` (28) e `ChatAISendMessageAgentTest` (8): **58 verdi, 199
asserzioni**. Il comportamento dopo la modifica è quindi provato; **non verificato**: che i
casi 1-3 di `ChatAIDeletedConfTest` fossero rossi sul codice precedente (non si è
rieseguito il test sul vecchio codice), quindi il difetto della Parte 2 resta dedotto.

## Rischi e note

1. La parte 2 è **dedotta** dal codice, non provata a runtime.
2. Il diff del controller contiene anche modifiche **non committate che non sono
   del 330**: doc 329 (guardie in `send_message_agent`) e doc 323 (due
   `curl_close` rimossi). Non attribuirle a questo intervento.
3. Un `find()` nullo negli hook darebbe ora un `TypeError` su `handle()` invece di
   un errore su `null->primary`; caso che non si verifica (l'id arriva dal
   salvataggio appena fatto).
4. Il caso 2 di `ChatAIDeletedConfTest` sul vecchio codice dipendeva da `first()`
   senza ordinamento (id minore).
5. L'assert dell'ultimo caso (4) si appoggia a `saveChatHistory` (`chat_ai_id`):
   **da verificare al primo run**.
6. `assertStatus(302)` nei test HTTP accetta qualunque redirect (mitigato dai
   controlli su riga/valore).
7. `hook_before_add/edit` non toccati: con la chiave `primary` assente
   `input_assignment` la valorizza e il risultato è 0 (letto dal codice, non
   provato).
8. ChatAI non è attivo in produzione (doc 300): impatto sui clienti nullo per la
   parte 1, limitato alla chat ChatAI per la parte 2.
9. Il batch B (client della chat, resto di `send_message*`) resta da fare.

## Rollback

Nessuna migrazione, nessun dato toccato.

- **Parte 1**: ripristinare dal diff i 6 metodi/hook del controller
  (`hook_after_add`, `hook_after_edit`, `add_tenant`, `add_authorization`,
  `remove_tenant`, `remove_authorization`), cancellare `app/Actions/ChatAI/` e i
  test `ChatAIPrimaryAndRedirectsTest.php` e `ChatAIActionsTest.php`.
- **Parte 2** (separabile): ripristinare le due query (`send_message()` e
  `send_message_agent()`, senza `whereNull('deleted_at')`) e togliere la guardia
  `missing_item` in `send_message()`; cancellare `ChatAIDeletedConfTest.php`.
