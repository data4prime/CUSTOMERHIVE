# Piano 334 — Profilo utente: Form Request per le sezioni `postProfile*` (batch 0 + 1)

Stato: **approvato ed eseguito (doc 335)**. Branch: `refactor/models`.
Autore: `/architect`, 2026-10-30. Destinatario: `ch-senior-dev` (non vede la
conversazione: questo file è autosufficiente). Origine: la persona ha chiesto di
standardizzare i controller di sistema secondo le best practice Laravel, partendo
dai Form Request (sessione `/architect` del 2026-10-30, con report di
`ch-architect-analyst` verificato a campione da `ch-reviewer`). Segue i doc 327
(Form Request pilota) e 333 (iniezione dalla firma nei metodi non-override).

## Obiettivo

Portare la validazione e il permesso scritti in linea in quattro endpoint del
profilo utente (`AdminCmsUsersController::postProfile*`) in Form Request dedicati,
**senza cambiare nessun comportamento visibile**: stessi esiti, stessi codici
HTTP, stesso JSON, stesso ordine tra permesso e validazione.

## Stato attuale (fatti verificati 2026-10-30, lettura del codice)

I quattro metodi sono `public` in `app/Http/Controllers/System/AdminCmsUsersController.php`
e sono rotte generate da `CRUDBooster::routeController` (`CRUDBooster.php:2416`:
registra ogni metodo pubblico `post*` come `Route::post`). **Non sono override di
`CBController`** (il padre non li ha), quindi il Form Request si **inietta dalla
firma** (pattern del doc 333, nessun `validateCurrent()`). Rispondono sempre in JSON
tramite gli helper privati `profileResponse()` e `profileValidationError()`
(stesso file, dopo `postProfileQlik`).

| Metodo (rotta) | Permesso in linea | Regole in linea | Dopo la validazione |
|---|---|---|---|
| `postProfileQlik` (`users/profile-qlik`) | `UserHelper::me()` non nullo **e** (superadmin **o** tenant admin) **e** `LicenseHelper::isActiveQlik()` → altrimenti JSON 403 `{ok:false,message:denied_access}` | `rows` nullable\|array; `rows.*.qlik_conf_id` required\|integer\|distinct\|exists:qlik_confs,id; `rows.*.qlik_login`, `rows.*.user_directory` nullable\|string\|max:255 | legge `Request::input('rows')`, chiama `QlikHelper` per le conf SaaS, transazione delete+insert su `qlik_users` |
| `postProfileGeneral` (`users/profile-general`) | `UserHelper::me()` non nullo → altrimenti 403 | `name` required\|alpha_spaces\|min:3; `photo` nullable\|image\|max:1000; **solo se superadmin/tenant admin**: `status` required\|in:Active,Inactive, `data_scadenza` nullable\|date | upload foto, update `cms_users`, sessione `admin_name`/`admin_photo` |
| `postProfileSystem` (`users/profile-system`) | `me()` non nullo **e** (superadmin **o** tenant admin) → altrimenti 403 | `tenant` nullable\|integer\|exists:tenants,id; `primary_group` required\|integer\|exists:groups,id | controllo `group_tenants`, cambio tenant/gruppo (restano nel controller) |
| `postProfilePreferences` (`users/profile-preferences`) | `me()` non nullo → altrimenti 403 | `lang` required\|in:en,it; `decimal_separator` required\|in:comma,dot; `date_format` required\|`Rule::in(DateFormat::FORMATS)` | update `cms_users`, flush di `NumberFormat`/`DateFormat` |

Formati di risposta oggi (da replicare identici):
- **403**: `response()->json(['ok'=>false,'message'=>trans('crudbooster.denied_access')], 403)`.
- **422 di validazione**: `response()->json(['ok'=>false,'message'=>$validator->errors()->first(),'errors'=>$validator->errors()], 422)`.
- Il JS di `resources/views/users/profile.blade.php` (righe ~557-706) legge solo
  `ok`, `message`, `photo_url`, `reload`, `email`, `expired`, `changed`; **non legge
  `errors`**, ma lo si mantiene comunque.

