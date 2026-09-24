# 090 - Documentazione API Generator: aggiunto il binario api2

- **Data**: 2026-09-24
- **Stato**: Completato
- **Area**: Documentazione / UI
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/api_documentation.blade.php` (admin, `/admin/api_generator`)
  - `resources/views/crudbooster/api_documentation_public.blade.php` (pubblica, `/api-documentation`)
  - `resources/lang/{en,it}/crudbooster.php`

## Contesto

Segnalato dall'utente guardando `/admin/api_generator`: la pagina
spiega solo lo schema di autenticazione di `api/` (SECRETKEY, `X-
Authorization-Token: md5(...)`, ecc. — vedi [086](086-api2-sanctum.md)),
nessun accenno al binario `api2` (Bearer token via Sanctum) introdotto
oggi. Stesso identico contenuto duplicato nella versione pubblica.

## Situazione prima

Entrambe le viste mostravano un solo blocco "API BASE URL" (`url('api')`)
e una sola sezione "How To Use" con lo schema `authAPI()` esistente — chi
gestisce le API da questa pagina non aveva modo di sapere che esiste
un'alternativa.

## Situazione dopo

In entrambe le viste, subito dopo il blocco esistente (non toccato):
- Un secondo campo **"API2 BASE URL (Bearer Token)"** (`url('api2')`).
- Una seconda sezione **"How To Use (api2)"**: header
  `Authorization: Bearer <token>`, con un link alla pagina
  `admin/api_tokens` per generarne uno.

Stesso permalink per riga già mostrato (`/{{$api->permalink}}`) — non
duplicato per api2 (sarebbe stato lo stesso identico valore, l'unica
differenza è quale dei due "BASE URL" ci si combina davanti).

## Motivazione

Pura documentazione, nessun cambiamento di comportamento: senza questo,
chi gestisce le API da questa pagina non scoprirebbe mai che esiste
un'alternativa più semplice (Bearer token invece di
SECRETKEY+TIME+md5) già disponibile per ogni permalink esistente.

## Test

- `php -l` sulla vista admin: pulito. Sulla vista pubblica: falso
  positivo preesistente e scollegato (un `continue;` dentro un tag
  `<?php ?>` annidato in un `@foreach` blade, riga ~137 — `php -l`
  valuta il file blade grezzo, non vede il `@foreach` come un vero
  ciclo PHP; a runtime Blade lo compila correttamente, verificato non
  toccato da questo intervento con `git diff --stat`).
- Verificato dal vivo (200 su entrambe le pagine, sessione superadmin
  per quella admin): la nuova sezione compare in entrambe.

## Rischi e note

- Il link a `admin/api_tokens` nella pagina **pubblica** (non protetta
  da login) porta a una pagina superadmin-only — un visitatore anonimo
  che ci clicca finisce sulla schermata di login, nessuna informazione
  in più esposta rispetto a quanto già implicito nell'esistenza
  dell'app stessa.

## Rollback

`git revert` del commit — solo template di rendering e traduzioni,
nessuna migration né dato coinvolto.
