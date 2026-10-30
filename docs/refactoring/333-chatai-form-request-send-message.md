# 333 - ChatAI: Form Request per send_message e send_message_agent

- **Data**: 2026-10-30
- **Stato**: Completato (test eseguiti il 2026-10-30: 58 verdi, 204 asserzioni)
- **Area**: Architettura / Form Request + Chat AI
- **File/aree di codice coinvolte**:
  - `app/Http/Requests/ChatAI/SendChatMessageRequest.php` (nuovo)
  - `app/Http/Requests/ChatAI/SendAgentMessageRequest.php` (nuovo)
  - `app/Http/Controllers/System/AdminChatAIController.php` (firme e prime righe di `send_message()` / `send_message_agent()`, rimosso `use Request;`)
  - `resources/lang/en/crudbooster.php`, `resources/lang/it/crudbooster.php` (chiave `chatai_invalid_request`)
  - `tests/Feature/ChatAISendMessageValidationTest.php` (nuovo)
  - `tests/Feature/ChatAISendMessageAgentTest.php` (modificato l'helper `postSendMessageAgent()`)

## Contesto

Batch 2 del piano
[`piani/332-chatai-controller-pulizia-i18n-form-request.md`](piani/332-chatai-controller-pulizia-i18n-form-request.md)
(batch 1 già documentato nel [332](332-chatai-controller-pulizia-i18n-access-tenant-content-view.md),
non riscritto qui). Dopo il primo Form Request del repo (327), questo porta la
validazione degli input anche agli endpoint di invio messaggio della Chat AI.
Decisione D2 del piano, approvata dalla persona il 2026-10-30: risposta JSON
`{"message": trans('crudbooster.chatai_invalid_request')}` con HTTP 422.

**Cambio di comportamento visibile dichiarato** (vedi "Situazione dopo").

## Situazione prima

- `send_message(Request $request)` e `send_message_agent(Request $request)`
  leggevano `message` (e `agent_id`) con `$request->all()` senza alcuna
  validazione.
- `message` mancante: errore 500 (HTML). `message` vuoto, solo spazi o non
  stringa: inoltrato comunque all'agente.
- `send_message_agent` controllava `ChatAIHelper::can_see_item($agent_id)` in
  un blocco inline all'inizio del metodo (introdotto dal doc 329) e rispondeva
  con un `echo` del JSON `{"message": trans('crudbooster.denied_access')}`,
  stato 200, `Content-Type` text/html.
- `send_message()` non fa `can_see_item` (scelta voluta, ADR A16).
- `agent_id` mancante: per un superadmin 500; per un non superadmin
  `denied_access`.

## Situazione dopo

- **`SendChatMessageRequest`**: `rules()` = `message` `required|string`;
  `authorize()` restituisce `true` (l'accesso è già filtrato dal gruppo di rotte
  con `CBBackend` + `EnforceModuleLicense`).
- **`SendAgentMessageRequest`**: `rules()` = `message` `required|string`,
  `agent_id` `required` (volutamente senza `integer`, come prima);
  `authorize()` replica `ChatAIHelper::can_see_item($this->input('agent_id'))`;
  `failedAuthorization()` lancia una `HttpResponseException` con lo stesso JSON
  di prima (`{"message": trans('crudbooster.denied_access')}`) e stato **200**
  (nessun 403, per non cambiare quanto il frontend si aspetta).
- In entrambe, `failedValidation()` lancia una `HttpResponseException` con
  `{"message": trans('crudbooster.chatai_invalid_request')}` e stato **422**.
- `AdminChatAIController`: firme `send_message(SendChatMessageRequest $request)`
  e `send_message_agent(SendAgentMessageRequest $request)`; `message`/`agent_id`
  letti da `$request->validated()`; tolto il blocco inline `can_see_item` (ora
  in `authorize()`); tolto `use Request;` (diventato inutilizzato). Invariati:
  init sessione, scelta della conf, `whereNull('deleted_at')`, guardie
  `missing_item`, `relayToAgent()`.
- Iniezione dalla firma, non con `validateCurrent()` né `app()` (come invece nel
  327): i due metodi non sono override di `CBController` e le rotte sono
  esplicite (`routes/web.php:75-76`). Il reviewer ha verificato che
  `CRUDBooster::routeController` registra solo metodi `get*`/`post*`, che non
  c'è `callAction`/`__call` personalizzato e che gli unici chiamanti sono le
  rotte e i `fetch` delle viste.
- Chiave nuova `chatai_invalid_request`: en "Invalid request: a valid message
  is required.", it "Richiesta non valida: serve un messaggio valido.".

### Effetti visibili

- (a) `message` mancante, vuoto, di soli spazi o non stringa (array, numero o
  booleano in un body JSON): JSON `chatai_invalid_request` con **422** (prima:
  500 se mancante, inoltrato all'agente negli altri casi).
- (b) `agent_id` mancante: **422** per un superadmin (per un non superadmin
  resta `denied_access`).
- (c) Il `denied_access` ora esce come risposta JSON con `Content-Type:
  application/json` (prima `echo`, text/html); stesso corpo e stesso stato 200.
- Frontend: `chat3.blade.php` e `chat_ai/view.blade.php` fanno `response.json()`
  e leggono `data.message` senza controllare `response.ok`, quindi il 422 viene
  mostrato come messaggio normale. `fullscreen_view` non è stato letto dal
  reviewer (stesso pattern, da verificare).
- Per l'uso normale della chat non cambia nulla: il JS blocca i messaggi vuoti
  e invia sempre stringhe.

## Motivazione

Portare gli input di endpoint esposti a un Form Request elimina un 500 su input
mancante e smette di inoltrare all'agente messaggi non validi (richieste a
servizi esterni inutili). Sposta inoltre il controllo di autorizzazione
(`can_see_item`) nel posto standard Laravel (`authorize()`), lasciando nel
controller solo la logica di business. Alternative scartate: `validateCurrent()`
o `app()` (servono solo quando la firma è vincolata da `CBController`, qui non
lo è); un 403 per `denied_access` (cambierebbe lo stato oltre il necessario).

## Test

Verifiche eseguite:

- `php -l` ok su tutti i file nuovi e toccati.
- Pint `--test`: PASS sui 3 file nuovi; sul controller solo stile storico (tab,
  righe vuote) non toccato in questo intervento.
- `route:list --path=chat_ai`: conferma le due rotte esplicite POST
  `admin/chat_ai/send_message` e `send_message_agent` (nome del metodo troncato
  dall'output).
- Larastan (`phpstan analyse --no-progress`) dopo `clear-result-cache`:
  nessun errore.
- Review di `ch-reviewer` sul diff.

**Correzione emersa dalla review (il piano si sbagliava)**: il piano dava per
scontato che `ChatAISendMessageAgentTest` restasse verde senza modifiche, ma il
`denied_access` ora esce come Response JSON e non come `echo`: il suo helper
`postSendMessageAgent()` leggeva solo il buffer `ob_start` e avrebbe dato i casi
1-4 rossi. Corretto: ora l'helper legge il corpo del `TestResponse` quando il
buffer è vuoto (JSON e stato 200 invariati).

Test **eseguiti il 2026-10-30 su richiesta della persona: 58 verdi (204 asserzioni)**,
al primo run, senza correzioni (10 nuovi + 48 di regressione: `ChatAISendMessageAgentTest` 8,
`ChatAIDeletedConfTest` 4, `ChatAIRelayCharacterizationTest` 8, `ChatAIAuthorizationTest` 28).
Non verificato: che i casi 1-3 del test nuovo fossero rossi sul codice precedente.

- Nuovo `tests/Feature/ChatAISendMessageValidationTest.php` (10 test):
  `message` mancante/vuoto/array -> 422 per entrambi gli endpoint, senza storico
  né chiave di sessione; `agent_id` mancante da superadmin -> 422; tenant admin
  non autorizzato con `message` mancante -> 200 `denied_access`; positivi
  affiancati che arrivano allo storico.
- Regressione (verde): `ChatAISendMessageAgentTest` (helper modificato),
  `ChatAIDeletedConfTest`, `ChatAIRelayCharacterizationTest`,
  `ChatAIAuthorizationTest`.

## Rischi e note

1. Test eseguiti: 58 verdi (vedi sopra). Non rieseguiti sul codice precedente.
2. Rilievo del reviewer secondo cui il messaggio ora sarebbe "trimmato"
   (`validated()` vs `Request::all()`): **non verificato**, probabilmente falso
   positivo (entrambi leggono lo stesso input già passato dai middleware).
   `TrimStrings`/`ConvertEmptyStringsToNull` non sono stati trovati
   nell'indice, che non copre 52 file PHP: non confermato. Da verificare.
3. `agent_id` array/non scalare: per il superadmin passa `authorize()` e
   `required` e poi dà 500 "Array to string" alla chiave di sessione; per i non
   superadmin il 500 viene dal log in `can_see_item`. Identico a prima, edge
   preesistente non coperto da test né corretto.
4. Lacune dei test (suggerite dal reviewer, non coperte): nessun test con body
   JSON (`postJson`), che è ciò che invia il frontend; nessun test per messaggio
   di soli spazi né numerico; i positivi usano `assertNotSame(422)` (debole,
   compensato dal conteggio dello storico); nel file nuovo manca il caso
   non-superadmin con `agent_id` non numerico + `message` valido (coperto solo
   da `ChatAISendMessageAgentTest` caso 3).
5. Fuori perimetro e non toccati: l'alias `'Name'` hardcoded in
   Qlik/Groups/Tenants/CmsUsers e gli altri problemi dei file lang già elencati
   nel doc 332.
6. Il diff di `AdminChatAIController.php` contiene anche modifiche non
   committate di altri interventi (doc 323, 329, 330, 331, 332 batch 1): non
   vanno attribuite a questo.
7. ChatAI non è attivo in produzione (doc 300): nessun impatto sui controller
   custom dei clienti.
8. Da verificare: `fullscreen_view` e il suo trattamento di una risposta 422.

## Rollback

Nessuna migrazione. Ripristinare le due firme e le prime righe di
`send_message()` / `send_message_agent()` in `AdminChatAIController.php` (incluso
`use Request;` e il blocco inline `can_see_item`), cancellare
`app/Http/Requests/ChatAI/` e `tests/Feature/ChatAISendMessageValidationTest.php`,
ripristinare l'helper `postSendMessageAgent()` di `ChatAISendMessageAgentTest`,
togliere `chatai_invalid_request` da `resources/lang/en/crudbooster.php` e
`resources/lang/it/crudbooster.php`. Attenzione: ripristinando solo il
controller senza l'helper del test, i casi 1-4 tornano a funzionare; con
l'helper nuovo e il controller vecchio dovrebbero funzionare comunque (da
verificare).
