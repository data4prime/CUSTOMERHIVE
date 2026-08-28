# 056 - Statistic Builder: massimo un widget per area del layout

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Miglioramento UI/UX
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/index.blade.php`

## Contesto

Segnalato dall'utente: su `/admin/statistic_builder/builder/2` si
potevano trascinare più widget nella stessa area (`connectedSortable`)
del layout — nessun limite imposto. Richiesto: ogni area deve poter
contenere un solo widget alla volta.

Il drag&drop è gestito da jQuery UI Sortable con `connectWith`: ogni
elemento `.connectedSortable` (sia le aree del layout `#area1`,
`#area2`... sia i singoli pulsanti "aggiungi widget" nella sidebar,
ciascuno in un proprio `<li class='connectedSortable'>`) è una lista
sortable collegata alle altre. Due flussi distinti finiscono
nell'area:
1. Un widget **esistente** spostato da un'altra area (un
   `.border-box`, la classe condivisa da tutti e 9 i tipi di widget).
2. Un **nuovo** widget trascinato dalla sidebar (`.button-widget-area`,
   che diventa `.border-box` solo dopo che `addWidget()` lo
   materializza via AJAX, nel gestore `stop`).

## Situazione prima

```js
$(".connectedSortable").sortable({
    ...
    stop: function (event, ui) { ... },
    update: function (event, ui) { ... }
});
```
Nessun controllo sul numero di widget per area.

## Situazione dopo

Aggiunto un gestore `receive` (evento jQuery UI che scatta sulla lista
che **riceve** un elemento da un'altra lista connessa) che conta gli
"occupanti" dell'area di destinazione (`.border-box` e
`.button-widget-area` insieme, per coprire sia il caso "widget
esistente spostato" sia "nuovo widget appena trascinato, non ancora
materializzato"): se il conteggio supera 1, annulla l'operazione con
`ui.sender.sortable('cancel')` (tecnica standard di jQuery UI per
rifiutare un drop tra sortable connesse — riporta l'elemento alla sua
posizione di origine).

Il controllo si applica solo alle aree vere del layout (id che inizia
per `"area"`), non ai pulsanti della sidebar, così non interferisce
con il funzionamento della palette widget.

Aggiunto anche un flag (`dropRejected`) per evitare che il gestore
`stop` esistente (che chiama `addWidget()` per i nuovi widget) scattasse
comunque dopo un annullamento, cosa che avrebbe creato un widget con
un'area di destinazione non valida.

## Motivazione

Un'area con più widget sovrapposti non ha senso nel modello del
layout (ogni area è una singola cella della griglia, vedi
[051](051-dashboard-layouts-builder-visuale.md)) — imporre il limite
lato UI evita di creare layout inconsistenti.

## Test

- Compilazione Blade (`Blade::compileString` + `php -l`): nessun
  errore.
- Verifica manuale della struttura JS (bilanciamento parentesi/graffe,
  ordine delle proprietà nell'oggetto passato a `.sortable()`).
- `curl` senza sessione su `/admin/statistic_builder`,
  `/admin/statistic_builder/builder/2`: entrambi 302, nessun 500.
- Route count invariato (513).

**Non verificato con un test di drag&drop reale in browser** (richiede
un'interazione utente reale con jQuery UI Sortable, non riproducibile
con gli strumenti di verifica usati in questa sessione) — lasciato al
giro di test manuale dell'utente. La logica si basa sul comportamento
documentato di `sortable('cancel')` all'interno dell'evento `receive`,
un pattern standard per questo tipo di vincolo.

## Rischi e note

- **Solo lato client**: nessun controllo server-side aggiunto (né in
  `postUpdateAreaComponent()` né in `postAddComponent()`, non
  toccati). Un utente che chiamasse direttamente quegli endpoint
  bypasserebbe il limite. Scelta deliberata per restare aderente alla
  richiesta (comportamento del drag&drop nell'interfaccia), in un'area
  amministrativa già protetta da login — se in futuro serve una
  garanzia più forte, andrebbe aggiunto anche un controllo lato
  server.
- Il riordino di un widget **all'interno della stessa area** non passa
  mai dall'evento `receive` (scatta solo per spostamenti tra liste
  diverse), quindi non è interessato da questo controllo.

## Rollback

`git revert` del commit — rimuove il gestore `receive` e il flag
`dropRejected`, tornando a poter accumulare più widget nella stessa
area.
