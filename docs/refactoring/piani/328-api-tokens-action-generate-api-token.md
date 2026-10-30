# Piano 328 — `ApiTokensController`: Action `GenerateApiToken` (B5, secondo passo)

Stato: **bozza, in attesa di approvazione della persona**. Branch: `refactor/models`.
Autore: `/architect`, 2026-10-30. Destinatario: `ch-senior-dev` (non vede la
conversazione: questo file è autosufficiente). Origine: la persona ha chiesto di
partire da `ApiTokensController` per portare la logica fuori dai controller
(sessione `/architect` del 2026-10-30). Segue il doc 327 (Form Request pilota).

## Obiettivo

Spostare in una Action la logica di dominio di `ApiTokensController::postAddSave()`
(calcolo della scadenza e creazione del token Sanctum), lasciando nel controller
solo ciò che dipende da HTTP/sessione. **Behavior-preserving**: nessun cambio
visibile (stesse pagine, stessi redirect, stessi dati a DB, stesso log).

## Stato attuale (fatti verificati 2026-10-30, lettura del codice)

`app/Http/Controllers/System/ApiTokensController.php` (circa 150 righe):

- `postAddSave()` (r.107-135): `StoreApiTokenRequest::validateCurrent()->validated()`
  (permesso `create` + regole, doc 327) → `User::find($data['tokenable_id'])` (se
  nullo: redirect back con errore `api_tokens_invalid_user`) → `match` su
  `expiry_choice` (`30`/`90`/`365` → `now()->addDays(n)`, `custom` →
  `Carbon::parse($data['expiry_custom_date'])->endOfDay()`, altro → `null`) →
  `$user->createToken($tokenName, ['*'], $expiresAt)` → `CRUDBooster::insertLog(...)`
  → `cbView('api_tokens.reveal', ...)` con `plain_text_token`.
- `selectableUsers()`, `getAdd()`, `getEdit()`, `postEditSave()`, `cbInit()`:
  glue o configurazione dichiarativa del motore. **Non si toccano.**
- `getDelete` è ereditato da `CBController::getDelete()` (`CBController.php:1852`):
  la tabella `personal_access_tokens` non ha `deleted_at` (migrazione Sanctum
  standard), quindi cancella la riga (= revoca). Fuori perimetro.
- `app/User.php` non usa `SoftDeletes`: `User::find()` e `DB::table('cms_users')`
  vedono gli stessi utenti, quindi **nessuna incoerenza** tra menu a tendina e
  salvataggio (ipotesi emersa in discussione, verificata e scartata). Il ramo
  `if (!$user)` resta come difesa (oggi irraggiungibile da HTTP perché la regola
  `exists:cms_users,id` lo anticipa), non si rimuove.

## Perimetro

Dentro:
- `app/Actions/ApiTokens/GenerateApiToken.php` (nuovo).
- `app/Http/Controllers/System/ApiTokensController.php`: solo `postAddSave()`.
- Test nuovi (vedi sotto).

Fuori (non toccare): `cbInit()` e i suoi `callback_php`, `selectableUsers()`,
`getAdd()`, `getEdit`/`postEditSave`, `StoreApiTokenRequest`, `CBController`
(incluso `getDelete`), le viste `api_tokens.*`, le stringhe `trans()`.

## Pattern scelto e perché

**Action** (un'operazione, un solo `handle()`), come `app/Actions/Groups/AddTenantToGroup.php`
(doc 322). Motivo: è un'operazione di scrittura con regole proprie (la mappatura
della scadenza) oggi mescolata a redirect, log e vista. Dalla Action restano
fuori sessione, richiesta, `CRUDBooster::insertLog()` e vista.

Firma proposta:

```php
namespace App\Actions\ApiTokens;

use App\User;
use Laravel\Sanctum\NewAccessToken;

class GenerateApiToken
{
    public function handle(User $user, string $name, string $expiryChoice, ?string $customDate = null): NewAccessToken
```

Dentro: la stessa `match` di oggi (stesse stringhe, stesso `default => null`,
`endOfDay()` per `custom`) e `$user->createToken($name, ['*'], $expiresAt)`.
Restituisce il `NewAccessToken` (il controller usa `->plainTextToken`).
`$customDate` è `?string` perché con scelta diversa da `custom` il campo arriva
vuoto o assente; nel ramo `custom` la validazione lo garantisce valorizzato.

