# Piano 324 — Rotte custom di sistema sotto login (B1 sicurezza)

Stato: **implementato** (doc `docs/refactoring/324-rotte-custom-sotto-login.md`);
test nuovi 42/42 verdi, regressione correlata da rilanciare. Branch: `refactor/models`.
Autore: `/architect`, 2026-10-29. Destinatario: `ch-senior-dev` (non vede la
conversazione: questo file è autosufficiente). Origine: analisi dei controller
di sistema (sessione `/architect` del 2026-10-29, report `ch-architect-analyst`
verificato a campione da `ch-reviewer`).

## Obiettivo

Chiudere le rotte di sistema raggiungibili **senza login** che non devono
esserlo, senza cambiare logica, query o dati. Una modifica dichiarativa alle
rotte più un controllo di accesso mancante in `ModulsController::saveEnable`.

## Perché serve (fatti verificati 2026-10-29, statici + `route:list`, non provati a runtime)

- **`ModulsController::saveEnable`** (`ModulsController.php:328-335`) non ha
  nessun controllo di accesso. Chiama `ModuleHelper::update_enabled_tenants`
  (`ModuleHelper.php:944-956`) che fa `ModuleTenants::query()->truncate()` e poi
  reinserisce. La rotta `POST admin/module_generator/save_enable`
  (`routes/web.php:129`) ha solo il middleware `web`; il gruppo `web`
  (`app/Http/Kernel.php`) non contiene autenticazione e `VerifyCsrfToken` è
  commentato (ADR A3). Quindi **un ospite non autenticato può svuotare e
  riscrivere `module_tenants`**; il `TRUNCATE` fa commit implicito in MySQL (non
  annullabile). Il gemello `enable()` (`:306-317`) controlla `isSuperadmin`.
- Tutte le rotte custom di `routes/web.php:85-133` stanno **fuori** dal gruppo
  `$licensed_module_middleware` (`web.php:29-78`): `route:list` mostra il solo
  middleware `web`. Dunque non agiscono `CBBackend` (login, `session_version`,
  lock-screen) né nulla di equivalente: restano solo i controlli fatti a mano
  nei controller (dove ci sono). Per un ospite `CRUDBooster::isUpdate()`
  (`CRUDBooster.php:~485`) fa un `foreach` su `null`: oggi è bloccato per caso
  (probabile 500, ipotesi non provata), non per design.
- `api-documentation` e `download-documentation-postman`
  (`routes/crudbooster.php:77-78`) hanno solo `web`. `apiDocumentation()`
  (`ApiCustomController.php:77-85`) mostra il catalogo `cms_apicustom` a chiunque.
- `/mashup/{componentID}` e `/mashup-objects/...` (`web.php:131,133`) sono
  pubbliche; il doc 076 le aveva lasciate fuori "di proposito" (pubbliche per
  design, con solo il controllo `isActiveQlik()` nel metodo). **La persona ha
  deciso (2026-10-29) che non devono essere pubbliche**: questo piano ribalta
  quella scelta.

## Decisioni della persona (2026-10-29)

- `mashup` e `mashup-objects`: dentro B1 (non pubbliche).
- `api-documentation`: dentro B1 (non pubblica). Default scelto da questo
  piano: **solo login** (`CBBackend`), non solo superadmin. Se la persona
  preferisce solo superadmin, aggiungere `denyUnlessSuperadmin`-like in
  `apiDocumentation()` (vedi "Domande aperte").
- `uploads/*`: **fuori da B1** (B1b, dopo verifica: la rotta non rende privati i
  file, vedi sotto). Non toccare la riga `crudbooster.php:79`.
- S3 (`postDataQuery`, `getUpdateSingle`, `whereRaw` da input in `CBController`):
  **lasciato com'è**, rischio noto accettato. Non toccare `CBController`.
- S2 (IDOR tenant su appartenenze), B3/B4: rimandati (dipendono dai privilegi
  reali dei tenant admin in produzione).

## Perimetro

Dentro: `routes/web.php`, `routes/crudbooster.php`,
`ModulsController::saveEnable` (e la riga di log nel ramo di rifiuto di
`enable()`, vedi passo 3), test nuovi.

Fuori: `CBController`, `CBBackend` (non si modifica), `EnforceModuleLicense`,
`FileController`/`uploads`, tutte le rotte `qi/{proxy_token}` (resta pubblica:
è il meccanismo di condivisione pubblica), login/forgot/MFA/health, i controller
dei clienti, ogni altra logica dei controller coinvolti.

## Passi

1. **Test prima** (vedi sezione Test): scritti da `ch-senior-dev`, non lanciati
   da lui. Devono essere scritti per il comportamento **obiettivo**: prima del
   passo 2 i casi "ospite/non superadmin respinto" sono rossi di proposito e i
   casi positivi verdi. Li lancia la persona con `/refactor-verify`.
