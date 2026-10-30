# 334 - Module Generator: import/export dei moduli in due Service

- **Data**: 2026-10-30
- **Stato**: Completato (test eseguiti: 106 verdi)
- **Area**: Architettura / Service + Module Generator
- **File/aree di codice coinvolte**:
  - `app/Services/ModuleGenerator/ModuleExporter.php` (nuovo)
  - `app/Services/ModuleGenerator/ModuleImporter.php` (nuovo)
  - `app/Services/ModuleGenerator/ModuleTransferException.php` (nuovo)
  - `app/Http/Controllers/System/ModulsController.php` (`getExport`, `postImport`, rimozione dei metodi privati migrati)
  - `app/Helpers/ModuleGeneratorLayout.php` (nuovo metodo statico `placeableFields()`)
  - `resources/lang/en/crudbooster.php`, `resources/lang/it/crudbooster.php` (10 chiavi `module_export_*` / `module_import_*`)
  - `tests/Feature/ModuleExportImportTest.php` (nuovo)

> **Nota sulla numerazione**: l'origine è il piano
> [`piani/335-module-generator-import-export-service.md`](piani/335-module-generator-import-export-service.md).
> Il numero del piano (335) e quello di questo documento (334) **non coincidono**:
> il 334 era "Prossimo numero libero" nel README, mentre il piano 334
> (`piani/334-profilo-utente-form-request.md`) è una bozza non ancora eseguita
> e non è stato toccato. Quando verrà eseguita la bozza 334 dovrà usare un
> numero libero successivo.

## Contesto

Batch 1 dell'analisi dei controller di `System/` (sessione `/architect` del
2026-10-30). `ModulsController` è la classe più grande (circa 3000 righe) e il
blocco import/export dei moduli (JSON) è il più isolato: circa 400 righe fra
`getExport`, `postImport` e sei metodi privati. Obiettivo: spostarle in Service
lasciando nel controller solo ciò che dipende da HTTP. Piano e decisione D1
approvati dalla persona il 2026-10-30.

## Situazione prima

- `getExport($id)` e `postImport()` contenevano tutta la logica: lettura di
  `cms_moduls`, costruzione del JSON (`format_version` 1), validazione del
  file, creazione della tabella, `CRUDBooster::generateController`, insert in
  `cms_moduls`, scrittura dei blocchi col/form/layout/config nel controller
  generato.
- Metodi privati nello stesso controller: `exportTableColumns`,
  `createImportedTable`, `replaceControllerBlock`, `writeImportedColumns/Form/
  Layout/Config`. `layoutPlaceableFields` (privato) era usato anche dal wizard
  V2.
- **Nessun test** copriva import/export.
- Dieci messaggi all'utente erano **hardcoded in italiano** (violazione della
  regola i18n del repo).
- L'import non è transazionale: un errore a metà lascia tabella/controller
  parziali.

## Situazione dopo

- `ModuleExporter::export($id): array{filename,json}` e
  `ModuleImporter::import(string $contents): string` (ritorna il nome del
  modulo importato), con i metodi privati spostati con logica identica
  (differenze solo di indentazione 2→4 spazi richiesta da Pint sui file nuovi
  e `new $controller_class()` con parentesi).
- `ModuleTransferException`: messaggio già tradotto e `type()`
  (`'warning'|'danger'`). Il controller la cattura e fa il redirect identico a
  prima per ciascun endpoint.
- `ModulsController` tiene solo glue HTTP: permesso superadmin con `insertLog`,
  controllo file/estensione `.json`, lettura del file, redirect.
  `getExport`, `getImport`, `postImport` invariati per nome e firma; nessun
  metodo pubblico nuovo (ogni pubblico di una sottoclasse di `CBController`
  diventa una rotta).
- `ModuleGeneratorLayout::placeableFields(array $form)`: statico, additivo
  (corpo di `layoutPlaceableFields`); il metodo privato del controller resta
  come wrapper di una riga, usato dal wizard V2, i cui chiamanti non sono
  stati toccati.
- 10 chiavi i18n in en e it: `module_export_not_exportable`,
  `module_export_controller_missing`, `module_import_invalid_file`,
  `module_import_bad_extension`, `module_import_invalid_json`,
  `module_import_name_exists` (`:name`), `module_import_reserved_table`,
  `module_import_table_used` (`:table`), `module_import_no_columns`,
  `module_import_success` (`:name`). Il testo it è identico alle frasi
  italiane che erano hardcoded.