Nel controller restano: `validateCurrent()`, `User::find()` + redirect di errore,
`CRUDBooster::insertLog()` (stessa stringa, stessi parametri, stesso ordine
**dopo** la creazione), preparazione di `$data` per la vista, `cbView`.

## Passi

1. **Test di caratterizzazione mancanti, prima di toccare il codice** (vedi sotto).
2. Creare `GenerateApiToken`.
3. Sostituire in `postAddSave()` il blocco `match` + `createToken()` con la chiamata
   all'Action. Togliere dal controller gli `use` diventati inutilizzati
   (`Illuminate\Support\Carbon`, se non serve altrove nel file).
4. `php -l` sui due file e Pint `--test` solo sui file toccati (regola A8).

## Test di caratterizzazione (per rischio decrescente)

Esistenti, **da non modificare** e che devono restare verdi prima e dopo:
`tests/Feature/ApiTokensAddSaveTest.php` (permesso negato prima della validazione,
payload non validi, `expires_at` per 30/90/365/never/custom a fine giornata, token
in chiaro mostrato una volta con solo l'hash a DB, log solo con `api_debug_mode`).
Coprono tutto il ramo toccato dal batch.

Da aggiungere:

1. `tests/Unit/Actions/GenerateApiTokenTest.php` (o Feature, se serve il DB: usa
   MySQL vero come gli altri test, ADR A4). Input → stato atteso:
   - `30`/`90`/`365` → `expires_at` ≈ `now()+n giorni` (tolleranza come nel test HTTP);
   - `custom` + data → `expires_at` a quella data alle 23:59:59;
   - `never` → `expires_at` nullo;
   - scelta sconosciuta (es. `7`) → `expires_at` nullo (**preserva il `default`
     attuale**, non raggiungibile da HTTP perché la validazione lo blocca);
   - nome e `tokenable_id` salvati, abilità `['*']`, un solo token creato.
2. (Opzionale, a basso costo) Test di `getAdd()`: senza permesso `create` redirect a
   `admin/api_tokens` con `denied_access`; con permesso la pagina contiene gli utenti.
   Non è toccato dal batch: serve solo come rete per il prossimo.

I test non vengono lanciati dall'architect. Li lancia la persona con
`/refactor-verify` (solo i file toccati).

## Vincoli

- Nessun cambio a messaggi, chiavi `trans()`, redirect, vista o log: **nessun testo
  nuovo in UI**, quindi nessuna chiave i18n da aggiungere.
- Niente `app(GenerateApiToken::class)` nel perimetro Larastan (ADR A14): la
  Action si istanzia con `new GenerateApiToken()` nel controller, come negli altri
  usi del repo; non ha dipendenze.
- `handle()` non legge sessione, richiesta, `UserHelper` né `CRUDBooster`.
- Nessun commit/push, nessuna suite di test lanciata in autonomia.

## Impatto sui clienti

Nessuno. `ApiTokensController` è un controller di sistema; non è ereditato né
richiamato dai controller dei clienti (nessuna proprietà né hook pubblico cambia).
`postAddSave()` mantiene nome e firma (`CBController::postAddSave()` senza parametri).

## Rischio

Basso. Una sola funzione spostata, copertura HTTP esistente sull'intero ramo.
Punto di attenzione: l'ordine "crea token → scrivi log" deve restare invariato
(se `createToken()` lanciasse un'eccezione, oggi non c'è nessun log).

## Rollback

Ripristinare `postAddSave()` dal diff e cancellare `app/Actions/ApiTokens/` e il
test dell'Action. Nessuna migrazione, nessun dato toccato.

## Dopo il batch

Reviewer (con blocco i18n, atteso "n/a") → correzioni → `ch-doc-writer` (doc 328) →
post-mortem in `docs/architettura/lezioni.md`; aggiungere `GenerateApiToken` come
secondo esempio di Action in `docs/architettura/pattern-nel-repo.md` (primo con un
modello Sanctum e fuori da Menu/Group) e aggiornare la riga "Esistono da poco" di
`mappa.md`. Nessun ADR nuovo.
