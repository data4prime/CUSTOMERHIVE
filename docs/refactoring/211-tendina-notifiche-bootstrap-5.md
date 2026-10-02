# 211 - Tendina notifiche in stile Bootstrap 5

- **Data**: 2026-10-02
- **Stato**: Completato (non visto a vista dal browser)
- **Area**: Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/header.blade.php`
  - `public/vendor/crudbooster/assets/js/main.js` (`loader_notification`)
  - `public/css/theme.css` (blocco `.ch-notifications`)

## Contesto

Dopo la rimozione di AdminLTE (208) la tendina delle notifiche aveva markup
Bootstrap 5 ma stili e JS ancora scritti per le classi AdminLTE.

## Situazione prima

- Stili in `custom.css`/`ch-layout.css` su `li.header`, `li.footer`, `.menu`:
  non corrispondevano al markup (`dropdown-header`/`dropdown-footer`), quindi
  titolo e footer erano senza stile; larghezza 600px e colori fissi
  (`#fff`, `#f4f4f4`, `#444`).
- `main.js` aggiornava `.notifications-menu .header`, che non esisteva piu':
  la scritta "Hai N notifiche" non si aggiornava mai e restava quella
  statica "nessuna notifica".
- Lista in un `div.overflow-auto` con altezza fissa 200px inline.

## Situazione dopo

- Markup: `dropdown-menu dropdown-menu-end ch-notifications`, titolo
  `h6.dropdown-header`, `dropdown-divider`, voci `a.dropdown-item`, link
  "vedi tutte" come `dropdown-item text-center`.
- `main.js`: aggiorna `#list_notifications .dropdown-header` (ora il
  conteggio si vede davvero) e genera le voci come `dropdown-item` con
  icona + testo in `span`.
- `theme.css`: larghezza 360px (max `100vw - 24px`), lista scorrevole fino a
  320px, solo token `--ch-*`. Selettore lungo per battere le vecchie regole
  di `custom.css`, che restano per i markup non migrati.

## Motivazione

Coerenza con lo standard UI (Bootstrap 5 + token, niente colori a mano) e
correzione del titolo che non si aggiornava.

## Test

Solo lettura del codice, non verificato nel browser. Da controllare: tendina
con 0 e con piu' notifiche, titolo con conteggio, click su una voce (segna
come letta e porta all'URL), "vedi tutte", mobile.

## Rischi e note

- Cambio visibile: titolo ora con conteggio, tendina piu' stretta.
- `obj.content` e' inserito come HTML nel JS (comportamento preesistente,
  non toccato).
- Le vecchie regole AdminLTE per notifiche/messaggi/task in `custom.css` e
  `ch-layout.css` sono ora inutilizzate dall'header ma lasciate per i
  markup dei clienti.

## Rollback

Ripristinare i tre file elencati (attenzione alle altre modifiche non
committate in `theme.css`).
