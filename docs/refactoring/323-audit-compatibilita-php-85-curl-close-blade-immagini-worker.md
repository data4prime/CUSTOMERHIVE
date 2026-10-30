# 323 - Audit compatibilità PHP 8.5 e correzioni (curl_close, due blade non compilabili, immagini worker/scheduler)

- **Data**: 2026-10-29
- **Stato**: Completato
- **Area**: PHP 8.5 / Qualità / Docker
- **File/aree di codice coinvolte**:
  - `app/Helpers/CRUDBooster.php`
  - `app/Helpers/QlikHelper.php`
  - `app/Http/Controllers/System/AdminChatAIController.php`
  - `app/Services/QlikConnectionTester.php`
  - `app/Services/QlikSync/OnPremQlikDriver.php`
  - `app/Services/QlikSync/SaasQlikDriver.php`
  - `resources/views/modules/index.blade.php`
  - `resources/views/crudbooster/menus/form_body.blade.php`
  - (ambiente, non repo) immagini Docker locali dei servizi `worker` e `scheduler`

## Contesto

Il progetto usa PHP 8.5 (`Dockerfile` su `php:8.5-apache`, `composer.json`
richiede `>=8.5`, vedi [303](303-passaggio-a-php-8-5.md)). È stato chiesto un
controllo completo della compatibilità. Metodo, tutto con PHP 8.5.11 nel
container `app`, con `E_ALL`:

1. `php -l` con deprecazioni su 450 file `.php` di `app/`, `routes/`,
   `config/`, `database/`, `bootstrap/`, `tests/`, `lang/`: pulito.
2. Tutte le 299 viste Blade compilate col compilatore reale di Laravel e poi
   lint con PHP 8.5.
3. Lint di `vendor/`: solo 3 deprecazioni di tipo "nullable implicito"
   (`textalk/websocket` `Base.php:71` e `ConnectionException.php:16`, usato
   solo da `app/Services/QlikSync/SaasQlikDriver.php`; `hamcrest` solo dev).
4. PHPCompatibility con `testVersion 8.5`, installato in `/tmp` del container
   (fuori dal progetto): 0 errori, unico problema reale `curl_close()`.
5. `composer check-platform-reqs` tutto ok; nessuna costante
   `PDO::MYSQL_ATTR_*` in `config/database.php`.

Falsi positivi scartati: "mixed line endings" nei controller cliente e warning
su `routes/console.php` (closure `Artisan::command` legata a `$this`).

## Situazione prima

1. **10 chiamate a `curl_close()`**, deprecata in 8.5 (no-op dal 8.0):
   `CRUDBooster.php:1589`, `QlikHelper.php:342,348,359`,
   `AdminChatAIController.php:723,879`, `QlikConnectionTester.php:552,570`,
   `OnPremQlikDriver.php:43` (dentro un `try/finally` che conteneva solo
   quella), `SaasQlikDriver.php:32` (`__destruct`). Non rompeva nulla: Laravel
   non trasforma le deprecazioni in eccezioni, `display_errors` viene spento
   al bootstrap, `prod.ini` ha `E_ALL & ~E_DEPRECATED`; ma `local.ini` ha
   `display_errors=On` / `E_ALL`.
2. `resources/views/modules/index.blade.php:13`: apice non chiuso
   `__('crudbooster.description)` (parse error; vista senza riferimenti nel
   codice).
3. `resources/views/crudbooster/menus/form_body.blade.php:68`:
   `$join_query_{$join_table}`, sintassi rimossa in PHP 8.0 (parse error). La
   vista, inclusa da `menus/form.blade.php:79`, non compilava. Già annotato
   in `docs/architettura/lezioni.md` e nel test
   `tests/Feature/ChatAIAuthorizationTest.php:443`.
4. Container `scheduler` e `worker` in crash-loop (`Restarting 255`): immagini
   vecchie (3 settimane) con PHP 8.3.35, mentre `vendor/` richiede `>=8.5`
   (`platform_check.php:26`). Il `Dockerfile` era già a 8.5.

## Situazione dopo

1. Rimosse le 10 chiamate a `curl_close()`. In `OnPremQlikDriver::qrsGet` è
   tolto l'intero `try/finally`; `SaasQlikDriver::__destruct` mantiene
   `$this->curl = null`. L'handle curl si libera a fine scope. Rimossa anche
   una riga vuota residua in `QlikConnectionTester`.
