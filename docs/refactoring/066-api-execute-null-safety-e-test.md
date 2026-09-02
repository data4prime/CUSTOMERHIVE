# 066 - API Generator: null-safety in execute_api() + test sul comportamento a runtime

- **Data**: 2026-09-02
- **Stato**: Completato
- **Area**: Bug fix + test
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/ApiController.php`
  - `tests/Feature/ApiExecuteTest.php` (nuovo)
  - `tests/Feature/ApiCustomCrudTest.php`

## Contesto

Seguito diretto di [065](065-api-generator-rce-e-test.md): quell'intervento
copre `ApiCustomController::postSaveApiCustom()` (creazione/modifica della
configurazione di una API) ma non il comportamento a runtime di
`ApiController::execute_api()` — il metodo che gestisce davvero una
richiesta a `/api/{permalink}`, applicando `parameters`/`responses`/`aksi`
letti da `cms_apicustom` **a ogni richiesta** (non "cotti" nel file
generato, solo il `permalink` lo è). Richiesta dall'utente un'analisi di
come testare questo comportamento (parametri, risposte, ecc.).

## Bug trovato e corretto

Scrivendo il test sul caso "permalink senza una riga `cms_apicustom`
corrispondente", `execute_api()` crashava con 500 invece di mostrare il
messaggio d'errore già previsto per questo caso esatto:

```php
$row_api = DB::table('cms_apicustom')->where('permalink', $this->permalink)->first();

$action_type = $row_api->aksi;      // <- crash se $row_api e' null
$table = $row_api->tabel;           // <- crash se $row_api e' null
$pk = CRUDBooster::pk($table);
...
if ($row_api->method_type) { ... }  // <- crash se $row_api e' null
...
// molto più sotto:
if (!$row_api) {
    $result['api_status'] = 0;
    $result['api_message'] = 'Sorry this API endpoint is no longer available...';
    goto show;
}
```

Il controllo "riga esistente" c'era già, ma troppo tardi: `$row_api` veniva
dereferenziato tre volte prima di arrivarci. Stessa classe di bug
null-safety già vista più volte in questa sessione (Menu Management,
Settings, API Generator in 065).

**Correzione**: il controllo `if (!$row_api) { ... goto show; }` è stato
spostato subito dopo la lettura di `$row_api`, prima di qualunque
dereferenziazione. Il controllo duplicato più avanti nel metodo è stato
lasciato (ora irraggiungibile ma innocuo — diff minimo, comportamento
identico in tutti i casi raggiungibili).

## Come si testa il comportamento a runtime (approccio scelto)

`execute_api()` non è raggiungibile in modo affidabile attraverso la
catena reale `postSaveApiCustom()` → `generateAPI()` → file scritto su
disco → rotta dinamica, perché per una tabella di sistema (non `mg_*`)
quel file oggi non è caricabile (namespace del controller "figlio" non
risolvibile — vedi "Rischi e note" in
[065](065-api-generator-rce-e-test.md)) e comunque non aggiungerebbe
copertura sulla logica qui testata, che è interamente guidata dal DB a
runtime (`parameters`/`responses`/`aksi`/`sql_where` riletti per
`permalink` a ogni richiesta).

**Scelto invece**: registrare in `setUp()` una rotta ad-hoc che istanzia
`ApiController` direttamente (stesso pattern usato da `CBBackendTest` per
isolare un middleware), bypassando del tutto la generazione/scrittura file.
La sottoclasse anonima dichiara la proprietà `$controller` (assente sulla
classe base — impostarla dall'esterno senza dichiararla creerebbe una
proprietà dinamica, deprecata in PHP 8.2+) e la valorizza con un
controller "figlio" reale (`SettingsController`, sulla tabella
`cms_settings`) — necessario perché `execute_api()` lo usa per
`cbInit()`/`ModuleHelper::can_view()` sul ramo `detail` anche per un
attore superadmin.

Questi test **non** passano dal middleware `CBAuthAPI`/
`CRUDBooster::authAPI()` (token+timestamp+user agent) — la rotta ad-hoc non
lo applica deliberatamente, per isolare la logica di `execute_api()` da
quel layer, separato e non ancora testato (vedi backlog in
[README](README.md), stessa voce sulla modernizzazione dell'autenticazione
API discussa con l'utente). L'unica autenticazione qui in gioco è quella
INTERNA di `execute_api()`: l'header `X-user` con l'email di un
`cms_users` attivo (`ApiController::login()`).

## Test

`tests/Feature/ApiExecuteTest.php` (nuovo, 5 test):
- `test_execute_api_list_rispetta_i_parametri_e_le_risposte_configurate` —
  un parametro (`group_setting`) filtra i risultati, solo i campi
  configurati come risposta (`used=1`) compaiono nell'output.
- `test_execute_api_detail_rispetta_le_risposte_configurate` — stesso, con
  una scoperta di comportamento non ovvia: a differenza di `list` (righe
  annidate sotto `data`), `detail` fa il merge dei campi della riga
  direttamente nel livello superiore della response
  (`array_merge($result, (array) $rows)`).
- `test_execute_api_senza_header_x_user_viene_rifiutato`.
- `test_execute_api_rifiuta_il_metodo_http_non_configurato`.
- `test_execute_api_su_permalink_inesistente_non_va_in_crash` —
  regressione del bug corretto sopra.

`tests/Feature/ApiCustomCrudTest.php` — un test aggiunto,
`test_creazione_api_salva_i_parametri_e_le_risposte_configurate`: verifica
il livello "persistenza" (che `postSaveApiCustom()` salvi `parameters`/
`responses` esattamente come inviati dal form), complementare al nuovo
file che copre il livello "comportamento a runtime".

Suite completa eseguita in Docker: 143/143 test passano (137 precedenti +
6 nuovi), nessuna regressione. Verificato nessun residuo di file generati
nel filesystem del container dopo l'esecuzione.

## Rischi e note

- Non è stato verificato il comportamento di `execute_api()` per `aksi`
  diversi da `list`/`detail` (`insert`/`update`/`delete` — visti scorrendo
  il file ma non testati in questo intervento) né la validazione dei
  singoli `type` di parametro oltre al caso base (`exists`, `unique`,
  `date_format`, ecc.) — fuori scope, il file è di 901 righe e questo
  intervento si è concentrato su ciò che serviva a rispondere alla
  domanda specifica dell'utente (parametri/risposte su list/detail).
- Il layer `CBAuthAPI`/`CRUDBooster::authAPI()` (token+timestamp+user
  agent) resta non testato — deliberatamente fuori scope qui, vedi
  backlog in [README](README.md).

## Rollback

`git revert` del commit — ripristina il crash su permalink inesistente e
rimuove i test.

Vedi anche [065](065-api-generator-rce-e-test.md).
