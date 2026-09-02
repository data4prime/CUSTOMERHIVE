# 064 - Modulo Settings: 3 bug reali corretti + 27 test CRUD

- **Data**: 2026-09-02
- **Stato**: Completato
- **Area**: Bug fix + test
- **File/aree di codice coinvolte**:
  - `app/Helpers/CRUDBooster.php`
  - `app/Http/Controllers/System/SettingsController.php`
  - `app/Http/Controllers/System/ApiCustomController.php`
  - `tests/Feature/SettingsCrudTest.php` (nuovo)
  - `tests/Concerns/SeedsCmsData.php`

## Contesto

Richiesta dall'utente un'analisi del modulo Settings in vista di test CRUD,
sullo stesso modello dei moduli precedenti (Tenants/Groups/Privileges/Users,
Menu Management - vedi [063](063-menu-management-bug-e-test-crud.md)).
Settings e' un modulo ibrido: meta' motore CBController standard su
`cms_settings` (righe singole: add/edit/delete), meta' due schermate custom
scritte a mano che sono il vero front-end usato dagli utenti
("Impostazioni" per gruppo): `getShow()` (mostra i setting di un gruppo) +
`postSaveSetting()` (li salva tutti insieme). L'analisi ha trovato 3 bug
reali, tutti corretti prima di scrivere i test.

## Bug 1: `CRUDBooster::valid()` chiamava `exit()` invece di tornare una Response

Stessa classe di problema gia' risolta in una sessione precedente per
`CRUDBooster::redirect()`/`CBController::validation()` (commit `4a5d5047`),
ma mai applicata a questo metodo. Usato per validare gli upload in
`postSaveSetting()` (`upload_image`/`upload_file`):

```php
$res = redirect()->back()->with([...])->withInput();
Session::driver()->save();
$res->send();
exit;
```

Quando la validazione di un file caricato falliva (mime sbagliato, troppo
grande), il codice inviava la response e poi terminava il processo con
`exit`. In produzione funzionava (la response era comunque gia' stata
inviata), ma **rendeva impossibile scrivere un test PHPUnit per questo
scenario**: un `exit()` in un test termina l'intero processo di test, non
solo il test corrente. Raggio d'azione contenuto: solo 2 punti di chiamata
in tutto il repo, entrambi con `$type='view'`
(`SettingsController::postSaveSetting()` e
`ApiCustomController::getStatusApikey()`).

**Correzione**: `valid()` ora ritorna la Response (stesso pattern di
`CRUDBooster::redirect()`/`CBController::validation()`, comprensivo dello
stesso commento "va sempre preceduto da `return`"). Aggiornati i 2
chiamanti per fare `return` sul risultato invece di ignorarlo.

## Bug 2: cancellare una riga di setting non invalidava la cache

