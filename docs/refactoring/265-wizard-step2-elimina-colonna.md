# 265 - Wizard v2, passo Campi: eliminazione definitiva di una colonna

- **Data**: 2026-10-07
- **Stato**: Completato (non committato; test manuale a cura dell'utente)
- **Area**: Module generator
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/ModulsController.php` (`postStep2V2`, nuovi `collectColumnDrops`, `removeDroppedColumnsFromList`, `dropTableColumns`)
  - `resources/views/crudbooster/module_generator/step2_v2.blade.php`
  - `resources/lang/{en,it}/crudbooster.php` (chiavi `mg_fld_drop*`, `mg_fld_err_drop*`)

## Contesto

Nel passo Campi del wizard v2 (interventi 196/199) il cestino su una colonna
già esistente la toglie solo dal modulo (`in_module = false`): la colonna e i
suoi dati restano nel database. Serviva un modo per eliminarla davvero.

## Situazione prima

`postStep2V2` poteva solo aggiungere colonne ("mai modifica/eliminazione").
L'unico modo di eliminare una colonna era la vecchia interfaccia (`save_table`),
che elimina implicitamente le colonne mancanti dalla richiesta.

## Situazione dopo

- Sulle colonne reali, esistenti e non di sistema compare un pulsante
  "elimina dal database" (icona `bi-database-x`), sia sulla riga attiva sia su
  quella "non nel modulo".
- Il pulsante apre una modale di conferma: bisogna **scrivere il nome della
  colonna**. La riga diventa "Sarà eliminata dal database al salvataggio", con
  pulsante Annulla.
- Al salvataggio (solo superadmin, tabella esistente, non `cms_*`) le righe con
  `drop: true` vengono validate lato server e la colonna è eliminata con
  `Schema::dropColumn`, con log `mg edit table drop column`. Il controller è
  salvato prima in backup (`backupControllerFile`).
- Eliminazione esplicita, mai implicita: il comportamento delle altre colonne
  non cambia.
- La colonna sparisce anche dal blocco COLUMNS (lista) e dall'`orderby`
  (se rimane vuoto torna a `id,desc`); i blocchi sono riscritti solo se
  contengono davvero la colonna.
- Rifiuti con messaggio: colonna di sistema/inesistente, colonna usata come
  latitudine/longitudine da un campo mappa ancora nel modulo, colonna che è il
  `title_field` della configurazione.

## Motivazione

Chiude il limite del passo Campi senza obbligare a usare la vecchia
interfaccia. Doppia protezione (modale con nome digitato + validazione server)
perché l'operazione cancella dati in modo irreversibile.

## Test

`php -l` su controller e file lingua; `view:cache` per la compilazione Blade.
Da provare a mano: elimina una colonna di un modulo di prova, con e senza
colonna nella lista, come ordinamento e come titolo; Annulla prima del
salvataggio; colonna usata da un campo mappa.

## Rischi e note

- I dati della colonna sono persi: nessun ripristino dal wizard (solo backup DB).
- Non sono controllati altri riferimenti alla colonna fuori dal controller del
  modulo (dashboard, widget, query personalizzate, `datamodal` di altri moduli).
- Se la colonna ha una foreign key MySQL l'eliminazione fallisce e il messaggio
  d'errore viene mostrato; il file del controller non viene toccato.
- Il file del controller si scrive dopo la modifica dello schema: se la
  scrittura fallisse dopo il drop, il backup resta disponibile.

## Rollback

Ripristinare i file elencati (git); le colonne già eliminate si recuperano
solo da un backup del database.
