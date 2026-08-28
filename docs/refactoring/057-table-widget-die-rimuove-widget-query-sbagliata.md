# 057 - Widget "Table": query sbagliata faceva sparire il widget (die())

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Bug fix
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/table.blade.php`

## Contesto

Segnalato dall'utente: nel widget "Table" dello Statistic Builder, se
la query SQL configurata è sbagliata, il widget sembra sparire
dall'area invece di mostrare un errore.

Causa: nel ramo `command=='showFunction'` (che sostituisce il
placeholder `[sql]` col risultato vero), un errore SQL veniva gestito
con `die('ERROR')`. Questo codice gira **dentro**
`StatisticBuilderController::getViewComponent()`, che normalmente
ritorna una risposta JSON (`response()->json(compact('componentID',
'layout', ...))`) consumata via AJAX da
`statistic_builder/index.blade.php`
(`$('#' + areaname).append(view.layout)`). `die()` interrompe
l'intero processo PHP **immediatamente**: la risposta HTTP diventa la
stringa letterale `"ERROR"` invece del JSON atteso. Il JS che si
aspetta `view.layout` si ritrova con `view` non un oggetto ma del
testo non parsabile — `view.layout` è `undefined`, `.append(undefined)`
non fa nulla, e il placeholder di caricamento del widget
(`.area-loading`) non viene mai sostituito: il widget risulta
visivamente assente, come se fosse stato tolto.

Lo stesso identico problema era già stato risolto per il widget Small
Box in [054](054-smallbox-errore-sql-validazione-required-testo-link.md)
(lì però l'errore veniva già mostrato con un `echo`, non un `die` — il
bug qui in Table è più grave perché interrompe l'intera risposta AJAX,
non solo il singolo placeholder).

## Situazione prima

```php
try {
    ...
    $sql = DB::select(DB::raw($value));
} catch (\Exception $e) {
    die('ERROR');
}
?>
@if($sql)
<table>...</table>
@endif
```

## Situazione dopo

```php
$sql = null;
$sqlError = null;
try {
    ...
    $sql = DB::select(DB::raw($value));
} catch (\Exception $e) {
    $sqlError = $e->getMessage();
}
?>
@if($sqlError)
<div class="alert alert-danger table-widget-sql-error" style="margin:15px;">{{ $sqlError }}</div>
@elseif($sql)
<table>...</table>
@endif
```

Niente più `die()`: l'errore viene mostrato **dentro** il widget
(come già fatto per Small Box), la risposta AJAX di
`getViewComponent()` resta un JSON valido, il widget non sparisce più.

## Bug corretto en passant

Nello stesso blocco, il loop di sostituzione dei placeholder di
sessione (`[SESSION_NAME]`) faceva `str_replace("[".$key."]", $val,
$value)` **dentro** un `foreach ($sessions as $k => $val)` — usando
`$key` (la variabile esterna, sempre `'sql'` a questo punto) invece di
`$k` (la chiave corrente della sessione). La sostituzione dei
placeholder di sessione non è mai scattata correttamente in questo
widget. Corretto in `str_replace("[".$k."]", $val, $value)`, coerente
con lo stesso meccanismo già funzionante nel widget Small Box.

## Motivazione

Un errore di configurazione (query SQL sbagliata) deve essere visibile
e correggibile, non far sparire silenziosamente il widget dalla
dashboard — stessa motivazione di 054, qui però il difetto era più
serio perché rompeva l'intera risposta AJAX invece del solo valore del
placeholder.

## Test

- Compilazione Blade (`Blade::compileString` + `php -l`): nessun
  errore.
- Reso `command=='showFunction'` con una query non valida (colonna
  inesistente): output contiene il messaggio SQL reale
  ("Unknown column ... in field list ..."), nessun `die`/troncamento.
- Stessa resa con una query valida: tabella HTML generata
  correttamente — nessuna regressione.
- `curl` senza sessione su `/admin/statistic_builder`,
  `/admin/statistic_builder/view-component/abc`: entrambi 302, nessun
  500.
- Route count invariato (513).

**Non verificato visivamente in browser** (il comportamento reale del
widget su una dashboard richiede un'interazione utente/sessione
reale) — lasciato al giro di test manuale dell'utente.

## Rischi e note

- Stesso rischio già discusso in 054: il messaggio SQL dettagliato è
  visibile a qualunque admin che veda la dashboard con quel widget,
  non solo a chi lo configura — coerente con la scelta già fatta per
  Small Box, non ristretto ulteriormente in questo intervento.

## Rollback

`git revert` del commit — ripristina `die('ERROR')` (il widget
tornerebbe a sparire su query sbagliata) e il bug di sostituzione
placeholder di sessione.
