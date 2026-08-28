# 054 - Widget "Small Box": errore SQL reale, validazione campi obbligatori, testo link

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Bug fix / UI-UX
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/smallbox.blade.php`
  - `resources/views/crudbooster/statistic_builder/index.blade.php`

## Contesto

Seguito di [053](053-smallbox-icon-searchable-color-picker.md). Tre
segnalazioni dell'utente sullo stesso widget:

1. Una query SQL sbagliata nel campo "Count (SQL QUERY)" mostrava solo
   "ERROR", senza dettagli.
2. Lasciando vuoto un campo obbligatorio (es. Link) e cliccando Salva,
   apparentemente non succedeva nulla.
3. Il testo "More info" del pulsante del widget andava cambiato.

## Situazione prima

**Errore SQL** (`smallbox.blade.php`, `command=='showFunction'`):
```php
} catch (\Exception $e) {
    echo 'ERROR';
}
```

**Validazione campi obbligatori** (`statistic_builder/index.blade.php`,
click su "Save Changes" del modal di configurazione widget): c'era
**già** un meccanismo che cercava di evidenziare in rosso i campi
vuoti, ma con un bug:
```js
var $form_group = $input.parent('.mb-3 row');   // <-- selettore rotto
...
.parent('.mb-3 row').addClass('has-error');     // <-- stesso bug
```
Il div che avvolge ogni campo ha `class="mb-3 row"` (**due classi sullo
stesso elemento**, convenzione Bootstrap). Il selettore jQuery
`'.mb-3 row'` (con uno spazio) significa invece "un elemento discendente
`<row>`" — `row` non è nemmeno un tag HTML valido, quindi non trova mai
nulla. Risultato: `.addClass('has-error')` non veniva mai applicata a
niente, `return false;` bloccava comunque il submit, ma **nessun
feedback visivo appariva** — esattamente il sintomo segnalato
("non succede nulla"). Bug generico, non specifico a Small Box: la
stessa funzione serve **tutti** i widget dello Statistic Builder.

**Testo pulsante**: `<a href="[link]" class="small-box-footer">More info ...`

## Situazione dopo

- **Errore SQL reale**: il messaggio dell'eccezione (`$e->getMessage()`)
  viene mostrato, con escape HTML, al posto del numero nel widget:
  ```php
  echo "<span class='small-box-sql-error'>" . e($e->getMessage()) . "</span>";
  ```
  Aggiunto uno stile scoped (`.small-box-sql-error`) per far andare a
  capo il testo ed evitare che un messaggio lungo rompa il layout della
  card (pensata per un numero corto).
- **Validazione campi obbligatori corretta**: selettore fixato in
  `.mb-3.row` (senza spazio, entrambe le classi sullo stesso elemento)
  sia per l'evidenziazione (`has-error`, bordo rosso via CSS Bootstrap
  già vendorizzato) sia per il recupero dell'etichetta del campo.
  Aggiunto anche un **messaggio di avviso esplicito**: un alert rosso
  fisso sopra il corpo del modal (`#modal-statistic-validation-alert`,
  fuori da `.modal-body` così sopravvive al `.html()` che sostituisce
  il contenuto del form ad ogni apertura), con testo
  "Campi obbligatori mancanti: <elenco etichette>".
- **Testo pulsante**: "More info" → "Dettagli".

## Bug corretto en passant

Lo stesso identico bug del selettore (`'.mb-3 row'` invece di
`'.mb-3.row'`) è nel file **generico** `statistic_builder/index.blade.php`,
usato dal modal di modifica di **ogni** widget (chartarea, chartbar,
table, panelarea, ecc.), non solo Small Box — quindi il fix qui si
propaga a tutti loro.

## Motivazione

- Un messaggio d'errore SQL vero aiuta a capire e correggere subito
  la query, invece di dover indovinare cosa non va.
- Un campo obbligatorio vuoto deve dare un riscontro visibile e
  comprensibile, non un fallimento silenzioso del salvataggio.
- "Dettagli" è più neutro/generico di "More info" (che era anche
  l'unico testo in inglese in un'interfaccia altrimenti italiana).

## Test

- Compilazione Blade (`Blade::compileString` + `php -l`) di entrambi i
  file: nessun errore.
- Reso `command=='showFunction'` con una query SQL non valida (colonna
  inesistente): output contiene il messaggio reale di MySQL
  ("Unknown column ... in field list ..."), non più la stringa
  generica "ERROR".
- Stessa resa con una query valida (`select count(*) as c from tenants`):
  nessuna regressione, restituisce il valore corretto.
- Verificato che i div dei campi nel form generato abbiano davvero
  `class="mb-3 row"` (contati 5 su 5 campi) — conferma che il nuovo
  selettore `.mb-3.row` li intercetta correttamente (il vecchio
  `.mb-3 row` non ne avrebbe trovato nessuno).
- `curl` senza sessione su `/admin/statistic_builder`,
  `/admin/statistic_builder/edit-component/abc`: 302, nessun 500.
- Route count invariato (513).

**Non verificato visivamente in browser** (il comportamento del modal e
del messaggio di validazione richiede un'interazione utente reale) —
lasciato al giro di test manuale dell'utente.

## Rischi e note

- Il messaggio SQL viene mostrato a **qualunque utente admin** che
  visualizzi la dashboard con quel widget (non solo a chi lo configura),
  perché la sostituzione avviene lato server ogni volta che il widget
  viene renderizzato (`StatisticBuilderController::getViewComponent()`,
  non toccato). Espone dettagli tecnici della query (nomi di tabelle/colonne)
  ad altri admin autenticati — rischio contenuto (ambiente già protetto da
  login admin), ma non anonimo: se in futuro si volesse restringere la
  visibilità del messaggio dettagliato ai soli superadmin, andrebbe
  aggiunto un controllo esplicito lì.
- Rimosso il `setTimeout(..., 200)` che ritardava l'applicazione della
  classe `has-error`: non serviva a nulla di osservabile (nessun'altra
  operazione asincrona nel mezzo), la evidenziazione ora è immediata.
- Il fix del selettore `.mb-3.row` è nel file condiviso da tutti i
  widget: non sono stati testati singolarmente gli altri tipi di widget,
  ma la modifica è puramente additiva/correttiva sullo stesso
  meccanismo generico, stesso rischio di regressione basso.

## Rollback

`git revert` del commit — ripristina "ERROR" generico, il selettore
jQuery rotto (nessun feedback su campi obbligatori vuoti) e il testo
"More info".
