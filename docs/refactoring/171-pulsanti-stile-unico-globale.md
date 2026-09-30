# 171 - Pulsanti: stile unico (mockup profilo) applicato a tutta l'app

- **Data**: 2026-09-30
- **Stato**: Completato (non verificato a occhio nel browser, vedi "Test")
- **Area**: UI/UX
- **File/aree di codice coinvolte**:
  - `public/css/theme.css` (nuovo blocco "Pulsanti: stile unico" in fondo al file)

## Contesto

Dopo il mockup del nuovo profilo (170) è stato chiesto di estendere a tutti i
pulsanti di CustomerHive lo stesso stile: accent pieno per l'azione
principale, "ghost" (bianco con bordo) per le secondarie, angoli 10px, testo
600.

## Situazione prima

L'aspetto del mockup (derivato dalla Fase 1 del revamp UI/UX) esisteva già, ma
solo dentro contesti specifici di `theme.css`: intestazione pagina
(`.content-header h1 .btn`), footer dei form (`.box-footer .btn`), modali
(`.modal-footer .btn`), contenitore `#box_main`, `.input-group-btn`, icone di
riga (`.button_action .btn`). Fuori da quei contesti (dentro `.card`, pagine
custom, la nuova pagina profilo, tabelle con `btn-sm`/`btn-xs`, ecc.) restavano
i pulsanti Bootstrap/AdminLTE. Inoltre `btn-success` era verde in `#box_main` e
accent nei footer/intestazioni (incoerente). Classi usate nel codice: `btn-default`
(~90), `btn-primary` (~74), `btn-success` (~50), `btn-danger` (~31), `btn-info` (14),
`btn-warning` (4), più `btn-sm`/`btn-xs`.

## Situazione dopo

Un blocco globale a specificità bassa (`body.ch-shell .btn`, 0,2,1) definisce
forma, stati (hover/focus/disabled) e varianti:

| Classe | Aspetto |
|---|---|
| `btn-primary`, `btn-success` | accent pieno (hover `--ch-accent-dark`) |
| `btn-default`, `-secondary`, `-outline`, `-light` | ghost (bianco, bordo `--ch-border-strong`) |
| `btn-danger` | rosso pieno |
| `btn-warning` | ambra piena |
| `btn-info` | accent tenue (`--ch-accent-soft`) |
| `btn-link`, `btn-box-tool` | senza sfondo |

`btn-sm`/`-xs`/`-lg`/`-block` ridimensionano di conseguenza. Focus: anello
`--ch-accent-soft`; disabilitato: opacità 0.55.

Le regole di contesto già esistenti restano più specifiche e quindi vincono:
pulsanti-icona della lista, pulsanti dentro `.input-group-btn`, altezze fisse dei
footer non cambiano. Nessun `:not()` nel blocco, proprio per non alzarne la
specificità. Cambio visibile: in `#box_main` i `btn-success` passano da verde ad
accent; `btn-warning`/`btn-info` cambiano forma/colore ovunque.

## Motivazione

Coerenza visiva e un'unica fonte di verità per i pulsanti, invece di regole per
contesto che lasciano scoperto tutto il resto. Scelta "solo CSS, nessuna
modifica ai Blade": nessuna classe da cambiare nei ~300 punti d'uso, rollback
immediato.

## Test

- Solo controllo che il blocco sia presente e che `theme.css` resti caricato
  con cache-busting (`?r=time()` in `admin_template`).
- **Non verificato a occhio nel browser**: pagine da controllare a campione
  (lista, form add/edit, modali, builder dashboard, Qlik, profilo, Module
  Generator, chat AI) cercando pulsanti con altezza/allineamento strani o
  pulsanti-icona cambiati per errore. Login/lockscreen/forgot/MFA usano layout
  propri e non sono toccati da `body.ch-shell`.

## Rischi e note

- `display:inline-flex` globale su `.btn` può alterare l'allineamento di pulsanti
  con testo+icona dentro layout particolari (già così nei contesti sopra).
- Pagine fuori da `body.ch-shell` (es. chat AI pubblica, embed senza shell)
  non cambiano.
- Il grigio dei ghost è `--ch-border-strong`, più marcato del bordo dei box:
  voluto per dare corpo al pulsante, regolabile in un punto solo.

## Rollback

Togliere il blocco "Pulsanti: stile unico" in fondo a `public/css/theme.css`
(o ripristinare il file da git).
