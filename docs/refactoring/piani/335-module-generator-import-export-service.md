# Piano 335 — Module Generator: import/export dei moduli in due Service (batch 1 dell'analisi controller)

Stato: **approvato dalla persona il 2026-10-30** (piano e D1). Branch: `refactor/models`.
Autore: `/architect`, 2026-10-30. Destinatario: `ch-senior-dev` (non vede la
conversazione: questo file è autosufficiente). Origine: analisi dei controller di
`System/` (sessione `/architect` del 2026-10-30): `ModulsController` è la classe più
grande (3001 righe) e il blocco import/export è il più isolato. La persona ha approvato
l'ordine dei batch e la decisione D1 sotto ("d'accordo con tutto").
Numero: 335 perché il 334 è già una bozza di piano (profilo utente, Form Request).
La riga "Prossimo numero libero" del README non si aggiorna finché non esiste l'intervento.

## Obiettivo

Spostare da `ModulsController` in due Service l'export e l'import di un modulo
(JSON), circa 400 righe, lasciando nel controller solo ciò che dipende da HTTP
(permesso, file caricato, risposta, redirect). Il comportamento resta identico, con
l'unica eccezione dichiarata in D1 (testi in inglese).

## Stato attuale (fatti verificati 2026-10-30, `ModulsController.php`)

Tutti metodi di `ModulsController` (sottoclasse di `CBController`):

| Metodo | Righe | Visibilità | Cosa fa |
|---|---|---|---|
| `getExport($id)` | 2095-2155 | public (rotta GET) | superadmin; legge `cms_moduls` (non cancellato, non `is_protected`); istanzia il controller del modulo e chiama `cbInit()`; costruisce il JSON (`format_version` 1, `module`, `table.columns`, `config`, `col`, `form`, `form_layout` solo se attivo); risponde con `Content-Disposition` |
| `getImport()` | 2158-2170 | public (rotta GET) | superadmin; mostra `crudbooster::module_generator.import` con `$page_title = 'Import Module'` |
| `postImport()` | 2181-2254 | public (rotta POST) | superadmin; controlla file/estensione/JSON/duplicati/tabella riservata; crea la tabella se manca; `CRUDBooster::generateController`; inserisce la riga in `cms_moduls`; scrive i blocchi col/form/layout/config nel controller generato. **Non crea menu né `cms_privileges_roles`** (voluto, doc 071) |
| `exportTableColumns` | 2262-2315 | private | colonne della tabella → tipi del generatore |
| `createImportedTable` | 2324-2377 | private | `Schema::create` con colonne di cornice (duplicato voluto di `save_table()`, non toccarlo) |
| `replaceControllerBlock` | 2382-2395 | private | sostituisce il testo tra due marcatori `# START … # END …` |
| `writeImportedColumns/Form/Layout/Config` | 2402-2500 | private | scrivono i blocchi con `min_var_export()`/`var_export()` (mai interpolazione grezza, doc 068) |
| `layoutPlaceableFields` | 1371-1385 | private | usato **anche dal wizard V2** (`postStep2V2` 1162-1163, `getStep4V2` 1398, `postStep4V2` 1453) e da `writeImportedLayout` |

- Chiamanti: `replaceControllerBlock` è chiamato solo da `writeImported*` (stessa classe);
  `layoutPlaceableFields` dal wizard e dall'import. Prima di togliere un metodo privato,
  ricontrollare i chiamanti con `tokensave_callers`/`tokensave_search` (non è una rotta:
  un metodo `private` non genera route).
- **Test esistenti**: `ModuleGeneratorCrudTest` copre il wizard (step 1-5, delete, RCE) e
  scrive file veri in `app/Http/Controllers/` (con `$fixtureFiles` e uno sweep
  `*PhpunitTest*.php` nel `tearDown`). **Nessun test** cita `module_generator/export` o
  `import` (ricerca su `tests/`, 2026-10-30): import/export oggi non hanno copertura.
