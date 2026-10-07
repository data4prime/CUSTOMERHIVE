# 224 - Standard unico per i campi testuali (text, email, number, money, percent, password, textarea)

- **Data**: 2026-10-05
- **Stato**: Completato (da verificare a vista)
- **Area**: Frontend / UI standard
- **File/aree di codice coinvolte**:
  - `public/css/ch-components.css` (blocco "Campi testuali con addon")
  - `public/js/ch-inputs.js` (nuovo), caricato da `partials/ch_scripts.blade.php`
  - `resources/views/crudbooster/partials/ch_input.blade.php` (nuovo)
  - `type_components/{text,email,number,money,percent,password,textarea}/component.blade.php`

## Contesto

Quarto passo dello standard campi (221 checkbox, 222 radio, 223 select). Mockup con tre
proposte (`.scratch/mockup-testuali.html`); scelta la **A, classica**: label sopra, campo
da 38px con bordo, prefissi/suffissi come blocchi grigi ai lati, stepper a due frecce
per i numeri.

## Situazione prima

- Sette componenti con markup quasi identico ma diverso nei dettagli: `input-group`
  Bootstrap per email/percent (addon con stili propri), nessun addon per gli altri.
- Bug piccoli: in `text` il `maxlength` da `validation:max` veniva calcolato ma mai
  stampato; in `email` e `percent` l'attributo era `name=" campo"` (spazio iniziale,
  che PHP scarta: funzionava per caso); placeholder inserito senza escape; messaggi
  d'errore non escapati.
- Numeri con le freccette native del browser, password senza mostra/nascondi, textarea
  senza contatore.

## Situazione dopo

- **Un solo markup** in `partials/ch_input.blade.php`; **un solo stile** in `ch-components.css`:
  - riquadro `.ch-input` con bordo unico (disegnato con `box-shadow`, altezza invariata),
    hover/focus/errore/disabilitato/sola lettura;
  - addon grigi (`prefix`/`suffix`/`prefix_icon`), stepper `▲▼` per `number`/`percent`
    (freccette native nascoste), occhio per `password`, contatore per `textarea` con `maxlength`.
- Comportamento in `public/js/ch-inputs.js` (delegazione di eventi: funziona anche in righe
  `child` e modali).
- Novità opzionale: le chiavi `prefix` e `suffix` della definizione del campo
  (`['prefix' => '€']`) mostrano un addon su text/number/money; `percent` ha sempre `%`,
  `email` l'icona busta.
- Il `<input>` mantiene `name`, `id`, `class="form-control"`: `money` conserva `.inputMoney`
  (priceFormat) e `password` il suo hint di robustezza.
- Il `<input type=text>` senza addon resta un singolo `<input>` (stesso DOM di prima).

## Motivazione

Una sola resa per tutti i testuali, nuovi addon senza ricreare stile, bug piccoli sistemati.

## Test

- `php artisan view:cache` e render dei sette componenti: attributi corretti, escape del
  valore, `maxlength` solo dove serve. **Non** verificato a vista né con la suite di test.

## Rischi e note

- Cambia l'aspetto per tutti i clienti (voluto); i valori inviati non cambiano.
- **`text` ora applica `maxlength`** quando la validazione ha `max:N` (prima mai): impedisce
  di digitare oltre il limite, coerente con la validazione.
- `money`: prefisso `€` di default (come nel mockup); si cambia o si toglie con `prefix` nella definizione del campo (`'prefix' => ''` lo elimina).
- Il bordo del riquadro è un `box-shadow` esterno: nessun effetto su layout, ma in
  contenitori con `overflow:hidden` stretti potrebbe essere tagliato.
- Da controllare a vista: un form con tutti i tipi, il campo password del profilo
  (hint di robustezza), `money` con priceFormat, un form dentro una modale.
- Non toccati: `date`/`datetime`/`time` (con datepicker), `color`, `hidden`, `upload` e il resto.

## Rollback

Ripristinare i file elencati (git) e rimuovere `ch_input.blade.php` e `ch-inputs.js`
(con la riga in `ch_scripts.blade.php`).
