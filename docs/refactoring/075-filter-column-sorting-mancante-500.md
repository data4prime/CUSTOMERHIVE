# 075 - Vista lista: 500 se `filter_column` non contiene la chiave `sorting`

- **Data**: 2026-09-24
- **Stato**: Completato
- **Area**: Bug fix (template CRUD comune)
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/default/table.blade.php`
  - `app/Helpers/CRUDBooster.php` (`getSortingFilter()`)

## Contesto

Bug trovato il 2026-09-03 durante il restyle del popup "Ordina e filtra"
(Fase 3 del revamp UI/UX, vedi [069](069-uiux-revamp-fase0-fase1-guscio-e-auth.md)),
costruendo a mano un URL di test. Preesistente, non introdotto dal restyle.
Segnato allora e rimandato su richiesta, ripreso ora.

## Situazione prima

La vista lista di qualunque modulo (template comune
`crudbooster::default.table`) legge `Request::get('filter_column')` in due
punti accedendo alla chiave `sorting` senza verificarne l'esistenza:

1. `table.blade.php`, loop delle intestazioni `<th>` (frecce di
   ordinamento): `if (isset($sort_column[$field]))` controlla solo che la
   colonna sia presente, poi `switch ($sort_column[$field]['sorting'])`.
2. `CRUDBooster::getSortingFilter($field)`, chiamato dal popup "Ordina e
   filtra" (renderizzato nella stessa pagina anche se chiuso):
   `if (!empty($filter[$field])) return $filter[$field]['sorting'];`.

Se il querystring contiene `filter_column[<campo>][type]`/`[value]` ma non
`[sorting]`, entrambi vanno in `ErrorException: Undefined array key
"sorting"` → pagina 500.

Non raggiungibile dal flusso normale: il form del popup invia sempre
`type`/`value`/`sorting` insieme per ogni colonna. Scatta solo con URL
parziali costruiti a mano (link diretti, bookmark, integrazioni esterne).

Il controller è già robusto: `CBController::getIndex()` legge
`@$fc['sorting']`.

## Situazione dopo

Due righe, stesso pattern `?? ` già usato altrove per accessi opzionali:

- `table.blade.php`: `switch ($sort_column[$field]['sorting'] ?? '')` —
  senza `sorting` si cade nel ramo `default` dello switch, che produce lo
  stesso link "ordina asc" con icona `fa-sort` del ramo `else` (colonna
  non ordinata).
- `CRUDBooster::getSortingFilter()`: `return $filter[$field]['sorting'] ??
  null;` — stesso valore che la funzione restituisce già quando il campo
  non è filtrato affatto (nessuna opzione selezionata nel popup).

Comportamento con `sorting=asc`/`desc` o senza filtri: invariato. Unico
cambiamento visibile: l'URL parziale ora mostra la pagina invece di un 500.

## Motivazione

Un link/bookmark/integrazione esterna con querystring parziale non deve
far cadere l'intera pagina lista. Corretto anche l'helper, non solo la
vista: correggendo solo `table.blade.php` il 500 si sarebbe spostato alla
riga dell'helper, chiamato dal popup nella stessa pagina.

## Test

Manuale via curl sull'ambiente Docker locale, modulo Users (`/admin/users`),
colonna `cms_users.name`:

- Prima del fix: `filter_column[cms_users.name][type]=like&[value]=admin`
  senza `sorting` → **500**; stesso URL con `sorting=asc` → 200.
- Dopo il fix: senza `sorting` → 200, intestazione "Name" con link
  `sorting=asc` e icona `fa-sort` (come colonna non ordinata); con
  `sorting=asc` → icona `fa-sort-asc`, link a `desc`; con `sorting=desc` →
  icona `fa-sort-desc`, link a `asc` (invariati); lista senza filtri → 200.
- `php -l` su `CRUDBooster.php`: nessun errore di sintassi.

Suite automatica non eseguita (solo su richiesta).

## Rischi e note

- Minimi: si toccano solo i casi che oggi vanno in errore.
- `getSortingFilter()` potrebbe essere usato anche da controller custom
  dei clienti (gitignored, non verificabili da qui): il fix li protegge
  allo stesso modo, senza cambiare il valore restituito nei casi validi.
- Notato (non toccato, preesistente): `php -l` segnala un deprecation
  PHP 8 su `CRUDBooster.php:1574` (parametro opzionale `$regID` dichiarato
  prima di uno obbligatorio).

## Rollback

Ripristinare le due righe originali (`$sort_column[$field]['sorting']` e
`$filter[$field]['sorting']`) — nessuna migration né dato coinvolto.
