# Piano 332 — `AdminChatAIController`: pulizia, i18n di `access`/`tenant`, `content_view` locale, Form Request per `send_message*`

Stato: **approvato dalla persona il 2026-10-30, con D1 e D2 come raccomandate**
(D1 solo pulizia locale di `content_view`; D2 JSON `chatai_invalid_request` con 422).
**Batch 1 chiuso e verificato (doc 332, 70 test verdi); batch 2 avviato il 2026-10-30.**
Branch: `refactor/models`.
Autore: `/architect`, 2026-10-30. Destinatario: `ch-senior-dev` (non vede la
conversazione: questo file è autosufficiente). Origine: analisi di
`AdminChatAIController` (sessione `/architect` del 2026-10-30), dopo i doc 329-331.
Il piano ha **due batch separati da una pausa**: il batch 2 parte solo dopo che il
batch 1 è stato verificato (A9).

## Obiettivo

Finire di portare il controller verso gli standard: eliminare duplicazioni e testi
non traducibili nelle pagine `access`/`tenant`, togliere codice morto, ripulire
`content_view` e dare a `send_message*` una validazione dichiarativa (Form Request).
Il batch 1 è **behavior-preserving tranne i testi tradotti**; il batch 2 ha **cambi
visibili dichiarati**.

## Stato attuale (fatti verificati 2026-10-30, lettura del codice, `AdminChatAIController.php`, 772 righe)

- **`access()` (r.397-430) e `tenant()` (r.432-467)**: gemelli. Stesso corpo (controllo
  `isSuperadmin`; `ChatAIConf::find`; query di elenco con join su `chatai_*_allowed`;
  alert in HTML con `$alert_id == '1'`; `forms` con campo datamodal e un campo
  `description`; `action`, `return_url`, `command`, `button_addmore`) e cambiano solo
  tabella (`groups`/`tenants`), vista (`chat_ai.access`/`chat_ai.tenant`), tipo di campo
  (`item_access_datamodal`/`item_tenant_datamodal`), percorsi (`/auth`+`access/` vs
  `/add_tenant`+`tenant/`) e la chiave `groups`/`tenants`. **Testi scritti a mano in
  inglese**: `'Authorize Group'`, `'Authorize Tenant'`, l'alert
  `<h4>…Warning!</h4>Select an element to add...`, le etichette `'Name'`/`'Description'`,
  i placeholder `'Group description'`/`'Tenant description'`. Violano la regola i18n del repo.
- **`resources/views/chat_ai/access.blade.php`**: non usa `$chat_ai`; usa `$groups`,
  `$action`, `$return_url`, `$item_id`, `$page_title`, ecc. Quindi con un id di conf
  inesistente la pagina **si renderizza con elenco vuoto** (nessun errore): la guardia
  "conf inesistente" ipotizzata prima **non serve e non va aggiunta**. Nella vista restano
  tooltip in inglese (`title='Remove'`, `title='Return'`, `title='Main Module'`).
  `tenant.blade.php` ha la stessa dimensione (4791 byte): verificare che sia la gemella.
- **`hook_before_add` (r.297-308) e `hook_before_edit` (r.334-350)**: identica
  normalizzazione `if ($postdata['primary'] == "primary") … = 1; else … = 0;`.
  Con la chiave `primary` assente non c'è errore (il motore la valorizza: doc 330, test verdi).
- **`use GroupHelper;`** (r.11): inutilizzato (già prima del 330). Da verificare ogni `use`
  del file con una ricerca letterale: `tokensave_unused_imports` non lo ha segnalato.
- **`content_view()` (r.657-701)**: controllo `can_see_item` (redirect `denied_access`);
  `ChatAIConf::find` (`missing_item`); risoluzione del menu con **`$_GET['m']`** diretto
  (`isset($_GET['m']) ? Menu::find($_GET['m']) : Menu::activeDashboard()->first()`);
  impostazione di `frame_width/height` (100% se manca il menu) e `target_layout` sul modello;
  dati di pagina; scelta tra `chat_ai.fullscreen_view` e `chat_ai.view` con
  `isset($menu->target_layout) && == 1`. **Confronto con `AdminQlikItemsController::content_view`
  (r.533-648)**: stessa idea ma **menu risolto diversamente**
  (`Menu::qlikItemContent($id)->first()`, il ramo `$_GET['m']` è commentato), rifiuto
  con `redirectBack` invece di `redirect`, vista scelta con logica JWT/SaaS, `htmlspecialchars_decode`
  sull'url. In comune restano circa 12 righe (frame e dati di pagina).
