# Test implementati

Catalogo di cosa è coperto da test automatici, mantenuto aggiornato ad ogni
aggiunta. Obiettivo: sapere a colpo d'occhio cosa è già protetto da un test
di caratterizzazione (vedi [`refactoring/glossario.md`](refactoring/glossario.md))
prima di toccare quella parte di codice, e cosa invece è ancora "alla cieca".

Come aggiungere una voce: quando si scrive un nuovo file di test, aggiungere
qui una sezione con lo stesso schema di quelle sotto.

---

## `tests/Unit/ExampleTest.php`

**Cosa copre**: niente di funzionale — è un canary minimo per validare che
la pipeline di test (locale e CI) funzioni davvero (introdotto quando si è
scoperto che la CI non eseguiva mai i test, vedi
[`cicd-passaggi-setup.md`](cicd-passaggi-setup.md)).

| Test | Cosa verifica |
|---|---|
| `test_application_boots` | l'app Laravel si avvia senza errori |
| `test_testing_environment_is_configured` | `APP_ENV=testing` viene letto correttamente |

Nessuna dipendenza da database.

## `tests/Feature/LoginTest.php`

**Cosa copre**: `AdminController::postLogin()` (CRUDBooster) — il login
dell'area admin. Test di **caratterizzazione**: fissano il comportamento
attuale. Dopo l'intervento [001](refactoring/001-auth-guard-additivo-fase-1.md)
il controller ha un'aggiunta additiva (`Auth::login()` accanto alla sessione
legacy) — questi test verificano anche quella.

| Test | Cosa verifica |
|---|---|
| `test_login_con_credenziali_corrette_riesce` | email+password corretti, utente `Active`, dominio del proprio tenant → sessione popolata (`admin_id`, `admin_is_superadmin`), redirect, **e** guard Laravel `Auth::check()`/`Auth::id()` popolato |
| `test_login_con_password_sbagliata_fallisce` | password errata → nessuna sessione impostata, `$this->assertGuest()` |
| `test_login_con_email_inesistente_fallisce` | email non presente in `cms_users` → rifiutato, messaggio d'errore in sessione (flash `message`, non il sistema standard di validation error di Laravel — dettaglio del codice reale) |
| `test_login_utente_non_active_fallisce` | credenziali corrette ma `status != 'Active'` → rifiutato |
| `test_login_da_dominio_di_un_altro_tenant_fallisce` | utente non-superadmin, login dal sottodominio di un **altro** tenant → rifiutato |
| `test_superadmin_bypassa_il_controllo_tenant` | stesso scenario sopra ma con `is_superadmin=1` → login riuscito comunque, guard popolato |
| `test_dopo_il_login_si_accede_a_una_pagina_protetta` | end-to-end: la sessione creata dal login basta davvero a superare il middleware `CBBackend` su una richiesta successiva, e il guard Laravel resta autenticato anche sulla seconda richiesta |

**Dipendenze/scelte note**:
- Il controllo di licenza (`LicenseHelper::canLicenseLogin()`) è oggi
  bypassato incondizionatamente (`LICENSE-CHECK-DISABLED-DEV`) — questi test
  dipendono da quel bypass per non fare chiamate di rete verso il license
  server esterno. **Quando il bypass verrà rimosso, questi test andranno
  rivisti** (vedi [`pre-push-checklist.md`](pre-push-checklist.md)).
- I test usano MySQL vero (non SQLite), tramite il DB dedicato
  `customerhive_testing` — vedi sotto.
- `postLogin()` legge il dominio da `$_SERVER['HTTP_HOST']` **direttamente**
  invece che dall'oggetto `Request` di Laravel: il client di test non
  sincronizza quella superglobale con l'URL simulato, quindi il test la
  imposta a mano (`postLoginFrom()` nel file). In produzione (richieste
  Apache reali) non è un problema — è solo un limite di testabilità del
  codice attuale, segnato come backlog per il futuro refactoring dell'auth
  (vedi [`refactoring/README.md`](refactoring/README.md)).

## `tests/Feature/CBBackendTest.php`

**Cosa copre**: il middleware `CBBackend` (CRUDBooster) — il gate che
protegge ogni pagina dell'area admin ad ogni richiesta (non solo al login).
Testato in isolamento usando la rotta vuota `GET /admin` come bersaglio
minimo, per non dipendere da un controller applicativo specifico.

| Test | Cosa verifica |
|---|---|
| `test_richiesta_senza_sessione_reindirizza_al_login` | nessun `admin_id` in sessione → redirect a `/admin/login` |
| `test_richiesta_con_sessione_valida_passa` | sessione valida, non bloccata → richiesta passa (200) |
| `test_richiesta_con_sessione_bloccata_reindirizza_al_lock_screen` | `admin_lock=1` in sessione → redirect a `/admin/lock-screen` |

**Scoperta scrivendo questo test** (non corretta, il middleware non è stato
toccato): il middleware globale `App\Http\Middleware\SetUserPreferredLanguage`
(gira su *ogni* richiesta web, non solo su quelle autenticate) presume che
l'id utente in sessione corrisponda sempre a una riga reale in `cms_users`,
altrimenti va in errore fatale (nessun controllo di null). In produzione non
è un problema perché `admin_id` viene sempre impostato da un login riuscito
su un utente reale — ma è un altro punto fragile da tenere presente per il
refactoring dell'auth.

## Helper di test condivisi

`tests/Concerns/SeedsCmsData.php` — trait con i metodi di seeding minimi
validi per `tenants`/`groups`/`cms_privileges`/`cms_users` (i campi NOT NULL
senza default in questo schema legacy vanno sempre passati esplicitamente:
`tenants.domain_name`, `cms_users.tenant`/`primary_group`, ecc.). Usato da
`LoginTest` e `CBBackendTest` — da riusare per i prossimi test che hanno
bisogno di un utente/tenant di base.

---

## Infrastruttura di test (non specifica a un file)

- **Database**: MySQL vero (non SQLite), stesso motore della produzione —
  scelta deliberata per un progetto già in produzione con clienti reali
  (vedi motivazione discussa in sessione). DB dedicato `customerhive_testing`,
  separato da quello di sviluppo, creato automaticamente da
  `docker/entrypoint.sh` in locale e da un service MySQL nella CI.
- **Bug corretto durante questo lavoro**: `phpunit.xml` impostava
  `CACHE_STORE=array`, ma questa app (Laravel 9) legge `CACHE_DRIVER`
  (`CACHE_STORE` è la chiave usata da Laravel 11+) — i test stavano quindi
  usando silenziosamente la cache su file, condivisa con qualunque altro
  processo dell'app sullo stesso filesystem, causa di comportamento non
  riproducibile tra run. Corretto in `phpunit.xml`.
