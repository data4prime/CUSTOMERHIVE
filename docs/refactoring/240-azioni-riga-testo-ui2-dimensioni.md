# 240 - Azioni di riga con testo: il pulsante "Azione" non è più più piccolo del testo

- **Data**: 2026-10-07
- **Stato**: Completato (da verificare a vista)
- **Area**: Frontend / UI-UX
- **File/aree di codice coinvolte**:
  - `public/css/ch-ui2.css`

## Contesto

Segnalato su `/admin/mg_fatture?m=8`: nella colonna "Azione" il pulsante è più
piccolo del testo che contiene. Lo stile "dropdown" (e `button_text`,
`button_icon_text`) è avvolto in `.ba-wide` da `components/action.blade.php`
proprio per sottrarsi alla regola solo-icona 30x30 di `theme.css`.

## Situazione prima

Con il nuovo look (`CH_UI_V2`), `ch-ui2.css` ha la regola
`body.ch-shell.ch-ui2 .button_action .btn { width:32px; height:32px }`, con la
stessa specificità di `theme.css` `.button_action .ba-wide .btn { width:auto;
height:auto }` ma caricata dopo: vinceva, e il pulsante con testo restava
32x32 con il testo che ne usciva.

## Situazione dopo

Nuova regola in `ch-ui2.css`, `body.ch-shell.ch-ui2 .button_action .ba-wide
.btn { width:auto; height:auto; border-radius: var(--ch-radius-md) }`, più
specifica della precedente: i pulsanti con testo tornano alla dimensione
del contenuto, le azioni solo-icona restano 32x32.

### Aggiunta: select "record per pagina" (list view)

Le regole di `#form-limit-paging` in `theme.css`/`ch-ui2.css` puntavano solo a
`.form-control`, ma il campo in `index.blade.php` è una `.form-select
.form-select-sm`, poi sostituita da select2 (il nativo è nascosto, 1x1px):
la prima correzione non aveva effetto. Il box select2 visibile aveva
`min-height: var(--ch-field-h)` (44px) dentro un `.input-group` da 36px con
bordo proprio: sbordava e il valore scendeva. Ora `theme.css` e `ch-ui2.css`
agiscono su `.select2-selection--single` di `#form-limit-paging`; in ui2
contenitore, `.selection` e box hanno `height:100%` e niente doppio bordo.
Misurato nel browser: scarto verticale del valore ≈ 0px. Solo CSS.

### Aggiunta: spunta rimossa da tutti i select

La spunta sulla voce selezionata (standard dei select, intervento 223) è stata
tolta ovunque su richiesta: rimossa la regola `::after` in `ch-components.css`
e il padding destro di 36px che le faceva spazio (ora 10px). La voce
selezionata resta evidenziata dallo sfondo. Solo CSS; `--ch-select-check`
resta definita ma inutilizzata.

## Motivazione

Correzione puramente CSS, limitata a `.ba-wide`; nessun markup toccato.

## Test

Non verificato a vista né con test automatici (da controllare su
`mg_fatture` e su un modulo con stile `button_icon_text`/`button_text`).

## Rischi e note

Solo i pulsanti dentro `.ba-wide` cambiano; le icone di default no.

## Rollback

Rimuovere il blocco `.button_action .ba-wide .btn` aggiunto in `ch-ui2.css`.