- **`send_message()` (r.617-640) e `send_message_agent()` (r.703-738)**: leggono `message`
  (e `agent_id`) con `Request::all()['…']`: chiave mancante = errore (500), tipo e
  lunghezza non controllati. Le rotte sono **esplicite** in `routes/web.php:75-76`, dentro il
  gruppo `CBBackend` + `EnforceModuleLicense`: un Form Request nella firma viene risolto
  dal container (il divieto A14 vale solo per gli override di `CBController`, che qui non
  sono: il padre non ha questi metodi). Il controllo `can_see_item($agent_id)` è nel
  controller (doc 329) e risponde con JSON `{"message": denied_access}`.
- Test esistenti utili: `ChatAISendMessageAgentTest` (8, accesso), `ChatAIDeletedConfTest`,
  `ChatAIRelayCharacterizationTest`, `ChatAIPrimaryAndRedirectsTest` (hook primary via
  HTTP), `ChatAIAuthorizationTest`. **Non coperti**: il contenuto delle pagine
  `access`/`tenant` (titoli, alert, elenco), `content_view`.

## Decisioni da confermare (della persona)

**D1 — `content_view`: pulizia locale o trait condiviso con Qlik?** Raccomandazione:
**solo pulizia locale in ChatAI** (non toccare `AdminQlikItemsController`, che
presumibilmente è in uso presso i clienti, per ~12 righe in comune e un menu risolto in modo
diverso). Se vuoi comunque il trait, serve un piano a parte che copra anche il lato Qlik.

**D2 — Risposta di `send_message*` ai dati non validi.** Raccomandazione: risposta JSON
`{"message": trans('crudbooster.chatai_invalid_request')}` con HTTP **422**, come le altre
risposte di errore di questi endpoint ma con stato esplicito; chiave nuova in en e it.
Oggi: chiave mancante = 500 (HTML), messaggio non stringa o vuoto = inoltrato all'agente.

## Perimetro

**Batch 1** (nessuna decisione necessaria oltre D1; behavior-preserving, salvo testi tradotti):
- `AdminChatAIController.php`: `use GroupHelper;` (e altri `use` verificati inutilizzati),
  `hook_before_add`/`hook_before_edit`, `access()`, `tenant()`, `content_view()`.
- `resources/views/chat_ai/access.blade.php` e `tenant.blade.php`: solo i tre tooltip in inglese.
- `resources/lang/en|it/crudbooster.php`: chiavi nuove.
- Test nuovi.

