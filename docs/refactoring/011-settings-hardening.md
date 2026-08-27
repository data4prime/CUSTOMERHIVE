# 011 - Sezione Settings: hardening, bonifica dati e pulizia

- **Data**: 2026-08-27
- **Stato**: Completato
- **Area**: Settings / CRUDBooster / Dati
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/SettingsController.php`
  - `packages/crocodicstudio/crudbooster/src/views/setting.blade.php`
  - `packages/crocodicstudio/crudbooster/src/views/setting_.blade.php` (rimosso)
  - `database/seeders/DatabaseSeeder.php`, `CmsSettingsSeeder.php`, `Qlik_Sett.php` (rimosso)
  - `packages/crocodicstudio/crudbooster/src/database/seeders/CBSeeder.php`
  - 4 migration nuove: `2026_08_27_0300{00,01,02,03}_*`

## Contesto

Analisi completa della sezione Settings (`admin/settings`), fatta su richiesta
dopo gli interventi [007](007-upload-path-relativo.md) e
[010](010-popup-select-non-si-chiude.md). Il modulo ha due UI sovrapposte sulla
stessa tabella `cms_settings`: il CRUD standard sulle righe e il form per gruppo
(`show?group=...`, l'unico realmente usato). L'analisi ha prodotto 10 rilievi;
questo documento copre quelli che si è deciso di sistemare subito.

Non affrontati di proposito: la fragilità del gruppo "Email Setting" rispetto a
`config/mail.php` (da vedere nell'audit Laravel) e `group_setting` come
etichetta tradotta usata da chiave dati — vedi *Rischi e note*.

## Situazione prima

1. **`getDeleteFileSetting()` senza autorizzazione**. La rotta
   `GET admin/settings/delete-file-setting?id=N` era protetta solo da
   `CBBackend`, che verifica soltanto "utente autenticato". Qualsiasi utente
   loggato, con qualsiasi privilegio, poteva azzerare il contenuto di qualsiasi
   setting (`logo`, `favicon`, `appname`, `smtp_*`) e cancellarne il file. Il
   middleware CSRF è disabilitato e la rotta è una GET, quindi bastava un
   `<img src>` su una pagina esterna. I due metodi custom fratelli (`getShow`,
   `postSaveSetting`) facevano già `isSuperadmin()`; l'analogo del framework
   (`CBController::getDeleteImage()`) fa `cbLoader()` + `isDelete()`.

2. **Upload con URL assoluto**. `postSaveSetting()` ricostruiva
   `protocol://host/storage/uploads/...` da `$_SERVER['HTTP_HOST']`: lo stesso
   pattern rimosso da `CRUDBooster::uploadFile()` in
   [007](007-upload-path-relativo.md). Quel fix e la sua migration di bonifica
   coprivano l'altro percorso, non questo, che ha continuato a produrre URL
   assoluti.

3. **Gruppo "Qlik Configuration" morto ma resuscitato dai seeder**. 13 righe
   (`confname`, `type`, `url`, `endpoint`, `keyid`, `issuer`, `web_int_id`,
   `private_key`, `qrsurl`, `QRSCert*`, `debug`) che duplicavano la tabella
   `qlik_confs` — la configurazione Qlik realmente usata da
   `QlikHelper::getConfFromItem()`. Nessuno di quei nomi è letto da
   `getSetting()`: le due occorrenze di `getSetting('type')` in
   `AdminQlikItemsController` e `AdminChatAIController` sono commentate.
   La migration `2024_07_16_111255_delete_qlik_setting` le cancellava e
   risultava `Ran`, ma **due** seeder le reinserivano (`Qlik_Sett.php` e
   `CmsSettingsSeeder.php`, entrambi chiamati da `DatabaseSeeder`): girando
   dopo le migration, vincevano loro.