Ordine: permesso → validazione → resto. Con un Form Request `authorize()` gira
prima di `rules()`: l'ordine coincide.

**Esclusi da questo batch** (vedi "Decisioni aperte"):
- `postProfileEmailStart` e `postProfilePasswordStart`: nel metodo, il controllo del
  rate limiter (`profileTooManyAttempts`, risposta 429) sta **tra** il permesso e la
  validazione. Con un Form Request la validazione passerebbe prima: un utente già
  bloccato con payload non valido riceverebbe 422 invece di 429. Cambio piccolo ma
  su un flusso di sicurezza.
- `postProfileEmailConfirm`, `postProfilePasswordConfirm`, `postMfaConfirm`,
  `postMfaDisable`, `postUserResetPassword`: leggono `code`/`totp_code`/`password`
  con `trim` senza validare. Un `required` cambierebbe il conteggio del rate
  limiter (un codice vuoto oggi è un tentativo errato). **Non si toccano.**

## Perimetro

Dentro:
- `app/Http/Requests/Profile/ProfileQlikRequest.php`, `ProfileGeneralRequest.php`,
  `ProfileSystemRequest.php`, `ProfilePreferencesRequest.php` (nuovi).
- `app/Http/Requests/Profile/Concerns/RespondsWithProfileJson.php` (nuovo trait, vedi sotto).
- `app/Http/Controllers/System/AdminCmsUsersController.php`: solo i quattro metodi
  (firma + rimozione di permesso e `Validator::make` in linea).
- Test nuovi di caratterizzazione (passo 1) e, se servono, ritocchi **solo
  additivi** a `tests/Feature/UserProfileSectionsTest.php`.

Fuori (non toccare): `profileResponse()`, `profileValidationError()` e
`profileTooManyAttempts()` (restano usati da EmailStart/PasswordStart), tutti gli
altri metodi del controller, `cbInit()`, il form utenti, `CBController`, le viste,
le chiavi `trans()`, `AppServiceProvider` (regole `alpha_spaces`,
`not_common_password`).

## Pattern scelto e perché

**Form Request iniettato dalla firma**, uno per endpoint (A14 + doc 333). Le
regole sono diverse tra i quattro, quindi niente classe base astratta: sarebbe una
novità senza guadagno. Il solo comportamento comune (rifiuto 403 e risposta 422
in JSON) va in un **trait** `RespondsWithProfileJson` con due metodi:

```php
protected function failedAuthorization(): void   // throw new HttpResponseException(response()->json([...], 403))
protected function failedValidation(Validator $validator): void  // 422, stessa forma di profileValidationError()
```

Il trait ha 4 usi (la lezione del doc 327 prevedeva di estrarre al secondo).
`failedAuthorization()` e `failedValidation()` **vanno sovrascritti sempre**: il
default sarebbe un 403 HTML/redirect-back nuovo. Il rifiuto è sempre JSON
indipendentemente da `expectsJson()` (come oggi).

Dettagli per Request:
- **General**: `authorize()` = `UserHelper::me() !== null`; `rules()` costruisce le
  regole base e aggiunge `status`/`data_scadenza` se `UserHelper::isSuperAdmin() ||
  UserHelper::isTenantAdmin()` (stessa condizione di `$canManage`).
- **System** e **Qlik**: `authorize()` = utente non nullo **e** (superadmin o tenant
  admin) [**e** `LicenseHelper::isActiveQlik()` per Qlik]. La condizione si
  riscrive uguale, non si semplifica.
- **Preferences**: `authorize()` come General; `date_format` con `Rule::in(DateFormat::FORMATS)`.
- Nel controller i dati si continuano a leggere con `Request::input(...)` come oggi
  (non si passa a `validated()`: comportamento identico su chiavi extra).
  Il controller recupera `$user = UserHelper::me()` perché gli serve.

