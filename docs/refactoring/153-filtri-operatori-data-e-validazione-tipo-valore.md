# 153 - Filtri Query guidata: operatori data (>/<) e validazione tipo valore

- **Data**: 2026-09-29
- **Stato**: Fatto
- **Area**: Statistic Builder / Backend + Frontend
- **File/aree di codice coinvolte**:
  - `app/Dashboards/DashboardDatasetRegistry.php`
  - `resources/views/crudbooster/statistic_builder/components/_query_builder_fields.blade.php`

## Contesto

Segnalato dall'utente su un widget "Grafico a linee": un filtro sembrava
"non salvarsi". Indagine (senza browser, solo script PHP isolati e
ispezione DB): la meccanica di salvataggio/ricarica dei filtri è risultata
corretta in ogni scenario verificabile da codice - form→server→DB→ricarica
tutto sano, sia per la linea principale sia per una linea extra del
grafico multi-linea (152). L'utente ha poi chiarito l'errore reale: aveva
scelto per sbaglio la colonna **"Created By"** (numerica, l'id
dell'utente che ha creato la riga) invece di **"Created At"** (data), con
un filtro tipo `Created By > 2026-01-01` - un confronto tra un intero e
una stringa data, che MySQL esegue con una conversione implicita
**silenziosa** (nessun errore, risultato vuoto o sbagliato).

Da qui due richieste dirette:
1. In un caso come questo, un messaggio d'errore esplicito invece del
   silenzio.
2. Le colonne di tipo data devono offrire anche `>`/`<` (non solo
   uguaglianza/diversità/"ultimi N giorni") - così l'utente può esprimere
   "prima/dopo il ..." direttamente sulla colonna giusta.

## Situazione prima

- `DashboardDatasetRegistry::OPERATORS_BY_TYPE['date']` offriva solo
  `['=', '!=', 'last_days']` - nessun confronto diretto prima/dopo una
  data.
- `applyFilter()` validava solo che l'operatore fosse ammesso per il tipo
  di colonna (whitelist), ma **non** che il *valore* fosse del formato
  giusto per quel tipo - un confronto numero-vs-stringa-data (o
  data-vs-testo-a-caso) passava silenzioso fino a MySQL, che lo esegue
  con conversione implicita invece di un errore.
- Lato client, `_query_builder_fields.blade.php` rispecchiava lo stesso
  elenco ridotto di operatori per le colonne data, e il campo valore per
  una colonna data restava un `<input type="text">` libero (nessun aiuto
  contro un formato scritto a mano sbagliato).

## Situazione dopo

- `OPERATORS_BY_TYPE['date']` ora include anche `>`, `<`, `>=`, `<=`
  (stesso elenco lato client in `_query_builder_fields.blade.php`).
- Nuovo metodo privato `DashboardDatasetRegistry::validateFilterValue()`,
  chiamato da `applyFilter()` prima di un confronto diretto (`=`, `!=`,
  `>`, `<`, `>=`, `<=` - non per `contains`/`last_days`, già validati a
  parte): per colonna `numeric` verifica `is_numeric($value)`, per colonna
  `date` verifica un formato `AAAA-MM-GG` (opzionalmente con orario) via
  regex. Se il valore non è del formato atteso, lancia
  `\InvalidArgumentException` con un messaggio che nomina la colonna e il
  valore ricevuto.
- Questo errore arriva già, senza altre modifiche, sia al pulsante "Prova"
  (`postDatasetPreview()` lo cattura già e lo restituisce come JSON
  `{error: ...}`, mostrato in rosso nel pannello) sia al rendering vero
  del widget in modalità builder a sorgente singola (già avvolto in
  `try/catch` in `renderComponentPayload()`, mostra
  `<span class="small-box-sql-error">`).
- Il campo valore del filtro diventa un `<input type="date">` (date picker
  nativo) invece di testo libero quando la colonna scelta è di tipo data e
  l'operatore non è "ultimi N giorni" - riduce alla radice il rischio di
  scrivere un formato sbagliato a mano.

## Motivazione

L'utente può facilmente scegliere la colonna sbagliata (numerica invece
di data, o viceversa) - senza validazione il sintomo è "il filtro non
sembra fare nulla" (dati vuoti o sbagliati, nessun errore), difficile da
diagnosticare. Con la validazione il sintomo diventa un messaggio
esplicito che nomina colonna e valore, uguale al pattern già esistente
per operatore non ammesso (`applyFilter()` lancia già
`\InvalidArgumentException` in quel caso, stesso stile di errore).

## Test

- `php -l` su entrambi i file.
- Script PHP isolato (bootstrap Laravel, poi rimosso), via
  `docker compose exec app php`:
  - Riprodotto esattamente lo scenario dell'utente (`created_by > '2026-01-01'`,
    colonna numerica) → `InvalidArgumentException: Il filtro su
    'created_by' richiede un valore numerico, non "2026-01-01".`
  - Colonna data con `>`/`<=` e valore data valido → risultati corretti.
  - Colonna data con valore non-data (`'pippo'`) → errore chiaro.
  - **Regressione**: rieseguiti tutti i filtri già salvati sui widget
    `chartline_v2` reali in DB (numerico `=`, testo `=`, `last_days`) e
    ri-renderizzati tutti i widget `chartline_v2` esistenti con filtri via
    `renderComponentPayload()` - nessun errore introdotto, tutti
    renderizzano come prima.
- Verificato il sorgente Blade compilato (`storage/framework/views/`) per
  confermare che i nuovi operatori/il nuovo `type="date"` siano presenti.
- Non testato in browser (regola dell'utente: solo su richiesta esplicita).

## Rischi e note

- La regex di validazione data accetta `AAAA-MM-GG` (e opzionalmente
  `HH:MM` o `HH:MM:SS`), lo stesso formato che il nuovo `<input
  type="date">` produce e che le colonne `date`/`datetime`/`timestamp` di
  MySQL usano nativamente - non valida che la data sia "reale" (es.
  `2026-13-45` passa la regex ma non è un giorno valido); un valore del
  genere arriverebbe comunque a MySQL, che lo rifiuterebbe con un proprio
  errore SQL (comunque visibile, solo con un messaggio meno amichevole).
- La validazione scatta solo per i confronti diretti su colonna `numeric`/
  `date` - un filtro `contains` su testo o `last_days` restano invariati
  (già validati a parte, non toccati qui).
- `resolveSourceRows()` (Grafico a linee multi-linea, 152) continua a
  inghiottire silenziosamente qualunque eccezione per le linee EXTRA
  (linea vuota invece di far fallire l'intero grafico, scelta già
  documentata in 152) - questa nuova validazione la farà scattare più
  spesso lì, ma il comportamento "silenzioso" per le linee extra resta lo
  stesso di prima, il messaggio d'errore esplicito arriva solo per la
  sorgente principale del widget e per "Prova".

## Rollback

`git diff` di `DashboardDatasetRegistry.php` e
`_query_builder_fields.blade.php` per tornare alla situazione precedente
(nessuna riga di dato già salvato viene toccata: filtri già validi
continuano a funzionare identicamente con o senza questa modifica).