4. **`cms_settings.name` senza vincolo di unicità**. `name` è la chiave logica
   di un setting (la usano `getSetting()`, la cache `setting_<name>`, l'UPDATE
   di `postSaveSetting` che scrive `WHERE name = ...`, e la dedup dei seeder che
   tiene l'id più basso e cancella il resto), ma non c'era né indice unique né
   controllo applicativo: `hook_before_add` derivava il nome da
   `str_slug($label)` e inseriva. Due righe con lo stesso nome — anche in gruppi
   diversi — si sovrascrivevano a vicenda e una veniva cancellata al primo
   `db:seed`.

5. **`content_input_type`: form e view disallineati**. Il form offriva
   `upload_document`, che `setting.blade.php` non gestiva: nessun campo a
   schermo e — poiché `postSaveSetting` ciclava su tutte le righe del gruppo
   facendo `$content = Request::get($name)` — valore azzerato ad ogni
   salvataggio del gruppo. Viceversa `upload_file`, usato da righe reali, non
   era selezionabile.

6. **`smtp_password` in chiaro**. `content_input_type = 'text'`, quindi
   renderizzata come `<input type="text" value="...">`: password SMTP leggibile
   a schermo e nel sorgente della pagina. Non esisteva il tipo `password`.

7. **`email_sender` era ancora il default upstream** `support@crudbooster.com`:
   un dominio di terzi presentato come mittente configurato.

8. **Upload fallito indistinguibile da uno riuscito**. `Storage::putFileAs()`
   ritorna `false` senza sollevare eccezioni (caso tipico: directory non
   scrivibile dall'utente del web server). Con `$storeFile` falso, `$content`
   restava `null` - un input file non compare in `Request::get()` - si arrivava
   all'UPDATE che **azzerava il setting**, e la pagina mostrava comunque
   "Your setting has been saved !". Emerso in modo concreto: un'immagine
   caricata su "Login Background Image" risultava salvata senza esserlo.

9. **Nessuna preview delle immagini**. Per i setting di tipo `upload_image` la
   pagina mostrava solo un link "Download file", quindi non c'era modo di
   vedere a colpo d'occhio cosa fosse stato caricato.

10. **Varie**: import di `Illuminate\Support\Facades\Excel` e `...\PDF` (classi
   che non esistono in quel namespace); view morta `setting_.blade.php`; la
   blade eseguiva la SELECT dei setting **e** una UPDATE per riparare le label
   vuote durante il render di una GET; il nome del gruppo finiva negli URL senza
   `urlencode`.

## Situazione dopo

**Codice**

- `getDeleteFileSetting()`: check `isSuperadmin()` + `insertLog` come i metodi
  fratelli, e guardia sulla riga inesistente (prima `$row->content` su `null`
  dava 500).
- La cancellazione del file usa `public_path($row->content)` + `is_file()` +
  `unlink()`. `Storage::exists()` non poteva funzionare: il valore salvato è
  relativo alla **public root**, non al disco `local` (la cui root è
  `storage/app/public`), quindi il file restava orfano ad ogni cancellazione.
  Stessa risoluzione già adottata dal componente upload in
  [007](007-upload-path-relativo.md).
- `postSaveSetting()`:
  - salva `'/storage/' . $directory . '/' . $filename` (path relativo);
  - salta le righe il cui campo non è arrivato nella richiesta, invece di
    metterle a `NULL`. Un input di testo svuotato arriva comunque come stringa
    vuota, quindi resta cancellabile;
  - per i campi `password`, valore vuoto significa "non modificare".
- `postSaveSetting()`, upload fallito: la riga non viene toccata (il valore
  precedente resta), viene scritto un `Log::error` col path, e l'utente riceve
  un messaggio `warning` con l'elenco dei campi falliti invece del messaggio di
  successo.
- `setting.blade.php`, tipo `upload_image`: preview dell'immagine (thumbnail
  alta max 120px, cliccabile con il lightbox già caricato da
  `admin_template_plugins`). Se il valore è in tabella ma il file non è sul
  disco, invece dell'immagine rotta compare un avviso con il path mancante e il
  pulsante di cancellazione resta disponibile per ripulire il valore. Un vecchio
  URL assoluto non è verificabile su disco, quindi viene mostrato senza check.
- `hook_before_add()`: blocca con messaggio esplicito se il nome tecnico
  derivato dalla label esiste già.
- `dataenum` di `content_input_type`: `upload_document` → `upload_file`,
  aggiunto `password`.
- `getShow()`: SELECT e backfill delle label spostati nel controller, la view
  riceve `$settings`. La UPDATE resta (comportamento invariato) ma non è più
  dentro il template.
- `setting.blade.php`: nuovo case `password` (input mai ripopolato col valore
  salvato), `upload_document` gestito come alias di `upload_file` per le righe
  legacy di altre installazioni, `urlencode` sul gruppo nei due link.
- Rimossi gli import inesistenti e la view `setting_.blade.php`.

**Seeder**

- `Qlik_Sett.php` eliminato e la sua chiamata rimossa da `DatabaseSeeder`.
- Blocco Qlik rimosso da `CmsSettingsSeeder.php` (era la seconda copia,
  scoperta solo rieseguendo il seeder dopo la migration).
- Rimosso da `packages/.../CBSeeder.php` il blocco Qlik, già interamente
  commentato: 167 righe di codice morto che rendevano difficile capire chi
  seminasse davvero quelle righe.
- `email_sender` seminato vuoto in entrambi i seeder.

**Migration** (in quest'ordine)

| File | Cosa fa |
|---|---|
| `..._030000_remove_qlik_configuration_settings` | cancella il gruppo (nomi tradotti EN+IT) e invalida la cache dei nomi rimossi |
| `..._030100_normalize_cms_settings_content_and_types` | URL assoluti → path relativi, `upload_document` → `upload_file`, `smtp_password` → tipo `password` |
| `..._030200_update_default_email_sender` | svuota `email_sender`, solo se vale ancora il default upstream `support@crudbooster.com` |
| `..._030300_add_unique_index_to_cms_settings_name` | crea `cms_settings_name_unique`, ma **solo** se non ci sono nomi duplicati |

Tutte idempotenti. `030300` è volutamente l'ultima: se si ferma, le altre tre
sono già passate.

**`030300`: nessuna deduplica automatica.** La prima stesura deduplicava da sola
tenendo l'id più basso, come fanno i seeder. Su richiesta è stata cambiata: ora
la migration **non cancella niente**. Se trova nomi duplicati scrive nel log
tutte le righe coinvolte (id, gruppo, tipo, contenuto — serve il contenuto per
capire quale tenere), stampa lo stesso dettaglio nell'output di
`php artisan migrate`, e solleva un'eccezione: **non viene registrata come
eseguita e resta pendente**. Chi fa il deploy guarda le righe, decide quale
tenere, elimina le altre e rilancia `php artisan migrate`. Se invece non ci sono
duplicati, l'indice viene creato subito senza nessun intervento manuale.
La motivazione: la politica "tengo l'id più basso" è arbitraria rispetto al
significato dei dati — su questa tabella il contenuto della riga scartata può
essere quello buono — e cancellerebbe dati di produzione senza che nessuno li
abbia visti.

**Righe di prova non toccate.** L'analisi aveva segnalato due righe di test nel
gruppo "General Setting" (`aaaa`, `fdgdfg`). Era stata scritta una migration per
rimuoverle, poi eliminata su richiesta: sono dati di prova locali dello
sviluppatore, non serve una migration che li cancelli su tutti gli ambienti.

## Motivazione

Il rilievo 1 è un buco di autorizzazione sfruttabile in produzione, quindi
prioritario. Il 2 completa [007](007-upload-path-relativo.md) su un percorso
rimasto fuori, rilevante per il passaggio dev → staging dove l'host cambia. Il 3
elimina un conflitto strutturale migration-contro-seeder che rendeva il DB non
riproducibile dal seeder — prerequisito per il "seeder/setup unico" della
roadmap. Il 4 mette un vincolo a DB dove il codice assumeva già unicità.

## Test

Verificato in locale nel container, senza suite automatica (login bloccato
localmente per assenza di licenza, quindi niente verifica via browser):

- `php -l` su tutti i file PHP modificati; `setting.blade.php` compilata con
  `blade.compiler` e lintata (`view:cache` non è utilizzabile: fallisce per un
  problema preesistente e indipendente, manca `app/Widgets/`).
- Migration eseguite: gruppo "Qlik Configuration" rimosso, `smtp_password` con
  tipo `password`, `email_sender` svuotato, indice `cms_settings_name_unique`
  presente. Verificato anche il ciclo `migrate:rollback --step=2` + `migrate`.
- `db:seed --class=Cms_settingsSeeder` rieseguito: **è così che si è scoperta la
  seconda copia del blocco Qlik**, che aveva ricreato tutte e 13 le righe. Dopo
  la correzione, il re-seed non le reintroduce e non produce duplicati.
- **`030300`, ramo "ci sono duplicati"**: inseriti due duplicati finti
  (`appname`, `smtp_port`), lanciato `migrate`. Risultato: comando fallito con
  l'elenco completo delle 4 righe (id, gruppo, tipo, contenuto), `Log::warning`
  scritto, indice **non** creato, migration **non** registrata in `migrations`,
  e nessuna delle 4 righe modificata o cancellata.
- **`030300`, ramo "nessun duplicato"**: rimossi i duplicati finti, rilanciato
  `migrate` → indice creato senza intervento manuale.
- **`down()` di `030300`**: `migrate:rollback` elimina l'indice, `migrate`
  successivo lo ricrea.
- `postSaveSetting()` invocato con una richiesta costruita a mano: campo
  modificato → aggiornato; `smtp_password` inviata vuota → valore conservato;
  `smtp_username` assente dalla richiesta → valore conservato; campo di testo
  svuotato → cancellato (regressione esclusa).
- `hook_before_add()`: con label che collide (`Smtp Host` → `smtp_host`) entra
  nel ramo di blocco, con label libera lo salta.
- Upload end-to-end: PNG caricato via `postSaveSetting` → salvato come
  `/storage/uploads/2026-08/<hash>.png`, file presente in
  `public/storage/uploads/...`. Poi `getDeleteFileSetting` da utente **non**
  superadmin → riga e file intatti; da superadmin → `content` a `NULL` **e**
  file rimosso dal disco (niente più orfani).
- Ramo "upload fallito": riprodotta la condizione reale (directory del mese di
  proprietà `root` mentre Apache gira come `www-data`; il solo `chmod` non
  basta a simularla, perché Flysystem rimette i permessi di default sulla
  directory prima di scrivere). Risultato: valore precedente conservato,
  `Log::error` scritto, messaggio di warning all'utente.
- Preview: il ramo `upload_image` estratto dal file reale e renderizzato con
  `Blade::render()` nei quattro casi — file presente (thumbnail + download),
  file mancante (avviso, nessuna immagine rotta), vecchio URL assoluto
  (mostrato senza check), nessun valore (input file).
- Stato del DB locale ripristinato dopo i test.

## Rischi e note

- **`030000` cancella dati** ed è irreversibile per scelta (`down()` vuoto e
  motivato): rimuove tutto ciò che sta nel gruppo "Qlik Configuration". Su
  un'installazione dove qualcuno avesse messo a mano un setting *usato* in quel
  gruppo, andrebbe perso: verificare il contenuto in produzione prima del
  deploy.
- **`030300` può bloccare il deploy** se la tabella ha nomi duplicati. È il
  comportamento voluto: si preferisce un deploy fermo a una cancellazione
  automatica. Il messaggio d'errore contiene già tutto il necessario per
  decidere; il dettaglio è anche in `storage/logs`.
- **Il gruppo "Email Setting" è funzionante solo grazie a un `config/mail.php`
  legacy** e non è stato toccato. `CRUDBooster::sendEmail()` fa
  `Config::set('mail.driver'|'mail.host'|...)`, chiavi che Laravel 9 legge solo
  attraverso il ramo di retro-compatibilità di `MailManager::getConfig()`, che
  scatta perché `config/mail.php` è ancora in forma Laravel 5 (`driver`/`host`
  al primo livello, nessun `default`/`mailers`). Verificato: impostando la
  config **prima** che il mailer venga risolto, il transport diventa quello dei
  settings; se il mailer è già stato risolto nella request, gli override sono
  ignorati senza errore. Conseguenza: **modernizzare `config/mail.php` spegne
  silenziosamente tutto il gruppo Email Setting**. Da mettere nell'audit di
  compatibilità Laravel 10/11/12. Note a margine dello stesso file:
  `'username' => env('service@data4prime.com')` — `env()` chiamata col valore
  invece del nome della variabile, ritorna sempre `null`; e `smtp_driver` a DB
  vale `mail`, driver rimosso da Laravel 7, che risolve al transport sendmail.
- **`email_sender` conta meno di quanto sembri.** Nel percorso di invio
  immediato (`CRUDBooster::sendEmail`) non viene mai letto: conta solo
  `cms_email_templates.from_email` e, se vuoto, `config('mail.from.address')`.
  Entra in gioco solo nel percorso accodato (`sendEmail` con `send_at` →
  `cms_email_queues` → `sendEmailQueue`, processato dal comando `Mailqueues`),
  che nessuna chiamata di questa applicazione usa. Svuotarlo quindi non cambia
  il comportamento degli invii attuali. Da tenere presente se un giorno si
  usasse la coda: `sendEmailQueue` chiama `$message->from($from_email, ...)`
  senza guardia, e con un mittente vuoto Symfony Mailer solleverebbe
  un'eccezione.
- **`cms_email_templates.from_email` vale `system@crudbooster.com`** (template
  `forgot_password_backend`, l'unico presente). Non toccato: è fuori dalla
  tabella `cms_settings` e quindi dallo scopo di questo intervento, ma è **il
  valore che conta davvero** per il mittente delle mail di reset password. Da
  sistemare, insieme a `config/mail.php`, quando si affronta l'invio email.
- **`group_setting` è un'etichetta tradotta usata come chiave dati** (i seeder
  scrivono `trans('crudbooster.email_setting')`, mentre `SettingsController`
  hardcoda `'General Setting'`). Non affrontato: cambiando il locale
  dell'applicazione i gruppi si sdoppierebbero. Le migration coprono le varianti
  EN e IT del nome del gruppo Qlik proprio per questo.
- **Fuori scope, notato durante il lavoro**: `database/seeders/Qlik_Conf.php` fa
  un `insert` in `cms_moduls` senza controllo di esistenza, quindi ogni
  `db:seed` aggiunge un duplicato — in locale ci sono già due righe
  "Qlik Configuration" (id 15 e 16).

## Rollback

- Codice: `git revert` del commit.
- Dati: `030100` (solo per `smtp_password`), `030200` e `030300` hanno un
  `down()` funzionante. `030000` no: per recuperare quelle righe serve un
  backup del DB precedente al deploy.