- **Testi hardcoded in italiano** (violano la regola i18n del repo): `'Questo modulo non e\'
  esportabile'`, `'Controller del modulo non trovato sul disco'`, `'Seleziona un file JSON
  valido'`, `'Il file deve avere estensione .json'`, `'File JSON non valido o incompleto'`,
  `"Esiste gia' un modulo chiamato \"{$name}\""`, `'Nome tabella riservato, non
  importabile'`, `"La tabella \"{$table_name}\" e' gia' usata da un altro modulo"`, `'Il file
  JSON non contiene colonne per creare la tabella'`, `"Modulo \"{$name}\" importato con
  successo"`.
- Gli errori dell'export escono con `CRUDBooster::redirect(CRUDBooster::mainpath(), msg,
  tipo)`; quelli dell'import con `redirect()->back()->with(['message'=>…,'message_type'=>…])`;
  il successo dell'import con `CRUDBooster::redirect(mainpath(), msg, 'success')`. Il rifiuto
  non-superadmin: `insertLog(trans('crudbooster.log_try_view', ['module' => 'Module
  Generator - Export'|'- Import']))` + `CRUDBooster::redirect(adminPath(), denied_access)`.
- L'import **non è transazionale** e oggi un errore a metà lascia stato parziale (tabella
  creata, controller generato). Resta così: non aggiungere transazioni.

## Decisione (approvata)

**D1 — Testi in `trans()`.** Le dieci frasi sopra passano a chiavi `crudbooster.*` nuove in
`resources/lang/it/crudbooster.php` (**testo identico a oggi**) e `en/crudbooster.php`
(inglese nuovo). **Cambio visibile solo per chi usa l'interfaccia in inglese**: oggi vede
comunque italiano. Fuori dal batch (da segnalare come follow-up): `$page_title = 'Import
Module'` (resterebbe inglese per tutti: tradurlo cambierebbe la pagina per gli utenti
italiani) e i testi della vista `module_generator/import.blade.php`.

## Perimetro

Dentro:
- `app/Services/ModuleGenerator/ModuleExporter.php`, `ModuleImporter.php`,
  `ModuleTransferException.php` (nuovi).
- `ModulsController.php`: `getExport`, `postImport` (corpi), rimozione dei privati migrati.
- `app/Helpers/ModuleGeneratorLayout.php`: nuovo metodo statico **additivo**
  `placeableFields(array $form)` con il corpo di `layoutPlaceableFields`; il metodo privato
  del controller resta come wrapper di una riga (lo usa il wizard: non toccare i chiamanti).
- `resources/lang/en/crudbooster.php` e `it/crudbooster.php`.
- Test nuovi (sotto).

Fuori (non toccare): wizard (`postStep*`, `save_table`, `getDelete`), `CRUDBooster::generateController`,
`min_var_export`, `ModuleHelper::sql_name_encode`, `getImport()` (nessuna logica), la vista
`import.blade.php`, le rotte e i link ai due endpoint (generati altrove, es. la lista moduli: non
cambiare gli URL), il formato del JSON (`format_version` 1 identico, retro-compatibile con i file
già esportati).

## Pattern scelto e perché

**Service semplice** (come `ChatAIClient`, doc 331): classi che non leggono sessione né richiesta;
il controller decide permesso, file e risposta. Si istanziano con `new` (ADR A14). Due classi
perché export e import hanno ragioni di cambiamento diverse; niente classe "ModuleManager"
generica (anti-pattern del repo). Gli errori di dominio escono come `ModuleTransferException`
con `getMessage()` (già tradotto) e `type()` (`'warning'|'danger'`): il controller la cattura e
fa il redirect **identico a oggi per ciascun endpoint**. Il service chiama `CRUDBooster::`
statico dove oggi lo fa il controller (`generateController`, `customControllersPath`,
`getTableStructure`): è accettato, wrapperizzarli è fuori scopo.

```php
namespace App\Services\ModuleGenerator;

class ModuleExporter
{
    /** @return array{filename: string, json: string} @throws ModuleTransferException */
    public function export($id): array
}

class ModuleImporter
{
    /** Tutto da "decodifica JSON" in poi, stesso ordine dei controlli di oggi.
     *  @return string nome del modulo importato  @throws ModuleTransferException */
    public function import(string $contents): string
}
```

Nel controller restano: `cbLoader()`, il controllo `isSuperadmin` con il suo `insertLog` e
redirect, e per l'import `Request::hasFile/isValid` ed estensione `.json` (HTTP), la lettura del
file (`file_get_contents($file->getRealPath())`) e il passaggio a `import()`. **Ordine invariato**:
file → estensione → JSON/struttura → nome esistente → prefisso riservato → tabella già usata →
colonne mancanti → crea tabella → `generateController` → insert → blocchi.

## Passi

1. **Test di caratterizzazione** (sotto), verdi sul codice attuale. Prima di procedere i test
   devono essere stati eseguiti (lezione 2026-10-30: la sola lettura non prevede i 500 di
   setup).
2. `ModuleGeneratorLayout::placeableFields()` + wrapper nel controller.
3. Creare `ModuleExporter`, poi `ModuleImporter` (spostando i privati con il codice **identico**,
   senza riformattare né rinominare variabili di logica) e la exception; chiavi i18n en/it.
4. Ridurre `getExport`/`postImport` a glue HTTP; togliere i privati migrati e gli `use`
   rimasti inutilizzati (`Blueprint` ecc.: controllare che `save_table` non li usi ancora).
5. `php -l` sui file nuovi/toccati; Pint `--test` solo su quelli (A8). Larastan solo su
   richiesta, e se si lancia, **da cache vuota** (`ModuleGeneratorLayout` è in `app/Helpers`).

## Test di caratterizzazione (rischio decrescente; sul codice attuale devono essere verdi)

Nuovo `tests/Feature/ModuleExportImportTest.php`, setup come `ModuleGeneratorCrudTest`
(`RefreshDatabase`, `SeedsCmsData`, `registerAdminModules()`, `actingAsSuperadmin()`, URL
`http://localhost/admin/module_generator/...`, file in `$fixtureFiles`). **Attenzione ai DDL**:
l'import fa `Schema::create` (commit implicito, rompe il rollback di `RefreshDatabase`): il
`tearDown` deve `Schema::dropIfExists` delle tabelle create, cancellare le righe `cms_moduls` e i
controller generati (stessa tecnica di `ModuleGeneratorCrudTest`, che crea tabelle dal wizard:
leggere come le ripulisce e replicarlo). Nomi di tabella con prefisso univoco `mg_phpunit_*`.

1. **Import, percorso felice con tabella nuova**: JSON con `module`, `table.columns` di tutti i
   tipi (`number`, `boolean`, `plaintext`, `longtext`, `decimal` con `size` `"10,2"`, `date`,
   `datetime`, `time`, `text` con `size`), `col`, `form`, `config`, `form_layout`. Attesi:
   redirect al mainpath con messaggio di successo, riga `cms_moduls` (`path`/`table_name`
   uguali alla tabella, `is_protected` 0, `is_active` 1), tabella con le colonne e quelle di
   cornice (`id`, `group`, `tenant`, `created_at`… `deleted_by`), file controller con i quattro
   blocchi, **nessuna** riga in `menus`/`cms_privileges_roles` per il nuovo modulo.
2. **Round trip**: dopo il test 1, `GET export/{id}` restituisce `col`, `form`, `config` e
   `form_layout` uguali a quelli importati (la whitelist di chiavi può farne cadere alcune: usare
   chiavi tutte ammesse) e `table.columns` con gli stessi tipi.
3. **Sicurezza (non deve peggiorare)**: valori pericolosi (`"; system('x'); //`) in `label`,
   `name`, `config.title_field`: nel controller generato compaiono solo via `var_export` (stessi
   assert di `assertPropertySafelyEmbedded`); chiavi `col` fuori whitelist scartate; campo form
   senza `label` o `name` saltato; **layout non valido scartato** (modulo con form piatto, nessun
   errore).
4. **Import, rifiuti** (uno per caso; **ciascuno preceduto dal positivo con lo stesso setup**, lezione
   2026-10-29): non superadmin → denied_access e nessuna riga; nessun file; estensione non
   `.json`; JSON invalido; `module.name` o `table.name` mancanti; nome modulo già esistente;
   tabella con prefisso riservato (`config('app.reserved_tables_prefix')`); tabella già usata da
   un altro modulo; tabella inesistente senza colonne. Per ognuno: testo e tipo del messaggio
   (`warning`/`danger`), redirect indietro, nessuna riga in `cms_moduls`, nessun file controller,
   nessuna tabella creata.
5. **Import su tabella già esistente**: la tabella non viene ricreata né toccata, il modulo sì.
6. **Export, rifiuti**: non superadmin; id inesistente; modulo cancellato (`deleted_at`);
   `is_protected = 1`; controller mancante su disco. Messaggio, tipo e redirect al mainpath.
7. **Export, colonne**: tabella con `int(11)`, `tinyint`, `text`, `longtext`, `decimal(10,2)`,
   `date`, `datetime`, `timestamp`, `time`, `varchar(50)` e un tipo non previsto: mappatura
   attesa (`timestamp`→`datetime`, sconosciuto→`text`); le colonne di `config('app.reserved_column_names')`
   escluse; header `Content-Type: application/json` e `Content-Disposition` con
   `<table>_module_export.json`; `form_layout` presente solo se il modulo ha un layout attivo.
8. **i18n**: le chiavi nuove esistono in en e it; il testo `it` coincide con la frase italiana di
   oggi (confronto con la stringa letterale, che il test conserva come riferimento); il testo `en`
   è diverso dall'`it`.

Regressione da rilanciare a fine batch: `ModuleGeneratorCrudTest`, `ModuleStep1MenuDefaultsTest`,
`PublicSystemRoutesAuthTest`, `StatisticBuilderCrudTest` (nessun legame, per escludere effetti sui
file generati). I test li lancia la persona con `/refactor-verify`.

## Vincoli

- Nessun metodo `public` nuovo nel controller (ogni metodo pubblico di una sottoclasse di
  `CBController` diventa una rotta); `getExport($id)`, `getImport()`, `postImport()` mantengono
  nome e firma.
- Codice dei privati spostato **identico**: niente type hint, cast o rinomine nuove (lezione
  doc 331). Nessuna transazione, nessun cambio all'ordine dei controlli o al messaggio italiano.
- Nessun `app()`/`resolve()` con classi nuove (ADR A14): `new ModuleExporter()`/`new ModuleImporter()`.
- Testi: ogni chiave in `en` **e** `it`; mai testo letterale nel service né nel controller.
- Il formato del JSON esportato non cambia di una virgola (file già in mano ai clienti).
- Nessun commit/push, nessuna suite in autonomia, nessun accesso a server remoti.

## Impatto sui clienti

Nessuno: è un controller di sistema, nessuna proprietà o hook di `CBController` cambia, i metodi
spostati erano `private` (nessun controller cliente può chiamarli), il JSON e gli URL non cambiano.
Effetto visibile unico: D1 (testi in inglese per chi usa l'interfaccia in inglese). Solo superadmin.

## Rischio

Medio-basso. Il punto delicato è che l'import scrive PHP nei controller generati: le difese
(`var_export`/`min_var_export`, whitelist) devono arrivare intatte nel service: i test 3 e 2 le
fissano. Secondo punto: l'import non è transazionale e non lo diventa. Terzo: i test creano tabelle
e file veri (DDL): senza un `tearDown` completo restano residui nel DB di test o in `app/Http/Controllers/`.

## Rollback

Ripristinare i corpi di `getExport`/`postImport` e i privati dal diff, rimuovere
`placeableFields()` e il wrapper (se non voluto), cancellare `app/Services/ModuleGenerator/`, i test
nuovi e le chiavi i18n. Nessuna migrazione, nessun dato.

## Dopo il batch

Reviewer (verifica anche i18n) → correzioni → `ch-doc-writer` (prossimo numero libero) →
post-mortem in `docs/architettura/lezioni.md`; aggiornare `pattern-nel-repo.md` (Service per
operazione di sistema con eccezione di dominio, ADR/nota se serve) e `mappa.md`
(`app/Services/ModuleGenerator/`). Follow-up da segnalare alla persona, non fatti: `$page_title
'Import Module'` e testi di `import.blade.php`; poi batch 2 (Hub/QMC di `AdminQlikItemsController`) e
`save_table`/`getDelete`.
