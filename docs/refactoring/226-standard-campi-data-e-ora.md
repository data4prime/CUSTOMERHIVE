# 226 - Standard unico per i campi data, data e ora, ora

- **Data**: 2026-10-05
- **Stato**: Completato (da verificare a vista)
- **Area**: Frontend / UI standard
- **File/aree di codice coinvolte**:
  - `public/js/ch-datetime.js` (nuovo), caricato da `partials/ch_scripts.blade.php`
  - `public/css/ch-components.css` (blocco "Campi data / data e ora / ora")
  - `resources/views/crudbooster/partials/ch_input.blade.php` (kind `date|datetime|time`)
  - `type_components/{date,datetime,time}/{component,asset}.blade.php`
  - `default/table.blade.php` (filtri della lista), `setting.blade.php`
  - `resources/lang/{en,it}/crudbooster.php` (`picker_today`, `picker_clear`, `picker_time`)

## Contesto

Quinto passo dello standard campi. Mockup con tre proposte (`.scratch/mockup-date.html`);
scelta la **A, classica**: icona a sinistra, campo di sola lettura che apre il selettore,
un solo aspetto per i tre tipi.

## Situazione prima

- Tre plugin con tre aspetti diversi: bootstrap-datepicker (`date`), daterangepicker
  (`datetime`, in modalità data singola con ora), bootstrap-timepicker (`time`, di fatto
  senza inizializzazione nel componente).
- Gli `asset.blade.php` inizializzavano i plugin; la sola presenza di un campo `date` nella
  pagina inizializzava anche i campi `.datepicker` dei filtri della lista.
- Input `type=date` nativo nel profilo, con aspetto del browser.

## Situazione dopo

- **Un selettore solo**, `ch-datetime.js` (nessuna dipendenza dai plugin): calendario con
  mese/anno e frecce, oggi cerchiato, giorno selezionato in accent, ora (ore/minuti) per
  `datetime`, colonne ore/minuti per `time`, pulsanti Oggi/Cancella. Mesi e giorni nella lingua
  dell'utente (`Intl`, `window.CH_LOCALE`), settimana da lunedì (domenica per `en`).
- **Formato salvato invariato**: `YYYY-MM-DD`, `YYYY-MM-DD HH:mm:ss`, `HH:mm:ss` (`HH:mm` se
  il valore esistente non ha i secondi). Alla scelta partono gli eventi `input` e `change`.
- Il popup è appeso al body (o alla modale/dialog del campo) con `position: fixed`: non viene
  tagliato dai contenitori con overflow. Si chiude con clic fuori o Esc; Invio/Spazio sul
  campo lo apre.
- `partials/ch_input` con `kind` `date|datetime|time` genera il riquadro (icona calendario/
  orologio, pulsante ✕ per cancellare); i tre componenti lo usano e i loro `asset` non
  inizializzano più nulla.
- **Ovunque**: i filtri "Tra… e…" della lista (data/ora) e il tipo `datepicker` dei Settings usano
  `data-ch-picker`; ogni `input[type=date|time].form-control` scritto a mano diventa un campo col
  selettore (stesso valore); `[data-ch-native]` esclude un campo.

## Motivazione

Un solo aspetto e un solo comportamento per data/ora in tutta l'app, senza tre plugin da
mantenere e con il popup che non viene più tagliato.

## Test

- Verificato in locale (Chrome): calendario in `mg_contratti/edit/71` (apertura, scelta del 20,
  valore `2026-01-20`, chiusura), `datetime` e `time` aggiunti da script (ora 18:30 letta,
  scelta ora → `14:00` mantenendo il formato senza secondi), nessun errore in console.
- **Non** verificato: filtri della lista, Settings, scelta tastiera, RTL; suite di test non eseguita.

## Rischi e note

- Il campo resta di sola lettura (come prima): non si digita la data.
- I plugin datepicker/daterangepicker/timepicker restano caricati da `admin_template_plugins`
  (altre pagine/moduli dei clienti possono usarli); non vengono più usati dai type component.
- Il tipo `datetime` ha ore 0-23 e minuti 0-59, secondi a `00`.
- Il `datetime` dei filtri usa solo la data (come prima).

## Rollback

Ripristinare i file elencati (git) e rimuovere `ch-datetime.js` (con la riga in `ch_scripts`).