2. Apice corretto in `modules/index.blade.php`.
3. `form_body.blade.php:68` ora è
   `$value = @${"join_query_".$join_table}->{$join_title};`, coerente con
   l'assegnazione di riga 66.
4. `docker compose build worker scheduler` + `up -d`: ora PHP 8.5.11, servizi
   Up; lo scheduler risponde "No scheduled commands are ready to run".

**Comportamento visibile**: un solo punto, `form_body.blade.php:68`. Prima la
vista non compilava su PHP 8 (in PHP 7 l'espressione dava `null`); ora, nel
ramo `$join && @$row` (solo se un campo del form ha `join`), `$value` è il
titolo del record joinato e la vista compila. Tutto il resto è invariato.

## Motivazione

- `curl_close()` è deprecata in 8.5 e inutile dal 8.0: rimuoverla evita
  avvisi visibili in locale (`display_errors=On`) e future rimozioni.
- Due blade non compilabili erano bombe a orologeria: la prima non è
  referenziata, la seconda è inclusa dalla schermata dei menu.
- Worker e scheduler in crash-loop significano code (mail, sync Qlik) e
  schedulazioni ferme in locale.
- Alternativa scartata: aggiornare `textalk/websocket` 1.5.8 → 1.6.3 per le
  2 deprecazioni "nullable implicito": tocca il sync Qlik SaaS, mai provato su
  Qlik reale (vedi sotto).

## Test

Nessuna suite lanciata (regola del repo). Verificato davvero:

- `php -l` con PHP 8.5 sui 6 file PHP toccati: ok.
- 299/299 viste Blade compilano e passano il lint 8.5.
- PHPCompatibility 8.5: nessun warning residuo.
- Chiavi i18n `crudbooster.name/description/price/action` presenti in en e it.
- Worker e scheduler ricostruiti: PHP 8.5.11, stato Up.
- Nessuna stringa nuova: i18n n/a.

Non verificato: render di `menus/form_body.blade.php` con un campo `join`
reale; percorsi Qlik/chat AI (poca copertura di test).

## Rischi e note

Rilievi del reviewer (`ch-reviewer`) su `form_body.blade.php`, ora esposti
perché la vista compila:

- (a) Riga 66 `$row->{"id_".$join_table}` senza controllo: se `$row` non ha la
  colonna dà "Undefined property" (con gli handler Laravel diventa
  `ErrorException`/500). Rischio preesistente.
- (b) `$join_arr[1]` senza controllo se `$join` non contiene la virgola
  (warning).
- (c) `array_walk($join_arr, 'trim')` (riga 61) non ha effetto (la callback
  agisce per valore): con "tab, col" lo spazio resta in `$join_title`.
- (d) Il valore del join sovrascrive `$value` impostato da
  `callback`/`callback_php` (righe 50-57).
- (e) Nessun test di render per questa vista: consigliato un test di
  caratterizzazione se la schermata menu verrà usata.

Altre note:

- `modules/index.blade.php`: vista senza riferimenti, correzione solo
  sintattica.
- `textalk/websocket` 1.5.8 ha 2 deprecazioni "nullable implicito" (solo
  avviso a compile-time al caricamento di `SaasQlikDriver`). Esiste la 1.6.3,
  **non aggiornata**: da valutare a parte (sync Qlik SaaS mai provato su Qlik
  reale).
- Le deprecazioni solo-runtime (indice `null` su array, cast float fuori
  range, destructuring di non-array) non sono rilevabili staticamente, e il
  canale log `deprecations` non è configurato in `config/logging.php` (le
  deprecazioni vengono scartate). Per coprirle serve una run della suite con
  deprecazioni loggate, solo su richiesta.
- Percorsi Qlik/chat AI con poca copertura di test: rimozione di no-op,
  rischio basso.
- Le immagini `worker`/`scheduler` sono ricostruite in locale (non è una
  modifica al repo): sui server di dev/staging/produzione la stessa
  ricostruzione va fatta a mano dalla persona, se le loro immagini sono
  precedenti al passaggio a 8.5.

## Rollback

- `git checkout` degli 8 file toccati (6 PHP + 2 Blade) elencati in testata.
- Le immagini non sono modifiche al repo: si ricostruiscono dal `Dockerfile`
  (`docker compose build worker scheduler`). Nessuna migrazione da annullare.
