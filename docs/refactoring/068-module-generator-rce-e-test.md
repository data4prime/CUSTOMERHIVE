# 068 - Module Generator: 3 RCE + 1 SQL injection corretti + test

- **Data**: 2026-09-03
- **Stato**: Completato
- **Area**: Sicurezza + Bug fix + test
- **File/aree di codice coinvolte**:
  - `app/Helpers/CRUDBooster.php`
  - `app/Http/Controllers/System/ModulsController.php`
  - `app/Helpers/functions.php`
  - `tests/Feature/ModuleGeneratorCrudTest.php` (nuovo)

## Contesto

Richiesta dall'utente un'analisi del modulo Module Generator
(`ModulsController` + `CRUDBooster::generateController()`, il wizard a 5
step che genera i controller CRUD dei moduli custom), sullo stesso modello
degli interventi precedenti su API Generator ([065](065-api-generator-rce-e-test.md))
e Statistic Builder ([067](067-statistic-builder-sql-arbitrario-e-test.md)).
L'analisi ha trovato la stessa classe di vulnerabilità del primo, in più
punti indipendenti, più una variante non ancora vista: SQL injection reale
dentro una funzione di introspezione schema di Laravel.

## Il problema

Ogni step del wizard scrive codice PHP letterale dentro il file del
controller generato (`app/Http/Controllers/{Controller}.php`, tra
marcatori `# START ... DO NOT REMOVE THIS LINE`), che Laravel autoload-a
immediatamente come controller live. Tre punti indipendenti incollavano
input utente grezzo dentro quel sorgente:

1. **`CRUDBooster::generateController()`** (`app/Helpers/CRUDBooster.php:1844`
   prima del fix): `$this->table = "' . $table . '";` — `$table` arriva da
   `ModulsController::postStep1()` **non risanificato** quando si seleziona
   una tabella "esistente" (il `<select>` è solo lato client, mai
   rivalidato server-side — solo il ramo "nuova tabella" passava da
   `ModuleHelper::sql_name_encode()`). Una virgoletta nel valore rompe il
   literal e inietta PHP arbitrario.
2. **`ModulsController::postStep3()`** (colonne lista): `column`, `name`,
   `join_table`, `join_field`, `width`, `callbackphp` incollati grezzi
   dentro literal a doppi/singoli apici per costruire `$this->col[] = [...]`
   (`query` aveva solo `addslashes()`, insufficiente). `callbackphp` è
   particolarmente critico: per design contiene una stringa di codice PHP
   (`"callback_php"=>'...'`), incollata senza alcun escaping.
3. **`ModulsController::postStep5()`** (configurazione): iterava su
   **ogni** chiave POST (tranne `_token`/`id`/`submit`) scrivendo
   `$this->{chiave} = "{valore}";` — sia la CHIAVE (nome di proprietà PHP)
   sia il VALORE erano controllati dall'attaccante, senza whitelist né
   escaping. **Questo metodo non aveva nemmeno il debole check `isView()`
   presente sugli altri step** - nessun controllo di privilegio.

**Aggravante comune ai tre**: gli step 3/4/5, quando ricaricati
(`getStep3`/`getStep4`/`getStep5`), fanno `eval()` sul codice estratto dal
file per ricostruire lo stato del wizard - un payload iniettato si esegue
**anche solo riaprendo il wizard**, non serve nemmeno visitare le route del
modulo generato.

## Una quarta scoperta, mentre si scriveva il test: SQL injection vera

Scrivendo il test di regressione per il punto 1, `CRUDBooster::pk($table)`
(chiamato da `generateController()` per calcolare `$this->orderby`) andava
in crash con un vero errore SQL, non solo una query "a vuoto":
`CB::pk()` chiama `Schema::getIndexes($table)`, e **la `quoteString()` di
Laravel usata da `MySqlGrammar::compileIndexes()` non è parametrizzata**
(`return "'$value'";`, zero escaping) - un apice nel valore rompe la query
SQL stessa. `$table` non sanificato in questa funzione non è quindi solo un
problema di introspezione fallita, è SQL injection reale contro il DB di
produzione.

## Correzione

- **`generateController()`**: `$table = ModuleHelper::sql_name_encode($table);`
  in testa alla funzione (stesso filtro già usato per ogni tabella/colonna
  creata da questo modulo) - chiude sia l'iniezione PHP sia la SQL
  injection in un colpo solo, per **qualunque** chiamante (anche
  `postAddSave()`/`postEditSave()`, non solo `postStep1()`). `$this->table`
  scritto comunque con `var_export()` (difesa in profondità). Sanificato
  anche `$controllername` (nome CLASSE e FILE, `var_export()` lì non aiuta)
  a `[A-Za-z0-9_]` - senza, un nome con `/`/`..` permetteva path traversal
  nel file scritto su disco (stesso fix già applicato a `$controllerName`
  in [065](065-api-generator-rce-e-test.md)).