`cms_settings` non ha `deleted_at`/`deleted_by`: `CBController::getDelete()`
(ereditato, non sovrascritto da `SettingsController`) fa quindi una `DELETE`
fisica. Ne' la base class ne' `SettingsController` definivano
`hook_before_delete()`/`hook_after_delete()` (entrambi no-op nella base
class). Siccome `CRUDBooster::getSetting()` mette il valore in cache con
`Cache::forever()`, cancellare la riga **non faceva sparire il setting dal
comportamento dell'app**: chiunque lo leggesse via `getSetting()`
continuava a vedere il vecchio valore indefinitamente, finche' la cache non
veniva svuotata manualmente. Bug di correttezza, non solo di risorse -
stessa causa gia' risolta correttamente per la modifica
(`hook_after_edit()` fa gia' `Cache::forget()`), mai estesa alla
cancellazione.

**Correzione**: aggiunto `hook_before_delete()` a `SettingsController` (gira
prima della `DELETE` vera e propria, quindi con la riga ancora leggibile) -
`Cache::forget('setting_'.$row->name)`.

## Bug 3: cancellare una riga con file associato lasciava il file orfano

Stessa causa del bug 2 (nessun hook su delete): un setting
`upload_image`/`upload_file` cancellato non rimuoveva il file fisico
associato, a differenza di `getDeleteFileSetting()` (che azzera solo il
*contenuto* di un setting, non l'intera riga) che lo fa gia' correttamente
tramite `public_path()` + `unlink()` (vedi
[007](007-upload-path-relativo.md)).

**Correzione**: stesso `hook_before_delete()` del bug 2 riusa la logica di
pulizia file gia' corretta di `getDeleteFileSetting()`.

## Comportamento verificato e SCARTATO come falso allarme

`postSaveSetting()`/`hook_before_add()` usano `str_random()`/`str_slug()`,
helper rimossi dai global helpers di Laravel 8+: sembravano un'altra
regressione dell'upgrade Laravel 9→13 (commit `2a4dab24`). Verificato che
`laravel/helpers` (`^1.6`) e' gia' presente in `composer.json` - li
polyfilla, nessun problema reale.

## Test

`tests/Feature/SettingsCrudTest.php` (nuovo, 27 test, tutti passano):
CRUD standard delle righe (lista, creazione con slug, blocco duplicati,
invalidazione cache su modifica, le due regressioni dei bug 2/3 sulla
cancellazione riga, guardia su content vuoto), `getShow()` (accesso negato,
filtro per gruppo, riparazione automatica delle label vuote),
`postSaveSetting()` (accesso negato, campi assenti nella request ignorati,
svuotamento di un campo di testo, password vuota non sovrascrive/valorizzata
aggiorna, upload immagine valido con path relativo, upload non valido
rifiutato - **ora testabile grazie al fix del bug 1** -, upload con
estensione non ammessa rifiutato, scrittura su storage fallita non azzera
il valore precedente, invalidazione cache multipla), `getDeleteFileSetting()`
(accesso negato - il bug piu' a rischio trovato in analisi, prima nessun
controllo -, rimozione file + azzeramento content, content gia' vuoto non fa
nulla, invalidazione cache), `CRUDBooster::getSetting()` (lettura + cache
forever, la cache ignora i cambi nel DB finche' non invalidata, nome
inesistente → null).

`tests/Concerns/SeedsCmsData.php`: nuovo helper `seedSetting()` (stesso
pattern di `seedMenu()`).

Per gli upload riusciti/rifiutati via `postSaveSetting()` si usa
`Storage::fake('local')` (il disco di default configurato in
`config/filesystems.php`) - nessun file reale toccato. Per
`getDeleteFileSetting()`/la cancellazione riga, il codice applicativo legge
e cancella il file via `public_path()` diretto (non passa dallo Storage
facade, quindi `Storage::fake()` non lo intercetta): questi test scrivono
un file reale sotto `public/storage/uploads/phpunit-settings-test/`,
ripulito in `tearDown()` ad ogni test (verificato che non lasci residui sul
filesystem del container dopo l'esecuzione).

Suite completa eseguita in Docker: 118/118 test passano (91 precedenti + 27
nuovi), nessuna regressione.

## Rischi e note

- Il test `test_postsavesetting_upload_fallito_lato_storage_non_azzera_il_valore`
  sostituisce l'intero facade Storage con un mock (`Storage::shouldReceive`)
  per simulare `putFileAs()` che ritorna `false` (caso reale: directory non
  scrivibile lato server) - piu' fragile degli altri test se in futuro
  `postSaveSetting()` iniziasse a chiamare altri metodi Storage nello stesso
  percorso.
- Il fix del bug 1 tocca anche `ApiCustomController::getStatusApikey()`
  (unico altro chiamante di `CRUDBooster::valid()`), non coperto da un test
  dedicato in questo intervento - fuori scope (modulo API Generator, non
  Settings).

## Rollback

`git revert` del commit - ripristina i 3 bug e rimuove i test.