2. **`routes/web.php`**: racchiudere in
   `Route::middleware([\App\Http\Middleware\CBBackend::class])->group(function () use ($controllers_base_path) { ... });`
   tutte le rotte da `admin/menu_management/edit/{id}` (`:87`) a
   `/mashup-objects/...` (`:133`) incluse. Solo `CBBackend`, **non**
   `EnforceModuleLicense`: questa agisce solo sui prefissi `qlik_items`,
   `qlik_confs`, `qlik/user`, `chat_ai` (`EnforceModuleLicense.php:25-30`) e
   nessuno di questi path è nel blocco. Non cambiare ordine, path, nome
   (`MenusControllerGetEdit`) né i metodi. Lasciare invariata la riga duplicata
   `groups/items/{group_id}/alert/{alert_id}` (`:106-107`). Non toccare
   `qi/{proxy_token}` (`:82`) né il gruppo licensed (`:31-78`).
3. **`ModulsController::saveEnable`**: stesso controllo di `enable()`:
   `$this->cbLoader();` poi, se `!CRUDBooster::isSuperadmin()`, `return
   CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));`
   prima di `update_enabled_tenants`. Nessuna chiave di traduzione nuova
   (`denied_access` esiste già). Nel ramo di rifiuto di `enable()`
   (`:312-315`) c'è un bug preesistente: usa `$row->{$this->title_field}` con
   `$row` mai definita (warning → `ErrorException` → 500 per un utente loggato
   non superadmin). Correggere il solo argomento `'name'` usando
   `CRUDBooster::getCurrentModule()->name` (già usato nella riga sotto), così il
   rifiuto produce il redirect voluto. **Cambia comportamento visibile**
   (500 → redirect con `denied_access`): va dichiarato nel doc.
4. **`routes/crudbooster.php`** (`:75-80`): separare `api-documentation` e
   `download-documentation-postman` dal gruppo `uploads`: nuovo gruppo con
   `'middleware' => ['web', '\App\Http\Middleware\CBBackend']` (stessa notazione
   di `:119`) e `'namespace' => $namespace`, contenente solo queste due rotte;
   la rotta `uploads/...` resta nel gruppo `['web']` invariata. Nomi di rotta
   invariati (`apiDocumentation`, `downloadDocumentationPostman`).
   `getDownloadPostman` ha già `denyUnlessSuperadmin`: non toccarlo.
5. **Verifiche leggere** (permesse): `php -l` sui file toccati, Pint `--test`
   solo sui file `.php` toccati (informativo), `docker compose exec app php
   artisan route:list --json -v` e confrontare **prima/dopo**: stesso numero di
   rotte, stessi path/nomi/azioni; cambia solo il middleware delle rotte
   elencate sopra (ora `web, CBBackend`). Allegare la differenza al doc.

## Impatto sui clienti

- Rotte e controller toccati sono di sistema e versionati: i controller custom
  dei clienti (gitignored) non li estendono né le chiamano (ipotesi: non
  verificabile dal repo). `CBController` e le sue API non cambiano.
- **Cambia comportamento visibile, solo per chi non è autenticato** (o ha la
  sessione invalidata): redirect a `admin/login` invece di eseguire/mostrare la
  pagina. Per gli utenti loggati con permesso: nessuna differenza.
- Ipotesi non verificabili dal repo, da riportare alla persona in "Rischi e
  note" del doc: (a) un cliente che incorpora `/mashup/...` o
  `/mashup-objects/...` fuori dal pannello (pagine esterne, iframe su altri
  siti): ora vedrebbe il login; il widget interno (`qlikwidget.blade.php:26,79,90`)
  è un iframe dentro il pannello, quindi con sessione, e non è impattato;
  (b) sviluppatori esterni che leggono `/api-documentation` senza account;
  (c) se la sessione scade, un iframe del widget mostrerà la pagina di login
  dentro il riquadro (senza `?embed=1` `CBBackend` non esce dal frame).
- `VerifyCsrfToken` esclude `/mashup/*` (`VerifyCsrfToken.php:17`): irrilevante
  con CSRF disattivato (ADR A3), non toccare.

## Test di caratterizzazione (rischio decrescente)

Nuovo file in `tests/Feature/` (suggerito `PublicSystemRoutesAuthTest.php`),
usando gli helper di login già presenti in `CBBackendTest.php`/`UsersCrudTest.php`
(leggerli prima; per `mashup` senza licenza Qlik il metodo risponde 200 con la
vista `mashup_noqlik`: va bene come caso "raggiunge il controller"). Ogni caso
negativo deve avere il positivo con lo stesso setup (lezione 2026-10-29,
doc 318): mai asserire solo "nessuna riga cambiata".

