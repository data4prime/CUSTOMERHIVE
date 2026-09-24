# 076 - Guard di licenza e login a livello di modulo per Qlik/ChatAI

- **Data**: 2026-09-24
- **Stato**: Completato
- **Area**: Sicurezza + Licensing
- **File/aree di codice coinvolte**:
  - `app/Http/Middleware/EnforceModuleLicense.php` (nuovo)
  - `routes/web.php`
  - `routes/crudbooster.php`
  - `resources/lang/{en,it}/crudbooster.php`

## Contesto

Lavoro rinviato il 2026-08-27: i pulsanti delle funzioni Qlik/ChatAI erano
già condizionati a `LicenseHelper::isActiveQlik()`/`isActiveChatAI()`, e i
3 endpoint Qlik di `AdminGroupsController` (`items`/`add_item`/
`remove_item`) già protetti, ma restavano raggiungibili via URL diretto gli
endpoint di `AdminQlikItemsController` e `AdminChatAIController`. Deciso
allora che serviva un guard a livello di modulo, non un controllo ripetuto
metodo per metodo.

Riprendendo l'analisi è emerso un problema più serio del gap di licenza:
un buco di **autenticazione** sulle stesse route.

## Situazione prima

Verificato dal vivo in locale (`route:list` + curl):

1. **Route custom di `routes/web.php` senza `CBBackend`.** I blocchi Qlik
   (`admin/qlik/user/creare/{id}`, `admin/qlik_items/{content,access,
   tenant,...}`, `admin/qlik_confs/QlikServerSense{Hub,QMC}/{id}`) e ChatAI
   (`admin/chat_ai/...`) hanno solo il middleware `web`: il controllo di
   login `CBBackend` è applicato solo alle route CRUD auto-generate da
   `routes/crudbooster.php`.
   - Le GET reggono solo grazie ai controlli interni ai metodi
     (`isSuperadmin()`, `can_see_item()`): da non loggato fanno redirect.
   - **`POST admin/chat_ai/send_message` e `send_message_agent` non hanno
     alcun controllo**: da non loggato la richiesta arriva fino a
     `AdminChatAIController.php:644`/`:792`, subito prima della chiamata
     cURL al servizio AI configurato (in locale si ferma con un 500 solo
     perché `chatai_confs` è vuota). Su un'installazione con ChatAI
     configurato, un anonimo potrebbe usare l'agente AI col token del
     server e scrivere nella cronologia chat.
2. **Licenza non applicata alle pagine.** Senza il modulo in licenza
   restano raggiungibili via URL: tutto il CRUD di `qlik_items` e
   `qlik_confs` (`QlikConfController`, non citato nel backlog originale),
   gli endpoint custom Qlik/ChatAI sopra, `create_qlik_user`. La licenza
   nasconde solo pulsanti/voci di menu.

Fuori scope, pubbliche per design e già con un controllo di licenza nel
metodo: `qi/{proxy_token}`, `/mashup/...`, `/mashup-objects/...`.

## Situazione dopo

- **Nuovo middleware `App\Http\Middleware\EnforceModuleLicense`**: mappa
  `prefisso di path (sotto ADMIN_PATH) → controllo di licenza`:
  `qlik_items`, `qlik_confs`, `qlik/user` → `isActiveQlik()`; `chat_ai` →
  `isActiveChatAI()`. Confronto a segmento intero (`qlik_items` non copre
  `qlik_items_x`); per ogni altro path costa solo un confronto di stringhe.
  Senza licenza: `CRUDBooster::redirect(adminPath, module_not_licensed)`
  (stesso pattern degli altri "accesso negato", JSON per richieste AJAX).
- **Protezione anti-loop**: se la dashboard del ruolo è un menu di tipo
  Qlik/Agent AI che punta a un modulo non in licenza, `CBBackend`
  rimanderebbe `/admin` proprio lì, e il redirect alla dashboard creerebbe
  un ciclo infinito. Il middleware flasha un flag in sessione prima del
  redirect; il flag sopravvive al giro `/admin` (il `reflash()` già
  presente in `CBBackend`) e al secondo passaggio si risponde con un 403
  (stesso messaggio) invece di rimandare di nuovo.
- **`routes/crudbooster.php`**: middleware aggiunto ai due gruppi `CBBackend`
  (moduli generati e moduli di sistema) → copre tutto il CRUD
  auto-generato di `qlik_items`/`qlik_confs`/`chat_ai`, inclusi eventuali
  moduli custom dei clienti con quei path.
- **`routes/web.php`**: le route custom Qlik (`qlik/user/creare`,
  `qlik_items/...`, `qlik_confs/QlikServerSense{Hub,QMC}`) e ChatAI
  (`chat_ai/...`, incluse `send_message`/`send_message_agent`) sono ora in
  un unico gruppo con `CBBackend` + `EnforceModuleLicense`. Stessi path,
  stessi controller/metodi, stessi nomi di route; cambia solo l'ordine di
  registrazione dei due blocchi Qlik Server/ChatAI (spostati in alto nel
  gruppo) — verificato con `route:list` nessuna collisione.
- Restano fuori di proposito, pubbliche: `qi/{proxy_token}`, `/mashup/...`,
  `/mashup-objects/...`.
