# 027 - Helpers (5/N): UserHelper spostato in App\Helpers

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Architettura / CRUDBooster
- **File/aree di codice coinvolte**:
  - `app/Helpers/UserHelper.php` (nuovo)
  - `packages/crocodicstudio/crudbooster/src/helpers/UserHelper.php` (eliminato)
  - `packages/crocodicstudio/crudbooster/src/CRUDBoosterServiceProvider.php`
  - `packages/crocodicstudio/crudbooster/src/helpers/CRUDBooster.php`
  - I 5 helper già spostati (`ChatAIHelper`, `LicenseHelper`, `MenuHelper`,
    `ModuleHelper`, `QlikHelper`)
  - 10 controller/comandi in `app/`, `app/Menu.php`,
    `statistic_builder/components/modulewidget.blade.php`

## Contesto

Continua [023](023-helpers-nnhelper-moduleHelperhelper-myhelper.md)-[026](026-helpers-qlikhelper-modulehelper.md).
`UserHelper` è tra gli 8 helper aliasati globalmente ed è il più
richiamato internamente: tutti e 5 gli helper già spostati in questo
lotto (`ChatAIHelper`, `LicenseHelper`, `MenuHelper`, `ModuleHelper`,
`QlikHelper`) lo importavano ancora dal vecchio namespace (aggiunto nei
lotti precedenti proprio per questo). Più altri 10 file esterni.

## Situazione prima

`UserHelper.php` nel pacchetto, chiama internamente `CRUDBooster::` (non
ancora spostato, bare). Riferimenti esterni noti: i 5 helper già mossi +
10 controller/comandi/model con un `use` pulito ciascuno (nessun FQCN
inline questa volta).

## Situazione dopo

- Spostato in `App\Helpers\UserHelper`, aggiunto
  `use crocodicstudio\crudbooster\helpers\CRUDBooster;` (bare, non ancora
  spostato).
- `AliasLoader::alias('UserHelper', ...)` ripuntato a `App\Helpers\UserHelper`.
- Aggiornati i `use` in tutti i 15 file esterni noti (5 helper +
  10 file in `app/`/view).
- `CRUDBooster.php`: **bug di distrazione trovato e corretto durante
  questo intervento** — il file aveva già un `use crocodicstudio\crudbooster\helpers\UserHelper;`
  preesistente (riga 32, sopravvissuto ai lotti precedenti perché mai
  toccato prima d'ora); il primo tentativo di aggiungere
  `use App\Helpers\UserHelper;` insieme agli altri import del lotto 4
  ([026](026-helpers-qlikhelper-modulehelper.md)) ha creato temporaneamente
  un **doppio `use` per lo stesso nome corto** (`UserHelper` importato sia
  dal vecchio sia dal nuovo namespace). Rilevato subito con un controllo
  esplicito di duplicati sulle righe `use` prima di procedere alla verifica
  in Docker — corretto aggiornando la riga preesistente invece di
  aggiungerne una nuova, poi rimossa la riga duplicata aggiunta per errore.

## Motivazione

Prosegue lo svuotamento di `helpers/`. L'episodio del doppio `use` è stato
usato per introdurre un controllo sistematico (grep di duplicati sulle
righe `use` di ogni file toccato) da ripetere anche nei prossimi lotti.

## Test

- Prima della verifica in Docker: controllo esplicito di righe `use`
  duplicate (stesso nome corto importato due volte) su tutti i file
  `app/Helpers/*.php` e su `CRUDBooster.php` — zero duplicati dopo la
  correzione.
- `php -l` su tutti i 17 file toccati: nessun errore (stessa deprecation
  preesistente in `CRUDBooster.php`).
- Compilazione isolata di `modulewidget.blade.php`: OK.
- Grep di conferma: zero riferimenti al vecchio namespace per `UserHelper`
  in tutto il repo.
- `composer dump-autoload`; `class_exists('App\Helpers\UserHelper')` →
  vero; `get_parent_class(new App\Helpers\CB())` →
  `crocodicstudio\crudbooster\helpers\CRUDBooster` (corretto, invariato:
  `CRUDBooster` non è ancora stato spostato).
- `php artisan route:list`: 486 rotte, invariato.
- `curl` senza sessione su `/admin`, `/admin/groups`, `/admin/logs`: 302,
  nessun 500.

## Rischi e note

- Restano da spostare: `Helper.php` (funzioni globali, solo un cambio di
  path nel `require`) e `CRUDBooster.php` per ultimo (80 KB, 101 metodi)
  — vedi [`README.md`](README.md#roadmap-uscita-da-crudbooster-packages).
  Con `CRUDBooster.php` prossimo passo, prestare attenzione a eventuali
  `use` preesistenti per FQCN già spostati (stesso tipo di errore corretto
  qui) prima di aggiungerne di nuovi.

## Rollback

`git revert` del commit — ripristina `UserHelper.php` nel pacchetto e
tutti i riferimenti, nessun impatto su codice esterno.
