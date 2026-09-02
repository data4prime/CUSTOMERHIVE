# 067 - Statistic Builder: SQL arbitrario corretto + 14 test

- **Data**: 2026-09-02
- **Stato**: Completato (parziale - vedi "Rischi e note")
- **Area**: Sicurezza + Bug fix + test
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/StatisticBuilderController.php`
  - `tests/Feature/StatisticBuilderCrudTest.php` (nuovo)

## Contesto

Richiesta dall'utente un'analisi del modulo Statistic Builder (dashboard
BI con widget configurabili: Small Box, Table, Chart Area/Bar/Line, Qlik)
in vista di test CRUD, sullo stesso modello degli interventi precedenti.
L'analisi ha trovato una vulnerabilità più grave di quella di API Generator
([065](065-api-generator-rce-e-test.md)): esecuzione di SQL arbitrario
contro il DB di produzione, senza alcun controllo di privilegio.

## Il problema

Alcuni widget (Small Box, Table, Chart Area/Bar/Line, Qlik - 6 dei 10 tipi
disponibili) hanno un campo "SQL Query" pensato per far scrivere all'admin
una query custom che popola il widget. Verificato nel codice:

- **`postSaveComponent()`** è l'**unico** punto di scrittura di `config`
  (compresa la chiave `sql`) - e non aveva **nessun** controllo di accesso,
  a differenza di `getBuilder()`/`getEditComponent()`/`getDeleteComponent()`
  nello stesso controller, che richiedono già `CRUDBooster::isSuperadmin()`.
- **`getViewComponent()`** (anch'essa senza alcun controllo di accesso)
  renderizza il widget: nel template del componente (es.
  `resources/views/crudbooster/statistic_builder/components/smallbox.blade.php`,
  ramo `$command == 'showFunction'`, stesso pattern in `table.blade.php` e
  in tutti i widget chart), per la chiave `sql`:
  ```php
  echo reset(DB::select($value)[0]);   // $value = config['sql'], testo grezzo
  ```
  Il testo salvato in `config['sql']` viene **eseguito direttamente** come
  SQL. Solo i placeholder `[NOME_SESSIONE]` vengono sostituiti (feature
  documentata nell'help text del form, non binding parametrico). Il
  `try/catch` attorno cattura solo l'eccezione di visualizzazione: se la
  query è una `UPDATE`/`DELETE` andata a buon fine e poi fallisce solo il
  fetch del risultato, l'effetto sul DB è già avvenuto.

**Conseguenza**: un utente con qualunque privilegio (non solo superadmin)
poteva scrivere una query arbitraria in un widget di **qualunque**
dashboard esistente (`componentid` non validato appartenere a una
dashboard "propria"), e vederla eseguita contro il DB reale quando
chiunque (anche un altro admin, se quella dashboard è impostata come
dashboard predefinita per il suo privilegio) visualizzava quella pagina.

## Verificato e SCARTATO come falso allarme

`component_name` (usato senza sanificazione in
`view('...components.' . $component_name, ...)` sia in
`getViewComponent()` che in `getEditComponent()`) sembrava un possibile
path traversal/LFI. Verificato leggendo
`Illuminate\View\FileViewFinder::getPossibleViewFiles()`
(`vendor/laravel/framework/.../FileViewFinder.php:146`): fa
`str_replace('.', '/', $name)` su **tutta** la stringa - qualunque `..`
nel valore (due punti letterali) viene convertito in `//` prima del
controllo `file_exists()`, quindi non sopravvive mai come vera risalita di
directory. L'attaccante può solo scegliere tra i file già presenti dentro
`.../components/`, nessuna fuga verso file esterni.

## Correzione

Aggiunto lo stesso controllo `CRUDBooster::isSuperadmin()` già usato da
`getBuilder()`/`getEditComponent()`/`getDeleteComponent()` in questo
stesso controller, ma **solo** a `postSaveComponent()`:

```php
if (!CRUDBooster::isSuperadmin()) {
    CRUDBooster::insertLog(trans("crudbooster.log_try_view", ['name' => 'Save Component', 'module' => 'Statistic']));
    return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
}
```

