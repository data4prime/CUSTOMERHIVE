# 164 - Integrazione Qlik: hardening e pulizia

- **Data**: 2026-09-30
- **Stato**: Completato (parziale: vedi "Rischi e note")
- **Area**: Sicurezza + Bug fix + Pulizia (Qlik)
- **File/aree di codice coinvolte**:
  - `app/Helpers/QlikHelper.php`
  - `app/QlikItem.php`
  - `app/Http/Controllers/System/QlikItemsController.php`
  - `app/Http/Controllers/System/AdminQlikItemsController.php`
  - `app/Http/Controllers/System/AdminCmsUsersController.php`
  - `routes/web.php`
  - `public/js/qliksaas_login.js`, `qlik_op_jwt_login.js`,
    `qlik_login_widget*.js` (eliminati 3 file morti)
  - `resources/views/qlik_items/*`, `mashup.blade.php`,
    `mashup_objects.blade.php`, `statistic_builder/components/qlikwidget.blade.php`
  - `resources/lang/{en,it}/crudbooster.php`

## Contesto

Analisi dell'integrazione Qlik (backend, controller/permessi, frontend) fatta
il 2026-09-30 con tre passate in parallelo. Ne è uscito un elenco di
problemi; questo intervento applica tutti quelli *behavior-preserving*.
Restano fuori (decisione da prendere) il filtro per tenant su
`QlikApp`/`QlikConf` e il possibile bug `menu_groups` (cambiano il
comportamento visibile).

## Situazione prima

- `AdminCmsUsersController::create_qlik_user` (+ route `admin/qlik/user/creare/{id}`)
  chiamava `QlikHelper::createUser($id)` con un solo argomento (ne servono
  due) e usava un valore di ritorno inesistente: sempre errore 500, senza
  controllo di ruolo.
- `QlikHelper::getJWTTokenOP2`: senza chiamanti, claim fissi con `exp` nel 2032.
- `QlikItem::enablePublicAccess`: token pubblico `md5(salt.url.title)`,
  deterministico e uguale dopo disable/enable (revoca inefficace).
- `createUser`: nessun timeout, cookie jar condiviso in
  `app/Helpers/cookie.txt`, nessun controllo delle risposte, handle curl non
  chiuso sui rami d'errore.
- `randString` con `mt_rand`; `env('APP_URL')` in `buildPublicUrl`;
  `->first()->campo` senza null-guard in `getTypeConf`/`confIsSAAS`/
  `getConfFromItem`; `is_int` dopo `find` in `can_see_item`.
- `QlikItemsController::show`: item non trovato → HTTP 200 con testo; conf
  cancellata o `auth != JWT` → errore PHP (variabili non definite).
- `add_tenant`/`add_authorization`: `$_POST` grezzo, nessuna validazione di
  item/tenant/gruppo, `redirect($_POST['return_url'])` (open redirect).
  `hook_before_delete` cancellava solo `ItemsAllowed`.
