# Backlog — voci risolte (storico)

Voci del backlog di [`README.md`](README.md) già chiuse, spostate qui per
tenere l'indice principale leggero. Ogni voce rimanda al numero
dell'intervento che l'ha risolta, dove sta il dettaglio completo.

- ~~70 vulnerabilità segnalate da GitHub Dependabot~~ (conteggio di
  agosto, era Laravel 9) — **rivalutato il 2026-09-24**: lato PHP ne
  restava 1 (`firebase/php-jwt`, aggiornata in [083](083-firebase-php-jwt-7.md),
  `composer audit` ora pulito); il resto veniva dai `package.json` della build morta,
  rimossi in [082](082-rimossa-build-gulp-e-package-json.md). ~~Punto cieco
  rimasto: librerie JS copiate a mano in `public/vendor/` (nessun manifest,
  Dependabot non le vede) — serve un censimento dedicato~~ — **censimento
  fatto e parte morta rimossa in
  [094](094-pulizia-librerie-js-morte-public-vendor.md)** (2026-09-25).
  Resta fuori scope `assets/adminlte/plugins/` (11 MB, non auditata
  internamente).
- ~~`ApiCustomController`: nessun controllo di privilegio~~ — **risolto in
  [078](078-api-generator-privilegi.md)** (2026-09-24).
- ~~`StatisticBuilderController`: nessun controllo di privilegio su
  add/update/list component~~ — **risolto in
  [079](079-statistic-builder-privilegi-componenti.md)** (2026-09-24).
  ~~**Resta aperto**: la visibilità delle dashboard per ruolo non è mai
  verificata lato server~~ — **implementato in
  [091](091-bug-noti-execute-api-logscontroller-visibilita-dashboard.md)**
  (2026-09-24): visibile solo a superadmin o a chi ha un menu (stesso
  meccanismo di `cms_menus_privileges` già in uso) verso quella specifica
  dashboard. **Comportamento visibile cambiato**: un link condiviso
  funziona solo se la dashboard è davvero nel menu del ruolo di chi lo
  apre.
- ~~`ModulsController`: privilegio debole sul wizard~~ — **risolto in
  [080](080-module-generator-wizard-solo-superadmin.md)** (2026-09-24).
  Prima del deploy verificare sui clienti che nessun ruolo non superadmin
  usi il wizard (aggiunto a
  [`../pre-push-checklist.md`](../pre-push-checklist.md)).
- ~~Trovato risolvendo 092, priorità da valutare: l'azione `list` del
  modulo API Generator sembra restituire **sempre zero righe** per
  qualunque chiamante non superadmin~~ — **`cbInit()` ora chiamato in
  [093](093-execute-api-cbinit-privilegi.md)** (2026-09-24): risolve i
  moduli Module Generator (`mg_*`, tenant admin) e `cms_users`/`groups`.
  Il punto ancora aperto (`ModuleHelper` senza ramo generico per
  `global_privilege=true`) resta nel backlog attivo di `README.md`.
- ~~`app/Http/Controllers/` nel `.gitignore`~~ — **voluto, non un rischio**:
  da interfaccia si possono creare moduli custom (controller generati), che
  devono restare specifici dell'ambiente in cui vengono creati e non
  finire nel repo condiviso. I 52 controller già tracciati lo erano prima
  che la regola venisse introdotta. **Aggiornato in
  [006](006-controller-sistema-app-http-controllers-system.md)**: la regola
  ora esclude `app/Http/Controllers/*` con un'eccezione esplicita per
  `System/` (i controller "di sistema" spostati lì sono codice vero e
  proprio del progetto, vanno tracciati) — il resto della cartella
  (controller generati da interfaccia) resta ignorato come prima.
- ~~Compatibilità delle migration con SQLite non verificata~~ — risolto
  passando i test a MySQL vero (stesso motore della produzione), vedi
  [`../test-coverage.md`](../test-coverage.md).
- ~~**Branch remoti obsoleti da ripulire**: `main_backup`, `main_backup2`,
  `sapienza`, `qlikdashboard`, `bootstrapupdate`, `ckeditor`,
  `license-local`~~ — **risolto in
  [085](085-pulizia-file-morti-e-branch-obsoleti.md)** (2026-09-24):
  verificato che tutti (+ `master`) sono ancestor completo di `main`/`dev`
  (zero commit unici), cancellati da origin.
- ~~`AdminController::postLogin()` legge `$_SERVER['HTTP_HOST']`~~ —
  **risolto in [081](081-fix-robustezza-helper-e-login.md)** (2026-09-24).
  ~~Stesso pattern ancora presente in `getLogin()`/`getForgot()`/
  `getResetPassword()`/`getLicensescreen()` e in
  `CRUDBooster::isEditPage()`/`isAddPage()`/`isProfilePage()`~~ —
  **risolto in [084](084-host-request-path-e-chatai-cms-moduls.md)**
  (2026-09-24).
- ~~`GET /testapi` pubblico con `dd()`~~ — **rimossa in
  [077](077-rimossa-route-debug-testapi.md)** (2026-09-24).
- ~~`getTableStructure()` size `"int"`~~, ~~deprecation `sendFCM()`~~,
  ~~`SetUserPreferredLanguage` senza null-check~~ — **risolti in
  [081](081-fix-robustezza-helper-e-login.md)** (2026-09-24).
- ~~**Ambiente locale: modulo ChatAI non registrato in `cms_moduls`**~~ →
  ~~`chat_ai/access|tenant/{id}` danno 500 (`Route
  [AdminChatAIControllerGetIndex] not defined`) anche con licenza ChatAI
  attiva.~~ — **risolto in
  [084](084-host-request-path-e-chatai-cms-moduls.md)** (2026-09-24): non
  era solo il DB locale, mancava la riga anche nel seeder (si sarebbe
  ripresentato su ogni installazione pulita), stesso pattern di 009/050.
- ~~**`form_body.blade.php`: il box collassabile "System Information" non si
  chiude mai per moduli con campo `tenant` ma senza campo `group`**~~
  (scoperto il 2026-09-25 lavorando su [102](102-mfa-badge-bootstrap3-vs-bootstrap5.md))
  — **risolto in [103](103-fix-save-invisibile-profilo-utenti.md)**
  (2026-09-25): il modulo Users usa `primary_group`, non `group` - la
  condizione di chiusura ora riconosce anche quel nome. Nessun campo tolto
  dal form (due tentativi precedenti che rimuovevano campi sono stati
  scartati dopo aver causato una corruzione dati reale, verificata e
  corretta a mano sull'ambiente locale - vedi 103, sezione dedicata).
