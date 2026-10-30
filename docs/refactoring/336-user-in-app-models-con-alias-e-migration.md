# 336 - `User` in `App\Models` con alias di compatibilità e migration dei token (piano 336, batch B1)

- **Data**: 2026-10-30
- **Stato**: Completato (test eseguiti: 109 verdi, 399 asserzioni)
- **Area**: Architettura / Modelli
- **File/aree di codice coinvolte**:
  - `app/User.php` → `app/Models/User.php` (solo namespace e tre `use`)
  - `app/Support/legacy_crudbooster_aliases.php` (alias `App\User`)
  - `database/migrations/2026_10_30_120000_update_tokenable_type_for_user_model.php` (nuova)
  - `config/auth.php`, `config/services.php`
  - riferimenti al nome nuovo in 16 file di `app/` e 10 di `tests/`
  - `tests/Feature/UserModelMoveTest.php` (nuovo, 7 test)
  - piano: `docs/refactoring/piani/336-modelli-in-app-models-user-per-primo.md`

## Contesto

La persona ha chiesto di portare i modelli nella cartella `App\Models\`, "un po' alla volta" e
con `User` per primo. Sostituisce la scelta dell'ADR A11 ("restare in `App\`"), motivata da
Sanctum (`tokenable_type`) e dai controller custom dei clienti. Il processo di update dei
clienti lancia `php artisan migrate` (confermato dalla persona), quindi i dati si possono
riallineare con una migration.

## Situazione prima

`App\User` (`app/User.php`, tabella `cms_users`, `Notifiable` + `HasApiTokens`) era referenziato
per nome in `config/auth.php:78`, `config/services.php:33`, in una decina di classi con
`use App\User;` e in vari `\App\User::find(...)` (login, MFA, Qlik, profilo). Sanctum salvava
`App\User` in `personal_access_tokens.tokenable_type`. Unica tabella polimorfica del DB
(verificata): nessuna `notifications` standard di Laravel.

## Situazione dopo

- `App\Models\User` (stesso corpo, namespace nuovo; `Tenant`, `Group`, `Role` restano in `App\`
  e sono importati con `use`).
- `class_alias(\App\Models\User::class, 'App\User')` nel file di alias già caricato da
  `composer.json`: il vecchio nome continua a funzionare per i controller custom dei clienti
  (`use`, `instanceof`, `extends`).
- Migration che porta `tokenable_type` da `App\User` a `App\Models\User` (con `down()` inverso).
- Il codice del repo e i test usano il nome nuovo; l'alias serve solo al codice dei clienti.

**Cambia comportamento visibile? No.**

## Motivazione

Allineamento allo standard Laravel (`App\Models`), con lo stesso meccanismo di compatibilità
già usato per `crudbooster` (ADR A1). Con l'alias i vecchi token funzionerebbero anche senza
migration: la migration evita di lasciare due nomi nel DB.

## Test

`tests/Feature/UserModelMoveTest.php` (7 test), scritto prima dello spostamento: sul codice
precedente 1 verde (token con tipo vecchio) e 6 rossi attesi (classe nuova, alias, tipo salvato
da un token nuovo, guard `web`, migration). Dopo: tutti verdi. Con la regressione
(`Api2SanctumAuthTest`, `GenerateApiTokenTest`, `LoginTest`, `CBBackendTest`,
`UserModelLookupsTest`, `ModelRelationsTest`, `CrossModuleRelationsTest`, `EmailDisabledTest`,
`UserProfileSectionsTest`, `UserProfileFormRequestsTest`, `UsersCrudTest`): 109 verdi, 399
asserzioni. `php -l` pulito, Larastan da cache vuota senza errori, `composer dump-autoload`
eseguito nel container.

Non verificato: la suite completa; un controller custom reale di un cliente (l'alias è provato
solo con `new \App\User` e `class_exists`); il comportamento in produzione con
`--classmap-authoritative`.

## Rischi e note

- **Aggiornamento dei clienti**: serve `composer dump-autoload` (come per ogni classe nuova) e
  `php artisan migrate`. Senza la migration tutto funziona comunque grazie all'alias.
- Un `use App\User;` dimenticato nel repo funziona (alias) e quindi non si vedrebbe: i
  riferimenti sono stati cercati a testo fisso in `app/`, `tests/`, `config/`, `routes/`,
  `database/`, `bootstrap/`; nessuna vista li usa.
- Incidente di processo: una sostituzione con `sed` inline ha corrotto il nome in 35 file
  (`AppModelsSER`) perché il Bash di questo ambiente consuma i backslash; ripristinato con uno
  script `sed` su file e verificato con una ricerca indipendente (PowerShell). Nessun file
  corrotto resta. Vedi la lezione in `docs/architettura/lezioni.md`.
- Restano in `App\` tutti gli altri modelli (batch B2-B4); i nomi al plurale
  (`UsersGroup`, ...) non sono stati toccati.

## Rollback

Rimettere `app/User.php` dal diff, togliere l'alias, ripristinare `config/auth.php` e
`config/services.php` e i `use`, poi `php artisan migrate:rollback --step=1` (il `down()` riporta
`tokenable_type` a `App\User`).
