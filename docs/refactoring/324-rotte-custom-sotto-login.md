# 324 - Rotte custom di sistema sotto login (B1 sicurezza)

- **Data**: 2026-10-29
- **Stato**: Completato (test dei due file nuovi verdi, suite non lanciata)
- **Area**: Sicurezza / Rotte
- **File/aree di codice coinvolte**:
  - `routes/web.php`
  - `routes/crudbooster.php`
  - `app/Http/Controllers/System/ModulsController.php` (`saveEnable`, `enable`)
  - `phpstan-baseline.neon` (rimossa una voce)
  - `tests/Feature/PublicSystemRoutesAuthTest.php` (nuovo)
  - `tests/Feature/PublicSystemRoutesSaveEnableTest.php` (nuovo)
  - Piano: [`piani/324-rotte-custom-sotto-login.md`](piani/324-rotte-custom-sotto-login.md)

## Contesto

Primo batch (B1) emerso dall'analisi dei controller di sistema fatta con
`/architect` il 2026-10-29 (report di `ch-architect-analyst` verificato a
campione da `ch-reviewer`). Obiettivo: chiudere le rotte di sistema
raggiungibili senza login che non devono esserlo, senza cambiare logica,
query o dati. Il piano (con i fatti file:riga) è in
`piani/324-rotte-custom-sotto-login.md`.

## Situazione prima

- Tutte le rotte custom di `routes/web.php` (da `admin/menu_management/edit/{id}`
  a `/mashup-objects/...`: gruppi, tenant, utenti, `module_generator/enable|save_enable`,
  `mashup`, `mashup-objects`) stavano fuori dal gruppo con i middleware di
  licenza/login e avevano solo `web`. Non agivano quindi `CBBackend` (login,
  `session_version`, lock-screen): restavano solo i controlli fatti a mano nei
  controller, dove presenti.
- `ModulsController::saveEnable` non aveva alcun controllo di accesso e chiama
  `ModuleHelper::update_enabled_tenants`, che fa `TRUNCATE` di `module_tenants`
  e reinserisce. Con CSRF disattivato globalmente (ADR A3), **un ospite non
  autenticato poteva svuotare e riscrivere `module_tenants`** (il `TRUNCATE`
  fa commit implicito in MySQL, non annullabile).
- Per un ospite, rotte come `add_member` finivano in 500 (`foreach` su `null`
  in `CRUDBooster.php:492`): bloccate per caso, non per design.
- `ModulsController::enable()` nel ramo di rifiuto usava `$row->{$this->title_field}`
  con `$row` mai definita: per un utente loggato non superadmin dava 500.
- `api-documentation` e `download-documentation-postman` (`routes/crudbooster.php`)
  avevano solo `web`: il catalogo `cms_apicustom` era visibile a chiunque.
- `/mashup/...` e `/mashup-objects/...` erano pubbliche per scelta del doc 076.

## Situazione dopo

- `routes/web.php`: tutte le rotte custom, da `admin/menu_management/edit/{id}`
  a `/mashup-objects/...`, sono in un gruppo con il solo `CBBackend`. 28 rotte
  passano da middleware `web` a `web, CBBackend`; 777 rotte prima e dopo, path,
  nomi e azioni identici (confronto `route:list` prima/dopo). Restano fuori
  `qi/{proxy_token}` (condivisione pubblica) e il gruppo licensed esistente.
  Non è stato usato `EnforceModuleLicense` (agisce solo su `qlik_items`,
  `qlik_confs`, `qlik/user`, `chat_ai`).
- `routes/crudbooster.php`: `api-documentation` e `download-documentation-postman`
  in un gruppo `['web','CBBackend']`; `uploads/...` resta pubblico nel gruppo
  `['web']` invariato (B1b, fuori scope). Nomi di rotta invariati.
- `ModulsController::saveEnable`: `cbLoader()` e controllo `isSuperadmin` (stesso
  schema di `enable()`) prima di `update_enabled_tenants`; rifiuto con redirect
  e `trans('crudbooster.denied_access')`.
- `ModulsController::enable()`: nel ramo di rifiuto l'argomento `'name'` usa
  `CRUDBooster::getCurrentModule()->name` invece della `$row` non definita.
- `phpstan-baseline.neon`: rimossa la sola voce `Undefined variable: $row` per
  `ModulsController.php`, diventata obsoleta perché il bug è corretto (Larastan
  usciva con errore `ignore.unmatched`). La voce analoga per `ApiController.php`
  resta.

### Cambiamenti di comportamento visibile

- Ospite o sessione invalidata sulle 28 rotte: redirect a `admin/login` invece
  di eseguire pagina/azione (prima, per `add_member` e simili, 500).
- `POST save_enable` da non superadmin: redirect con `denied_access`;
  `module_tenants` non viene più svuotata.
- `enable()` per utente loggato non superadmin: da 500 a redirect con
  `denied_access`.
- `api-documentation` e `download-documentation-postman` richiedono login;
  `mashup` e `mashup-objects` richiedono login. Decisione della persona che
  **ribalta il doc 076** (le mashup erano pubbliche "per design").
  `api-documentation` richiede solo login, non superadmin: qualunque utente di
  qualunque tenant vede il catalogo `cms_apicustom` (accettato).

