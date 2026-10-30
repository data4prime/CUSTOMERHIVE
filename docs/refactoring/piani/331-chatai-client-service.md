# Piano 331 — ChatAI: client della chat in un servizio (batch B)

Stato: **approvato dalla persona il 2026-10-30, con le 3 decisioni come raccomandate**:
D1 semantica di `send_message_agent()` per entrambe; D2 nessun timeout in questo batch;
D3 chiave i18n `crudbooster.chatai_request_error` (it identica a oggi, en nuova).
Branch: `refactor/models`.
Autore: `/architect`, 2026-10-30. Destinatario: `ch-senior-dev` (non vede la
conversazione: questo file è autosufficiente). Origine: analisi di
`AdminChatAIController` (sessione `/architect` del 2026-10-30). Segue i doc 329 (controllo
`agent_id`) e 330 (Action e conf soft-deleted). ADR A16: `send_message()` usa la conf primary
per tutti, voluto: **non aggiungere `can_see_item` a `send_message()`**.

## Obiettivo

Le due funzioni `send_message()` e `send_message_agent()` di `AdminChatAIController`
duplicano quasi per intero la parte che parla con l'agente (payload, cURL, lettura della
risposta). Estrarla in un servizio `ChatAIClient`, lasciando nel controller solo ciò che
dipende da HTTP e sessione. Il resto resta **behavior-preserving**, con l'unica eccezione
dichiarata in "Decisioni da confermare".

## Stato attuale (fatti verificati 2026-10-30, lettura del codice, `AdminChatAIController.php`)

`send_message()` (r.616-~710) e `send_message_agent()` (r.774-~880), dopo i doc 329/330.

**Parte comune** (identica nelle due, dal calcolo del token in poi):
1. `$token = ChatAIHelper::getToken($conf->id)`; se falso: `echo json_encode(["message" =>
   trans('crudbooster.chatai_token_error')]); return;`.
2. Payload: `["action" => "sendMessage", "sessionId" => '"'.CRUDBooster::myId().'"',
   "chatInput" => $message]` (i doppi apici dentro `sessionId` sono voluti: non cambiarli).
3. cURL: `curl_init($url)`, `RETURNTRANSFER`, `POST`, header `Accept: application/json`,
   `Content-Type: application/json`, `Authorization: Bearer <token>`, `POSTFIELDS =
   json_encode($data)`; `curl_exec`. **Nessun timeout, nessun `curl_close`** (tolti per PHP
   8.5, doc 323), SSL e redirect ai default di cURL.
4. Se `curl_exec` è `false`: `$response_message = 'Errore nella richiesta. Verifica la
   configurazione attiva!'` e `$response = json_encode(["message" => $response_message])`.
5. Poi aggiunge `['message' => $message, 'response' => $response_message]` all'array di
   sessione, `ChatAIHelper::saveChatHistory(<id>, [...stessa coppia])` e fa `echo $response`.

**Differenze tra le due**:

| | `send_message()` | `send_message_agent()` |
|---|---|---|
| Conf | `chatai_confs` con `primary = 1` non cancellata (doc 330) | quella di `agent_id`, non cancellata |
| Controllo accesso | nessuno, voluto (A16) | `can_see_item($agent_id)` (doc 329) |
| Chiave di sessione | `chat_messages` | `chat_messages_<agent_id>` |
| 1º argomento di `saveChatHistory` | `null` | `$agent_id` |
| **Lettura della risposta se non è `false`** | `text` se presente, **altrimenti `$decoded["message"]` senza `isset`** | `text` se presente, poi `message` se presente, **altrimenti `$response = json_encode(["message" => $decoded])`** |

L'ultima riga della tabella è l'unica differenza di logica. Con una risposta dell'agente
senza `text` né `message` (o non JSON) `send_message()` legge una chiave inesistente
(warning, che Laravel converte in eccezione: 500, **ipotesi dal codice, non provata**),
mentre `send_message_agent()` risponde `{"message": <contenuto decodificato o null>}`.

- **Test esistenti** che toccano questi metodi: `ChatAISendMessageAgentTest` (8),
  `ChatAIDeletedConfTest` (4), `ChatAIAuthorizationTest`. Coprono controllo di accesso,
  conf cancellate, arrivo fino a `saveChatHistory` con URL irraggiungibile. **Non coprono**:
  il messaggio di errore di rete, il percorso `chatai_token_error`, l'accumulo in sessione
  su due messaggi, il contenuto della riga di storico, `send_message()` come punto di accesso
  completo.
