# 277 - API v2: token mancante/errato → JSON 401 invece di 404 HTML

- **Data**: 2026-10-08
- **Stato**: Completato
- **Area**: API Generator / Auth
- **File/aree di codice coinvolte**:
  - `app/Exceptions/Handler.php` (`unauthenticated()`)

## Contesto

Chiamando un endpoint `api2/*` (middleware `auth:sanctum`) senza token, o con
token sbagliato, la risposta era un 404 con pagina HTML invece di un JSON.

## Situazione prima

`Handler::unauthenticated()` rispondeva in JSON 401 solo se la richiesta
dichiarava `Accept: application/json` (`expectsJson()`). Senza quell'header
(curl semplice, Postman senza header, browser) faceva
`redirect()->guest('login')`: redirect a `/login`, che in questa app non
esiste (il login e' sotto `ADMIN_PATH`) → 404 HTML.

## Situazione dopo

Per le richieste a `api2/*` la risposta e' sempre `401 {"error":"Unauthenticated."}`,
indipendentemente dall'header `Accept`. Il comportamento per le rotte web
resta invariato (redirect al login).

## Motivazione

Un client API deve ricevere un errore leggibile e uno status coerente (401),
non una pagina HTML con 404 fuorviante. Cambio minimo, limitato al prefisso
`api2/`.

## Test

- `php -l` sul file: OK.
- Non eseguita la suite. Verifica manuale suggerita: `curl -i
  <host>/api2/<permalink>` senza token e con token errato, senza header
  `Accept` → attesi 401 + JSON.

## Rischi e note

Cambia il comportamento visibile (404 HTML → 401 JSON) solo per `api2/*`,
rotte nate con Sanctum e destinate a client programmatici. Le rotte `api/*`
(v1, `CBAuthAPI`) non sono toccate. Il ramo `NotFoundHttpException` in
`Handler::render()` non e' importato (`use` mancante) e quindi non scatta mai:
non toccato qui.

## Rollback

Ripristinare la condizione `if ($request->expectsJson())` in
`Handler::unauthenticated()`.
