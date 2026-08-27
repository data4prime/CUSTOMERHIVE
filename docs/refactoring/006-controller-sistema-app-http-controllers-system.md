# 006 - Controller "di sistema" spostati in App\Http\Controllers\System

- **Data**: 2026-08-27
- **Stato**: Completato (verifica manuale in corso, vedi Test)
- **Area**: Architettura / CRUDBooster
- **File/aree di codice coinvolte**:
  - 21 controller spostati da `packages/crocodicstudio/crudbooster/src/controllers/`
    a `app/Http/Controllers/System/`
  - `packages/crocodicstudio/crudbooster/src/routes.php`
  - `packages/crocodicstudio/crudbooster/src/helpers/CRUDBooster.php`
  - `packages/crocodicstudio/crudbooster/src/middlewares/CBBackend.php`
  - `packages/crocodicstudio/crudbooster/src/views/chat2.blade.php`
  - `routes/web.php`
  - `resources/views/mashup.blade.php`, `resources/views/mashup_objects.blade.php`
  - `.gitignore`

## Contesto

Primo passo del lavoro "portare fuori da `packages/` tutto quello che si
può, rispettando gli standard Laravel" (CRUDBooster è un fork non
mantenuto). Discusso a fondo prima di scrivere codice: la cartella
`packages/crocodicstudio/crudbooster/src/controllers/` contiene 26 file,
ma solo 5 hanno un vero contratto esterno (vengono `extends`-i per FQCN
dai controller creati da interfaccia in produzione, mai in questo repo):
`Controller.php`, `CBController.php`, `ApiController.php`, `ExportData.php`,
`ImportData.php` — questi restano dove sono, saranno affrontati in un
intervento dedicato successivo con uno shim `class_alias()`. Gli altri 21
sono "schermate" (Utenti, Gruppi, Tenant, Moduli, Menu, Privilegi,
Impostazioni, Log, Notifiche, Qlik, ChatAI, ecc.): verificato via grafo
delle dipendenze che nessun codice esterno al pacchetto le referenzia per
FQCN, quindi spostabili senza shim di compatibilità.

## Situazione prima

- I 21 controller vivevano in `packages/crocodicstudio/crudbooster/src/controllers/`,
  namespace `crocodicstudio\crudbooster\controllers`.
- La distinzione "modulo di sistema vs modulo generato da interfaccia" era
  fatta in due punti diversi, entrambi basati sulla presenza fisica di un
  file in quella cartella:
  - `routes.php`: `glob(__DIR__.'/controllers/*.php')` per popolare
    `$master_controller`, usato per instradare le righe di `cms_moduls`
    che puntano a un controller di sistema.
  - `helpers/CRUDBooster.php::mainpath()`: `str_replace()` del prefisso di
    namespace sull'azione della rotta corrente, per ricavare il nome nudo
    del controller.
- Riferimenti sparsi (verificati con una ricerca sull'intero repo, non solo
  nel pacchetto) al namespace `crocodicstudio\crudbooster\controllers`:
  `packages/.../middlewares/CBBackend.php` (un redirect hardcoded),
  `packages/.../views/chat2.blade.php` (chiamata statica a
  `AdminChatAIController::getConf()`), e soprattutto **`routes/web.php`**
  (~40 rotte extra usano una variabile locale `$controllers_base_path` con
  lo stesso prefisso) e le viste `resources/views/mashup*.blade.php`
  (`QlikAppController::getConf()`).
- `app/Http/Controllers/` era interamente in `.gitignore` (voluto: i
  controller creati da interfaccia sono specifici di ogni ambiente) — senza
  modificarlo, qualunque cosa spostata lì non sarebbe mai stata tracciata.

## Situazione dopo

- 21 controller ora in `app/Http/Controllers/System/`, namespace
  `App\Http\Controllers\System`. Impronta meccanica interna minima: solo
  20 file avevano un `extends CBController`/`extends Controller` a nome
  nudo (bisogno di un nuovo `use` verso il namespace del pacchetto, dove
  restano `CBController`/`Controller`); un file (`EmailTemplatesController`)
  aveva già l'FQCN completo, nessuna modifica necessaria oltre al namespace;
  un file (`StatisticBuilderController`) importava `QlikAppController` per
  FQCN — anche lui spostato, quindi l'import è stato aggiornato al nuovo
  namespace invece che lasciato puntare al vecchio.
- `.gitignore`: `app/Http/Controllers/` cambiato in `app/Http/Controllers/*`
  con eccezioni `!app/Http/Controllers/System/` e `!.../System/**` (verificato
  con `git check-ignore`/`git status` che la cartella sia davvero tracciata
  ora, e che tutto il resto di `app/Http/Controllers/` resti ignorato).
- `routes.php`: `$namespace` e i 3 override puntuali (dashboard fallback,
  `api_generator`, loop dei moduli di sistema) ripuntati a
  `App\Http\Controllers\System`; il `glob()` ora scansiona
  `app_path('Http/Controllers/System/*.php')` invece della vecchia cartella.