- JS che consuma le risposte: `chat3.blade.php` (chat fluttuante, `send_message`) e
  `fullscreen_view.blade.php` e `view.blade.php` (`send_message_agent`); leggono `data.text`
  e `data.message`, con un messaggio di errore generico se mancano entrambi.
- cURL non è sostituibile con un fake nei test: per questo i rami di lettura della risposta
  non sono raggiungibili da un test HTTP senza un server vero.

## Decisioni da confermare (della persona)

**D1 — Lettura della risposta: quale delle due semantiche?** Un servizio solo non può
avere due comportamenti senza un flag. Raccomandazione: usare quella di
`send_message_agent()` per entrambe. **Cambia comportamento visibile solo per
`send_message()`**: una risposta dell'agente senza `text`/`message` o non JSON non dà più
(ipoteticamente) un 500 ma `{"message": ...}`. È un miglioramento, ma è un cambio. L'alternativa è un
parametro che conservi le due semantiche: più codice per preservare un difetto.

**D2 — Timeout cURL.** Oggi non c'è: una richiesta lenta o appesa blocca il processo PHP e
il lock di sessione. Raccomandazione: **non aggiungerlo in questo batch**. Un agente
(workflow n8n/LLM) può rispondere in decine di secondi; un valore sbagliato romperebbe chat
oggi funzionanti. Si può aggiungere dopo, configurabile.

**D3 — Il testo `'Errore nella richiesta. Verifica la configurazione attiva!'` è scritto
in italiano nel codice** (vedi anche le regole del repo: ogni testo visibile in en e it, mai
hardcoded). Raccomandazione: **sostituirlo ora** con una chiave `crudbooster.chatai_request_error`
(it identica a oggi, en nuova). Cambio visibile solo per gli utenti con interfaccia in
inglese: oggi vedono comunque il testo in italiano, con D3 lo vedrebbero in inglese.
L'alternativa è lasciarlo identico e registrarlo come difetto noto.

## Perimetro

Dentro:
- `app/Services/ChatAI/ChatAIClient.php` (nuovo).
- `AdminChatAIController.php`: `send_message()` e `send_message_agent()`, più un metodo
  `private` di appoggio (mai `public`: ogni metodo pubblico di una sottoclasse di
  `CBController` diventa una rotta).
- Test nuovi (sotto); con D3: `resources/lang/en/crudbooster.php` e `it/crudbooster.php`.

Fuori (non toccare): `ChatAIHelper::getToken()`/`saveChatHistory()`/`can_see_item()`,
`content_view`, `getConf()`, le viste e il JS, le rotte, la scelta della conf, il controllo
`can_see_item` e le guardie `missing_item` (restano nel controller), `Request::all()['message']`.

## Pattern scelto e perché

**Service** per un'integrazione con un sistema esterno (come `app/Services/QlikSync/`,
senza Strategy/Factory: qui c'è una sola variante). Il servizio **non legge sessione,
richiesta, `CRUDBooster` né `Session`**; riceve tutto per parametro. Si istanzia con `new`
nel controller (ADR A14: niente `app()` nel perimetro Larastan). Niente "Manager" generico.

Forma proposta:

```php
namespace App\Services\ChatAI;

class ChatAIClient
{
    /**
     * @return array{raw: string, message: mixed}  raw = ciò che il controller stampa;
     *         message = ciò che finisce in sessione e nello storico
     */
    public function send(string $url, string $token, string $message, $userId): array

    /** Lettura della risposta, pubblica per poterla testare senza rete. */
    public function interpret(string|false $raw): array
}
```

`send()` costruisce il payload (con i doppi apici attorno a `$userId`), fa la chiamata cURL
con le stesse opzioni di oggi, **senza** timeout (D2) e **senza** `curl_close`, e passa il
risultato a `interpret()`. `interpret()` implementa la semantica scelta in D1 e il ramo
`false` → messaggio di errore (testo da D3).