- Nuova chiave di traduzione `crudbooster.module_not_licensed` (en/it).
- I controlli già presenti dentro i metodi (`isSuperadmin()`,
  `can_see_item()`, `isActiveQlik()` in `AdminGroupsController`) non sono
  stati toccati: restano come seconda difesa.

**Comportamento visibile che cambia**:
- Utente non loggato su una route custom Qlik/ChatAI → redirect al login
  (prima: redirect a `/admin` o `/`, oppure, per `send_message*`,
  esecuzione vera della richiesta).
- Utente loggato su una pagina Qlik/ChatAI senza il modulo in licenza →
  redirect alla dashboard con "Questa funzionalità non è inclusa nella tua
  licenza" (prima: pagina accessibile).
- Chi ha il modulo in licenza: nessuna differenza.

## Motivazione

Un solo punto di controllo per modulo invece di un `if` ripetuto in ogni
metodo (facile da dimenticare sul prossimo endpoint aggiunto), che copre
anche le route CRUD auto-generate e i moduli custom dei clienti. Aggiungere
`CBBackend` alle route custom chiude un buco di autenticazione reale
(`send_message*` usabili da anonimi) che era più grave del gap di licenza
per cui si era partiti.

Alternative scartate: controllo in `cbInit()`/`cbLoader()` del controller
(non coprirebbe le route custom che non passano da `cbLoader()`, es.
`content_view`, `send_message`); un check per metodo (esattamente il
pattern che si voleva evitare).

## Test

Manuali via curl sull'ambiente Docker locale (licenza locale con solo il
modulo `n8n`, cioè né Qlik né ChatAI):

- **Prima del fix**, da non loggato: `POST admin/chat_ai/send_message` e
  `send_message_agent` → arrivano fino a `AdminChatAIController.php:644`/
  `:792` (subito prima della cURL al servizio AI).
- **Dopo**, da non loggato: tutte le route custom Qlik/ChatAI (GET e POST,
  9 URL) → 302 a `/admin/login`.
- Loggato, **senza** licenza Qlik/ChatAI, seguendo i redirect come un
  browser: `qlik_items`, `qlik_items/content/1`, `qlik_confs`,
  `chat_ai/access/1`, `qlik/user/creare/1`, `QlikServerSenseHub/1` →
  dashboard con il messaggio di licenza; `users`/`tenants` → 200
  invariati; `send_message` via AJAX → risposta JSON con il messaggio.
- **Loop**: menu dashboard (id 1) temporaneamente cambiato in tipo `Qlik` →
  `qlik_items/content/1`: `/admin` si ferma con un 403 dopo 3 redirect
  invece di ciclare; le altre pagine restano accessibili. Riga di menu
  ripristinata subito dopo (`Statistic` / `dashboard`).
- **Con** licenza Qlik+ChatAI (moduli aggiunti temporaneamente a
  `storage/app/license.json`, poi ripristinato dal backup): `qlik_items`,
  `qlik_confs`, `qlik_items/access/1`, `qlik_items/tenant/1` → 200, nessun
  messaggio di licenza. `chat_ai/access/1`/`tenant/1` arrivano al
  controller ma danno 500 per un problema preesistente dell'ambiente
  locale, non di questo intervento: il modulo ChatAI non è registrato in
  `cms_moduls` in locale, quindi la route `AdminChatAIControllerGetIndex`
  usata dalla vista non esiste.
- Route pubbliche da non loggato: `qi/...`, `mashup/1`, `admin/login` →
  200 invariati.
- `php -l` su tutti i file toccati; `route:list -v` conferma i middleware
  sulle route.

Suite automatica non eseguita (solo su richiesta).

## Rischi e note

- **Da verificare prima del deploy su ogni cliente**: chi ha menu o
  dashboard che puntano a item Qlik/ChatAI con una licenza che **non**
  include quel modulo vedrà quelle pagine bloccate (prima funzionavano).
  È il comportamento corretto rispetto alla licenza, ma è un cambiamento
  visibile: controllare i moduli in licenza dei clienti su dev/staging.
- Il flag anti-loop vive una sola richiesta: in casi limite (due pagine
  non in licenza aperte in rapida successione senza caricare la dashboard
  in mezzo, es. un client che non segue i redirect) la seconda riceve un
  403 invece del redirect. Stesso messaggio, nessun impatto funzionale.
- Notato, non toccato (fuori scope): `GET /testapi` in `routes/web.php` è
  pubblico e fa `dd()` di un oggetto controller — codice di debug rimasto
  in produzione, da valutare a parte.
- Notato, non toccato: `send_message*` e gli altri POST restano senza CSRF
  (gap globale noto, vedi `docs/login-e-licensing.md`).

## Rollback

Rimuovere `\App\Http\Middleware\EnforceModuleLicense` dai due gruppi di
`routes/crudbooster.php`, riportare i blocchi Qlik/ChatAI di
`routes/web.php` fuori dal gruppo (versione precedente nel git history),
eliminare `app/Http/Middleware/EnforceModuleLicense.php`. La chiave di
traduzione può restare. Nessuna migration né dato coinvolto.
