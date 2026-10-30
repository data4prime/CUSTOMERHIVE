# 331 - ChatAI: client della chat in un servizio (ChatAIClient)

- **Data**: 2026-10-30
- **Stato**: Completato (test eseguiti il 2026-10-30: 54 verdi, 181 asserzioni)
- **Area**: Architettura / Service + Chat AI
- **File/aree di codice coinvolte**:
  - `app/Services/ChatAI/ChatAIClient.php` (nuovo)
  - `app/Http/Controllers/System/AdminChatAIController.php` (`send_message()`, `send_message_agent()`, nuovo metodo privato `relayToAgent()`)
  - `resources/lang/en/crudbooster.php`, `resources/lang/it/crudbooster.php` (chiave `chatai_request_error`)
  - `tests/Feature/ChatAIRelayCharacterizationTest.php`, `tests/Feature/ChatAIClientTest.php` (nuovi)
  - Piano: `docs/refactoring/piani/331-chatai-client-service.md` (batch B del flusso `/architect`)

## Contesto

Batch B del refactoring di `AdminChatAIController`, dopo il 329 (controllo
`can_see_item($agent_id)` in `send_message_agent()`) e il 330 (Action e conf
soft-deleted). Le due funzioni `send_message()` e `send_message_agent()`
duplicavano quasi per intero la parte che parla con l'agente (payload, cURL,
lettura della risposta, sessione, storico). Si estrae la parte "client" in un
servizio, lasciando nel controller ciò che dipende da HTTP e sessione.

Intervento con **due cambi visibili dichiarati** (decisioni della persona,
2026-10-30): D1 e D3, descritti sotto.

## Situazione prima

Dal calcolo del token in poi le due funzioni erano identiche:

1. `ChatAIHelper::getToken($conf->id)`; se falso, risposta con
   `chatai_token_error` e `return`.
2. Payload `["action" => "sendMessage", "sessionId" => '"'.CRUDBooster::myId().'"',
   "chatInput" => $message]` (i doppi apici in `sessionId` sono voluti).
3. cURL con `RETURNTRANSFER`, `POST`, header `Accept`/`Content-Type`
   `application/json` e `Authorization: Bearer <token>`, `POSTFIELDS` JSON.
   Nessun timeout; nessun `curl_close` (tolti per PHP 8.5, doc 323).
4. Se `curl_exec` è `false`: messaggio hardcoded in italiano
   `'Errore nella richiesta. Verifica la configurazione attiva!'`.
5. Aggiunta di `['message', 'response']` alla sessione, `saveChatHistory(...)`,
   `echo` della risposta.

Unica differenza di logica: la lettura della risposta dell'agente.
`send_message()` leggeva `text`, altrimenti `$decoded["message"]` senza
`isset` (con una risposta senza `text`/`message` o non JSON: warning che
Laravel converte in eccezione, quindi 500 - **ipotesi dal codice, non
provata**). `send_message_agent()` leggeva `text`, poi `message`, altrimenti
`raw = json_encode(["message" => decodificato])`.

Altre differenze (invariate): conf primary vs conf di `agent_id`;
`can_see_item` solo nell'agente (per `send_message()` è una scelta voluta,
ADR A16: non aggiungerlo); chiave di sessione `chat_messages` vs
`chat_messages_<agent_id>`; primo argomento di `saveChatHistory` `null` vs
`$agent_id`.

## Situazione dopo

- Nuovo `App\Services\ChatAI\ChatAIClient`:
  - `send($url, string $token, $message, $userId)`: costruisce il payload
    (doppi apici attorno a `$userId` in `sessionId`), fa la stessa chiamata
    cURL (stesse opzioni, **nessun timeout, nessun `curl_close`**) e passa il
    risultato a `interpret()`. Ritorna `['raw' => ..., 'message' => ...]`.
  - `interpret(string|false $raw)`: pubblica, testabile senza rete. Legge
    `text`, poi `message`, altrimenti `raw = json_encode(["message" =>
    decodificato])`; `false` -> messaggio `trans('crudbooster.chatai_request_error')`.
  - I type hint di `$url` e `$message` sono stati tolti dopo la review (come
    nel codice vecchio, senza tipo); solo `$token` è `string`.
