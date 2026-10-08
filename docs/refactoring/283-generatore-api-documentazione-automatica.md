# 283 - Generatore API: documentazione automatica dei parametri + "Aggiorna doc"

- **Data**: 2026-10-08
- **Stato**: Completato (da verificare a vista)
- **Area**: API Generator
- **File/aree di codice coinvolte**:
  - `app/Helpers/ApiDocBuilder.php` (nuovo)
  - `database/migrations/2026_10_08_100000_change_cms_apicustom_keterangan_to_text.php` (nuova)
  - `app/Http/Controllers/System/ApiCustomController.php` (`storeNewApi`, `getDownloadPostman`, `postRefreshDoc`)
  - `resources/views/crudbooster/api_documentation.blade.php` (pulsanti)
  - `resources/lang/{it,en}/crudbooster.php` (chiavi `api_autodoc_*`)

## Contesto

Chi usa un'API non sa quali valori accetta un campo select, ne' il formato di
date/file. Il campo Descrizione dell'endpoint (`cms_apicustom.keterangan`)
resta vuoto salvo testo scritto a mano.

## Situazione prima

Pagina documentazione ed export Postman mostravano per ogni parametro solo
nome, tipo tecnico (`string`, ...) e obbligatorieta'. I valori ammessi di una
select (definiti nel blocco FORM del controller del modulo) non arrivavano
all'API. Postman mostrava la Descrizione come HTML grezzo.

## Situazione dopo

- `ApiDocBuilder` costruisce, per i parametri attivi, un elenco con:
  obbligatorio/facoltativo e, secondo il campo, valori ammessi (select a
  valori fissi, con valore e etichetta), "ID di un record della tabella X
  (campo mostrato: Y)" per le select su tabella, query personalizzata, formato
  data, email, file, numero. Per l'elenco aggiunge la nota su
  `limit`/`offset`/`orderby`. I dati sulle select vengono dal blocco FORM del
  controller del modulo (stessa lettura del module generator); se non e'
  leggibile (modulo custom) si documentano solo i tipi.
- Il testo sta in un `<div data-ch-autodoc="1">` dentro la Descrizione, in
  coda al testo scritto a mano. Alla **creazione** di un endpoint (editor e
  "Crea endpoint per...") viene aggiunto in automatico.
- Pulsante **Aggiorna doc**: nella barra della pagina documentazione (tutti gli
  endpoint) e per singolo endpoint (icona). `POST admin/api_generator/refresh-doc`
  (solo superadmin) rigenera il blocco; il testo fuori dal blocco non viene
  toccato; messaggio con quanti endpoint sono cambiati ("gia' aggiornata" se
  nessuno).
- **Postman**: la descrizione della richiesta e' ora testo semplice (non HTML)
  e ogni parametro del body ha la propria `description`.

### Correzione (stesso giorno): colonna troppo corta

Il primo "Aggiorna doc" fallì con `SQLSTATE[22001] Data too long for column
'keterangan'`: `cms_apicustom.keterangan` era `VARCHAR(255)`. Aggiunta la
migration `2026_10_08_100000_change_cms_apicustom_keterangan_to_text.php`
(`TEXT NULL`, come per `qlik_items.subtitle`): allarga soltanto, i dati
esistenti restano validi. Va eseguita (`php artisan migrate`) su ogni
ambiente/cliente all'aggiornamento; senza, creazione automatica e "Aggiorna
doc" falliscono. Il `down()` tronca a 255 caratteri.

## Motivazione

Dare a chi integra le API le informazioni minime per usarle senza guardare il
modulo, e mantenerle allineate a mano con un click quando le select cambiano.
Scelte: nessun elenco dei record per le select su tabella (dati variabili,
per tenant, e la Descrizione e' anche nella documentazione pubblica); blocco
marcato per non sovrascrivere il testo manuale.

## Test

`php -l` su helper, controller, viste lang. Provato `ApiDocBuilder::build`
su `mg_ordini_vendita` e `mg_contratti` nel container (valori select e campo
file corretti, anche in testo per Postman); provati `merge`/`sameBlock`
(sostituzione, testo manuale preservato, confronto). Non provati nel browser:
pulsante, creazione da editor, export Postman in Postman, TinyMCE che
risalva il blocco.

## Rischi e note

- Il testo e' generato nella lingua dell'utente che crea/aggiorna: un
  "Aggiorna doc" lo riscrive nella lingua di chi lo preme.
- Gli endpoint esistenti non cambiano finche' non si preme "Aggiorna doc".
- Se il wysiwyg dell'editor rimuovesse l'attributo `data-ch-autodoc`, il blocco
  verrebbe visto come testo manuale e un successivo "Aggiorna doc" ne
  aggiungerebbe un secondo: da verificare aprendo e salvando un endpoint.
- L'editor del singolo endpoint non ha il pulsante (solo la pagina
  documentazione).
- Cambia visibile: descrizione Postman da HTML a testo.

## Rollback

Rimuovere `ApiDocBuilder`, le tre chiamate nel controller, `postRefreshDoc`,
i due form nella vista e le chiavi `api_autodoc_*`. Il blocco gia' scritto nelle
Descrizioni resta come testo.
