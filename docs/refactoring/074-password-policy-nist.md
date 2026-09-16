# 074 - Policy password stile NIST 800-63B su cms_users

- **Data**: 2026-09-16
- **Stato**: Completato
- **Area**: Auth / Sicurezza
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/AdminCmsUsersController.php` (`cbInit()`,
    campo `password`)
  - `app/Providers/AppServiceProvider.php` (regola di validazione custom)
  - `app/Helpers/PasswordPolicy.php` (nuovo)
  - `resources/security/common-passwords.txt` (nuovo)

## Contesto

Richiesta esplicita di analizzare e poi implementare una policy sulla
lunghezza/complessità delle password che restasse compatibile con gli
utenti già esistenti. Vedi anche
`docs/refactoring/072-forgot-password-link-invece-di-password-in-chiaro.md`
(intervento collegato, stessa regola riusata lì).

## Situazione prima

Il campo password nel form utenti (`AdminCmsUsersController::cbInit()`) non
aveva **nessuna** regola di validazione: nessuna lunghezza minima, nessun
controllo di complessità, e nemmeno un confronto reale tra `password` e
`password_confirmation` (il secondo campo veniva solo scartato in
`hook_before_add()`/`hook_before_edit()`, mai confrontato — un errore di
battitura nella conferma passava silenzioso). Il campo non era nemmeno
obbligatorio in creazione: la colonna `cms_users.password` è nullable, quindi
si poteva creare un utente senza password.

Il login (`AdminController::postLogin()`) fa `Hash::check($password,
$users->password)` contro l'hash salvato — nessuna dipendenza da una
policy. Per questo introdurre regole solo sulla scrittura (creazione/cambio
password) è compatibile al 100% con gli hash già esistenti: chi ha già una
password "debole" continua a fare login esattamente come prima, la policy
si applica solo alle password nuove o cambiate da questo momento in poi.

## Situazione dopo

- Filosofia NIST 800-63B: lunghezza minima invece di regole di composizione
  rigide (niente maiuscola/simbolo obbligatori), blocco di password
  comuni/prevedibili, niente scadenza forzata.
- `app/Helpers/PasswordPolicy.php`: `isCommon()` (confronto case-insensitive
  contro una blocklist statica), `hasSequentialOrRepeatedChars()` (rifiuta
  run di 6+ caratteri identici o in sequenza ascendente/discendente per
  codice ASCII, es. `aaaaaa`, `123456`, `abcdef`), `containsContextWord()`
  (rifiuta se la password contiene l'email o il nome dell'utente come
  sottostringa, token da 4+ caratteri per evitare falsi positivi).
- `resources/security/common-passwords.txt`: blocklist di password
  comuni/deboli pubblicamente note, un valore per riga, estendibile a mano.
- `AppServiceProvider::boot()`: registrata `Validator::extend(
  'not_common_password', ...)`, stesso pattern già in uso lì per
  `alpha_spaces`/`alpha_num_spaces`.
- Campo `password` in `cbInit()`: `validation` ora è
  `(required|nullable)|min:12|max:72|confirmed|not_common_password` —
  `required` solo in creazione (`CRUDBooster::isAddPage()`), `nullable` in
  modifica/profilo (preserva il comportamento esistente "lascia vuoto per
  non cambiarla" — verificato che Laravel salta comunque le regole non
  implicite su valore vuoto, quindi il comportamento è invariato per chi non
  cambia password). `max:72` per il limite byte di bcrypt
  (`password_hash()` tronca silenziosamente oltre 72 byte).

## Motivazione

Le linee guida NIST 800-63B sconsigliano le regole di composizione rigide
(spingono verso pattern prevedibili) a favore di lunghezza minima più alta e
blocklist di password comuni/compromesse. `confirmed` chiude un bug reale
(typo nella conferma password mai rilevato). Applicare la regola solo in
scrittura, senza toccare gli hash esistenti, è l'unico modo per introdurre
una policy senza rompere l'accesso di chi ha già una password valida.

## Test

Verificato leggendo il codice (non eseguita la suite automatica, su
richiesta dell'utente di non lanciarla in autonomia):
- `tests/Feature/UsersCrudTest.php::test_creazione_utente_riesce_...` usa
  già una password di 23 caratteri, conforme alla nuova regola — non
  impattato.
- `tests/Feature/LoginTest.php`/`LogoutTest.php` seedano l'utente
  direttamente in DB (bypassano il form), non impattati dalla regola.
- Verificata a mente/con un piccolo script isolato la logica di
  `PasswordPolicy` (blocklist, sequenze/ripetizioni, parole di contesto) su
  una manciata di casi.

## Rischi e note

- Non toccato: `AdminController::postForgot()` generava una password di 5
  caratteri in chiaro via email, bypassando questa policy — sistemato
  separatamente, vedi `docs/refactoring/072-forgot-password-link-invece-di-password-in-chiaro.md`.
- Non toccato: altri campi `password`-like nel modulo Settings (es.
  password SMTP) — fuori scope, non sono password di login utente.
- La blocklist in `resources/security/common-passwords.txt` è un punto di
  partenza (poche centinaia di voci), non un dataset di breach reale —
  estendibile in futuro.

## Rollback

Rimuovere `validation` dal campo password in `AdminCmsUsersController.php`
(tornando al form senza regole), la regola `not_common_password` in
`AppServiceProvider.php`, e i due file nuovi. Nessuna migration coinvolta,
nessun hash esistente da toccare.
