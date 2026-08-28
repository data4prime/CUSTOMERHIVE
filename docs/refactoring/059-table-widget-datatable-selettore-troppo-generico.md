# 059 - Widget "Table": selettore DataTable troppo generico ("tabella tagliata")

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Bug fix
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/table.blade.php`

## Contesto

Segnalato dall'utente: sulla dashboard vera
(`/admin/statistic_builder/show/test-dashboard?m=2`) il widget Table
appare "tagliato" (nessuna barra di ricerca/paginazione, righe non
gestite), mentre sul builder
(`/admin/statistic_builder/builder/2`) mostra correttamente i
controlli DataTables (search, ecc.).

Causa: lo script che inizializza DataTables usava un selettore
**globale**, non specifico al widget:

```js
$('table.table').DataTable({...});
```

`table.table` seleziona **qualunque** `<table class="table">`
presente nella pagina in quel momento — comprese le tabelle di **altri**
widget Table già renderizzati sulla stessa dashboard (uno scenario
plausibile: più widget Table nella stessa dashboard, caricati in modo
asincrono via AJAX). Se una di quelle tabelle è già stata trasformata
in DataTable, richiamare `.DataTable()` di nuovo su di essa lancia un
errore ("Cannot reinitialise DataTable"), che può impedire
l'inizializzazione anche della tabella nuova appena aggiunta,
lasciandola come semplice HTML grezzo — tutte le righe renderizzate
senza controllo, aspetto "tagliato" nel contenitore pensato per una
vista paginata.

## Situazione prima

```html
<table class='table table-striped'>...</table>
<script>
    $('table.table').DataTable({...});
</script>
```

## Situazione dopo

```html
<table id="table-widget-{{ $componentID }}" class='table table-striped'>...</table>
<script>
    (function () {
        var $table = $('#table-widget-{{ $componentID }}');
        if ($.fn.DataTable.isDataTable($table)) {
            return;
        }
        $table.DataTable({...});
    })();
</script>
```

Ogni tabella ha ora un id univoco legato al `componentID` del widget;
l'inizializzazione è scoped a quella singola tabella e protetta da un
controllo (`$.fn.DataTable.isDataTable()`) che evita di reinizializzare
una tabella già trasformata.

## Motivazione

Un selettore globale (`table.table`) su una pagina che può ospitare
più istanze dello stesso widget è intrinsecamente fragile — il fix
elimina la possibilità di interferenza tra widget Table diversi sulla
stessa dashboard, indipendentemente dai dettagli specifici di quale
sequenza di caricamento asincrono l'ha fatta emergere nel caso
segnalato.

## Test

- Compilazione Blade (`Blade::compileString` + `php -l`): nessun
  errore.
- Reso `command=='showFunction'` con una query valida: la tabella ha
  l'id `table-widget-{componentID}`, lo script usa il selettore
  scoped, il controllo anti-doppia-inizializzazione è presente,
  nessuna traccia del vecchio selettore generico `table.table`.
- `curl` senza sessione su `/admin/statistic_builder`,
  `/admin/statistic_builder/view-component/abc`: entrambi 302, nessun
  500.
- Route count invariato (513).

**Non verificato visivamente in browser con più widget Table sulla
stessa dashboard** (richiede un'interazione utente reale) — lasciato
al giro di test manuale dell'utente.

## Rischi e note

- Non è stata individuata con certezza la sequenza esatta che causava
  il problema sulla dashboard specifica dell'utente (richiederebbe
  ispezionare il DOM/console del browser in quel momento); il fix
  risolve comunque la causa strutturale più probabile e rende il
  meccanismo robusto indipendentemente dal numero di widget Table
  presenti.

## Rollback

`git revert` del commit — ripristina il selettore globale
`table.table`, che torna a rischiare conflitti tra più widget Table
sulla stessa pagina.
