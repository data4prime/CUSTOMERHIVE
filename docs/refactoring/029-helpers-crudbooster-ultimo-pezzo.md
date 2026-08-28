# 029 - Helpers (7/7): CRUDBooster.php spostato — helpers/ non esiste più

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Architettura / CRUDBooster
- **File/aree di codice coinvolte**:
  - `app/Helpers/CRUDBooster.php` (nuovo, 2332 righe)
  - `packages/crocodicstudio/crudbooster/src/helpers/` (rimossa interamente — ultima cartella)
  - `packages/crocodicstudio/crudbooster/src/CRUDBoosterServiceProvider.php`
  - Gli altri 8 helper già spostati (`CB`, `ChatAIHelper`, `GroupHelper`,
    `MenuHelper`, `ModuleHelper`, `QlikHelper`, `TenantHelper`, `UserHelper`)
  - 5 controller in `app/Http/Controllers/System/`,
    `app/Http/Middleware/SetUserPreferredLanguage.php`, 4 view Blade
  - **Fix di un bug latente**: `AdminCmsUsersController.php`,
    `AdminGroupsController.php` (riferimenti a `MyHelper` rimasti rotti
    dall'intervento [023](023-helpers-nnhelper-moduleHelperhelper-myhelper.md))

## Contesto

Ultimo pezzo della serie [023](023-helpers-nnhelper-moduleHelperhelper-myhelper.md)-[028](028-helpers-functions-globali.md).
`CRUDBooster.php` è il file più grosso e centrale del pacchetto (2332
righe, 101 metodi), aliasato globalmente e referenziato (per FQCN, dagli
altri 8 helper già spostati) o tramite l'alias corto ovunque nell'app.
Confermato, come per tutti gli altri helper: **nessun controller custom
cliente lo referenzia per FQCN** — solo tramite l'alias globale.

Per un file di queste dimensioni, spostamento fatto con `cp` a livello
filesystem + modifica della sola riga di namespace (stessa cautela già
usata per `CBController.php` in [015](015-cbcontroller-shim-class-alias.md)),
verificato con un diff automatico che il resto sia identico.

**Durante la scansione finale di tutti i riferimenti**, trovato un bug
latente non causato da questo intervento ma da uno precedente:
`app/Http/Controllers/System/AdminCmsUsersController.php` e
`AdminGroupsController.php` importavano ancora
`\crocodicstudio\crudbooster\helpers\MyHelper` — namespace che non esiste
più da quando `MyHelper` è stato spostato in
[023](023-helpers-nnhelper-moduleHelperhelper-myhelper.md). La ricerca dei
riferimenti esterni fatta in quel momento non li aveva individuati.
Essendo `MyHelper::is_int()` chiamato solo dentro metodi che gestiscono il
salvataggio di utenti/gruppi (non nel percorso eseguito da una richiesta
`GET` qualunque), il problema è rimasto silente finché non è stata fatta
la scansione repo-wide completa per questo intervento. Corretto in questa
stessa modifica.

## Situazione prima

`CRUDBooster.php` in `packages/.../helpers/`, ultimo file rimasto in
quella cartella (gli altri 8 già spostati nei lotti precedenti).

## Situazione dopo

- Spostato in `App\Helpers\CRUDBooster` (contenuto identico, verificato
  con diff automatico escludendo la sola riga di namespace).
- `packages/crocodicstudio/crudbooster/src/helpers/` **non esiste più**
  (cartella vuota rimossa).
- `AliasLoader::alias('CRUDBooster', ...)` ripuntato a `App\Helpers\CRUDBooster`.
- Aggiornati i `use` nei restanti 8 helper (ora tutti nello stesso
  namespace `App\Helpers` — import diventati tecnicamente
  auto-referenziali/ridondanti ma lasciati per coerenza con gli altri
  lotti, innocui) e nei 9 file esterni noti (5 controller, 1 middleware,
  4 view/route).
- **Corretti i 2 riferimenti rotti a `MyHelper`** in
  `AdminCmsUsersController.php`/`AdminGroupsController.php`.

## Motivazione

Chiude interamente la cartella `helpers/`: tutte le classi vivono ora in
`App\Helpers`, stessa struttura standard usata per le classi motore
CRUDBooster ([012](012-controller-motore-shim-class-alias.md)-[017](017-rimozione-cartella-controllers-legacy.md)).
Nessuno shim `class_alias()` necessario in nessun punto della serie
helpers — a differenza dei controller, zero contratto FQCN esterno da
proteggere.

## Test

- Diff automatico riga per riga (esclusa la riga di namespace): zero
  differenze tra vecchio e nuovo file.
- `php -l` su tutti i 16 file PHP toccati: nessun errore (stessa
  deprecation preesistente, invariata).
- Compilazione isolata delle 4 view Blade toccate: tutte OK.
- **Scansione repo-wide finale**: zero riferimenti al vecchio namespace
  `crocodicstudio\crudbooster\helpers` in tutto il repo, salvo il file
  morto già noto (`sidebar.blade copy.php`).
- `composer dump-autoload`; `class_exists('App\Helpers\CRUDBooster')` →
  vero; `get_parent_class(new App\Helpers\CB())` → `App\Helpers\CRUDBooster`
  (corretto: prima del suo spostamento risolveva ancora al vecchio
  namespace, ora risolve al nuovo — la catena di ereditarietà segue
  correttamente lo spostamento finale);
  `App\Helpers\MyHelper::is_int('42')` → vero.
- `php artisan route:list`: 486 rotte, invariato.
- `curl` senza sessione su `/admin`, `/admin/groups`, `/admin/logs`,
  `/admin/users`: tutti 302, nessun 500.

## Rischi e note

- Con questo intervento **l'intera cartella `packages/crocodicstudio/crudbooster/src/helpers/`
  è stata eliminata** — non resta nulla da spostare in questa serie.
  Restano nel pacchetto solo `assets/`, `fonts/`, `middlewares/` (2 file
  vivi), `views/`, `CRUDBoosterServiceProvider.php`, `routes.php`.
- Il bug dei riferimenti rotti a `MyHelper` era presente **dall'intervento
  023** (quindi per l'intera durata dei lotti 024-028) ma non è mai stato
  eseguito realmente (nessun salvataggio di utenti/gruppi effettuato in
  quella finestra) — nessun impatto noto in produzione, ma da tenere a
  mente: la verifica "grep dei riferimenti esterni" fatta *prima* di
  ogni spostamento va sempre ripetuta con una scansione finale
  *dopo*, sull'intero repo, per essere certi di non aver perso nulla —
  applicato qui, da applicare anche a `middlewares/`/`commands/`/altre
  cartelle già "chiuse" se si torna a toccarle.

## Rollback

`git revert` del commit — ripristina `CRUDBooster.php` nel pacchetto,
tutti i riferimenti, e (attenzione) reintroduce anche il bug su
`MyHelper` corretto qui — da tenere presente se si fa un revert parziale.
