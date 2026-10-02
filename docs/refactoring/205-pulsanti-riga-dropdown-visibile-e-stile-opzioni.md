# 205 - Pulsanti di riga: dropdown visibile e stile delle opzioni

- **Data**: 2026-10-02
- **Stato**: Completato (da provare a mano nel browser)
- **Area**: Frontend / UI
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/components/action.blade.php`
  - `public/css/theme.css`
  - `resources/views/crudbooster/admin_template.blade.php`

## Contesto

Dopo il 204 (lo stile scelto nel module generator viene letto dalla lista),
aprendo `/admin/mg_fatture` con lo stile a tendina il pulsante risultava
bianco e invisibile.

## Situazione prima

`theme.css` (`body.ch-shell .button_action .btn`) riduce **ogni** `.btn` della
cella azioni a un riquadro 30x30 trasparente, senza bordo e con
`font-size: 0` (pensato per le sole icone dello stile di default). Questo
colpiva anche:
- il bottone del dropdown (nessuno sfondo, testo nascosto: invisibile);
- gli stili `button_text` e `button_icon_text`, con le etichette nascoste
  dal `font-size: 0` (il 204 le aveva ripristinate nel markup, ma il CSS le
  nascondeva).

Il dropdown usava inoltre markup Bootstrap 3 (`btn-group` + `caret` +
`dropdown-menu-action { left: -130% }`) su Bootstrap 5.3.3: voci senza
`dropdown-item`, senza padding né hover, posizione da regola obsoleta.

## Situazione dopo

- Gli stili con testo e il dropdown sono avvolti in `.ba-wide` (con
  `.ba-text`, `.ba-icon-text`, `.ba-dropdown`): `theme.css` li sottrae alla
  regola solo-icona e li disegna come pulsanti piccoli con bordo, in
  linea con i token del tema (`--ch-*`); hover accento per
  dettaglio/modifica, rosso per elimina.
- Dropdown in markup Bootstrap 5: un solo bottone con etichetta e freccia
  (`dropdown-toggle`), menu `dropdown-menu-end` con
  `data-bs-popper-config='{"strategy":"fixed"}'` (non tagliato dal
  contenitore della tabella), voci `dropdown-item` con icona a larghezza
  fissa, hover tenue, "Elimina" in rosso separato da un divisore (mostrato
  solo se sopra c'e' almeno un'altra voce).
- Rimosse da `admin_template.blade.php` le regole `.dropdown-menu-action` e
  `.btn-group-action .btn-action`, usate solo da questo markup.
- Lo stile di default a icone e `button_icon_strict` non cambiano.

## Motivazione

Il dropdown deve essere leggibile e usabile, e gli stili con testo devono
mostrare davvero il testo. Alternativa scartata: modificare la regola
`.button_action .btn` generale, che avrebbe toccato lo stile di default.

## Test

- Render di `components.action` per tutti gli stili nel container:
  markup corretto, tre voci nel dropdown, nessun errore Blade.
- NON verificato nel browser: aspetto reale di bottoni e menu, apertura
  della tendina nelle ultime righe della tabella, tendina con azioni
  aggiuntive (`addaction`), viewport stretti.

## Rischi e note

- Cambia l'aspetto visibile della lista per i moduli con stile a tendina,
  testo o icona+testo.
- Gli stili con testo hanno ora un bordo e fondo bianco: non piu' i colori
  pieni primary/success/danger di Bootstrap.
- `.ba-wide` e' definito solo in `theme.css` (`body.ch-shell`).

## Rollback

Ripristinare i tre file dal git.