1. **`POST admin/module_generator/save_enable`**, payload
   `module_tenant_enabler[<modulo>][<tenant>]=1` (la chiave deve esistere, o
   `$_POST[...]` dà 500 per warning), con `module_tenants` pre-popolata:
   - ospite → atteso (dopo): 302 a `admin/login`, `module_tenants` **identica**;
   - utente loggato **non superadmin** → 302 con `denied_access`, tabella identica;
   - **superadmin** → tabella riscritta secondo il payload (caso positivo).
2. **`GET admin/module_generator/enable`**: superadmin 200 con la vista;
   non superadmin 302 con `denied_access` (oggi 500 per `$row`, vedi passo 3);
   ospite 302 al login.
3. **Ospite su ciascuna rotta custom** (data provider; la sostituzione di
   `{id}` con id reali creati nel test): `admin/menu_management/edit/{id}`,
   `admin/tenants/members/{id}`, `admin/tenants/group/{id}`,
   `admin/groups/members/{id}`, `admin/groups/items/{id}`,
   `admin/groups/tenant/{id}`, `admin/users/groups/{id}`, più le mutazioni:
   `POST admin/groups/{id}/add_member`, `GET admin/groups/{id}/remove_member/{uid}`,
   `POST admin/users/{id}/add_group`, `POST admin/tenants/{id}/add_group`
   → 302 a `admin/login` e **nessuna riga** in `users_group`/`group_tenants`;
   positivo: superadmin sulle stesse GET → non 302 al login (200), e su una
   `add_member` valida la riga viene creata.
4. **`GET /mashup/{componentID}`** e **`/mashup-objects/{mashup}/{componentID}/{objectid}`**:
   ospite 302 al login; utente loggato 200.
5. **`GET api-documentation`**: ospite 302 al login; utente loggato 200.
   **`GET download-documentation-postman`**: ospite 302 al login; superadmin
   200 (download); non superadmin loggato invariato rispetto a oggi.
6. **Sessione invalidata**: su una delle rotte nuove (es. `admin/groups/members/{id}`)
   con `admin_session_version` vecchio → redirect a login con
   `session_invalidated` (come `CBBackendTest`).
7. **Non regressione**: (a) `GET uploads/...` e `GET qi/{proxy_token}` restano
   raggiungibili da ospite (stessa risposta di oggi); (b) i nomi di rotta
   `MenusControllerGetEdit`, `apiDocumentation`, `downloadDocumentationPostman`,
   `fileControllerPreview` esistono; (c) la lista dei middleware di
   `fileControllerPreview` è ancora solo `web`.

Copertura attuale: nessun test per URL su queste rotte (`UsersCrudTest.php:21`
lo dice esplicitamente).

## Rischio

Basso-medio. Modifica dichiarativa a rotte + un controllo, nessuna logica nuova;
il rischio vero è il comportamento visibile per chi usa queste URL senza
sessione (vedi ipotesi sopra) e l'effetto del login redirect dentro un iframe.

## Rollback

`git revert` del commit: tre file (`routes/web.php`, `routes/crudbooster.php`,
`ModulsController.php`) più i test. Nessuna migrazione, nessun dato toccato.

## Rischi accettati e fuori scope (da riportare nel doc dell'intervento)

- **S3** aperto di proposito: `CBController::postDataQuery`
  (`DB::select(Request::get('query'))`), `getUpdateSingle` (update con
  tabella/colonna/valore da input, GET), `whereRaw` da input in `getModalData`/
  `getDataTable`: raggiungibili da **qualsiasi utente loggato** su ogni modulo
  (`CBBackend` non controlla il permesso sul modulo). B1 non lo riduce per chi è
  già loggato.
- **S2** (IDOR tenant su `add_member`/`remove_member`/`add_item`/`add_group`):
  B1 impedisce solo l'accesso da ospite.
- Open redirect su `return_url` (B2) e mutazioni via GET (CSRF off): non toccati.
- `uploads/*` pubblica (B1b): **la rotta non protegge i file**, perché i file
  sono linkati quasi sempre come `storage/uploads/...` (symlink pubblico,
  `CBController.php:1430`, `SettingsController.php:205`). Prima di agire:
  verificare chi genera URL `uploads/...` (anche template email, logo di
  login), e se `storage/uploads` è raggiungibile direttamente.
- `$_POST['module_tenant_enabler']` mancante dà 500 per warning: non toccato.

## Domande risolte (2026-10-29)

1. `api-documentation`: **solo login** (confermato dalla persona). Nota: qualunque
   utente di qualunque tenant vede il catalogo completo di `cms_apicustom`;
   accettato.
2. Nessun cliente incorpora `/mashup/...` o `/mashup-objects/...` fuori dal
   pannello (risposta della persona, non verificabile dal repo): l'ipotesi (a)
   di "Impatto sui clienti" è quindi considerata non applicabile.
