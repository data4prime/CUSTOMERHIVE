# 007 - Upload file: salvato path relativo invece di URL assoluto, fix controllo "file rotto"

- **Data**: 2026-08-27
- **Stato**: Completato
- **Area**: Upload/File
- **File/aree di codice coinvolte**:
  - `packages/crocodicstudio/crudbooster/src/helpers/CRUDBooster.php` (`uploadFile()`)
  - `packages/crocodicstudio/crudbooster/src/views/default/type_components/upload/component.blade.php`
  - `database/migrations/2026_08_27_020000_convert_absolute_upload_urls_to_relative_paths.php` (nuovo)

## Contesto

Emerso testando manualmente [006](006-controller-sistema-app-http-controllers-system.md):
caricando un logo su un Tenant, il campo mostrava "Oops looks like File was
Broken! Click Delete and Re-Upload." nonostante il file fosse stato
salvato correttamente. Prima causa trovata e corretta a parte: il symlink
`public/storage` era rotto (era un file di testo tracciato in git col path
dello staging). Dopo quel fix il problema persisteva comunque, e ha
portato a una domanda più di fondo dall'utente: perché il meccanismo di
upload salva un URL assoluto invece di un path relativo?

## Situazione prima

- `CRUDBooster::uploadFile()` salvava nel DB l'**URL assoluto completo**
  (`$protocol://$host/storage/uploads/{userID}/{Y-m}/{filename}`),
  costruito da `$_SERVER['HTTP_HOST']` al momento dell'upload. Il codice
  aveva già un tentativo precedente commentato che salvava solo il path
  relativo (`//return '/storage'.$file_path . '/' . $filename;`), poi
  scartato.
- Il componente che mostra il campo (`component.blade.php`) verificava se
  il file era "rotto" con una **vera richiesta HTTP** (`checkHttpStatus()`,
  cURL) verso l'URL salvato — non un controllo su disco (anche qui c'era
  un tentativo precedente commentato: `//if(Storage::exists($value) ||
  file_exists($value)):`).
- Conseguenza concreta: in sviluppo locale via Docker, l'host visto dal
  browser include la porta pubblicata (`localhost:8080`), ma quella porta
  **non è raggiungibile dall'interno del container** (Apache ascolta sulla
  80, la 8080 è solo il mapping Docker verso l'host) — verificato con un
  `curl` diretto dal container che falliva con "Could not connect to
  server". Il controllo HTTP falliva quindi **sempre**, anche a file
  perfettamente integro, mostrando il messaggio di errore per qualunque
  immagine appena caricata in locale.
- Più in generale (a prescindere dal problema di porta): un URL assoluto
  salvato nel DB si "congela" sull'host/protocollo visti al momento
  dell'upload — si romperebbe comunque con un cambio di dominio o
  http→https, indipendentemente da Docker.

## Situazione dopo

- `uploadFile()` ora ritorna solo il path relativo alla public root
  (`/storage/uploads/{userID}/{Y-m}/{filename}`), da salvare così com'è.
- `component.blade.php`: rimossa la funzione `checkHttpStatus()` (cURL) e
  la richiesta HTTP; il controllo "file esiste" ora usa
  `file_exists(public_path($value))` — un controllo diretto sul disco
  (attraverso il symlink `public/storage`), niente più round-trip HTTP né
  dipendenza da quale host/porta sia raggiungibile. La build dell'URL da
  mostrare (`asset($value)`) era già corretta e non è cambiata: con
  `APP_URL` vuoto (voluto, vedi `.env.example`) Laravel lo deduce dalla
  richiesta corrente, quindi il link mostrato è sempre giusto per
  l'ambiente/dominio/porta attuali.
- **Nuova migration**, generica invece che mirata alle sole colonne note:
  scansiona `INFORMATION_SCHEMA` di tutte le colonne testuali di tutte le
  tabelle base del DB corrente, e per ognuna cerca valori che combaciano
  col pattern `schema://host/storage/uploads/...` (indipendentemente da
  quale host/porta avessero), tagliando via schema+host e lasciando solo
  il path relativo. Non serve conoscere in anticipo quali tabelle/colonne
  hanno campi upload — funziona sia sui campi "di sistema" (`tenants.logo`,
  `cms_users.photo`, ecc.) sia su eventuali moduli creati da interfaccia in
  produzione, che da questo repo non si possono enumerare.
- Verificato che l'unico altro punto realmente affetto dal cambio erano
  `CBController::postUploadSummernote()`/`postUploadFile()` (upload da
  editor di testo ricco), che già facevano `echo asset($file)` sul
  risultato — prima si rompevano anche loro silenziosamente (URL doppio,
  `asset()` su un valore già assoluto), ora funzionano correttamente come
  effetto collaterale positivo del fix.