- `AdminChatAIController`: `send_message()` e `send_message_agent()` tengono
  lettura di `message` (per l'agente anche `agent_id` e `can_see_item` del 329),
  init della chiave di sessione, scelta della conf e guardie `missing_item`,
  poi chiamano il metodo **privato** `relayToAgent($conf, $message,
  $sessionKey, $historyConfId)`: `getToken` (errore `chatai_token_error`),
  `ChatAIClient::send`, aggiunta alla sessione, `saveChatHistory`
  (`null` per `send_message`, `$agent_id` per l'agente), `echo`. L'ordine delle
  operazioni è invariato. Nessun metodo pubblico nuovo (sarebbe una rotta).
  Il servizio si istanzia con `new` (ADR A14).
- Nuova chiave `crudbooster.chatai_request_error`: en "Request failed. Check
  the active configuration!"; it identica al vecchio testo hardcoded, che è
  stato rimosso dal controller.
- Nessuna rotta, vista, JS o migrazione toccati.

### Cambi visibili (dichiarati)

- **D1 - solo `send_message()`**: la lettura della risposta usa la semantica
  di `send_message_agent()`. Una risposta dell'agente senza `text`/`message` o
  non JSON non dà più (ipoteticamente, non provato) un 500 ma `{"message":
  ...}`. `send_message_agent()` invariato.
- **D3 - utenti con interfaccia in inglese**: il messaggio di errore di rete
  prima compariva sempre in italiano, ora è tradotto in inglese. Invariato per
  l'interfaccia in italiano.

## Motivazione

Elimina la duplicazione (un solo punto per payload, cURL e lettura della
risposta), rende la lettura della risposta testabile senza rete
(`interpret()` pubblica), allinea il controller al pattern Service già usato
in `app/Services/QlikSync/` e rispetta la regola i18n (niente testo UI
hardcoded in una lingua). Alternative scartate: un flag per conservare le due
semantiche di lettura (più codice per preservare un difetto, D1); un timeout
cURL (D2, vedi Rischi: un agente n8n/LLM può rispondere in decine di secondi,
un valore sbagliato romperebbe chat oggi funzionanti).

## Test

Verificato davvero:
- `php -l` ok su tutti i file nuovi e toccati (controller, servizio, due test,
  due file lang).
- Pint `--test` PASS sui file nuovi; su controller e file lang segnala solo
  stile storico non toccato da questo intervento.
- Review di `ch-reviewer`; lettura del diff per payload e opzioni cURL (non
  verificabili senza un server).

Test nuovi, **eseguiti il 2026-10-30 su richiesta della persona (verdi)**:
- `tests/Feature/ChatAIRelayCharacterizationTest.php`: test 1-4 del piano,
  ciascuno per entrambi gli endpoint (errore di rete con risposta/storico/
  sessione; errore token con positivo affiancato; accumulo in sessione su due
  messaggi con una sola riga di storico; contenuto dello storico, `chat_ai_id`
  `null` vs id).
- `tests/Feature/ChatAIClientTest.php`: `interpret()` per `text`, `message`,
  né l'uno né l'altro, non JSON, `false`; `send()` su URL irraggiungibile.

Regressione, eseguita nella stessa run, verde: `ChatAISendMessageAgentTest` (8),
`ChatAIDeletedConfTest` (4), `ChatAIAuthorizationTest` (28). Totale: 54 test (8
`ChatAIRelayCharacterizationTest`, 6 `ChatAIClientTest`), 181 asserzioni.

## Rischi e note

1. I test (nuovi e di regressione) sono stati eseguiti il 2026-10-30: 54 verdi. Non si
   è invece rieseguita la caratterizzazione sul codice precedente al refactoring, quindi
   "verde prima e dopo" è dedotto, non osservato.
2. Payload e richiesta cURL non sono verificabili senza un server: verificati
   solo leggendo il diff.
3. Il test 1 usa `trans()` sulla chiave: sul codice vecchio sarebbe verde solo
   con locale `it` (locale di test non verificato).
4. Il test 3 assume che richieste consecutive condividano lo store di sessione
   (verificato dal reviewer leggendo `StartSession`).
5. La porta `127.0.0.1:9` deve rifiutare subito la connessione nel container
   (stessa assunzione di `ChatAISendMessageAgentTest`).
6. `ChatAIClientTest` non copre `text` pari a `''`/`0`, `message` non stringa,
   `text` null che cade su `message` (non bloccante).
7. Il diff del controller contiene anche modifiche non committate di altri
   interventi, da non attribuire al 331: doc 329 (guardie in
   `send_message_agent`), doc 330 (Action e `whereNull('deleted_at')`), doc 323
   (`curl_close` rimossi).
8. Follow-up non fatti: timeout cURL assente (D2, da aggiungere dopo,
   configurabile); `getConf()` risulta senza chiamanti nel codice PHP indicizzato,
   ma i Blade non sono indicizzati: da verificare prima di toglierlo.
9. ChatAI non è attivo in produzione (doc 300); nessun impatto sui controller
   dei clienti (controller di sistema, nessuna proprietà o hook cambia).
10. Fuori perimetro, emerso dal reviewer nel confronto en/it dell'intero file
    lang, **non toccato, da verificare a mano**: la chiave
    `your_file_extension_is_not_allowed` risulta presente da un solo lato
    (probabile spazio finale nella chiave); i segnaposto di
    `statistic_builder_extra_series_name` / `statistic_builder_extra_series_color`
    differiscono tra en e it.
11. Rilievo del reviewer sui type hint di `send()` (`$url`, `$message`):
    corretto, tolti.
12. `send_message()` resta senza `can_see_item` (ADR A16, decisione della
    persona).

## Rollback

Ripristinare dal diff le due funzioni `send_message()` e
`send_message_agent()` di `AdminChatAIController` (rimuovendo `relayToAgent()`),
cancellare `app/Services/ChatAI/` e i due test nuovi, togliere la chiave
`chatai_request_error` da `resources/lang/en/crudbooster.php` e
`resources/lang/it/crudbooster.php`. Nessuna migrazione da annullare.
