# 278 - API v2: l'utente viene dal Bearer token, non serve l'header X-User

- **Data**: 2026-10-08
- **Stato**: Completato
- **Area**: API Generator / Auth
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/ApiController.php` (`login()`)

## Contesto

Con un Bearer token valido su `api2/*` la risposta era comunque
`{"api_status":0,"api_message":"User not found! Make sure you insert the X-User header ..."}`.
Seguito di 086 (api2 Sanctum) e 277.

## Situazione prima

`api2/*` e' protetta da `auth:sanctum`, ma poi esegue lo stesso
`execute_api()` della v1, che chiama `login()`: questo cercava l'utente solo
tramite l'header `X-user` (email). Senza l'header, errore, anche con token
valido.

## Situazione dopo

Su `api2/*`, se Sanctum ha identificato un utente, `login()` usa la sua email
(l'utente deve comunque risultare `Active` in `cms_users`); l'header `X-User`
non serve piu'. Su `api/*` (v1) nulla cambia.

## Motivazione

Il token identifica gia' l'utente: richiedere anche `X-User` e' ridondante e,
sulla v2, permetterebbe di dichiarare un'identita' diversa da quella del token.

## Test

`php -l` OK. Suite non eseguita. Verifica manuale: chiamata `api2/<permalink>`
con solo `Authorization: Bearer <token>` → risposta normale dell'endpoint.

## Rischi e note

Cambia comportamento visibile solo su `api2/*`: l'header `X-User`, se inviato,
viene ignorato. Eventuale documentazione/Postman che lo cita per api2 va
allineata.

## Rollback

Rimuovere il blocco `if (Request::is('api2/*') ...)` in `login()`.