- **Bug scoperto e corretto durante la verifica** (non nello scope
  originale, ma bloccava l'intero pannello): il blocco "ROUTER FOR API
  GENERATOR" fa `scandir(base_path("app/Http/Controllers"))` e prova a
  istanziare ogni voce come controller, escludendo solo `.`, `..` e `Auth`
  per nome. Non escludeva le **sotto-cartelle** in generale: con `System/`
  presente, `scandir()` la restituiva come voce e `app("App\Http\Controllers\System")`
  falliva con `BindingResolutionException` ad ogni richiesta, portando giù
  tutta l'app (non solo i moduli spostati). Corretto aggiungendo un
  controllo `is_dir()` allo skip, che esclude qualunque sottocartella
  presente e futura, non solo `System/`.
- `helpers/CRUDBooster.php::mainpath()`: aggiunto un terzo prefisso da
  togliere (`App\Http\Controllers\System\`) **prima** di quello più corto
  `App\Http\Controllers\` nell'array di `str_replace()` — l'ordine conta,
  altrimenti il prefisso corto viene consumato per primo e lascia
  `System\` nel risultato.
- `middlewares/CBBackend.php`, `views/chat2.blade.php`, `routes/web.php`
  (variabile `$controllers_base_path`, usata da ~40 rotte, più una riga
  commentata), `resources/views/mashup.blade.php`,
  `resources/views/mashup_objects.blade.php`: tutti i riferimenti al
  vecchio namespace aggiornati al nuovo.
- Nessuna modifica a `composer.json`: sia `App\` che (di riflesso)
  `App\Http\Controllers\System\` erano già coperti dal mapping PSR-4 di
  `app/`, PSR-4 non richiede rigenerazione per file nuovi in una radice
  già mappata.
- Nessuna migrazione dati: `cms_moduls.controller` contiene solo il nome
  nudo della classe (es. `"AdminGroupsController"`), mai il namespace.

## Motivazione

Primo passo, a basso rischio, del percorso di uscita da CRUDBooster:
isolare la fetta senza contratto esterno (le 21 "schermate") da quella con
contratto esterno vero (le 5 classi motore), e affrontarle separatamente
invece che come un blocco unico da 26 file. Verificato prima di scrivere
codice che l'impronta di modifica sarebbe stata piccola (3 righe di
riferimenti incrociati dentro la cartella) — confermato vero, la parte più
grande del lavoro è stata aggiornare i punti di wiring (routes.php,
CRUDBooster.php, le rotte extra in routes/web.php), non i controller
stessi.

## Test

Non eseguita la suite PHPUnit (nessun test automatico copre questi
controller oltre a `AdminController`, già testato indirettamente da
`LoginTest`/`LogoutTest`) — verifica manuale via browser lasciata
esplicitamente all'utente. Fatto io, con mezzi leggeri (non la suite):
- `php -l` su tutti i 21 controller spostati e su tutti i file PHP toccati:
  nessun errore di sintassi.
- `php artisan route:list`: ha permesso di scoprire e correggere il bug
  `scandir()`/`is_dir()` sopra. Dopo il fix, 467 rotte registrate senza
  errori; confermato via `route:list --json` che **nessuna** rotta punta
  ancora a `crocodicstudio\crudbooster\controllers` per uno dei 21
  controller spostati, e che tutti i moduli con una riga `cms_moduls`
  valida in questo ambiente (Gruppi, Log, Moduli, Email Templates,
  ApiCustomController, login/logout, ecc.) risolvono correttamente a
  `App\Http\Controllers\System\*`.
- `curl` su una selezione di schermate (`/admin`, `/admin/groups`,
  `/admin/logs`, `/admin/module_generator`, `/admin/email_templates`,
  `/admin/tenants`, `/admin/users`) senza sessione: tutte rispondono 302
  (redirect al login, comportamento atteso), nessun 500.
- Verificato che `QlikAppController`, `DashboardLayoutController` e
  `AdminModuleHelperController` non compaiono in nessuna rotta: confermato
  via query diretta su `cms_moduls` che **non hanno righe** in questo
  ambiente (quindi non erano raggiungibili via URL nemmeno prima dello
  spostamento) — non è una regressione introdotta qui.

**Da fare**: verifica manuale via browser di tutti i moduli spostati
(l'utente ha detto di farla lui, segnalando eventuali malfunzionamenti).

## Rischi e note

- Le 5 classi "motore" (`CBController`, `ApiController`, `Controller`,
  `ExportData`, `ImportData`) restano in `packages/.../controllers/` — sono
  il prossimo pezzo, quello con il vero contratto esterno (i controller
  generati da interfaccia in produzione fanno
  `extends \crocodicstudio\crudbooster\controllers\CBController` per FQCN
  letterale), da affrontare con uno shim `class_alias()` discusso ma non
  ancora implementato.
- `packages/crocodicstudio/crudbooster/src/middlewares/CBBackend__.php`
  (doppio underscore, stesso pattern di redirect hardcoded di `CBBackend.php`)
  non è referenziato da nessuna parte del repo (verificato) — lasciato
  intoccato, sembra un file morto/di backup, da confermare ed
  eventualmente rimuovere in un intervento a parte.
- `routes.php` riga finale `$controllers_base_path = '\crocodicstudio\crudbooster\controllers\\';`
  risultava già inutilizzata in quel file prima di questo intervento —
  lasciata intoccata (dead code preesistente, fuori scope).
- Il bug `scandir()`/`is_dir()` corretto qui era latente da sempre
  (sarebbe scattato con qualunque sottocartella futura in
  `app/Http/Controllers/`), non introdotto da questo intervento, ma reso
  visibile da esso.

## Rollback

`git revert` del commit — tutte le modifiche sono behavior-preserving
(spostamento + aggiornamento riferimenti) più un fix di un bug latente
(scandir/is_dir), nessun cambiamento di dati o di schema coinvolto.
