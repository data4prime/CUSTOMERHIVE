# 173 - Builder a griglia: "Importa" un widget da un'altra dashboard

- **Data**: 2026-09-30
- **Stato**: Completato (test scritti ma non eseguiti; non verificato nel browser)
- **Area**: Statistic Builder (UI/UX + backend)
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/StatisticBuilderController.php` (`getImportSources`, `getImportSourceWidgets`, `postImportComponent` + helper privati)
  - `resources/views/crudbooster/statistic_builder/builder_grid.blade.php` (riquadro "Importa", modale, JS)
  - `resources/lang/{en,it}/crudbooster.php`, `tests/Feature/StatisticWidgetImportTest.php`

## Contesto

Richiesta: poter usare un widget di una dashboard anche in un'altra. Dopo
brainstorming scelta la strada più semplice: **copia indipendente**, solo
superadmin, nessun collegamento tra originale e copia.

## Situazione prima

Un widget è una riga di `cms_statistic_components` legata a una sola dashboard
(`id_cms_statistics`), con tipo, nome, `config` JSON e posizione/dimensioni
(`pos_x/pos_y/width/height`). Per averlo in un'altra dashboard andava ricreato a
mano (o copiato a mano dal DB).

## Situazione dopo

Nel builder a griglia, sotto la lista dei widget disponibili, c'è un riquadro
"Importa" (stessa grafica dei tipi di widget, bordo tratteggiato). Il click apre
la modale **"Copia da"** con due select2 ricercabili in ordine alfabetico:
prima la dashboard, poi il widget da copiare (la seconda si abilita dopo la
prima). "Importa" aggiunge la copia in griglia, già selezionata con la sidebar
di configurazione aperta, come un widget appena creato dalla palette.

- Dashboard elencate: solo quelle con almeno un widget, inclusa la corrente
  (serve anche a duplicare un widget nella stessa dashboard: in quel caso il nome
  prende il suffisso "(copia)"). Sorgenti legacy a aree incluse.
- Etichetta widget: `Nome · Tipo` (nome da `config->name`, altrimenti colonna
  `name`, altrimenti "Senza nome").
- La copia mantiene tipo, nome e `config`; dimensioni = quelle dell'originale, o
  quelle standard del tipo se mancano (widget da dashboard legacy); posizione
  scelta da gridstack (prima libera). `componentID` nuovo, generato con
  `uniqid()` (il flusso di creazione usa `md5(time())`, che collide per due
  widget creati nello stesso secondo).
- Endpoint: `GET import-sources`, `GET import-source-widgets/{id}`,
  `POST import-component`. Tutti solo superadmin (403 JSON per gli altri).
- Il widget Qlik funziona subito: il collegamento al mashup sta dentro il
  `config` (`config->mashups`), non in tabelle separate.
- Log: riga `log_statistic_widget_imported` con widget, dashboard di origine e
  destinazione.

## Motivazione

Copia indipendente = nessuna dipendenza tra dashboard, nessuna modifica allo
schema (nessuna migration), rollback banale. Alternative considerate e rimandate:
widget collegato (una modifica si propaga) e libreria di widget.

## Test

- `php -l` su controller, file lingua e test; compilazione Blade + `php -l` di
  `builder_grid`; `route:list` mostra le 3 nuove route.
- 7 test in `tests/Feature/StatisticWidgetImportTest.php` (ordinamento
  alfabetico, dashboard vuote escluse, dimensioni di default, copia
  indipendente, suffisso "(copia)" nella stessa dashboard, 404 su id inesistenti,
  403 per chi non è superadmin). **Non eseguiti** (la suite parte solo su
  richiesta): `docker compose exec app php artisan test --filter=StatisticWidgetImportTest`.
- **Non verificato nel browser**: modale e select2 (selezione, ricerca, stato
  disabilitato), comparsa della copia in griglia, widget Qlik copiato.

## Rischi e note

- Il `config` viene copiato tale e quale, inclusa l'eventuale SQL libera: per
  questo l'import è solo superadmin, come `postSaveComponent()`.
- `select2` è caricato a richiesta con lo stesso caricatore condiviso dei pannelli
  di configurazione (`window.__chEnsureSelect2`); se il suo CSS/JS non fosse
  raggiungibile la modale non si apre.
- Il riquadro vive solo nel builder a griglia: per importare in una dashboard
  legacy a aree va prima convertita alla griglia libera.
- Una copia in dashboard dove il widget "Elenco record" punta a una tabella non
  leggibile dal visualizzatore resta nascosta come l'originale.

## Rollback

Togliere le tre route/metodi dal controller e il blocco "Importa" (riquadro,
modale, JS) da `builder_grid.blade.php`; nessuna modifica a DB.
