# 327 - Primo Form Request del repo: StoreApiTokenRequest per ApiTokensController::postAddSave (B5)

- **Data**: 2026-10-30
- **Stato**: Completato (22 test di caratterizzazione verdi prima e dopo, suite completa non lanciata)
- **Area**: Architettura / Controller di sistema / Form Request
- **File/aree di codice coinvolte**:
  - `app/Http/Requests/ApiTokens/StoreApiTokenRequest.php` (nuovo; prima non esisteva `app/Http/Requests/`)
  - `app/Http/Controllers/System/ApiTokensController.php` (`postAddSave()`)
  - `tests/Feature/ApiTokensAddSaveTest.php` (nuovo, 22 test)

## Contesto

Batch B5 del backlog dei controller di sistema, pilota del pattern Form Request,
dopo i batch B1/B1b/B2 ([324](324-rotte-custom-sotto-login.md),
[325](325-uploads-rotta-non-serve-file-reali-500-diventa-404.md),
[326](326-open-redirect-add-groups-users-tenants-safe-redirect-target.md)).
Obiettivo: allineare il repo agli standard Laravel spostando permesso e
validazione fuori dal controller. `ApiTokensController::postAddSave()` e' un
candidato piccolo e isolato (4 regole, un solo permesso). Sul metodo non
c'era alcuna copertura di test: per la regola "codice senza copertura, prima
un test di caratterizzazione" i test sono stati scritti prima del cambio.

## Situazione prima

- Dopo `cbLoader()`, `postAddSave()` controllava in linea
  `!CRUDBooster::isCreate() && $this->global_privilege == false` (con
  `global_privilege` sempre `false` in `cbInit()`) e rispondeva con un redirect a
  `mainpath()` + messaggio `denied_access`.
- Poi costruiva un `Validator::make(Request::input...)` con 4 regole:
  `tokenable_id` (`required|integer|exists:cms_users,id`), `name`
  (`required|string|max:255`), `expiry_choice` (`required|in:30,90,365,never,custom`),
  `expiry_custom_date` (`required_if:expiry_choice,custom|nullable|date|after:today`);
  se falliva: `redirect()->back()->withErrors($validator)->withInput()`.
- 0 Form Request nel repo (vedi `docs/architettura/mappa.md`).

## Situazione dopo