**Batch 2** (cambio visibile D2):
- `app/Http/Requests/ChatAI/SendChatMessageRequest.php` e `SendAgentMessageRequest.php` (nuovi).
- `AdminChatAIController.php`: solo le firme e le prime righe di `send_message()` e
  `send_message_agent()` (lettura di `message`/`agent_id` e, per l'agente, il controllo `can_see_item`).
- `resources/lang/en|it/crudbooster.php`: chiave `chatai_invalid_request`.
- Test nuovi.

Fuori (non toccare): `cbInit()`, `hook_after_*`, `hook_before_delete`, `getEdit`, `getDetail`,
`getConf()` (tutti rimandati: `getEdit`/`getDetail` con `findActiveRow` cambierebbe comportamento
per i soli URL diretti a una conf cancellata; `getConf` da verificare nei Blade), `relayToAgent()`,
`ChatAIClient`, `AdminQlikItemsController`, `AdminGroupsController`, le rotte, i modelli,
`ChatAIHelper`, `AdminChatAIController::add_*`/`remove_*`.

## Batch 1 — passi e pattern

**1a. Pulizia (`use` e hook).** Togliere `use GroupHelper;` e ogni altro `use` che la
ricerca letterale nel file mostra non usato (uno per uno: `Route`, `LicenseHelper`, ecc.;
non fidarsi solo di `tokensave_unused_imports`). `hook_before_add`/`hook_before_edit`: tenere
nome, firma e commenti-guida; spostare la normalizzazione in un metodo **privato**
(`normalizePrimary(array &$postdata): void`) con la stessa espressione
(`$postdata['primary'] == "primary" ? 1 : 0`, confronto lasco identico). Coperti da
`ChatAIPrimaryAndRedirectsTest` (add/edit con e senza checkbox).

**1b. `access()` / `tenant()`.** Un metodo **privato** di appoggio che costruisce i dati comuni
dato un piccolo array di differenze (vista, tabella pivot, tabella entità, tipo campo, percorsi,
chiavi); i due metodi pubblici mantengono nome, firma (`$item_id, $alert_id = null`) e il
controllo `isSuperadmin` con il redirect `denied_access` **per primi**, poi chiamano l'helper e
`$this->cbView(...)`. Tutte le query e i dati identici a oggi (stesso `select('groups.*')` /
`select('tenants.*')`, stesso `find`). **Testi**: ogni testo scritto a mano diventa `trans('crudbooster.…')`:
riusare le chiavi esistenti dove il testo coincide (`crudbooster.name`, `crudbooster.description`
sono già usate dalle viste; cercare nei file lingua una chiave esistente per "Warning!"/"Select an
element to add..." prima di crearne una); altrimenti chiavi nuove con prefisso `chatai_`
(titoli pagina, testo dell'alert, due placeholder) **in en e in it**, con l'inglese identico al
testo di oggi e l'italiano tradotto. L'alert resta HTML con la stessa struttura (`<h4>` con
l'icona `bi bi-exclamation-triangle-fill` + testo): passare le stringhe tradotte nei punti
giusti, non l'HTML dentro le lingue. Nelle due viste: tre tooltip `title=` in inglese → `trans`
(riusare chiavi esistenti, es. `button_back`; altrimenti nuove); rispettare le regole UI
(`.claude/rules/ui-standard.md`: nessun CDN, commenti Blade chiusi con `--}}`; dopo la modifica
`php artisan view:clear` e un render di prova, perché `php -l` non controlla Blade).

**1c. `content_view()` (D1 = solo locale).** `$_GET['m']` → lettura dalla query della richiesta con
la stessa semantica (`isset`: presente anche se vuoto → `Menu::find(<valore>)`, che con valore
vuoto dà `null` → frame 100%); l'impostazione di frame/`target_layout`/dati di pagina in un metodo
**privato** (`frameData(...)`); stesso ordine di controlli (`can_see_item`, `find`, menu, dimensioni),
stesse viste, stesse condizioni (`isset($menu->target_layout) && == 1`). Nessun cambio al lato Qlik.

### Test del batch 1 (rischio decrescente; verdi sul codice attuale, poi aggiornati dove cambiano i testi)

Setup come `ChatAIAuthorizationTest` (licenza simulata, `registerAdminModule('chat_ai',
'AdminChatAIController', 'chatai_confs')`); le pagine usano `cbView()` (echo): catturare con
`ob_start()` come negli altri test ChatAI.
1. **`access` e `tenant` come superadmin**: stato 200, il titolo, l'elenco dei gruppi/tenant
   autorizzati **di quella conf soltanto** (non quelli di un'altra), e con `…/alert/1` l'alert
   presente (senza `/alert/1` assente). Con un positivo affiancato.
2. **Non superadmin** (tenant admin) su `access` e `tenant`: redirect `denied_access`, nessuna pagina.
3. **Conf inesistente da superadmin**: la pagina si renderizza con elenco vuoto, senza errore
   (fissa il comportamento verificato; **nessuna guardia nuova**).
4. **Gruppo/tenant soft-deleted nell'elenco** (se le tabelle hanno `deleted_at`: leggere lo schema):
   fissare il comportamento attuale (compare o no) senza correggerlo; segnalare nel doc.
5. **Testi tradotti**: nel caso 1 il titolo/alert/placeholder corrispondono a `trans()` in **it** e
   in **en** (cambiare locale nel test); scrivere il test prima con il testo inglese di oggi,
   poi portarlo alle chiavi quando si traducono (dichiarandolo, come nel 331).
6. **`content_view`**: conf visibile → 200 e vista `chat_ai.view` (o `fullscreen_view` con un menu
   `target_layout = 1`); `?m=<id menu>` applica le dimensioni di quel menu; `?m=` vuoto e assente →
   100%; conf non visibile → redirect `denied_access`; conf inesistente → `missing_item`.
7. **Hook primary**: già coperti da `ChatAIPrimaryAndRedirectsTest` (devono restare verdi).

## Batch 2 — Form Request per `send_message*` (solo dopo il batch 1)

Due classi in `app/Http/Requests/ChatAI/` (cartella per area, come `ApiTokens/`, ADR A14):
- `SendChatMessageRequest`: `rules()` → `message => 'required|string'`.
- `SendAgentMessageRequest`: `message => 'required|string'`, `agent_id => 'required'` (senza
  `integer`: un `agent_id` non numerico deve continuare a dare `denied_access` da `can_see_item`);
  `authorize()` replica `ChatAIHelper::can_see_item($this->input('agent_id'))` e
  `failedAuthorization()` lancia `HttpResponseException` con **lo stesso JSON di oggi**
  (`{"message": trans('crudbooster.denied_access')}`, stato 200 come prima, nessun 403).
  L'autorizzazione precede le regole (comportamento standard di Laravel): un utente non
  autorizzato con dati non validi riceve `denied_access`, come prima riceveva `denied_access` o un 500.
- `failedValidation()` in entrambe: `HttpResponseException` con JSON `{"message":
  trans('crudbooster.chatai_invalid_request')}` e stato **422** (D2). Chiave nuova in en e it.
- Controller: `send_message(SendChatMessageRequest $request)` e `send_message_agent(SendAgentMessageRequest
  $request)`; il messaggio si legge da `$request->validated()['message']` (e `agent_id` da
  `$request->validated()['agent_id']`); si **toglie** dal controller solo il blocco `can_see_item`
  che passa nell'`authorize()`. Il resto (sessione, conf, `missing_item`, `relayToAgent`) non cambia.
  **Verificare con `php artisan route:list --path=chat_ai`** che le due rotte siano quelle esplicite
  di `web.php` e che la risoluzione dalla firma funzioni (è il motivo per cui qui non serve
  `validateCurrent()`; **non** usare `app(Classe::class)`).

### Effetti visibili (da dichiarare nel doc)
(a) `message` mancante, vuoto, non stringa → JSON `chatai_invalid_request` con 422 (prima 500 se
mancante, inoltrato all'agente se vuoto o di altro tipo); (b) `agent_id` mancante → idem 422 per un
superadmin (per un non superadmin resta `denied_access`). La chat non cambia per l'uso normale: il
JS non invia messaggi vuoti e invia sempre stringhe.

### Test del batch 2 (scritti prima; rossi attesi sul codice attuale: 1-3)
1. `message` mancante, vuoto, array → **422 JSON** con la chiave (per entrambi gli endpoint);
   nessuna riga di storico, nessuna chiave di sessione aggiunta. *Rosso prima.*
2. `agent_id` mancante da superadmin → 422. *Rosso prima.*
3. Utente non autorizzato **con** `message` mancante → `denied_access` (non 422). *Rosso prima
   (era 500).*
4. **Positivi affiancati**: dati validi → arrivano allo storico (come `ChatAISendMessageAgentTest`).
5. `ChatAISendMessageAgentTest`, `ChatAIDeletedConfTest`, `ChatAIRelayCharacterizationTest`
   restano **verdi senza modifiche** (stesso JSON, stesso 200 per `denied_access`).

## Vincoli
- Ogni metodo pubblico di una sottoclasse di `CBController` è una rotta: i metodi di appoggio sono
  **private**. Nomi e firme pubbliche invariati (tranne le due firme del batch 2, non override).
- Ogni testo UI nuovo in en **e** it con `trans()`; nessuna stringa letterale nel PHP o nel JS.
- Nessun `app()`/`resolve()` con classi FormRequest; nessun `validateCurrent()` (qui non serve).
- Nessun commit/push, nessuna suite in autonomia, nessun accesso a server remoti; `php -l` nel
  container su tutti i file nuovi/toccati e Pint `--test` solo sui nuovi.

## Impatto sui clienti
Nessuno: controller di sistema non ereditato dai clienti, nessuna proprietà né hook cambia firma;
ChatAI non è attivo in produzione (doc 300). Effetti visibili: pagine `access`/`tenant` in
italiano per gli utenti italiani (batch 1) e risposte di errore del batch 2 (D2).

## Rischio
Basso (batch 1: refactoring locale con test nuovi sulle pagine, oggi scoperte) e medio-basso
(batch 2: nuova validazione sugli endpoint della chat). Attenzione a: ordine dei controlli in
`access`/`tenant`; stesse query; chiavi i18n in entrambi i file; `route:list` per il batch 2.

## Rollback
Batch 1: ripristinare i metodi dal diff, togliere le chiavi lingua nuove e i test. Batch 2:
ripristinare le due firme e le prime righe, cancellare `app/Http/Requests/ChatAI/` e i test,
togliere `chatai_invalid_request`. Nessuna migrazione.

## Dopo ogni batch
Reviewer (verifica i18n en/it) → correzioni → `ch-doc-writer` → test (li lancia la persona) →
post-mortem in `docs/architettura/lezioni.md`. Dopo il batch 2: aggiornare
`pattern-nel-repo.md` (Form Request su metodi custom **non** override: si inietta dalla firma,
niente `validateCurrent()`) e `mappa.md` (`app/Http/Requests/`).

## Rimandato (non in questo piano)
`getEdit`/`getDetail` con `findActiveRow` (cambio per id inesistente/cancellato), `getConf()`
(verifica Blade), controllo `isSuperadmin` ripetuto 6 volte, `hook_query_index` con il TODO sul
permesso gruppi, trait `content_view` condiviso con Qlik (D1), alert "Select an element to add..."
identico anche in `AdminGroupsController::members()` e probabilmente in Qlik.
