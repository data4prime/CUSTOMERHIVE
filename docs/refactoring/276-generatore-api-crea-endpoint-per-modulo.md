# 276 - Generatore API: "Crea endpoint per..." (endpoint standard di un modulo)

- **Data**: 2026-10-08
- **Stato**: Completato (da verificare a vista)
- **Area**: API Generator
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/ApiCustomController.php` (`postBulkCreate`, `storeNewApi`, `apiMapType`, `getIndex`)
  - `resources/views/crudbooster/api_documentation.blade.php` (pulsante + modale)
  - `resources/lang/{it,en}/crudbooster.php` (chiavi `api_bulk_*`)

## Contesto

Creare gli endpoint di un modulo richiedeva di compilare a mano l'editor
cinque volte (elenco, dettaglio, creazione, modifica, eliminazione). Serve
un modo per generarli in automatico e ritoccarli poi uno per uno.

## Situazione prima

Un endpoint si creava solo da `/admin/api_generator/generator`, uno alla
volta. La logica di creazione (nome controller sanificato, gestione
collisioni, `generateAPI`, insert in `cms_apicustom`) stava inline in
`postSaveApiCustom`.

## Situazione dopo

- Pagina elenco: pulsante "Crea endpoint per..." → modale con la scelta del
  modulo (solo moduli in `cms_moduls` la cui tabella ha il prefisso del module
  generator) → `POST admin/api_generator/bulk-create`.
- Per il modulo scelto crea 5 endpoint, permalink `<tabella>_list|detail|create|update|delete`,
  nome `<Modulo> - <Azione>`, con le stesse impostazioni che propone l'editor:
  - Elenco (GET): tutti i campi come parametri, non obbligatori e non attivi
  - Dettaglio (GET) e Eliminazione (POST): solo `id`, obbligatorio e attivo
  - Creazione (POST): tutte le colonne tranne `id`, attive; obbligatorie
    quelle NOT NULL senza default e non auto_increment
  - Modifica (POST): tutte le colonne attive, solo `id` obbligatorio
  - Risposte: colonne come nell'editor (comprese quelle collegate `id_xxx`), tutte attive
- Endpoint con permalink già esistente vengono saltati; messaggio finale con
  creati/saltati.
- Solo superadmin; operazione scritta nel log.
- `postSaveApiCustom` usa ora `storeNewApi()` (estratta senza cambiare
  comportamento) condivisa con la creazione automatica.

### Aggiunta (stesso giorno)

Nell'elenco endpoint sotto il nome compare ora il **nome del modulo**
(`cms_moduls.name`, ripiego sul nome tabella se il modulo non esiste più) al
posto del nome della tabella; la ricerca trova l'endpoint anche per nome
modulo. Modifica in `getIndex` (`moduleNames`) e `api_documentation.blade.php`.

L'export Postman (`getDownloadPostman`) raggruppa ora gli endpoint in
cartelle per nome modulo (ordinate alfabeticamente; ripiego sul nome tabella).
Formato collection v2.0.0 invariato: cambia solo che `item` contiene cartelle
`{name, item[]}` invece di richieste piatte. Rollback: rimettere l'elenco piatto.

## Motivazione

Riuso della stessa logica di creazione per non duplicare le protezioni
(sanificazione nome controller, anti-collisione). Default coerenti con
l'editor, così gli endpoint generati si comportano come quelli fatti a mano.

## Test

`php -l` su controller e lang, route registrata (`route:list`). Non
eseguito in browser né creati endpoint reali (genera file controller in
`app/Http/Controllers/`).

## Rischi e note

Ogni endpoint crea un file `Api<Nome>Controller.php`: cinque file per modulo.
Gli endpoint nuovi sono raggiungibili anche su `/api` (v1) oltre che `/api2`,
come tutti gli altri. Le colonne obbligatorie in creazione sono una stima dal
DB: da rivedere nell'editor.

## Rollback

Eliminare i cinque endpoint dall'elenco (cestino) e rimuovere `postBulkCreate`,
pulsante/modale e chiavi `api_bulk_*`; `storeNewApi` può restare.