- Nuovo `StoreApiTokenRequest`:
  - `rules()`: le stesse 4 regole di prima, invariate.
  - `authorize()`: `(bool) CRUDBooster::isCreate()` (equivalente al vecchio
    controllo, dato che `global_privilege` e' sempre `false`).
  - `failedAuthorization()`: lancia `HttpResponseException` con
    `CRUDBooster::redirect(CRUDBooster::mainpath(), trans('crudbooster.denied_access'))`,
    cosi' il rifiuto resta il redirect di prima e non diventa un 403.
  - metodo statico `validateCurrent()`: `static::createFrom(request())`,
    `setContainer(app())->setRedirector(redirect())`, `validateResolved()`;
    replica quello che fa il container quando risolve un FormRequest.
- `postAddSave()`: dopo `cbLoader()` ora
  `$data = StoreApiTokenRequest::validateCurrent()->validated();` e `tokenable_id`,
  `expiry_choice`, `expiry_custom_date`, `name` si leggono da `$data`. Tolti il
  controllo `isCreate` in linea, il `Validator::make` e gli `use` `Request` e
  `Validator`. Il resto (`User::find` con errore su `tokenable_id`, `match` sulla
  scadenza, `createToken`, `insertLog`, vista `api_tokens.reveal`) e la firma sono
  invariati.
- **Cambio di comportamento visibile**: nessuno per il flusso normale (form HTML
  POST: stesso redirect indietro con errori e input, stesso messaggio di rifiuto).
  Unica differenza tecnica: una richiesta JSON/ajax (`expectsJson`) con dati non
  validi ora riceverebbe un 422 JSON dal Form Request invece di un redirect; il
  rifiuto del permesso resta un redirect anche con JSON. Nessun chiamante ajax
  trovato (l'unico riferimento a `api_tokens/add-save` e' il test e il doc 086):
  non applicabile, non fissato da un test.

## Motivazione

Primo passo concreto verso Form Request nel repo: regole e permesso in una
classe dedicata, controller piu' snello, regole testabili e riusabili.

**Perche' il Form Request non e' nella firma del metodo.**
`CBController::postAddSave()` non ha parametri. Un override con parametro
obbligatorio e' un errore fatale PHP (firma incompatibile); con parametro
opzionale (`= null`) Laravel NON inietta la classe ma passa il default (`null`).
Il Form Request va quindi costruito dentro il metodo, da qui `validateCurrent()`.

**Seconda lezione: `app(Classe::class)` e Larastan.** La prima versione usava
`app(StoreApiTokenRequest::class)`. Larastan
(`Larastan\Larastan\ReturnTypes\AppMakeHelper::resolveTypeFromCall`, usato dalle
estensioni di `app()`/`make()`) RISOLVE DAVVERO dal container le classi passate
come stringa costante durante l'analisi: costruiva il FormRequest ed eseguiva
`authorize()` senza sessione, producendo un warning PHP non ignorabile
(`foreach() argument must be of type array|object, null given`,
`Helpers/CRUDBooster.php:507`, `isCreate()`). Si vedeva SOLO con la cache dei
risultati svuotata (`phpstan clear-result-cache`): il secondo giro con cache dava
"No errors". Verificato per esclusione: i singoli file erano puliti, escludendo
`ApiTokensController` dall'analisi completa era pulito, con il controller incluso
e `app(Classe::class)` no. Soluzione: `validateCurrent()` senza
`app(Classe::class)`.
**Regola per i prossimi batch**: non usare `app()`/`resolve()`/`make()` con una
classe FormRequest dentro il perimetro Larastan, e dopo ogni modifica che
introduce chiamate al container lanciare phpstan anche da cache svuotata.

## Test

Verificato davvero:
- `php -l` ok.
- Pint PASS su `StoreApiTokenRequest.php` (sul controller resta solo la riga vuota
  storica dopo il primo `use`, non toccata).
- Larastan "No errors" da cache svuotata.
- 22 test di caratterizzazione (109 asserzioni) in `tests/Feature/ApiTokensAddSaveTest.php`,
  scritti PRIMA del cambio e verdi sul codice vecchio, poi identici e verdi dopo.
  Coprono: permesso create negato (redirect a `admin/api_tokens` + `denied_access`,
  nessun token); permesso negato con dati non validi (ordine permesso -> validazione,
  nessun errore di validazione); 11 payload non validi (utente inesistente/non
  numerico/mancante, nome vuoto/>255, scadenza fuori elenco/mancante, data custom
  mancante/non valida/nel passato/oggi: redirect al Referer + errori sul campo
  giusto, nessun token); input rimandato indietro; scadenze 30/90/365 giorni, mai,
  personalizzata (fine giornata 23:59:59); pagina di rivelazione col token in
  chiaro e solo hash a DB; log con `api_debug_mode` acceso e spento.
- Correzioni ai test in corso d'opera: `cbView()` fa `echo` invece di restituire la
  vista (HTML catturato con `ob_start`/`ob_get_clean`); `insertLog()` scrive in
  `cms_logs` solo con `api_debug_mode` attivo e legge `REMOTE_ADDR`/`HTTP_USER_AGENT`
  da `$_SERVER` (impostati e ripristinati in `finally`); la vista `reveal` mostra
  solo il token in chiaro, non il nome; il token Sanctum e' `id|` + 40 caratteri +
  checksum crc32 (la regex accetta 40+), a DB resta lo sha256 della parte dopo `|`.
- Review `ch-reviewer`: nessun rilievo bloccante ne' strutturale. Verificata
  l'equivalenza: ordine permesso -> validazione (`validateResolved()` esegue
  `passesAuthorization` prima di `getValidatorInstance`); `createFrom(request())`
  copia input/route/user resolver/json/sessione; `getRedirectUrl` usa
  `url()->previous()` come `redirect()->back()`; error bag `default`; `validated()`
  contiene solo i 4 campi (niente mass-assignment).

NON verificato: suite completa (non lanciata), comportamento con richieste JSON,
prova manuale da browser.

## Rischi e note

- Dubbio del reviewer, riportato: 422 JSON per richieste `expectsJson` con dati non
  validi (vedi "Situazione dopo"); nessun chiamante ajax trovato.
- `tokenable_id` puo' essere QUALUNQUE utente: invariato e voluto (vedi doc 088).
- `validateCurrent()` e' un pattern non standard: dai prossimi Form Request andra'
  probabilmente estratto in un trait comune (regola dei tre: finche' ce n'e' uno
  solo resta sulla classe). Il reviewer suggerisce una riga in
  `pattern-nel-repo.md` e un ADR breve, da fare a parte (non in questo intervento).
- `getEdit`/`postEditSave` restano negati e non toccati; `getAdd` non toccato.
- Regola Larastan sulle chiamate al container: vedi "Motivazione".

## Rollback

Ripristinare in `postAddSave()` il controllo `isCreate` in linea e il
`Validator::make` con `redirect()->back()->withErrors($validator)->withInput()`,
riaggiungere gli `use` `Request` e `Validator`, eliminare
`app/Http/Requests/ApiTokens/StoreApiTokenRequest.php` (e la cartella se vuota) e
`tests/Feature/ApiTokensAddSaveTest.php`. Nessuna migrazione coinvolta.
