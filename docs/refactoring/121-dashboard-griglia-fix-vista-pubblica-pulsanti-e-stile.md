# 121 - Dashboard a griglia libera: rimossi pulsanti edit/delete dalla vista pubblica, stile card coerente col builder

- **Data**: 2026-09-28
- **Stato**: Completato e verificato in browser
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/show_grid.blade.php`
  - `resources/views/crudbooster/statistic_builder/builder_grid.blade.php`

## Contesto

Segnalato dall'utente su una dashboard reale
(`/admin/statistic_builder/show/test2?m=9`): sulla vista di sola
lettura comparivano pulsanti di modifica/eliminazione (matita/cestino)
posizionati in modo scorretto (galleggianti, slegati dal widget), e i
widget non configurati non avevano alcun bordo/sfondo visibile
("Widget non configurato" appariva come testo nudo sullo sfondo della
pagina).

## Situazione prima

Il markup `.action` (matita/cestino, pensato per il builder legacy) è
parte del template `layout` condiviso di ogni widget
(`smallbox`/`table`/`chartline_v2`/`chartbar_v2`). Lo stile che lo
posiziona correttamente e lo nasconde di default vive solo nel
`<style>` di `index.blade.php` (builder/vista legacy) - **né**
`show_grid.blade.php` **né** `builder_grid.blade.php` lo includono
(sono pagine standalone), quindi `.action` compariva sempre, come
`<div>` normale senza posizionamento, non agganciato visivamente al
proprio widget. Allo stesso modo, `show_grid.blade.php` non dava alla
cella della griglia nessuno sfondo/bordo proprio: un widget configurato
appariva comunque a posto (ha una sua card Bootstrap), ma un widget
**non configurato** (solo l'icona/testo del placeholder di
[120](120-dashboard-griglia-placeholder-widget-non-configurato.md),
senza alcuno sfondo proprio) restava senza alcun contorno visibile.

## Situazione dopo

- `.border-box .action { display: none !important; }` in entrambe le
  pagine: i pulsanti modifica/elimina legacy (che in questo contesto
  punterebbero comunque a handler mai definiti qui, quindi già rotti)
  non compaiono più - né nel builder (dove la modifica/eliminazione
  passa dalla sidebar e da `.ch-widget-toolbar`) né nella vista
  pubblica (dove non deve esserci alcuna azione di modifica).
- `show_grid.blade.php`: ogni cella della griglia ha ora lo stesso
  stile "card" del builder (`.ch-grid-view-cell`: sfondo bianco, bordo,
  angoli arrotondati, ombra leggera, scrollbar sottile) - un widget non
  configurato appare quindi come una card vuota coerente, esattamente
  come nella pagina del builder.

## Motivazione

Coerenza visiva builder/vista pubblica (stessa richiesta esplicita
dell'utente: "falli vedere come nella pagina del builder"); i pulsanti
modifica/elimina legacy erano comunque non funzionanti in questo
contesto (nessun handler JS li intercetta né in `builder_grid.blade.php`
né in `show_grid.blade.php`), quindi rimuoverli non toglie capacità
reali, solo un elemento visivo rotto.

## Test

Verificato in browser sulla dashboard reale `test2` (3 widget, di cui 2
non configurati): nessun pulsante matita/cestino residuo, ogni widget
in una card bianca ben delimitata, posizionata correttamente nella
griglia. Verificato anche il builder sulla stessa dashboard: nessuna
regressione, stesso aspetto di prima meno i pulsanti legacy ridondanti.

## Rischi e note

Nessuna nota aggiuntiva: fix isolato a CSS, nessuna funzionalità
rimossa (solo pulsanti già non funzionanti in questi due contesti).

## Rollback

`git diff` di questi due file per ripristinare `.action` visibile e le
celle senza sfondo proprio.
