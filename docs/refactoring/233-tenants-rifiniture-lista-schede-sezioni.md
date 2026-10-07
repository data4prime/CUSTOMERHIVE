# 233 - Tenants: rifiniture lista, schede e sezioni

- **Data**: 2026-10-06
- **Stato**: Completato (da vedere a vista)
- **Area**: Frontend / UI standard / Amministrazione
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/AdminTenantsController.php`
  - `resources/views/crudbooster/default/index.blade.php`
  - `resources/views/crudbooster/default/table.blade.php`
  - `resources/views/crudbooster/partials/flat_form_header.blade.php`

## Contesto

Dopo l'intervento 232 (pagine amministrazione in stile mockup), prima prova a
vista dei tenants: restavano differenze rispetto al mockup.

## Situazione prima

- "Creato il" mostrato come `Y-m-d H:i:s`.
- Colonne Utenti/Gruppi con numeri allineati a destra, non sotto l'intestazione.
- Barra della lista: "Ordina e Filtra" (`btn-sm`), campo Cerca e select dei record
  (`input-sm`, classe BS3 inesistente in BS5) con altezze diverse; select di 56px.
- Schede e card di modifica/dettaglio: schede con riquadro BS standard, card
  bianche su sfondo quasi uguale (poco contrasto).

## Situazione dopo

- Data in formato `gg/mm/aaaa hh:mm` (come il registro accessi).
- `table.blade.php`: `'align' => 'center'` supportato (intestazione `text-center`);
  Utenti/Gruppi centrati.
- `index.blade.php` (tutti i moduli): barra con flex e gap, bottone, campo Cerca e
  select tutti nella misura `sm` (stessa altezza); select dei record 76px.
- `flat_form_header.blade.php` (solo pagine con `FlatForm::share`): schede
  sottolineate con accento, card con bordo e ombra piu' definiti, sfondo pagina
  un filo piu' scuro per staccare le card.

## Motivazione

Avvicinarsi al mockup; la barra della lista allineata migliora tutti i moduli.

## Test

`php -l` sul controller, `view:clear`. Non verificato a vista: da controllare lista
tenants e altri moduli (barra filtro/cerca), modifica/dettaglio di tenants, ruoli,
gruppi, utenti (schede e sfondo).

## Rischi e note

- Modifica visibile a tutte le liste (barra in alto a destra, leggermente piu' compatta).
- Il mockup non era leggibile da file: i valori di schede/sfondo sono una stima, da
  rifinire con un confronto a vista.
- `color-mix()` richiede browser recenti.

## Rollback

Ripristinare i quattro file da git.
