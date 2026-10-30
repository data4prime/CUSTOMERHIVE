# Piano 329 — ChatAI: `send_message_agent` controlla l'autorizzazione sull'`agent_id` (batch 0)

Stato: **bozza, in attesa di approvazione della persona**. Branch: `refactor/models`.
Autore: `/architect`, 2026-10-30. Destinatario: `ch-senior-dev` (non vede la
conversazione: questo file è autosufficiente). Origine: analisi di
`AdminChatAIController` (sessione `/architect` del 2026-10-30). È il batch 0
della serie su quel controller: **non è un refactoring, è una correzione**;
precede il batch A (Action per le scritture di autorizzazione e per la regola
"conf primary") e il batch B (client della chat), che non fanno parte di questo piano.

## Obiettivo

Impedire che un utente loggato, ma non autorizzato a una conf ChatAI, la usi
chiamando a mano `POST admin/chat_ai/send_message_agent` con un `agent_id`
qualunque. Oggi l'endpoint non controlla nulla; la pagina che lo usa
(`content_view`) invece sì.

**Cambia comportamento visibile**, ma solo per chi chiama l'endpoint fuori
dall'interfaccia (vedi "Impatto").

## Stato attuale (fatti verificati 2026-10-30, lettura del codice, non provati a runtime)

- `AdminChatAIController::send_message_agent()` (`AdminChatAIController.php:801-894`)
  legge `message` e `agent_id` dalla richiesta, crea la chiave di sessione
  `chat_messages_<agent_id>`, carica `DB::table('chatai_confs')->where('id', $agent_id)->first()`,
  ricava il token con `ChatAIHelper::getToken()` e chiama l'URL della conf via cURL.
  Non c'è alcun controllo di autorizzazione sull'`agent_id`.
- `ChatAIHelper::can_see_item($chat_ai_id)` (`app/Helpers/ChatAIHelper.php:69`):
  superadmin sempre `true`; id non intero o conf inesistente `false`; poi tenant
  abilitato (tenant admin basta) e, per gli altri, appartenenza a un gruppo
  abilitato. È già coperto da `tests/Feature/ChatAIAuthorizationTest.php`.
- `can_see_item` oggi è chiamato per ChatAI solo in `content_view`
  (`AdminChatAIController.php:757`) e nel menu (`CRUDBooster::sidebarMenu`).
- L'unico chiamante dell'endpoint è `resources/views/chat_ai/view.blade.php:141`
  (`fetch` con `{message, agent_id}`), pagina raggiungibile solo da `content_view`,
  quindi già filtrata da `can_see_item`. Il JS mostra `data.message` o `data.text`
  nella chat, e un errore generico se mancano entrambi.
- La rotta è in `routes/web.php:76`, nel gruppo con `CBBackend` +
  `EnforceModuleLicense` (doc 324): serve login e licenza ChatAI. `isActiveChatAI()`
  è falso in produzione (doc 300): oggi nessun cliente è esposto.
- Se la conf non esiste (id inventato, solo superadmin dopo il fix) `$chatai_conf` è
  `null` e `$chatai_conf->url` produce un errore (500).

## Perimetro

Dentro:
- `AdminChatAIController::send_message_agent()`, solo l'inizio del metodo.
- Test nuovo: `tests/Feature/ChatAISendMessageAgentTest.php`.

Fuori (non toccare): `send_message()` (conf `primary` per tutti: scelta di prodotto
ancora da prendere), `content_view`, `ChatAIHelper`, la vista `view.blade.php`, la
rotta, le Action/il client della chat (batch A e B), il cURL e il suo parsing, la
sessione e `saveChatHistory`.

## Cosa fare

Nel metodo, **prima** della riga che crea la chiave di sessione
`chat_messages_<agent_id>` (così un rifiuto non lascia chiavi in sessione), dopo aver
letto `message` e `agent_id` come oggi:

1. Se `!ChatAIHelper::can_see_item($agent_id)`: rispondere con lo **stesso formato
   JSON già usato dal metodo per l'errore del token** — `echo json_encode(["message" => trans('crudbooster.denied_access')]); return;`
   — senza redirect (la chiamata è un `fetch`: un redirect restituirebbe HTML).
   Il JS la mostra già come messaggio nella chat. Nessuna chiave nuova: si riusa
   `crudbooster.denied_access`, che esiste in en e it.
2. Dopo il caricamento della conf: se `$chatai_conf` è vuota (possibile solo per un
   superadmin con id inesistente), stessa risposta JSON con
   `trans('crudbooster.missing_item')` (chiave già esistente, usata in `content_view`).
   Questo trasforma un 500 in un messaggio: è l'unico altro cambio di comportamento,
   da dichiarare nel doc.