**Comportamento visibile (D1, approvata)**: i 10 messaggi ora sono in inglese
per chi usa l'interfaccia in inglese (prima erano in italiano per tutti); per
l'italiano il testo è identico. URL, formato JSON (`format_version` 1),
permessi, redirect e ordine dei controlli sono invariati.

## Motivazione

Ridurre `ModulsController`, rendere import/export testabili senza passare dal
controller e rispettare la regola i18n. Pattern: Service semplice che non legge
sessione né richiesta, istanziato con `new` (come `ChatAIClient`, ADR A14); due
classi perché export e import hanno ragioni di cambiamento diverse. Scartata
una classe generica "ModuleManager" (anti-pattern del repo). I service
chiamano `CRUDBooster::` statico dove lo faceva il controller: wrapperizzarli è
fuori scopo.

## Test

- Scritto **prima** del refactoring `tests/Feature/ModuleExportImportTest.php`
  (22 test): sul codice vecchio 21 verdi più 1 rosso atteso (i18n); dopo il
  refactoring tutti verdi. Copre import felice con tutti i tipi di colonna,
  round trip export/import, sicurezza (`var_export`, whitelist, layout non
  valido scartato), rifiuti di import ed export, import su tabella esistente,
  mappatura colonne in export, i18n.
- Eseguiti il 2026-10-30 dalla sessione architect: il file nuovo più
  `ModuleGeneratorCrudTest`, `ModuleStep1MenuDefaultsTest`,
  `PublicSystemRoutesAuthTest`, `StatisticBuilderCrudTest` = **106 test verdi,
  628 asserzioni**. Nessun residuo (file `*PhpunitTest*.php`, tabelle
  `mg_phpunit_*`).
- Review `ch-reviewer`: nessun bloccante. Ha confrontato i privati riga per
  riga col vecchio codice; i18n ok sulle 10 chiavi; nessun riferimento rotto ai
  privati rimossi.
- **Non eseguito**: Larastan. Pint `--test` segnala violazioni preesistenti nei
  file già esistenti toccati (vedi sotto), non corrette.

## Rischi e note

- **D1**: cambio visibile per l'interfaccia in inglese (vedi sopra).
- L'import resta **NON transazionale**: un errore a metà lascia tabella e/o
  controller parziali. Invariato rispetto a prima, voluto.
- **Follow-up non fatti**: `$page_title = 'Import Module'` in `getImport()` e i
  testi di `module_generator/import.blade.php` sono ancora non tradotti
  (tradurre il titolo cambierebbe la pagina per gli utenti italiani).
- **Lacune dei test**: nessun test sul fallimento a metà dell'import; i tipi di
  colonna `mediumtext` e sconosciuti sono coperti solo in export; nessun test
  che l'import ignori `exported_at`; il caso `isValid()` falso e `getImport()`
  non sono coperti.
- I test creano DDL e file veri: `tearDown` con snapshot degli id e drop delle
  tabelle `mg_phpunit_*`.
- Dubbi del reviewer senza azione: `import(string $contents)` ha un type hint
  nuovo ma previsto dalla firma del piano (con `file_get_contents()` false
  diventa `""` e finisce nel messaggio "JSON non valido", come prima); con
  `module.name` array il messaggio si comporta in modo analogo a prima (caso
  patologico, solo superadmin). Rilievi banali (docblock e attributo `#[Group]`
  obsoleti nel test) già corretti.
- Pint segnala violazioni **preesistenti** nei file già esistenti toccati
  (indentazione a 2 spazi nel controller, `const` senza visibilità in
  `ModuleGeneratorLayout`, righe vuote nei file lang): non toccate.
- Nessun impatto su login, licensing, permessi o dati dei clienti: controller
  di sistema, i metodi spostati erano `private`, JSON e URL invariati.

## Rollback

Ripristinare i corpi di `getExport`/`postImport` e i metodi privati dal diff
di `ModulsController.php`; rimuovere `placeableFields()` da
`ModuleGeneratorLayout.php` (e il wrapper se non voluto); cancellare
`app/Services/ModuleGenerator/`, `tests/Feature/ModuleExportImportTest.php` e
le 10 chiavi i18n in en e it. Nessuna migrazione, nessun dato da annullare.