- **`ModulsController::postStep1()`**: `table_name` ora passa SEMPRE da
  `ModuleHelper::sql_name_encode()` (prima solo per il ramo "nuova
  tabella") - non solo un input più sicuro per `generateController()`, ma
  anche un valore pulito persistito in `cms_moduls.table_name`.
- **`postStep3()`**: ogni valore dinamico in `$this->col[] = [...]`
  incollato via `var_export()` invece che concatenazione grezza. Bug
  indipendente corretto nello stesso punto: il flag "download" controllava
  `isset($id_download[$i])` - variabile mai definita, sempre falso - invece
  di `$is_download[$i]`, quindi non veniva mai scritto.
- **`postStep5()`**: whitelist esplicita sulle sole chiavi realmente
  esposte dal form (`title_field`, `limit`, `orderby`,
  `global_privilege`, i `button_*`) + `var_export()` sui valori. Aggiunto
  `CRUDBooster::isSuperadmin()` (mancava qualunque controllo), stesso
  livello già richiesto da `save_table()` (step2, la DDL) - aggiunto anche
  a `postStep3()` per lo stesso motivo (scrive nello stesso sorgente PHP
  live).

## Altri 2 problemi trovati e corretti nello stesso giro

- **`getDelete()` non ricontrollava `is_protected`**: `hook_query_index()`
  nasconde i moduli protetti dalla index list, ma `getDelete($id)` carica
  la riga per id senza rifiltrare - un id diretto bypassava il filtro,
  permettendo di cancellare/rompere un modulo di sistema (riga
  `cms_moduls` + file controller unlinkato in `hook_before_delete()` + voci
  di menu). Aggiunto il check + una null-safety mancante sull'id
  inesistente (crashava dereferenziando `$module->{title_field}` su
  `null`).
- **`getTableColumns()`/`getCheckSlug()` senza alcun controllo di
  privilegio**: il primo esponeva lo schema (nomi colonna) di QUALUNQUE
  tabella - incluse `cms_users`, `cms_apikey` - a chiunque fosse
  autenticato, a prescindere dal privilegio sul modulo. Aggiunto lo stesso
  check `isView()` usato dal resto del controller.

## Bug indipendente corretto per far passare i test

`add_log_ch()` (`app/Helpers/functions.php`) leggeva
`$_SERVER['REMOTE_ADDR']`/`$_SERVER['HTTP_USER_AGENT']` senza fallback -
andava in crash (`Undefined array key`) in un contesto senza quelle chiavi
(richieste HTTP simulate nei test, alcuni contesti CLI/proxy in
produzione). Corretto con `?? null` su entrambe: il log viene comunque
scritto, solo con `ip`/`useragent` nulli invece di far fallire l'intera
richiesta.

## Test

`tests/Feature/ModuleGeneratorCrudTest.php` (nuovo, 15 test, tutti
passano):
- **Regressione RCE/SQLi - `generateController()`** (2 test): table_name
  pericoloso sanificato prima di raggiungere sia il sorgente generato sia
  l'introspezione schema; nome con `/`/`..` sanificato, nessun file scritto
  fuori da `app/Http/Controllers/`.
- **Regressione RCE - `postStep3()`** (2 test): label/name/join/width/
  callback_php/query pericolosi salvati come dato letterale via
  `var_export()`; flag "download" scritto correttamente (bug fix).
- **Regressione RCE - `postStep5()`** (2 test): title_field/orderby
  pericolosi salvati come dato letterale, `table` sempre forzato dal
  valore reale in DB; chiave POST non whitelisted (es. `evil_property`)
  ignorata, mai scritta nel file.
- **Regressione privilegio** (2 test): `postStep3()`/`postStep5()`
  negano l'accesso a un non-superadmin anche con pieno accesso CRUD al
  modulo "Modules" (prova che il fix non dipende dai ruoli per-modulo).
- **Regressione `getDelete()`** (3 test): modulo `is_protected` non
  cancellabile via id diretto; id inesistente non va in crash; modulo non
  protetto resta cancellabile (nessuna regressione sul comportamento
  esistente).
- **Regressione `getTableColumns()`/`getCheckSlug()`** (4 test): negano
  l'accesso senza privilegio di visualizzazione, funzionano normalmente
  con privilegio.

Come per API Generator, i file generati/modificati finiscono dentro
l'albero sorgente vero e proprio (`app/Http/Controllers/`, tracciato da
git): ogni test traccia i file che tocca in `$fixtureFiles`, ripuliti in
`tearDown()` insieme a uno sweep di sicurezza su qualunque
`*PhpunitTest*.php` rimasto.

Suite completa eseguita in Docker: 172/172 test passano (157 precedenti +
15 nuovi), nessuna regressione.

## Rischi e note

- **Restano deliberatamente non corretti** (vedi anche backlog in
  `docs/refactoring/README.md`): `getStep1`-`postStep2`/`getStep4` restano
  protetti solo da `isView()` (permesso di sola visualizzazione, non
  create/update) - non estesi a `isSuperadmin()` in questo intervento per
  non rischiare di rompere un uso legittimo con permessi di sola view non
  ancora caratterizzato. Non sono più una RCE (il punto pericoloso,
  `generateController()`, è sanificato indipendentemente dal chiamante),
  restano solo un gap di autorizzazione funzionale.
- `postAddSave()`/`postEditSave()` (la creazione "rapida" di un modulo, non
  passando dal wizard) chiamano anch'essi `generateController()` con
  `table_name` non sanificato a monte - ora sicuri perché la sanificazione
  è dentro `generateController()` stessa, ma il valore grezzo può ancora
  finire in `cms_moduls.table_name` da quel percorso (non toccato, fuori
  scope di questo intervento incentrato sul wizard).
- Il fix è scoped al minimo necessario per chiudere le iniezioni trovate,
  non un audit completo del controller (1599 righe).

## Rollback

`git revert` del commit - ripristina le 3 RCE, la SQL injection e i 2 bug,
e rimuove i test. **Sconsigliato**: le vulnerabilità sono un rischio reale
su un'app in produzione.

Vedi anche [065](065-api-generator-rce-e-test.md),
[067](067-statistic-builder-sql-arbitrario-e-test.md),
[[project-production-clients]].