## Passi

1. **Batch 0: test di caratterizzazione** (vedi sotto). Prima di toccare il codice;
   vanno scritti **sul codice attuale** e devono essere verdi prima e dopo.
2. Creare il trait e i quattro Form Request.
3. In ciascun metodo del controller: firma con il Form Request, rimuovere il blocco
   permesso e il `Validator::make`/`fails()` in linea. Non cambiare nient'altro.
4. Togliere dal controller gli `use` diventati inutilizzati (`Validator` **resta**:
   serve ancora a EmailStart/PasswordStart; controllare `LicenseHelper` ecc.).
5. `php -l` sui file toccati e Pint `--test` solo su di essi (A8).

## Test di caratterizzazione (per rischio decrescente, tutti via HTTP)

Fatto: `tests/Feature/UserProfileSectionsTest.php` esiste (26 test) e copre i casi
felici di General/Preferences/System, un 422 per Preferences (`lang` non valido), un
422 per System (gruppo di altro tenant, che però esce dal controllo `group_tenants`,
non dal Form Request), il 403 di System per utente base. **`postProfileQlik` non ha
nessun test** (ricerca di `profile-qlik` in `tests/`: nessun risultato). Nessun test
asserisce la **forma** del JSON di 403 e 422 (solo lo status).

Regola (lezione 2026-10-29): ogni test "negativo" ("non viene scritta nessuna
riga") deve avere nello stesso setup il corrispondente caso positivo che crea la
riga. Per Qlik serve simulare la licenza come nei test ChatAI (`Storage::fake('license')`
+ riga in `license` + token in cache): il senior dev copia l'helper da
`ChatAIAuthorizationTest::fakeActiveChatAILicense` adattandolo al modulo Qlik.

Da aggiungere (nuovo file `tests/Feature/UserProfileFormRequestsTest.php`, stesso
`setUp` di `UserProfileSectionsTest` per le chiavi `$_SERVER`):

1. **Qlik** (nessuna copertura, rischio più alto):
   - superadmin con licenza e una conf on-premise: `rows` valido → 200 `{ok:true,reload:true}` e riga in `qlik_users` (caso positivo);
   - `qlik_conf_id` inesistente → 422, e le righe esistenti **non** vengono cancellate;
   - due righe con la stessa conf (`distinct`) → 422;
   - `qlik_login` oltre 255 caratteri → 422; `rows` non array → 422;
   - utente base → 403 JSON `{ok:false,message:denied_access}`; tenant admin **senza** licenza Qlik → 403.
2. **Forma delle risposte** (su tutti e quattro): un 422 reale ha `ok=false`,
   `message` uguale alla prima riga di `errors`, e `errors` come oggetto per campo;
   un 403 reale ha `{ok:false,message:trans('crudbooster.denied_access')}`.
3. **Ordine permesso → validazione**: utente base che invia a `profile-system` un
   payload non valido → 403, non 422 (idem Qlik).
4. **General**: `name` solo numeri o di 2 caratteri → 422 e `cms_users.name` invariato;
   `photo` non immagine → 422; superadmin con `status` non valido → 422; `data_scadenza`
   non è una data → 422. Utente base con `status` non valido → **200** (la regola non si applica).
5. **Preferences**: ciascun campo assente → 422; `decimal_separator` e `date_format`
   non ammessi → 422; DB invariato.
6. **System**: `primary_group` assente / inesistente → 422; `tenant` inesistente → 422;
   DB invariato.

I test non vengono lanciati dall'architect né dal senior dev in autonomia. Li
lancia la persona con `/refactor-verify`. Da `lezioni.md` (batch 1 e 2 del piano 332):
un test scritto "alla cieca" va **eseguito** prima di darlo per buono, la lettura
non basta; la persona li lancia prima del refactor (devono essere verdi sul codice
attuale) e dopo.

## Vincoli

- Nessun testo nuovo in UI: nessuna chiave i18n da aggiungere (i messaggi sono
  quelli di `trans('crudbooster.denied_access')` e delle regole Laravel).
- Niente `app()`/`resolve()`/`make()` con un FormRequest nel perimetro Larastan
  (A14); qui l'iniezione è dalla firma.
- Non cambiare la firma di nessun metodo di `CBController`; i quattro metodi non lo
  sono.
- Nessun nuovo metodo pubblico nel controller (diventerebbe una rotta): gli helper
  restano `private`/`protected`.
- Non toccare `TrimStrings`/`ConvertEmptyStringsToNull`: il Form Request è creato
  dalla richiesta corrente **dopo** i middleware globali, quindi vede gli stessi
  input di `Request::all()` (ipotesi: da confermare con i test di caratterizzazione).
- Nessun commit/push, nessuna suite lanciata in autonomia.

## Impatto sui clienti

Nessuno atteso. `AdminCmsUsersController` è un controller di sistema; i quattro
metodi hanno gli stessi nomi, quindi le stesse rotte. Nessuna proprietà né hook
pubblico di `CBController` cambia. Da verificare con `tokensave_callers` che nessun
altro punto del repo chiami direttamente `postProfile*` (sono rotte HTTP).

**Cambia comportamento visibile? No** (obiettivo del piano). Il punto da
confermare con i test è la forma di 403 e 422 e l'uguaglianza di `Request::all()` e
`validationData()` anche per l'upload della foto.

## Rischio

Basso-medio. Quattro metodi, JSON uniforme, copertura HTTP già buona su General,
Preferences e System; Qlik oggi scoperto, quindi il batch 0 è il vero prerequisito.
Punti di attenzione: (1) `rules()` di General dipende dall'utente corrente;
(2) l'upload della foto deve continuare a essere visto da `image|max:1000` come
oggi; (3) il rifiuto deve restare JSON anche per richieste non AJAX.

## Rollback

Ripristinare i quattro metodi dal diff e cancellare `app/Http/Requests/Profile/`.
Nessuna migrazione, nessun dato toccato.

## Decisioni aperte (per la persona)

- **D-a (decisa dalla persona, 2026-10-30).** `postProfileEmailStart` e
  `postProfilePasswordStart` **restano fuori** da questo batch e si rimandano a un
  batch dedicato (il controllo del rate limiter sta prima della validazione: un
  Form Request invertirebbe 429 e 422). Per `PasswordStart` servirebbe anche
  `validationData()` che unisce `email`/`name` dell'utente, perché lo richiede la
  regola `not_common_password`.
- **D-b (decisa dalla persona, 2026-10-30).** Si usa il trait
  `RespondsWithProfileJson` condiviso dai quattro Form Request, non i due metodi
  duplicati in ciascuna classe.

## Dopo il batch

Reviewer (con blocco i18n, atteso "n/a") → correzioni → `ch-doc-writer` → post-mortem
in `docs/architettura/lezioni.md`. Aggiornare `pattern-nel-repo.md` (riga Form
Request: terzo esempio non-override con permesso a più livelli e trait condiviso) e
`mappa.md` (riga "Esistono da poco": numero di Form Request; la voce "37 file di
test" va corretta in 46). Nessun ADR nuovo.

## Prossimi batch (non in questo piano)

Dopo il 334, in ordine: `DashboardLayoutController::postAddSave/postEditSave`
(override, `validateCurrent()` estratto in trait), poi `add_*` di ChatAI/Qlik e di
Groups/Users/Tenants (richiedono le decisioni su `safeRedirectTarget`, `$_POST`
assente e classe base). Interventi di sicurezza separati: `postSaveMenu` senza
permesso e `NotificationsController::getRead` senza controllo di proprietà.