## Motivazione

Il bug del symlink locale ha reso visibile un problema di design più
profondo (salvare uno stato derivato — l'host — insieme al dato vero, il
path del file) che si sarebbe ripresentato in qualunque altro cambio di
ambiente/dominio, non solo in locale. Correggere solo il symlink avrebbe
lasciato il vero difetto intatto.

## Test

Verificato manualmente (nessun test automatico esistente su questo
componente):
- `php -l` su entrambi i file PHP toccati e sulla migration: nessun errore.
- Provata a mano la query SQL della migration (`SELECT ... SUBSTRING(...)`)
  sul valore reale di `tenants.logo` prima di eseguirla, per confermare
  che producesse il risultato atteso.
- Migration eseguita per davvero su `customerhive` (dev): 1 riga corretta
  in `tenants.logo` (unica presente al momento), loggata
  (`storage/logs/laravel.log`), nessun errore/warning sulle altre colonne
  scansionate.
- Rifetchata la pagina di edit del tenant (via sessione autenticata reale,
  non solo l'HTML statico): il messaggio "File was Broken" non compare
  più, l'immagine è renderizzata con l'URL corretto, e l'URL stesso
  risponde 200.

## Aggiunta successiva: stesso bug nella pagina di dettaglio

Segnalato dall'utente su `/admin/users/detail/1`: il campo "Photo" non
mostrava l'immagine. Causa gemella a quella descritta sopra, ma in un
file diverso non toccato dal fix originale:
`packages/crocodicstudio/crudbooster/src/views/default/type_components/upload/component_detail.blade.php`
(usato solo dalla pagina di **dettaglio**, non da quella di modifica) aveva
lo stesso controllo sbagliato (`Storage::exists($value) || file_exists($value)`
su un path relativo alla public root) — corretto allo stesso modo
(`file_exists(public_path($value))`), con l'aggiunta di un controllo che
`$value` non sia vuoto (altrimenti `public_path('')` è la cartella
`public/` stessa, che esiste sempre, facendo apparire un'immagine anche
per campi senza foto). Verificato: con foto impostata l'immagine compare
correttamente; senza foto la cella resta vuota (nessuna immagine rotta).

## Aggiunta successiva: nessuna immagine di riserva sul dettaglio utente

L'utente ha fatto notare che `/admin/users` mostra un'immagine anche per
gli utenti senza foto caricata (avatar di riserva in base al ruolo, via
`User::photo()`/`UserHelper::icon()`, usati dalla colonna con `'image'=>1`
in `CBController` per la lista), mentre `/admin/users/detail/{id}` non
mostrava nulla — incoerente. Aggiunto lo stesso fallback anche in
`component_detail.blade.php`, ma **solo** quando `$table === 'cms_users'
&& $name === 'photo'`: `UserHelper::icon()` cerca uno user per id, quindi
usarlo genericamente per qualunque campo upload di qualunque modulo (come
già fa, senza guardia, la lista) mostrerebbe l'avatar di uno user scelto
a caso in base all'id della riga corrente su una tabella diversa da
`cms_users` — qui limitato di proposito al solo caso in cui ha senso.
Verificato che il fallback compaia sul dettaglio utente senza foto, e che
il dettaglio tenant (altra tabella, altro campo upload) resti invariato.

## Rischi e note

- **Trovato ma non toccato** (fuori scope): `CRUDBooster::uploadBase64()`
  ha la stessa idea giusta (ritorna path relativo) ma con un bug diverso —
  ritorna `uploads/...` **senza** il prefisso `/storage/`, quindi
  `asset()` su quel valore punterebbe al posto sbagliato. Non è il metodo
  toccato in questo intervento (usato solo dal tipo di campo
  `base64_file`), da sistemare a parte se emerge un caso d'uso reale.
- La migration è **volutamente irreversibile** (`down()` vuoto, motivato
  nel file): non è possibile recuperare l'host/protocollo originale una
  volta rimosso, e valori diversi potrebbero averne avuti di diversi nel
  tempo.
- La migration scansiona *tutte* le colonne testuali del DB (via
  `INFORMATION_SCHEMA`), non solo quelle note: scelta deliberata per
  coprire anche moduli creati da interfaccia in produzione senza doverli
  enumerare, a scapito di eseguire una query `REGEXP` su ogni colonna
  testuale del database (accettabile per una migration one-shot, non
  qualcosa che gira ad ogni richiesta).

## Rollback

- `uploadFile()`/`component.blade.php`: `git revert` del commit, nessuna
  dipendenza da dati.
- La migration: **non riducibile con un rollback** (vedi sopra) — se
  necessario tornare indietro richiede un backup del DB precedente a
  questa migration, non `php artisan migrate:rollback`.
