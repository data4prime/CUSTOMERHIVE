# 329 - ChatAI: `send_message_agent` controlla l'autorizzazione sull'`agent_id`

- **Data**: 2026-10-30
- **Stato**: Completato (test eseguiti: 36 verdi)
- **Area**: Sicurezza / Chat AI (correzione, non refactoring)
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/AdminChatAIController.php` (solo l'inizio di `send_message_agent()`, +15 righe)
  - `tests/Feature/ChatAISendMessageAgentTest.php` (nuovo, 8 casi)
  - Piano: `docs/refactoring/piani/329-chatai-send-message-agent-controllo-autorizzazione.md`

## Contesto

Intervento nato dall'analisi di `AdminChatAIController` nella sessione
`/architect` del 2026-10-30. È il **batch 0** di una serie sul controller: una
correzione di sicurezza che precede il batch A (Action per le scritture di
autorizzazione e per la regola "conf primary") e il batch B (client della
chat), non ancora eseguiti. Segue il 318 (autorizzazioni ChatAI su tabelle
dedicate) e il 324 (rotte di sistema sotto login/licenza).

## Situazione prima

`POST admin/chat_ai/send_message_agent` leggeva `message` e `agent_id` dalla
richiesta, creava la chiave di sessione `chat_messages_<agent_id>`, caricava la
conf da `chatai_confs` e chiamava via cURL l'URL della conf con il suo token.
**Nessun controllo di autorizzazione sull'`agent_id`.**

L'unico chiamante legittimo è `resources/views/chat_ai/view.blade.php`, pagina
raggiungibile solo da `content_view`, che già filtra con
`ChatAIHelper::can_see_item()`. Ma l'endpoint, chiamato a mano, no: un utente
loggato con licenza ChatAI poteva usare URL e token di una conf a cui non era
autorizzato. Con un id inesistente `$chatai_conf` era `null` e l'accesso a
`->url` dava un 500.

La rotta è dietro `CBBackend` + `EnforceModuleLicense`; ChatAI non è attivo in
produzione (doc 300), quindi nessun cliente era esposto oggi.

## Situazione dopo

Due guardie in `send_message_agent()`, entrambe con lo stesso formato JSON già
usato dal metodo per l'errore token (`echo json_encode(["message" => ...]); return;`,
nessun redirect perché la chiamata è un `fetch`; il JS mostra `data.message`
nella chat):

1. Dopo la lettura di `message` e `agent_id` e **prima** della chiave di
   sessione: se `!ChatAIHelper::can_see_item($agent_id)` risponde con
   `trans('crudbooster.denied_access')`.
2. Dopo il caricamento della conf: se vuota, risponde con
   `trans('crudbooster.missing_item')` (raggiungibile solo dal superadmin,
   per il quale `can_see_item` è sempre vero).

Nessun testo nuovo: le chiavi esistono già in en e it (i18n n/a). Nessuna
modifica a rotta, vista, `ChatAIHelper`, cURL, sessione, `saveChatHistory`,
`send_message()`.

**Cambia comportamento visibile: SÌ**, ma solo fuori dall'interfaccia normale:
- (a) chi chiama l'endpoint a mano con un `agent_id` non autorizzato riceve il
  messaggio di accesso negato;
- (b) un superadmin con id inesistente riceve `missing_item` invece di un 500.

Nessun cambio per l'uso dalla pagina chat.

## Motivazione

Chiude un accesso non autorizzato a risorse (URL/token di una conf) per
qualunque utente loggato con licenza, riusando un helper già testato
(`ChatAIAuthorizationTest`). Scelta del formato di risposta: il JSON del
metodo, perché un redirect restituirebbe HTML a un `fetch`. Chiavi di
traduzione riusate invece di crearne di nuove.

## Test

Verificato davvero:
- `php -l` su controller e test: ok.
- Pint `--test` sul test nuovo: PASS. Sul controller segnala solo stile
  storico (tab, righe vuote) non toccato da questo intervento.
- Lettura del diff.

Scritto ma **NON eseguito** (da lanciare con `/refactor-verify`):
`tests/Feature/ChatAISendMessageAgentTest.php`, 8 casi, più
`ChatAIAuthorizationTest` come regressione.
- Negativi (rossi sul codice vecchio per costruzione): tenant admin con conf
  abilitata solo a un altro tenant; utente standard con tenant ma non gruppo
  abilitato; `agent_id` non intero; `agent_id` inesistente da non superadmin;
  superadmin con id inesistente (`missing_item`).
- Positivi (3): tenant admin abilitato, utente standard con tenant e gruppo,
  superadmin su conf esistente. Servono a provare che i negativi non siano
  verdi a vuoto, con assert di 1 riga in `chat_ai_history`.

**Esito (eseguiti il 2026-10-30 su richiesta della persona)**: `ChatAISendMessageAgentTest`
8/8 e `ChatAIAuthorizationTest` come regressione, 36 test verdi in totale (107
asserzioni), compreso l'assert di 1 riga in `chat_ai_history` nei positivi (la
richiesta arriva a `saveChatHistory` con url `http://127.0.0.1:9/` e passphrase di
40 caratteri). **Non verificato**: che i casi 1-4 e 8 fossero davvero rossi sul
codice precedente (non si è rieseguito il test sul vecchio codice).

## Rischi e note

- Review di `ch-reviewer`: nessun bloccante. Il rilievo sui test positivi (provare
  anche la scrittura in `chat_ai_history`) è stato corretto con l'assert di 1 riga
  citato sotto; gli altri punti (chiave di sessione vuota, porta 9, `send_message()`
  senza test) sono riportati qui di seguito.
- Un utente a cui viene revocata l'autorizzazione con la pagina già aperta
  vede ora il messaggio di rifiuto nella chat (voluto).
- Per un superadmin con id inesistente la chiave di sessione
  `chat_messages_<id>` viene comunque creata (vuota) prima della guardia
  `missing_item`: innocuo, il piano metteva lì la guardia; il test 8 non lo
  verifica.
- `send_message()` resta con conf `primary` per tutti gli utenti loggati con
  licenza, senza controllo: domanda di prodotto aperta, per un batch
  successivo. Resta anche senza test.
- Il cURL non ha timeout e il test positivo dipende dal fatto che la porta 9
  rifiuti subito la connessione nel container (non verificato).
- Il diff di `AdminChatAIController.php` contiene altre modifiche non
  committate non del 329 (due rimozioni di `curl_close`, probabilmente audit
  PHP 8.5, doc 323): da non attribuire a questo intervento.
- Nessun impatto su dati, schema, `CBController` o controller dei clienti.
  ChatAI non attivo in produzione (doc 300).

## Rollback

Togliere le due guardie dal metodo `send_message_agent()` in
`app/Http/Controllers/System/AdminChatAIController.php` e cancellare
`tests/Feature/ChatAISendMessageAgentTest.php`. Nessuna migrazione da annullare.
