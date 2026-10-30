# 335 - Profilo utente: Form Request per le sezioni `postProfile*` (piano 334)

- **Data**: 2026-10-30
- **Stato**: Completato (test eseguiti: 47 verdi, 252 asserzioni)
- **Area**: Architettura / Form Request + Profilo utente
- **File/aree di codice coinvolte**:
  - `app/Http/Requests/Profile/ProfileQlikRequest.php`, `ProfileGeneralRequest.php`,
    `ProfileSystemRequest.php`, `ProfilePreferencesRequest.php` (nuovi)
  - `app/Http/Requests/Profile/Concerns/RespondsWithProfileJson.php` (nuovo trait)
  - `app/Http/Controllers/System/AdminCmsUsersController.php` (solo i quattro metodi)
  - `tests/Feature/UserProfileFormRequestsTest.php` (nuovo, 16 test)
  - piano: `docs/refactoring/piani/334-profilo-utente-form-request.md` (il numero del piano
    non coincide con quello di questo documento)

## Contesto

Seguito dei doc 327 (primo Form Request) e 333 (iniezione dalla firma nei metodi che non
sono override di `CBController`). La persona ha chiesto di portare i controller di sistema
verso gli standard Laravel; in sessione `/architect` si è scelto di partire dal profilo
utente. `AdminCmsUsersController` è il quinto controller di sistema per dimensione (1491
righe) e più della metà è il blocco self-service del profilo (profilo a sezioni, cambio
email/password, reset, MFA).

## Situazione prima

Quattro endpoint del profilo (`postProfileQlik`, `postProfileGeneral`, `postProfileSystem`,
`postProfilePreferences`, rotte `admin/users/profile-*` generate da `routeController`)
avevano in linea nel metodo il controllo del permesso (con un JSON 403) e un
`Validator::make(Request::all(), ...)` con la risposta 422 tramite
`profileValidationError()`. `postProfileQlik` non aveva nessun test. Nessun test asseriva
la forma del JSON di 403 e 422.

## Situazione dopo

- Quattro Form Request in `app/Http/Requests/Profile/`, iniettati dalla firma del metodo
  (i metodi non sono override di `CBController`, vedi A14 e doc 333). `authorize()` replica
  la condizione di prima, `rules()` è identica (General aggiunge `status`/`data_scadenza`
  solo per superadmin e tenant admin, come `$canManage`).
- Trait `RespondsWithProfileJson` condiviso dai quattro: `failedAuthorization()` e
  `failedValidation()` rilanciano un `HttpResponseException` con lo stesso JSON di prima
  (`{ok:false, message}` con 403; `{ok:false, message, errors}` con 422), sempre JSON anche
  per richieste non AJAX.
- Nei quattro metodi del controller sono stati tolti solo il blocco del permesso e il
  `Validator::make`/`fails()`; il resto del corpo è identico e continua a leggere i dati con
  `Request::input(...)`. Gli helper `profileResponse()`, `profileValidationError()` e
  `profileTooManyAttempts()` restano (li usano ancora `EmailStart`/`PasswordStart`).
- Le rotte non cambiano: `routeController` usa wildcard fisse (`{one?}..{five?}`) e non
  guarda i parametri dei metodi.

**Cambia comportamento visibile? No.** Stessi stati HTTP, stesso JSON, stesso ordine tra
permesso e validazione.

## Motivazione

Separare permesso e validazione dalla logica di salvataggio e uniformare i controller di
sistema al pattern già usato (doc 327, 333). Alternativa scartata: duplicare i due metodi di
rifiuto in ciascun Form Request (il trait ha quattro usi identici).
**Esclusi di proposito** (decisione della persona): `postProfileEmailStart` e
`postProfilePasswordStart`, perché il controllo dei tentativi (429) sta tra permesso e
validazione e un Form Request invertirebbe l'ordine (429 diventerebbe 422 per un utente già
bloccato con dati non validi), su un flusso di sicurezza. Restano fuori anche i passi di
conferma, l'MFA e il reset password, che non validano con regole.

## Test

`tests/Feature/UserProfileFormRequestsTest.php` (16 test), scritto ed eseguito **sul codice
precedente** prima di toccare il controller (16 verdi), poi di nuovo dopo: copre Qlik (prima
senza test, con licenza simulata come nei test ChatAI), la forma esatta del 403 e del 422,
l'ordine permesso → validazione, DB invariato quando si rifiuta e il caso positivo con lo
stesso setup per ogni negativo. Dopo il refactoring: 47 verdi (252 asserzioni) con
`UserProfileFormRequestsTest`, `UserProfileSectionsTest` e `UsersCrudTest`. Verifiche
statiche: `php -l` pulito, Pint `--test` pulito sui 5 file nuovi (sul controller segnala solo
lo stile storico del file), Larastan da cache vuota senza errori.

Non verificato: l'upload della foto con un file reale oltre al caso di un PDF rifiutato; la
suite completa non è stata lanciata.

## Rischi e note

- Rischio basso-medio, come da piano. Il punto di attenzione era che `Request::all()` e
  `validationData()` coincidano anche per l'upload: i test non hanno mostrato differenze.
- Nessun reviewer lanciato: per le nuove regole del progetto (agenti solo per modifiche
  sostanziali) la modifica è stata verificata con i test, il lint e la lettura del diff.
- Restano fuori `postProfileEmailStart` e `postProfilePasswordStart` (batch dedicato, con
  `validationData()` che unisce `email`/`name` per `not_common_password`).

## Rollback

Ripristinare i quattro metodi di `AdminCmsUsersController` dal diff e cancellare
`app/Http/Requests/Profile/`. Nessuna migrazione, nessun dato toccato.