Per gli utenti loggati con permesso non cambia nulla.

## Motivazione

Sicurezza: la scrittura distruttiva su `module_tenants` da non autenticato era
il rischio principale; il resto porta le rotte custom allo stesso livello di
protezione (login, `session_version`, lock-screen) delle altre rotte del
pannello, con una modifica dichiarativa e senza toccare la logica. Scartato:
solo superadmin per `api-documentation` (la persona ha scelto solo login);
aggiungere i middleware rotta per rotta (il gruppo unico è più leggibile e non
cambia l'ordine delle rotte).

## Test

Nuovi test, scritti per il comportamento obiettivo prima della modifica:

- `tests/Feature/PublicSystemRoutesAuthTest.php`: `enable`, 7 rotte GET custom
  (ospite/superadmin), `add_member`/`remove_member`/`users add_group`/`tenants add_group`,
  `mashup`, `api-documentation`/postman, sessione invalidata, non regressione
  (`uploads`, `qi`, nomi di rotta, middleware di `fileControllerPreview`).
- `tests/Feature/PublicSystemRoutesSaveEnableTest.php`: `save_enable` da ospite,
  non superadmin, superadmin; `tearDown` che ripulisce dopo il `TRUNCATE`
  (commit implicito).

Esiti, lanciati dall'orchestratore su richiesta della persona, solo questi due
file, sul DB `customerhive_testing`:

- Prima della modifica: 21 rossi / 21 verdi (20 rossi attesi + 1 falso rosso:
  `AdminCmsUsersController::cbInit()` legge `$_SERVER['REQUEST_URI']`, ora
  simulato nel test).
- Dopo la modifica: **42/42 verdi (102 asserzioni)**.

Nota tecnica: `add_member`, `add_group` e `saveEnable` leggono `$_POST`
direttamente, che il client di test non popola; i test lo simulano con un
helper (ripristinato in `finally`).

Verifiche leggere: `php -l` pulito; Larastan "No errors"; Pint informativo solo
su stile storico (nessuna riga scritta da questo intervento); confronto
`route:list` prima/dopo (777 rotte, solo il middleware delle 28 rotte cambia);
i18n: nessun testo nuovo (riusa `crudbooster.denied_access`, presente in en:366
e it:373).

**Non verificato**: non sono stati rilanciati `ModuleGeneratorCrudTest`,
`MenusCrudTest`, `GroupsCrudTest`, `UsersCrudTest`, `CBBackendTest` (consigliato,
per controllare che il `tearDown` del TRUNCATE non lasci dati sporchi e che
nulla di esistente si rompa), né la suite completa. Nessuna prova manuale da
browser.

## Rischi e note

Rilievi di `ch-reviewer` (nessun bloccante):

1. `saveEnable` non fa `insertLog(log_try_edit)` nel ramo di rifiuto, a
   differenza di `enable()`: i tentativi non autorizzati non lasciano traccia in
   `cms_logs`. Non chiesto dal piano, opzionale, non fatto.
2. Il widget Qlik usa un iframe verso `/mashup-objects/...`
   (`qlikwidget.blade.php:79,90`): con sessione scaduta l'iframe mostrerà il
   login dentro il riquadro (senza `?embed=1` `CBBackend` non esce dal frame).
   Per un utente loggato non si rompe nulla.
3. La riga duplicata `groups/items/{group_id}/alert/{alert_id}` in `web.php`
   resta com'è.
4. Il `tearDown` del test SaveEnable è sicuro solo perché i test girano su
   `customerhive_testing` (`.env.testing`).

Rischi accettati e fuori scope:

- **S3 aperto di proposito**: `CBController::postDataQuery`
  (`DB::select(Request::get('query'))`), `getUpdateSingle` via GET con
  tabella/colonna/valore da input, `whereRaw` da input in
  `getModalData`/`getDataTable` restano raggiungibili da **qualsiasi utente
  loggato** su ogni modulo; B1 non lo riduce per chi è già loggato. La persona
  ha scelto di lasciarlo così per ora.
- **S2** (IDOR tenant su `add_member`/`remove_member`/`add_item`/`add_group`):
  B1 impedisce solo l'accesso da ospite. B3/B4 rimandati finché non si sa se i
  tenant admin hanno `is_edit` su Groups/Users in produzione.
- Open redirect su `return_url` (B2) e mutazioni via GET (CSRF off): non toccati.
- `uploads/*` pubblica (B1b): la rotta non protegge i file, linkati come
  `storage/uploads/...`; da indagare prima di agire.
- `$_POST['module_tenant_enabler']` mancante dà 500 per warning: non toccato.
- Impatto sui clienti non verificabile dal repo: la persona ha dichiarato che
  nessun cliente incorpora le mashup fuori dal pannello; chi usa
  `/api-documentation` senza account vedrà ora il login (da verificare).

## Rollback

`git revert` del commit: ripristina `routes/web.php`, `routes/crudbooster.php`,
`ModulsController.php`, `phpstan-baseline.neon` (la voce `$row` torna e va
rimessa, altrimenti Larastan segnala di nuovo l'errore) e rimuove i due file di
test. Nessuna migrazione, nessun dato toccato.
