# 152 - Grafico a linee: più linee sullo stesso grafico

- **Data**: 2026-09-29
- **Stato**: Fatto
- **Area**: Statistic Builder (dashboard a griglia libera)
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/chartline_v2.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/_source_config.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/_query_mode_toggle.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/_query_builder_fields.blade.php`
  - `app/Http/Controllers/System/StatisticBuilderController.php`

## Contesto

Il widget "Grafico a linee" (`chartline_v2.blade.php`) supportava una sola
serie, a differenza del widget legacy `chartline.blade.php` (Morris.js,
usato solo dalle dashboard `legacy_areas`), che ammetteva più query separate
da `;` con relativi nomi linea. Discusso con l'utente il caso d'uso reale:
linee **indipendenti**, con sorgenti dati diverse (es. "Fatturato reale" da
`mg_fatture` accanto a "Obiettivo" da un'altra query), non solo una singola
metrica spezzata per categoria — quel caso, più comune, era già coperto
separando "Raggruppa per" in Colonna+Formato (doc 151).

## Situazione prima

`chartline_v2.blade.php` renderizzava sempre una singola serie ApexCharts:
la configurazione (`config[name]`, `config[color]`, `config[dataset]`/
`config[sql]`, ecc.) descriveva un'unica sorgente dati, risolta in
`StatisticBuilderController::renderComponentPayload()` (dataset via
`DashboardDatasetRegistry::execute()` in modalità builder, oppure `DB::select()`
sulla SQL libera) e passata come array piatto `{label,value}[]` allo
`showFunction` del widget.

## Situazione dopo

- La prima linea resta **esattamente come prima** (stessi nomi di campo
  `config[...]`, stesso rendering quando non ci sono linee extra — nessuna
  differenza per nessun widget già salvato).
- Nuova sezione "Altre linee (opzionale)" nel popup di configurazione: fino a
  4 blocchi extra, ciascuno una copia completa della configurazione sorgente
  (Nome, Colore, toggle SQL libera/Query guidata), salvati come
  `config[lines][N]`. Solo i blocchi già popolati sono visibili all'apertura;
  "+ Aggiungi linea" rivela il successivo. "Rimuovi" pulisce nome/dataset/sql
  del blocco e lo nasconde di nuovo.
- Per riusare gli stessi partial di configurazione (`_source_config`,
  `_query_mode_toggle`, `_query_builder_fields`) una volta per linea senza
  collisioni di `name=`/id DOM, sono stati parametrizzati con `$fieldPrefix`
  (default `'config'`) e `$scopeId` (default `$componentID`) — con i default
  il comportamento per gli usi esistenti (smallbox, prima linea del grafico)
  resta identico.
- Nuovo metodo privato `StatisticBuilderController::resolveSourceRows()`,
  che estrae la logica di risoluzione di UNA sorgente (builder → dataset,
  sql → `DB::select()` con sostituzione sessioni) già usata nel blocco
  `mode==='builder'` esistente. `renderComponentPayload()` ha un nuovo ramo,
  attivo solo se `!empty($config->lines)`, che risolve la prima linea più
  ogni linea extra non vuota e passa a `showFunction` un array annidato
  `{name,color,rows}[]` invece del vecchio array piatto.
- `chartline_v2.blade.php` distingue le due forme con
  `isset($value[0]['rows'])`: se assente, comportamento invariato (una sola
  serie); se presente, applica l'algoritmo di unione label del widget legacy
  (`array_unique` su tutte le label di tutte le linee, poi un punto per
  linea per label, `0` dove manca) e costruisce una serie ApexCharts per
  linea, con colore di default da una palette fissa per le linee senza
  colore proprio.

## Motivazione

Permette di confrontare metriche di sorgenti diverse sullo stesso grafico
(es. reale vs obiettivo) senza dover ricorrere a un secondo widget separato,
mantenendo lo stesso principio "behavior-preserving" già seguito negli
interventi precedenti su questo widget (147-151): nessuna riga del percorso
a una sola linea viene toccata quando `config->lines` è vuoto.

## Test

- `php -l` su tutti i file toccati.
- Script PHP isolato (bootstrap Laravel, poi rimosso) via
  `docker compose exec app php`, contro i dati reali già seminati in
  `mg_fatture`/`mg_contratti`:
  - `resolveSourceRows()`: modalità builder (dataset `table:mg_contratti`,
    group by `stato`) e modalità sql libera, entrambe con dati corretti;
    sorgente con SQL che fallisce → `[]` senza lanciare; sorgente vuota →
    `[]`.
  - `renderComponentPayload()` **senza** `config->lines` (mode `sql` e mode
    `builder`): output identico a prima dell'intervento, una sola serie.
  - `renderComponentPayload()` **con** 2 linee extra (una builder valida,
    una vuota): la vuota viene scartata, le categorie sono l'unione delle
    label di entrambe le linee valide, ogni serie ha `0` nei punti dove non
    ha un valore proprio.
  - Una linea extra con query che fallisce non fa fallire il grafico: la
    linea valida resta, quella rotta appare con tutti i punti a `0`.
- **Bug trovato e corretto durante la verifica**: il ciclo generico dei
  placeholder in `renderComponentPayload()` (quello che sostituisce
  `[chiave]` per ogni proprietà scalare di `$config`) consumava già
  `[sql]` quando la prima linea era in modalità `sql` (il salto esisteva
  solo per `mode==='builder'`), lasciando il nuovo blocco multi-linea senza
  alcun `[sql]` da sostituire — il grafico mostrava solo la prima linea
  anche con linee extra configurate. Corretto estendendo la condizione di
  salto a `$mode === 'builder' || !empty($config->lines)`.
- Verifica del sorgente Blade compilato (`storage/framework/views/`) per il
  commento in cima al file, riscritto in questo intervento (gotcha noto,
  vedi `CLAUDE.md`): confermato che il `@for` delle "Altre linee", la JS di
  aggiungi/rimuovi e la nuova logica di pivot in `showFunction` sono tutte
  presenti nel compilato, nulla è stato inghiottito.
- Non testato in browser (regola dell'utente: solo su richiesta esplicita).

## Rischi e note

- Una linea con query in errore non mostra alcun messaggio d'errore nel
  grafico (appare come una linea piatta a `0`) — per il debug resta il
  pulsante "Prova" nel pannello di configurazione di quella linea, che
  mostra l'errore vero. Scelta di v1: un errore su una linea extra non deve
  far sparire l'intero grafico.
- "Rimuovi linea" pulisce solo nome/dataset/sql (i campi che il backend usa
  per decidere se una linea è vuota) via JS, non ogni singolo filtro/select
  della Query guidata — se il blocco viene riaperto con "+ Aggiungi linea"
  può mostrare filtri/metrica ancora valorizzati da una configurazione
  precedente, orfani ma innocui (il dataset vuoto fa comunque ignorare la
  linea lato backend finché non si sceglie un nuovo dataset).
- Massimo 4 linee extra (5 linee totali) — limite fisso scelto per evitare
  un endpoint AJAX dedicato; se in futuro serve di più va rivista la UI.

## Rollback

Nessuna migrazione di dati: i widget salvati prima di questo intervento non
hanno mai la chiave `config.lines`, quindi tornare al commit precedente (o
rimuovere il codice aggiunto) non richiede alcuna pulizia — il formato
config resta compatibile in entrambe le direzioni.
