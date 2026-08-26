# 002 - Refactoring auth: primo file migrato al guard (Fase 3), fix logout

- **Data**: 2026-08-26
- **Stato**: Completato
- **Area**: Auth
- **File/aree di codice coinvolte**:
  - `packages/crocodicstudio/crudbooster/src/middlewares/CBBackend.php`
  - `packages/crocodicstudio/crudbooster/src/controllers/AdminController.php` (`getLogout()`)
  - `tests/Feature/LogoutTest.php` (nuovo)
  - `tests/Concerns/LogsInAdmin.php` (nuovo, trait estratto da `LoginTest.php`)
  - `tests/Feature/CBBackendTest.php` (aggiornato)
  - `docker-compose.yml` (fix non legato all'auth, trovato durante la verifica — vedi sotto)

## Contesto

Continuazione della Fase 3 del refactoring auth (vedi
[001](001-auth-guard-additivo-fase-1.md)): migrare, uno alla volta, i 42
punti che leggono lo stato di auth legacy verso il guard Laravel. Primo
candidato scelto: `CBBackend`, il middleware a maggior leva (gira su ogni
richiesta dell'area admin), già coperto da test.

## Situazione prima

`CBBackend::handle()` verificava l'autenticazione con
`CRUDBooster::myId() == ''` (legge `Session::get('admin_id')`).
`AdminController::getLogout()` invalidava solo la sessione legacy
(`Session::flush()`), senza toccare il guard Laravel introdotto nella
Fase 1.

## Situazione dopo

- `CBBackend`: il check è ora `Auth::guest()`.
- `getLogout()`: aggiunta (non sostitutiva) la chiamata `Auth::logout()`
  prima di `Session::flush()`.

## Motivazione

`CBBackend` è il file a maggior leva tra i 42 individuati in Fase 0 (gira
su ogni richiesta admin, non solo al login) — migrarlo per primo dà la
prova più solida che il pattern additivo regge su vasta scala, non solo sul
login stesso.

**Perché è servito anche il fix su `getLogout()`**: scrivendo
`tests/Feature/LogoutTest.php` **prima** di toccare `CBBackend` (per
prudenza, dato il rischio di un cambiamento trasversale su un progetto in
produzione) si è scoperto che `Session::flush()` da solo **non** invalida
il guard Laravel — dopo un logout, `Auth::check()` restava vero. Se
`CBBackend` fosse stato migrato ad `Auth::guest()` senza prima sistemare
questo, il logout avrebbe smesso di funzionare (un utente disconnesso
avrebbe potuto continuare ad accedere alle pagine protette). Individuato e
corretto **prima** di toccare `CBBackend`, non dopo.

## Un problema serio scoperto durante la verifica (non di auth, ma di infrastruttura di test)

Verificando manualmente il flusso login → pagina protetta → logout →
pagina protetta contro l'app reale in Docker, il database di sviluppo
(`customerhive`) è apparso vuoto più volte, in modo apparentemente casuale.
Inseguendo il problema (vedi log della sessione di lavoro) si è scoperto
che **non era un problema casuale**: `docker-compose.yml` forzava
`DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` a livello di container per il
servizio `app`. Queste variabili, essendo già presenti nell'ambiente di
processo prima che Laravel/dotenv carichi `.env.testing`, **bloccavano
silenziosamente l'override** (dotenv, in modalità immutabile, non
sovrascrive variabili già impostate). Risultato: `php artisan test`
lanciato in locale (via `docker compose exec`) stava usando il database di
**sviluppo vero** (`customerhive`) invece del database dedicato ai test
(`customerhive_testing`) introdotto proprio per evitare questo — e ogni run
dei test lo svuotava.

**Confermato che la CI su GitHub Actions non è mai stata affetta**: non
passa da `docker-compose.yml`, usa un service MySQL dedicato senza questo
conflitto.

**Fix**: rimosse `DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` dal blocco
`environment:` del servizio `app` in `docker-compose.yml`, lasciando solo
`DB_CONNECTION`/`DB_HOST`/`DB_PORT` (identici sia per `.env` che per
`.env.testing`, quindi innocui da forzare). `.env` e `.env.testing` hanno
già i valori corretti per il proprio contesto — non serve altro.

**Questo spiega retroattivamente** diversi episodi di "il DB di sviluppo si
è svuotato" osservati in sessione durante il lavoro sulla Fase 1: non erano
instabilità casuale dell'ambiente Docker, era il test runner locale che
scriveva/droppava tabelle sul database sbagliato.

## Test

- `tests/Feature/LogoutTest.php` (nuovo): login → verifica
  `Auth::check()===true` → logout → verifica sia `admin_id` assente dalla
  sessione sia `$this->assertGuest()`. Ha permesso di scoprire il problema
  di `getLogout()` PRIMA di introdurre una regressione in `CBBackend`.
- `tests/Feature/CBBackendTest.php`: aggiornato perché simulare una
  richiesta autenticata ora richiede anche il guard (non solo la sessione
  legacy) — `getAdmin()` accetta un id utente e usa `actingAs()`.
- Tutti i 13 test (incluso il nuovo) verdi, eseguiti più volte per
  stabilità.
- **Verifica manuale end-to-end sull'app reale**, dopo il fix
  dell'isolamento DB: login (302) → pagina protetta (200) → logout (302,
  non più 500) → pagina protetta dopo logout (redirect corretto al login).
  Confermato anche che il database di sviluppo resta popolato dopo i test.

## Rischi e note

- Restano 41 file (su 42 individuati in Fase 0) ancora sul meccanismo
  legacy — solo `CBBackend` è stato migrato finora.
- Il fix dell'isolamento DB in `docker-compose.yml` è indipendente
  dall'auth ma bloccante per fidarsi di qualunque verifica manuale locale
  fatta finora con MySQL — verificato che non ha impatto sulla CI.

## Rollback

- `CBBackend`: ripristinare `CRUDBooster::myId() == ''` al posto di
  `Auth::guest()`.
- `getLogout()`: rimuovere la riga `Auth::logout();`.
- `docker-compose.yml`: il fix sull'isolamento DB andrebbe mantenuto anche
  in caso di rollback dell'auth — è corretto indipendentemente.