Nel controller un unico metodo **privato** `relayToAgent($conf, $message, $sessionKey,
$historyConfId)` esegue, nell'ordine di oggi: `getToken` (errore token), `ChatAIClient::send`,
aggiunta alla sessione sotto `$sessionKey`, `saveChatHistory($historyConfId, ...)`, `echo` di
`raw`. Le due funzioni pubbliche mantengono **tutto ciò che precede**: lettura di `message`,
(per l'agente: `agent_id` e `can_see_item`), inizializzazione della chiave di sessione, scelta
della conf e guardia `missing_item`. L'ordine (token → richiesta → sessione → storico → echo)
non cambia.

## Passi

1. **Test di caratterizzazione** (sotto), verdi sul codice attuale.
2. Creare `ChatAIClient` e i suoi test; con D3, le chiavi i18n.
3. Sostituire i corpi delle due funzioni con la chiamata al metodo privato; togliere gli
   `use` rimasti inutilizzati.
4. `php -l` sui file nuovi/toccati e Pint `--test` solo su quelli nuovi (A8).

## Test di caratterizzazione (rischio decrescente; sul codice attuale devono essere verdi)

Setup come `ChatAISendMessageAgentTest`: conf con `url = http://127.0.0.1:9/` e passphrase di
40 caratteri (il cURL fallisce subito), licenza simulata, `ob_start()` per catturare l'`echo`.
Ogni test vale per **entrambi** gli endpoint (`send_message` con conf primary,
`send_message_agent` con `agent_id`), come test parametrizzato.

1. **Errore di rete**: risposta `{"message": "Errore nella richiesta. Verifica la
   configurazione attiva!"}` (con D3 il test usa la chiave), una riga in `chat_ai_history`, e la
   coppia messaggio/risposta nella sessione (`chat_messages` / `chat_messages_<id>`).
2. **Errore token** (conf con passphrase di 10 caratteri): risposta `chatai_token_error`,
   **nessuna** riga di storico, nessuna aggiunta alla sessione. Con un positivo affiancato
   (stesso setup, passphrase valida → arriva allo storico: lezione 2026-10-29).
3. **Accumulo**: due messaggi di fila → due coppie in sessione, una sola riga di storico
   (aggiornata, non duplicata).
4. **Contenuto dello storico**: `chat_ai_id` è `null` per `send_message`, l'id per
   `send_message_agent`; `messages` contiene il messaggio inviato.
5. **Test del servizio** (`tests/Feature/ChatAIClientTest.php` o Unit, senza rete):
   `interpret()` con risposta JSON con `text` → `message = text`, `raw` invariato; con solo
   `message`; **senza né `text` né `message`** → `message` decodificato e `raw =
   json_encode(["message" => decodificato])` (comportamento D1); non JSON; `false` → errore di
   rete. `send()` con URL irraggiungibile → stesso risultato del punto 1. Il payload non è
   verificabile senza server: leggerlo nel diff.

I test non vengono lanciati dall'architect: li lancia la persona con `/refactor-verify`.
Regressione da rilanciare: `ChatAISendMessageAgentTest`, `ChatAIDeletedConfTest`,
`ChatAIAuthorizationTest`.

## Vincoli

- Nessun metodo pubblico nuovo nel controller; i due esistenti mantengono nome e firma
  (sono rotte). Nessuna rotta, vista o JS toccati.
- `$userId`, URL, header, opzioni cURL e struttura del payload identici a oggi (inclusi i
  doppi apici in `sessionId`). Niente `curl_close`, niente timeout (D2).
- Testi UI: se D3 è sì, chiave nuova in `resources/lang/en/crudbooster.php` **e**
  `it/crudbooster.php`; altrimenti nessun testo nuovo.
- Niente `app()`/`resolve()` con classi nuove nel perimetro Larastan; `new ChatAIClient()`.
- Nessun commit/push, nessuna suite in autonomia, nessun accesso a server remoti.

## Impatto sui clienti

Nessuno sui controller dei clienti (controller di sistema, nessuna proprietà né hook
cambia). ChatAI non è attivo in produzione (doc 300). Effetti visibili solo dalle decisioni
D1 (risposta anomala dell'agente su `send_message()`) e D3 (testo di errore per chi usa
l'interfaccia in inglese).

## Rischio

Medio-basso: la chiamata esterna non è testabile a pieno da un test HTTP. Mitigazioni: il
servizio mantiene opzioni e payload identici, `interpret()` è testato a parte, i punti 1-4
fissano sessione e storico sul codice di oggi. Attenzione particolare a: ordine delle
operazioni, doppi apici di `sessionId`, nessuna differenza di chiave di sessione.

## Rollback

Ripristinare le due funzioni dal diff e cancellare `app/Services/ChatAI/` e i test nuovi (e,
se D3, le due chiavi i18n). Nessuna migrazione.

## Dopo il batch

Reviewer (verifica i18n se D3) → correzioni → `ch-doc-writer` (doc 331) → post-mortem in
`docs/architettura/lezioni.md`; aggiornare `pattern-nel-repo.md` (servizio di
integrazione con una parte di lettura della risposta testabile senza rete) e `mappa.md`
(`app/Services/`). Segnalare alla persona, come follow-up non fatto: timeout (D2) e
`getConf()`, che risulta senza chiamanti nel codice PHP indicizzato (i Blade non lo sono,
quindi da verificare prima di toglierlo).