3. Nient'altro. Nessun refactoring, nessuna estrazione, nessuna riga spostata oltre
   al punto di inserimento. Il metodo resta pubblico e con lo stesso nome (è una rotta).

Non toccare la riga `Request::all()['agent_id']`: se la chiave manca oggi dà errore,
e il comportamento non è parte di questo batch.

## Test (da scrivere PRIMA della modifica; devono essere rossi sul codice vecchio dove indicato)

Nuovo `tests/Feature/ChatAISendMessageAgentTest.php`, stesso setup di
`ChatAIAuthorizationTest` (`registerAdminModules()`, `registerAdminModule('chat_ai',
'AdminChatAIController', 'chatai_confs')`, `fakeActiveChatAILicense()`, `seedConf`,
`allowTenant`, `allowGroup`; copia i helper, non c'è trait condiviso). Crea le conf con
un `url` che fallisce subito (es. `http://127.0.0.1:9/`) così il cURL non attende;
`getToken()` può restituire `false` e rispondere con `chatai_token_error` prima del
cURL: entrambe le risposte provano che la richiesta ha **superato** il controllo.
Leggi `ChatAIHelper::getToken()` e `saveChatHistory()` per capire cosa serve a una conf
valida e dove finisce lo storico. Le chiamate scrivono con `echo`: cattura con
`ob_start()` come in `ApiTokensAddSaveTest::postAddSave()`.

In ordine di rischio decrescente (input → risposta/stato atteso):

1. **Tenant admin, conf abilitata solo a un altro tenant** → JSON `message =
   denied_access`, **nessuna riga di storico**, nessuna chiave `chat_messages_<id>`
   in sessione. *Rosso sul codice vecchio.*
2. **Utente standard, tenant abilitato ma non il suo gruppo** → come sopra. *Rosso.*
3. **`agent_id` non intero** (es. `abc`) da non superadmin → `denied_access`. *Rosso.*
4. **`agent_id` inesistente** da non superadmin → `denied_access`. *Rosso.*
5. **Caso positivo, tenant admin con tenant abilitato** → risposta diversa da
   `denied_access` (token error o errore di rete). *Verde prima e dopo; prova che i
   negativi 1-4 non sono verdi "a vuoto" (lezione 2026-10-29, ChatAI).*
6. **Caso positivo, utente standard con tenant e gruppo abilitati** → idem.
7. **Superadmin, conf esistente** → idem (non è bloccato).
8. **Superadmin, `agent_id` inesistente** → JSON `message = missing_item`, nessun 500.
   *Rosso sul codice vecchio.*

I test non vengono lanciati dall'architect: i rossi attesi sono 1-4 e 8; il batch non
è chiuso finché la persona non lancia `/refactor-verify` e tutti sono verdi, più
`ChatAIAuthorizationTest` come regressione.

## Vincoli

- Nessun testo nuovo in UI: `denied_access` e `missing_item` esistono già in
  `resources/lang/en|it/crudbooster.php` (verificalo prima di usarle).
- Nessun `app(...)`/`resolve()` nel perimetro Larastan (ADR A14); non serve.
- Nessun commit/push, nessuna suite lanciata in autonomia, nessun accesso a server remoti.
- `php -l` e Pint `--test` solo sui file toccati.

## Impatto sui clienti

Nessuno per l'uso normale dell'interfaccia: la pagina chat è già filtrata da
`can_see_item`, quindi per chi la usa il nuovo controllo dà sempre lo stesso esito.
Cambia solo per chi invia a mano un `agent_id` non autorizzato (ora riceve un
messaggio di accesso negato) e per un superadmin con id inesistente (messaggio invece
di 500). Il modulo ChatAI non è attivo in produzione (doc 300). Nessuna modifica a
dati, schema, rotte, proprietà o hook di `CBController`, nessun impatto sui controller
dei clienti.

## Rischio

Basso. Un controllo in testa a un metodo, riusando un helper già testato.
Punto di attenzione: l'utente a cui venisse revocata l'autorizzazione con la pagina
già aperta riceve ora il messaggio di rifiuto nella chat (comportamento voluto).

## Rollback

Togliere le due guardie dal metodo e cancellare il test. Nessuna migrazione.

## Dopo il batch

Reviewer (blocco i18n atteso "n/a": solo chiavi già esistenti) → correzioni →
`ch-doc-writer` (documentare come correzione di sicurezza, con il cambio di
comportamento dichiarato) → post-mortem in `docs/architettura/lezioni.md`. Nessun ADR
nuovo. Resta aperta, come domanda di prodotto per un batch successivo, la scelta su
`send_message()` (conf `primary` per tutti gli utenti loggati con licenza).