**Deliberatamente NON toccate** `getViewComponent()`/`getListComponent()`:
verificato che `show.blade.php` (la pagina di visualizzazione normale
delle dashboard, usata da **tutti** gli utenti con visibilità su quella
dashboard) e `builder.blade.php` (l'editor, solo superadmin) includono
entrambe lo stesso `statistic_builder/index.blade.php`, che chiama
`list-component`/`view-component` via AJAX per renderizzare i widget.
Limitarle al superadmin avrebbe rotto la visualizzazione delle dashboard
per chiunque non lo sia - una regressione seria su una funzionalità reale.
La scrittura (`postSaveComponent()`) era invece raggiungibile solo
dall'editor (già protetto da `isSuperadmin()` a monte, su
`getBuilder()`/`getEditComponent()`): nel flusso normale nessun utente
non-superadmin arrivava comunque a chiamarla, quindi il fix non cambia
nulla per l'uso legittimo.

## Test

`tests/Feature/StatisticBuilderCrudTest.php` (nuovo, 14 test, tutti
passano):
- **Regressione del fix** (2 test): un non-superadmin non riesce più a
  scrivere `config`; un superadmin continua a poterlo fare (prova che
  l'uso legittimo non si è rotto).
- **CRUD standard delle dashboard** (4 test): lista, creazione (slug
  derivato dal nome), modifica (**caratterizzazione**: lo slug non viene
  mai rigenerato - è il permalink pubblico della dashboard, comportamento
  voluto e già commentato nel codice), cancellazione (`cms_statistics` non
  ha `deleted_at`: DELETE fisica, stesso ramo di Settings/API Generator).
- **Visualizzazione dashboard `getShow()`** (3 test): slug inesistente
  (redirect gestito, già presente), layout assegnato (risolto da
  `dashboard_layouts`), nessun layout (griglia di default a 9 aree).
- **Controlli di accesso già esistenti** (2 test): `getBuilder()`/
  `getEditComponent()` negano l'accesso a un non-superadmin (pattern
  "normale", il check c'era già).
- **Caratterizzazione del gap generico, non corretto** (3 test):
  `postAddComponent()`/`postUpdateAreaComponent()`/`getListComponent()`
  restano raggiungibili da un utente senza alcun permesso - tripwire per
  quando quel lavoro verrà ripreso.

**Deliberatamente non testato in questo intervento** (deciso con
l'utente): il rendering end-to-end di un widget SQL configurato
correttamente (proverebbe che il fix non ha rotto l'uso legittimo del
widget più delicato del modulo, ma richiede di renderizzare Blade reali
con dipendenze esterne - icone Ionicons da un CSS vendorizzato, ecc. - più
fragile/complesso, rimandato).

Suite completa eseguita in Docker: 157/157 test passano (143 precedenti +
14 nuovi), nessuna regressione.

## Rischi e note

- **Restano deliberatamente non corretti** (decisi con l'utente, "il
  resto ci pensiamo più avanti"): nessun controllo di privilegio su
  `postAddComponent()`/`postUpdateAreaComponent()`/`getListComponent()`
  (nessuno di questi permette di iniettare SQL eseguibile, a differenza di
  `postSaveComponent()` - il `config` iniziale di un componente appena
  creato è sempre vuoto); nessuna validazione che `componentid`/
  `id_cms_statistics` appartengano alla dashboard su cui l'utente dovrebbe
  poter agire (IDOR); bug null-safety su `getBuilder()`/`getEditComponent()`/
  `getViewComponent()` con un id/componentID inesistente (crash 500 invece
  di un errore gestito - stessa classe di bug già corretta altrove in
  questa sessione, qui lasciata per un secondo giro).
- Il fix è scoped al minimo necessario per chiudere l'esecuzione SQL
  arbitraria (unico punto di scrittura del `config` pericoloso), non
  un audit completo del controller.

## Rollback

`git revert` del commit - ripristina la possibilità di scrivere SQL
arbitrario in un widget e rimuove i test. **Sconsigliato**: la
vulnerabilità è un rischio reale su un'app in produzione.

Vedi anche [065](065-api-generator-rce-e-test.md),
[[project-production-clients]].
