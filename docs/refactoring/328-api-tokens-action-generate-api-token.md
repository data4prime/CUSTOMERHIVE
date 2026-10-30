# 328 - Action `GenerateApiToken` per `ApiTokensController::postAddSave` (B5, secondo passo)

- **Data**: 2026-10-30
- **Stato**: Completato (test eseguiti il 2026-10-30: 29 verdi)
- **Area**: Architettura / Action
- **File/aree di codice coinvolte**:
  - `app/Actions/ApiTokens/GenerateApiToken.php` (nuovo)
  - `app/Http/Controllers/System/ApiTokensController.php` (solo `postAddSave()` e gli `use`)
  - `tests/Feature/GenerateApiTokenTest.php` (nuovo)
  - Piano: `docs/refactoring/piani/328-api-tokens-action-generate-api-token.md`

## Contesto

Sessione `/architect` del 2026-10-30: obiettivo ridurre la logica di dominio
nei controller di sistema. Dopo il Form Request pilota (doc 327, validazione e
permesso di `postAddSave()`), questo è il passo successivo sullo stesso metodo:
portare fuori la logica di dominio. È la seconda Action del repo, dopo quelle
di `app/Actions/` introdotte con il doc 322 (`AddTenantToGroup` ecc.).

## Situazione prima

`ApiTokensController::postAddSave()` mescolava HTTP e dominio: dopo
validazione e `User::find()`, il controller calcolava `expires_at` con un
`match` su `expiry_choice` (`30`/`90`/`365` giorni da adesso, `custom` a fine
giornata della data indicata con `Carbon::parse(...)->endOfDay()`, qualunque
altro valore `null`) e chiamava `$user->createToken($tokenName, ['*'],
$expiresAt)`. Poi `CRUDBooster::insertLog()` e la vista `api_tokens.reveal`.
Il ramo era già coperto da `tests/Feature/ApiTokensAddSaveTest.php` (via HTTP).

## Situazione dopo

- Nuova classe `App\Actions\ApiTokens\GenerateApiToken` con un solo metodo
  `handle(User $user, string $name, string $expiryChoice, ?string $customDate = null): NewAccessToken`.
  Contiene la stessa `match` di prima (stesse stringhe, stesso `default => null`,
  `endOfDay()` per `custom`) e `createToken($name, ['*'], $expiresAt)`. Non
  legge sessione, richiesta, `UserHelper` né `CRUDBooster`.
- Il controller la chiama con
  `(new GenerateApiToken())->handle($user, $tokenName, $data['expiry_choice'], $data['expiry_custom_date'] ?? null)`
  e conserva: `StoreApiTokenRequest::validateCurrent()` (doc 327), `User::find()` con
  redirect di errore, `CRUDBooster::insertLog()` (sempre **dopo** la creazione,
  stessa stringa e parametri) e la vista.
- Rimosso l'`use Illuminate\Support\Carbon` non più usato nel controller.
- `postAddSave()` mantiene nome e firma. **Nessun cambio di comportamento
  visibile.**

## Motivazione

Una operazione di scrittura con regole proprie (la mappatura della scadenza)
non deve vivere accanto a redirect, log e vista: la Action è riusabile e
testabile senza HTTP. Si istanzia con `new` (nessuna dipendenza) per non
incorrere nel problema Larastan su `app(Classe::class)` emerso nel doc 327
(ADR A14). Nessun testo UI nuovo: i18n non applicabile.

## Test

Verificato davvero:
- `php -l` ok su Action, controller e nuovo test.
- Pint `--test` ok sui file nuovi; sul controller segnala solo una riga vuota
  tra gli `use`, preesistente e non toccata.
- Lettura del diff.

Eseguiti il 2026-10-30 su richiesta della persona, **29 test verdi** (122 asserzioni):
- `tests/Feature/GenerateApiTokenTest.php` (nuovo): 30/90/365 giorni, `custom`
  a fine giornata, `never`, scelta sconosciuta (nessuna scadenza, preserva il
  `default` attuale), nome, proprietario e abilità `['*']`.
- `tests/Feature/ApiTokensAddSaveTest.php` esistente, invariato, deve restare verde.

## Rischi e note

- Rischio complessivo basso: una funzione spostata, copertura HTTP preesistente.
- Rilievo reviewer D1 (corretto): il test `custom` usava la data fissa 2099, ma
  `personal_access_tokens.expires_at` è un `TIMESTAMP` MySQL (limite 2038);
  sostituita con data relativa `now()->addDays(10)`.
- Rilievo reviewer D2 (accettato, rischio basso): `storedToken()` nel test usa
  `first()` senza filtro; va bene con `RefreshDatabase` e un solo token per test.
- Il ramo `if (!$user)` del controller resta come difesa ma da HTTP è
  irraggiungibile (la regola `exists:cms_users,id` lo anticipa).
- Ipotesi scartata: `User` non usa `SoftDeletes`, quindi nessuna incoerenza tra
  il menu a tendina (`DB::table('cms_users')`) e `User::find()`.
- `getDelete` resta ereditato da `CBController` (cancella la riga = revoca del
  token): fuori perimetro.
- Impatto sui clienti: nessuno, controller di sistema non ereditato dai
  controller dei clienti.
- Il diff del controller include anche le modifiche del doc 327 (ancora non
  committate).
- I test sono stati eseguiti il 2026-10-30: `GenerateApiTokenTest` (7) e
  `ApiTokensAddSaveTest` (22) verdi.

## Rollback

Ripristinare `postAddSave()` in `ApiTokensController.php` dal diff (rimettendo
`match` + `createToken()` e l'`use Illuminate\Support\Carbon`), cancellare
`app/Actions/ApiTokens/` e `tests/Feature/GenerateApiTokenTest.php`. Nessuna
migrazione, nessun dato toccato.