- Viste: valori JS inline in apici con `{{ }}` / `@php echo` (non sicuri per
  `'`, `\`, `</script>`), `frame_width`/`frame_height` stampati in un
  `<style>` senza validazione, login JS senza gestione errori né timeout,
  xrfkey fisso, `console.log(webIntegrationId)` attivo, bug
  `qlik_login_widget_objopticket.js` (`value` non definito → ReferenceError),
  3 file JS morti, stringhe UI hardcoded.

## Situazione dopo

- Rimossi `create_qlik_user` (metodo + route) e `getJWTTokenOP2`.
- Token pubblico casuale (`bin2hex(random_bytes(20))`); i token già emessi
  restano validi finché l'item non viene disabilitato.
- `createUser`: timeout (connect 5s / totale 15s), cookie engine solo in
  memoria (`COOKIEFILE ''`), verifica TLS esplicita, redirect limitati a
  http/https e max 5, controllo errori curl / risposta / `subject`, log
  warning senza token, handle sempre chiuso; restituisce `null` in caso di
  fallimento (come prima nel caso "non OK").
- `randString` con `random_int`; `config('app.url')`; helper con
  `->value()` / null-guard; `is_int` prima di `find`; nuovo helper
  `QlikHelper::safeCssSize()`.
- `QlikItemsController::show`: `abort(404)` per item/conf mancanti o auth non
  gestita; codice commentato rimosso.
- `add_tenant`/`add_authorization`: input via `request()->input`, `is_int` +
  esistenza di item/tenant/gruppo, `return_url`/`ref_mainpath` accettati solo
  se relativi o dello stesso host (`safeRedirectTarget`).
  `hook_before_delete` cancella anche `TenantsAllowed`.
- Viste: `json_encode((string) …)` per tutti i valori JS inline; `safeCssSize`
  per larghezza/altezza; rimosso `!important;` orfano in
  `fullscreen_view_saas`; `console.log` rimosso.
- Login JS (`qliksaas_login.js`, `qlik_op_jwt_login.js`): `try/catch`,
  `AbortController` con timeout 15s, controllo `response.ok`; se il login
  fallisce compare un messaggio tradotto (`role="alert"`) ma l'iframe viene
  comunque caricato (stesso comportamento di prima: sarà Qlik a chiedere le
  credenziali). xrfkey on-premise casuale a ogni richiesta. Rimosse funzioni
  inutilizzate (`getQCSHeaders`, `checkLoggedIn`, `getJWTToken` placeholder).
- Fix `function (key, value)` in `qlik_login_widget_objopticket.js`.
- Eliminati `qlik_login_widget copy.js`, `qlik_login_widget_objop2.js`,
  `users_qlik_fields.js` (nessun riferimento nel repo).
- Traduzioni en+it (`crudbooster.qlik_*`): messaggio di login fallito, testo
  "caricamento oggetto", "Selezioni correnti", errore JWT, etichette del
  widget Qlik, "Attenzione!" in `no_credentials`; per il JS le stringhe
  arrivano da Blade tramite `json_encode(trans(...))`.

## Motivazione

Sicurezza (token pubblico prevedibile, open redirect, endpoint rotto e non
protetto, XSS/CSS injection, cookie condiviso tra utenti), robustezza
(null-guard, 404, timeout, errori visibili) e manutenibilità (codice morto).

## Test

- `php -l` su tutti i file PHP toccati (via Docker); compilazione Blade di
  tutte le viste modificate + lint del PHP compilato: OK.
- Verificato in Docker: `safeCssSize('100%')` → `100%`, valore malevolo →
  default; `randString(8)` lunghezza 8; chiave di traduzione it risolta.
- **Non** eseguita la suite di test e **non** provato il flusso end-to-end
  contro un Qlik reale (SaaS/on-premise): da fare manualmente (login item
  SaaS, on-premise, mashup, creazione utente Qlik da form utenti).

## Rischi e note

- On-premise: il login ora controlla `response.ok` su `qrs/about`; se il
  proxy risponde non-2xx pur autenticando, comparirà il messaggio di errore
  (non bloccante). Da verificare su un'istanza on-premise.
- `hook_before_delete` ora cancella anche i tenant abilitati; con soft delete
  un ripristino perde anche quelli (come già i gruppi). Le voci di menu
  `qlik_items/content/{id}` orfane non sono toccate.
- La modalità `auth = Ticket` (`mashup_objects.blade.php`,
  `qlik_login_widget_objopticket.js`) chiama `QlikHelper::getTicketFromConf`
  che **non esiste più**: ramo già non funzionante prima e ancora così.
  Da decidere se rimuoverlo del tutto.
- Non fatto: traduzione delle etichette del `cbInit()` di
  `QlikAppController`/`QlikConfController`/`AdminQlikItemsController`/
  `AdminCmsUsersController` (il nome campo CRUDBooster è spesso derivato
  dalla label, va fatto con test dedicati); i `console.error` in inglese/
  italiano dei JS sono log per sviluppatori, non UI.
- Non fatto (decisione): filtro tenant su `QlikApp`/`QlikConf`, campo
  `menu_groups` dei form tenant admin, `DB::select` con SQL da configurazione
  in `qlikwidget.blade.php` (`showFunction`), `remove_*` via GET con CSRF
  globalmente disabilitato.

## Rollback

Ripristinare i file elencati dal commit precedente (git); i file JS
eliminati tornano con `git checkout -- public/js/…`. Nessuna migrazione.
