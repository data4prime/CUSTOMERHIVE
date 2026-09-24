# 081 - Fix di robustezza: `getTableStructure()`, `sendFCM()`, lingua utente, host nel login

- **Data**: 2026-09-24
- **Stato**: Completato
- **Area**: Bug fix / robustezza
- **File/aree di codice coinvolte**:
  - `app/Helpers/CRUDBooster.php` (`getTableStructure()`, `sendFCM()`, `getLang()`)
  - `app/Http/Middleware/SetUserPreferredLanguage.php`
  - `app/Http/Controllers/System/AdminController.php` (`postLogin()`, `getLogin()`)

## Contesto

Quattro voci di backlog piccole e indipendenti, riprese insieme su
richiesta. Durante la verifica della terza è emerso un quinto problema
(ciclo di redirect), corretto nello stesso giro perché diventava
raggiungibile proprio grazie al fix.

## Situazione prima

1. **`getTableStructure()` → `size: "int"`**: per le colonne `int` il size
   veniva ricavato con `str_replace('int(', '', COLUMN_TYPE)`. Su MySQL 8
   `COLUMN_TYPE` è `int` / `int unsigned` (niente display width), quindi il
   size risultava la stringa `"int"` (o `"int unsigned"`). Finiva nel campo
   "size" dello step 2 del Module Generator e nell'export JSON dei moduli
   ([073](073-module-generator-export-import.md)).
2. **Deprecation PHP 8 su `sendFCM($regID = [], $data)`**: parametro
   opzionale prima di uno obbligatorio (`php -l` lo segnala a ogni lint del
   file). Il default non era comunque mai usabile.
3. **`SetUserPreferredLanguage` senza null-check**: il middleware (gruppo
   `web`, gira su ogni richiesta prima di qualunque controllo di login)
   chiamava `CRUDBooster::getLang()` due volte;
   `getLang()` faceva `->first()->lang` sull'utente in sessione. Se
   l'`admin_id` in sessione non corrisponde più a un utente (es. utente
   cancellato mentre era loggato) → errore fatale su **ogni** pagina per
   quella sessione.
4. **`postLogin()` leggeva `$_SERVER['HTTP_HOST']` direttamente** per
   ricavare il tenant dal sottodominio: codice testabile solo forzando la
   superglobale (`tests/Concerns/LogsInAdmin.php`).
5. (emerso verificando il 3) **Ciclo di redirect con sessione orfana**:
   `CBBackend` controlla il guard Laravel (`Auth::guest()` → login),
   `getLogin()` controllava la sessione legacy (`CRUDBooster::myId()` →
   `/admin`). Con un utente cancellato durante la sessione il guard non lo
   trova più ma `admin_id` resta: `/admin` ↔ `/admin/login` all'infinito.
   Prima del fix 3 non si vedeva perché ogni richiesta andava in 500 prima.

## Situazione dopo

1. `size` = display width come intero se presente (`int(11)` → `11`),
   altrimenti `null`. I consumatori gestiscono già il vuoto:
   `createImportedTable()` usa `?: 11`; in `save_table()` il confronto
   `!=` tra `''` dal form e `null` è falso, quindi nessun "cambio size"
   spurio; lo step 2 mostra il campo vuoto col placeholder invece di "int".
2. `sendFCM($regID, $data)`: firma equivalente (il parametro era già di
   fatto obbligatorio), deprecation sparita.
3. `getLang()` usa `?->lang` (restituisce `null` se l'utente non esiste); il
   middleware lo chiama una sola volta (una query per richiesta invece di
   due).
4. `postLogin()` usa `Request::getHost()`. Stesso valore di prima tranne la
   porta (`getHost()` non la include): conta solo per host senza punti con
   porta (es. `localhost:8080` → prima `"localhost:8080"`, ora
   `"localhost"`); nessun tenant locale ha `domain_name` = `localhost`, e in
   produzione i sottodomini (`cliente.dominio.tld`) danno lo stesso primo
   segmento. `LogsInAdmin` continua a funzionare (fa la POST sull'URL
   assoluto `http://{host}/...`, da cui la Request ricava l'host); l'hack su
   `$_SERVER` è ora superfluo ma innocuo, lasciato.
5. `getLogin()`: se `myId()` c'è ma `Auth::check()` è falso (sessione legacy
   orfana) → `Session::flush()` e pagina di login, invece di rimandare a
   `/admin`. Nei casi normali le due sessioni vengono scritte/cancellate
   insieme (`postLogin()`/`getLogout()`, vedi 001/002): comportamento
   invariato. È anche la migrazione al guard di un altro punto di lettura
   (Fase 3 della roadmap auth).

## Motivazione

Robustezza: nessuno dei problemi era raggiungibile nell'uso quotidiano
normale, ma 3 e 5 rendevano una sessione inutilizzabile (500 o ciclo di
redirect) finché l'utente non cancellava i cookie; 1 produceva dati
sbagliati nell'export moduli; 2 è rumore a ogni lint e un blocco per un
futuro salto di versione PHP.

## Test

Manuali sull'ambiente Docker locale:

- **1**: script usa-e-getta (poi cancellato) su tutte le tabelle `mg_*`:
  colonne `int` (`importo`, `quantita`, `contratto`) → `size: null` (prima
  `"int"`); `module_generator/step2/19` → 200; export del modulo 19 →
  `"size": null`.
- **2**: `php -l app/Helpers/CRUDBooster.php` senza più la deprecation.
- **3 + 5**: creato un utente temporaneo, login, pagina → 200; utente
  cancellato dal DB con la sessione ancora aperta → prima (solo fix 3)
  ciclo `/admin` ↔ `/admin/login` (8 redirect), dopo il fix 5 → login dopo
  1 redirect. Utente temporaneo rimosso.
- **4**: login admin con password sbagliata → login; corretta → `/admin`;
  pagina protetta → 200. Il ramo "login da sottodominio di un altro tenant"
  è coperto da `LoginTest` (non eseguito, suite solo su richiesta).
- Admin loggato che apre `/admin/login` → `/admin` (invariato); anonimo →
  pagina di login (invariato).

## Rischi e note

- Stesso pattern `$_SERVER['HTTP_HOST']` resta in `getLogin()`,
  `getForgot()`, `getResetPassword()`, `getLicensescreen()` (branding
  tenant delle pagine pubbliche) e `isEditPage()`/`isAddPage()`/
  `isProfilePage()` in `CRUDBooster.php`: non toccati (fuori dalla voce di
  backlog); da allineare in un giro dedicato.
- `postLogin()` ha ancora un accesso non null-safe a `->first()->domain_name`
  sul tenant dell'utente: non toccato.

## Rollback

Ripristinare dal git history le righe indicate (nessuna migration né dato
coinvolto).
