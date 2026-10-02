# 210 - Sidebar a tutta altezza, header e footer a destra

- **Data**: 2026-10-02
- **Stato**: Completato (non visto a vista dal browser)
- **Area**: Frontend
- **File/aree di codice coinvolte**:
  - `public/css/theme.css` (`.main-header`, `.sidebar-toggle`, `.main-sidebar`, regole `sidebar-collapse`/mobile)

## Contesto

Richiesta dell'utente: la sidebar deve occupare tutta l'altezza della
pagina, con header e footer che partono dal suo bordo destro e arrivano al
lato destro, invece di attraversare l'intera larghezza.

## Situazione prima

- `.main-header` era in flusso, `width: 100%`, sopra la sidebar (z-index 1030
  contro 810): la sidebar partiva sotto l'header, quindi aveva
  `padding-top: 64px` per non finirci dietro.
- Il burger (`.sidebar-toggle`) usava `margin-left: var(--ch-sidebar-width)`
  per allinearsi al bordo della sidebar.
- `.main-sidebar` era `position: absolute` (scorreva con la pagina).
- Il footer aveva gia' `margin-left: var(--ch-sidebar-width)`.

## Situazione dopo

- `.main-header`: `width: auto` + `margin-left: var(--ch-sidebar-width)`
  (0 con sidebar chiusa, su mobile e nelle pagine `layout-top-nav` come lo
  Statistic Builder, che non hanno la sidebar principale).
- `.main-sidebar`: `position: fixed; top: 0; bottom: 0; padding-top: 0`,
  sempre a tutta altezza anche scorrendo pagine lunghe.
- Burger: `margin-left: 8px` fisso (l'header parte gia' dal bordo sidebar).
- Angoli della sidebar dritti (`border-radius: 0`, prima 20px a destra).
- `.sidebar-logo` alto 53px e navbar `min-height: 52px` (+1px di bordo):
  la linea sotto il logo e quella sotto l'header sono sulla stessa riga.
  Se il contenuto dell'header superasse 52px le due linee si
  disallineerebbero di nuovo.
- Footer: invariato (gia' a destra della sidebar).
- Mobile con sidebar aperta: l'header scorre insieme al contenuto come gia'
  fanno `.content-wrapper` e `.main-footer`.

## Motivazione

Layout a "sidebar a colonna intera", richiesto esplicitamente. Solo CSS,
nessun cambio di markup o JS.

## Test

Solo lettura del CSS: non verificato nel browser. Da controllare a mano:
desktop con sidebar aperta/chiusa, pagina lunga con scroll, mobile (sidebar
a scomparsa), dashboard Statistic Builder, dropdown di notifiche/utente.

## Rischi e note

- La sidebar ora e' fissa: prima scorreva con la pagina. Cambio visibile.
- Su mobile il footer ha ancora `margin-left` della sidebar (regola
  preesistente in `.main-footer`, non toccata).

## Rollback

Ripristinare `public/css/theme.css` (`git checkout -- public/css/theme.css`
annulla anche altre modifiche non committate in quel file: meglio invertire
a mano le regole elencate sopra).
