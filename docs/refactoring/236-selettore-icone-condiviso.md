# 236 - Selettore di icone condiviso

- **Data**: 2026-10-06
- **Stato**: Completato (verificato a vista in Gestione Menu; widget KPI da riprovare)
- **Area**: Frontend / UI-UX
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/partials/ch_icon_picker.blade.php` (nuovo)
  - `public/js/ch-icon-picker.js`, `public/css/ch-icon-picker.css` (nuovi)
  - `resources/views/crudbooster/components/list_icon.blade.php` (Gestione Menu, Module Generator)
  - `resources/views/crudbooster/statistic_builder/components/smallbox.blade.php` (widget KPI)
  - `resources/lang/{it,en}/crudbooster.php` (`icon_search`, `icon_no_results`, `icon_more`, `icon_none`)

## Contesto

Il prototipo "Confronto UI" propone lo stesso selettore di icone ovunque
(pulsante con l'icona scelta + pannello con ricerca e griglia). Seguito di
[234](234-nuovo-linguaggio-visivo-ui2.md) / [207](207-ui-standard-icone-bootstrap-icons.md).

## Situazione prima

Menu e Module Generator usavano una select2 con oltre 2000 voci
(`list_icon.blade.php`); il widget KPI un'altra select2 con la stessa lista e il
proprio script. Tre implementazioni dello stesso concetto, nessuna con ricerca
per anteprima.

## Situazione dopo

Un solo partial, `ch_icon_picker`:

- input hidden col valore salvato (`bi bi-<nome>`, oppure solo `<nome>` con
  `bare`, come il widget KPI: il formato salvato è invariato);
- pannello con ricerca e griglia, costruita solo all'apertura (max 240 icone
  mostrate, "affina la ricerca"); voce "Nessuna icona";
- JS puro in delegazione di eventi, registrato una sola volta (il widget KPI
  inietta l'HTML via ajax);
- stile in un file dedicato, caricato dal partial, perché finisce anche in
  pagine che non caricano `ch-components.css` (builder a griglia).

`list_icon.blade.php` ora include il partial; il widget KPI sostituisce la sua
select e lo script `initIconSelect` (rimosso).

## Motivazione

Meno codice duplicato e un solo comportamento. Lo script è inline nel partial,
non in `@push('bottom')`: `list_icon` viene reso con `view()->render()` dentro
un controller e in quel caso gli stack di Blade si perdono (scoperto provandolo).

## Test

Verificato a vista in Gestione Menu (modifica): il pulsante mostra l'icona
attuale, il pannello si apre con la griglia. `view:cache` senza errori.
**Da provare**: salvataggio di una voce di menu con icona cambiata, Module
Generator (nuovo/modifica), widget KPI nel builder a griglia (icona e
salvataggio), tema scuro.

## Rischi e note

- `ModulsController::script_js` chiama ancora `$('#list-icon').select2(...)`:
  ora l'elemento non esiste e la chiamata non fa nulla (resta per non toccare il controller).
- Il valore inviato per menu/moduli resta `bi bi-<nome>`; le icone `fa fa-*` già
  salvate continuano a essere tradotte da `IconMap::toBi()`.

## Rollback

Ripristinare `list_icon.blade.php` e il blocco select + script in `smallbox.blade.php`
da git; il partial e i file `ch-icon-picker.*` possono restare inutilizzati.
