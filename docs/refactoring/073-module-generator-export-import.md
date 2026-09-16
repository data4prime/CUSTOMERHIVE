# 073 - Module Generator: export/import di un modulo custom

- **Data**: 2026-09-16
- **Stato**: Completato
- **Area**: Module Generator
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/ModulsController.php` (`getExport()`,
    `getImport()`, `postImport()`, `createImportedTable()`,
    `replaceControllerBlock()`, `writeImportedColumns()`,
    `writeImportedForm()`, `writeImportedConfig()`)
  - `resources/views/crudbooster/module_generator/import.blade.php` (nuova)

> Nota: questo documento è stato scritto a posteriori leggendo il diff
> (il codice non è stato scritto in questa conversazione). Le sezioni
> "Motivazione" e "Test" sono dedotte dai commenti già presenti nel codice
> e dal comportamento implementato, non da una verifica di prima mano fatta
> qui.

## Contesto

Il Module Generator crea moduli CRUD scrivendo un controller PHP su disco a
partire da un wizard a passi (`postStep1()`...`postStep5()`, vedi anche
`docs/refactoring/068-module-generator-rce-e-test.md` per l'hardening già
fatto su quella scrittura). Non esisteva un modo per esportare la
definizione di un modulo già creato (tabella, colonne, form) e reimportarla
altrove (es. da un ambiente all'altro, o tra installazioni diverse).

## Situazione prima

Un modulo custom esisteva solo come: riga in `cms_moduls`, tabella SQL
dedicata, e controller generato su disco con i blocchi
`col`/`form`/config racchiusi tra marcatori `# START ... # END`. Per
portarlo altrove serviva copiare manualmente riga DB + tabella + file
controller.

## Situazione dopo

- **`getExport($id)`**: per un modulo non protetto (`is_protected == 0`),
  produce un JSON scaricabile con nome/icona del modulo, struttura della
  tabella (`CRUDBooster::getTableStructure()`), e i tre array
  `col`/`form`/config letti istanziando il controller generato e
  chiamandone `cbInit()` (non re-interpretando il sorgente PHP).
- **`getImport()`/`postImport()`**: pagina di upload + endpoint che, dato
  un JSON di questo formato, crea la tabella se manca
  (`createImportedTable()`, stesse colonne di cornice
  id/group/tenant/created_at/.../deleted_by usate da `save_table()` per un
  modulo nuovo da wizard), genera il controller con la stessa
  `CRUDBooster::generateController()` del wizard, inserisce la riga
  `cms_moduls`, e scrive i blocchi `col`/`form`/config nel controller con
  `replaceControllerBlock()` (stesso schema di sostituzione tra marcatori
  usato da `postStep3()`/`postStep4()`/`postStep5()`), passando sempre da
  `var_export()`/`min_var_export()` — mai interpolazione grezza di stringa,
  stesso principio del fix RCE di 068.
- **Non assegna il modulo a nessun menu o ruolo**: dopo l'import va abilitato
  manualmente da Privileges, scelta esplicita (stesso motivo del modulo
  Logs in `docs/refactoring/071-privileges-modulo-logs-non-assegnabile.md`).
- Aggiunti anche un pulsante "Export" per riga modulo (nascosto se
  `is_protected`) e un pulsante "Import Module" nell'indice.

## Motivazione

Riusa gli stessi meccanismi di scrittura già presenti e già messi in
sicurezza per il wizard (`var_export`/`min_var_export`, marcatori
`# START/END`), invece di introdurne di nuovi: la superficie di rischio
aggiuntiva è quindi limitata al parsing del JSON caricato e alla creazione
di tabella/controller, non alla generazione di codice PHP da stringhe
grezze.

## Test

Non risulta una menzione di test automatici scritti per questa feature in
questo diff. Da verificare manualmente prima di considerarla production-
ready: export di un modulo esistente → import in un ambiente pulito →
modulo funzionante e assegnabile da Privileges.

## Rischi e note

- `postImport()` è protetto da `CRUDBooster::isSuperadmin()`, coerente con
  il resto del Module Generator.
- Whitelist di chiavi in `writeImportedColumns()`/`writeImportedForm()`/
  `writeImportedConfig()`: non è un confine di sicurezza (quello è
  `var_export`/`min_var_export`), solo coerenza con ciò che il wizard stesso
  sa generare.
- Non gestisce collisioni di `table_name` con tabelle riservate in modo
  diverso da quanto già fa `save_table()` per il wizard (stesso controllo
  su `app.reserved_tables_prefix`).

## Rollback

Rimuovere i tre metodi da `ModulsController.php`, i due pulsanti aggiunti in
`cbInit()`, e `resources/views/crudbooster/module_generator/import.blade.php`.
Nessuna migration coinvolta.
