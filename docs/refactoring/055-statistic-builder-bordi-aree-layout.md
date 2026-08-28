# 055 - Statistic Builder: bordi visibili delle aree in modalità builder

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Miglioramento UI/UX
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/index.blade.php`

## Contesto

Segnalato dall'utente: su `/admin/statistic_builder/builder/2` (la
pagina dove si trascinano i widget nelle aree del layout) non si
vedevano i confini delle singole aree (`.connectedSortable`) — solo
`position: relative`, nessun bordo/sfondo. Difficile capire dove
finisce un'area e inizia l'altra mentre si organizzano i widget.

## Situazione prima

```css
.connectedSortable {
    position: relative;
}
```
Nessun indicatore visivo dei confini dell'area.

## Situazione dopo

Aggiunta una regola CSS con bordo tratteggiato, sfondo leggero e
altezza minima, **scoped alla sola modalità builder** (stesso
`@if(CRUDBooster::getCurrentMethod()=='getBuilder')` già usato dal
CSS preesistente per lo stile "cursor: move" durante il drag&drop):

```css
@if(CRUDBooster::getCurrentMethod()=='getBuilder')
    ...
    .connectedSortable {
        border: 2px dashed #ccc;
        border-radius: 4px;
        background: #fafafa;
        min-height: 100px;
    }
@endif
```

La regola preesistente `.connectedSortable { position: relative; }`
(fuori dal guard, sempre attiva) resta invariata.

## Motivazione

I bordi devono comparire solo nella pagina di editing del layout (dove
serve capire la struttura per trascinare i widget), non sulla
dashboard finita (`getDashboard()`/`getShow()`, che riusa la stessa
view `index.blade.php`) — lì i widget mostrano contenuto vero e un
bordo tratteggiato attorno sarebbe solo rumore visivo.

## Test

- Compilazione Blade (`Blade::compileString` + `php -l`): nessun
  errore.
- Verificato isolatamente che `CRUDBooster::getCurrentMethod()`
  risolve correttamente a `'getBuilder'` quando la rotta corrente è
  `StatisticBuilderController@getBuilder` (stesso meccanismo già
  usato, non toccato, dal blocco CSS preesistente subito sopra).
- `curl` senza sessione su `/admin/statistic_builder`,
  `/admin/statistic_builder/builder/2`: entrambi 302, nessun 500.
- Route count invariato (513).

**Non verificato visivamente in browser** (il rendering completo di
questa pagina, che dipende da molte variabili popolate solo da una
sessione admin reale, non è renderizzabile in isolamento con gli
script di verifica usati in questa sessione — stesso limite
già documentato ripetutamente, es.
[043](043-privileges-form-come-standard.md#test)) — lasciato al giro
di test manuale dell'utente.

## Rischi e note

- Modifica puramente CSS, scoped allo stesso guard preesistente già
  usato per altri stili della sola modalità builder: rischio di
  regressione minimo.

## Rollback

`git revert` del commit — le aree tornano senza bordo visibile in
modalità builder.
