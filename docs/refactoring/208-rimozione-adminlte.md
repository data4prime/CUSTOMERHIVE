# 208 - Rimozione di AdminLTE

- **Data**: 2026-10-02
- **Stato**: Completato (da provare a mano nel browser)
- **Area**: Frontend
- **File/aree di codice coinvolte**:
  - `public/css/ch-layout.css` (nuovo, codice del progetto)
  - `public/js/ch-shell.js` (nuovo)
  - `public/vendor/crudbooster/assets/js/main.js`
  - `resources/views/crudbooster/header.blade.php`, `admin_template*.blade.php`, `statistic_builder/layout.blade.php`
  - eliminata `public/vendor/crudbooster/assets/adminlte/` (934 file tracciati: AdminLTE 2.3.8, Bootstrap 3, FontAwesome 4, plugin)

## Contesto

Obiettivo: togliersi del tutto AdminLTE (Bootstrap 3) che conviveva con
Bootstrap 5 (206). Scelta di metodo, per non poter verificare a vista: **tenere
stabile il markup** (stesse classi di struttura: `wrapper`, `main-header`,
`main-sidebar`, `content-wrapper`, `sidebar-menu`, `treeview`, `box`...) cosi'
`theme.css` (che ne ridefinisce l'aspetto con ~150 selettori) e i moduli dei
clienti restano validi, e sostituire solo **CSS e JS** di AdminLTE con file del
progetto.

## Situazione prima

`AdminLTE.min.css` (90 KB) + 12 file skin, `dist/js/app.js` (24 KB), plugin
(datepicker, daterangepicker, timepicker, DataTables, jQuery, slimScroll) dentro
la sua cartella, `@import` di Google Fonts. Il toggle della sidebar si reggeva su
`data-bs-toggle="offcanvas"` letto da `app.js` (collisione con l'attributo
omonimo di Bootstrap 5, vedi memoria "verificare JS prima di rimuovere
attributi"). Le pagine standalone caricavano ognuna la sua combinazione.

## Situazione dopo

- **`ch-layout.css`** (34 KB): le sole regole di AdminLTE usate dal progetto
  (selettori filtrati in base alle classi realmente presenti in viste, JS e
  controller), **senza** i componenti generici (pulsanti, label, alert, modali,
  tabelle, form: li governano Bootstrap 5 e `ch-components.css`), colori
  ricondotti ai token `--ch-*`, font `var(--ch-font)`. Avviso di licenza MIT
  nell'intestazione. E' generato una volta e da ora **si modifica a mano**.
- **`ch-shell.js`**: sostituisce `app.js` - altezza minima del contenuto, sidebar
  a scomparsa, menu ad albero, barra di controllo, box comprimibili/rimovibili
  (`data-widget="collapse|remove"`, con scambio icone fa/bi), tooltip Bootstrap 5
  creati al primo passaggio del mouse (il vecchio `body.tooltip({selector})` era
  Bootstrap 3), gruppi `btn-toggle`. Il toggle usa `data-ch-toggle="sidebar"`
  (header aggiornato); `data-bs-toggle="offcanvas"` resta come selettore di
  ripiego. Shim minimo `$.AdminLTE` per codice che lo richiamasse (deprecato).
  `main.js` non duplica piu' `rama_fix_sidebar()`.
- Il builder legacy (`statistic_builder/layout`) non usa piu' `skin-*`: stesso
  accento di ruolo del layout principale (`data-role-accent`).
- Licenza e 404 riscritte su Bootstrap 5 / classi `ch-auth-*` (stesso aspetto del
  login), campi e azioni dei form invariati.
- Eliminati: la cartella `adminlte/`, `AdminLTE.min.css`, le skin, FontAwesome
  4 (font e CSS), i temi/plugin non usati (iCheck, input-mask, colorpicker,
  ckeditor 4, flot, fullcalendar, ...), `slimScroll`.

## Motivazione

Un solo framework (Bootstrap 5), nessun CSS/JS del 2016 con colori e font propri,
nessuna regola in conflitto da sovrascrivere con `!important`.

## Test

- Viste compilate; pagine admin 200; log pulito (esclusi i timeout noti del
  license server locale).
- Pagine eseguite in jsdom: gli handler del guscio risultano registrati e
  rispondono (toggle sidebar su schermo piccolo aggiunge `sidebar-open`, click su
  albero e su box senza errori); nessun errore JS.
- **Da provare a mano nel browser** (non verificabile dal codice): sidebar aperta/
  chiusa su desktop, animazione dell'albero, riga attiva nel menu, dropdown di
  notifiche e utente, barra di controllo del builder legacy, altezze, pagine
  embed (`?embed=1`), tutte le pagine fuori dal guscio.

## Rischi e note

- **`box` / `small-box` / `info-box` / `callout` restano**: sono componenti propri
  del progetto (ora in `ch-layout.css`), non piu' una dipendenza. Sostituirli con
  `card`/`alert` di Bootstrap significa riscrivere ~40 file e il JS dei box: da
  fare come intervento successivo (inventario: `local-scripts/ui-inventory.ps1`).
- Il file manager UniSharp mantiene Bootstrap 3 (vedi 206).
- Le regole di `theme.css` che combattevano AdminLTE (`!important`, selettori
  doppi) sono innocue ma ridondanti: pulizia futura.
- Sorgente: `gen-ch-layout.php` e' stato eliminato insieme alla cartella
  AdminLTE da cui estraeva; il file generato e' ora la sola fonte.

## Rollback

Ripristinare da git la cartella `public/vendor/crudbooster/assets/adminlte/` e i
file delle viste (`admin_template.blade.php`, `admin_template_plugins.blade.php`,
`header.blade.php`, ...); rimuovere `ch-layout.css`, `ch-shell.js`.
