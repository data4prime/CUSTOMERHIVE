# 209 - Standard UI: token, componenti e compatibilita' Bootstrap 3

- **Data**: 2026-10-02
- **Stato**: Completato (da provare a mano nel browser)
- **Area**: Frontend
- **File/aree di codice coinvolte**:
  - `public/css/theme.css` (token e mappa `--bs-*`), `public/css/ch-components.css` (nuovo), `public/css/ch-compat.css` (nuovo)
  - 94 file di vista/JS/controller per le classi Bootstrap 3 -> 5, 43 per i colori, 20 per i font
  - `local-scripts/ui-inventory.ps1`, `migrate-bs3-to-bs5.php`, `normalize-colors.php` (nuovi)
  - `CLAUDE.md` (sezione Standard UI)

## Contesto

Il vero obiettivo: stesse icone, colori definiti e non distinti, **un campo uguale
ovunque** (moduli generati, viste custom, popup, pagine pubbliche). Decisioni
(2026-10-02): look attuale (indaco, Plus Jakarta Sans) e accento per ruolo
mantenuti; tema scuro piu' avanti (token predisposti); solo browser recenti;
accessibilita' = buona pratica.

## Situazione prima

Misurato con `local-scripts/ui-inventory.ps1` (243 file Blade):

| Metrica | Prima | Dopo |
|---|---|---|
| Icone FontAwesome / Ionicons / Lucide | 545 / 2 / 32 | 0 / 0 / 4 (commenti) |
| `btn-default` | 99 | 0 |
| `pull-left/right` | 40 | 1 (dinamico) |
| `col-xs-*` | 9 | 0 |
| `label label-*` | 12 | 2 |
| colori hex nelle viste | 459 | 268 |
| CDN esterni nelle viste | 49 | 4 (guida e file manager) |

Campi, select2, datepicker e pulsanti avevano aspetti diversi a seconda del
contenitore (`#advanced_filter_modal`, `#box_main`, `.modal-footer`...);
select2 aveva `border-radius:0 !important`, altezza 35px e blu AdminLTE
nei blocchi `<style>` di tre viste; 14 raggi di bordo diversi.

## Situazione dopo

- **Token** in `theme.css` (`:root`): tipografia (`--ch-font`, corpi), spaziature
  `--ch-space-1..6`, raggi (`--ch-radius-sm/md/lg/pill`), campi (`--ch-field-h`
  38px, bordo, sfondo, fuoco, anello, disabilitato). **Mappa su Bootstrap 5.3**:
  `--bs-body-*`, bordi, link, `--bs-primary-rgb` & co. (badge, `bg-*`, `text-bg-*`),
  sfumature "subtle" per alert. Cambiando un token cambia tutto il prodotto.
- **`ch-components.css`** (valido ovunque, anche fuori da `body.ch-shell`):
  `.form-control`/`.form-select` (altezza uniforme, fuoco, errore, disabilitato),
  input-group, checkbox/radio/switch, **select2** (stesso aspetto dei campi;
  selettori con prefisso `body` perche' il CSS base di select2 e' caricato dopo),
  popup di datepicker/daterangepicker/timepicker, badge, modali, card, dropdown,
  **pulsanti** (primario = accento pieno, secondario = bianco con bordo,
  danger/warning/info), dimensioni e animazione icone.
- **Classi Bootstrap 3 -> 5 nelle viste e nel JS di progetto** (script
  `migrate-bs3-to-bs5.php`, riusabile sulle viste dei clienti): `btn-default`->
  `btn-secondary` (i selettori di contesto di `theme.css` estesi di conseguenza),
  `pull-*`->`float-*`, `col-xs-*`->`col-*`, `btn-xs`->`btn-sm`, `btn-block`->`w-100`,
  `label label-*`->`badge text-bg-*`, `input-group-addon`->`input-group-text`,
  `table-condensed`->`table-sm`, `panel*`->`card*`.
- **Colori e font**: 199 colori nei blocchi `<style>`/`style=""` ricondotti ai
  token (`normalize-colors.php`; mai nel JS dei grafici, esclusi chat e widget
  grafici), 14 `font-family` -> `var(--ch-font)`, Manrope/Segoe/Roboto/Lato tolti
  dalle viste.
- **`ch-compat.css`** - strato **temporaneo** per le classi Bootstrap 3 che
  Bootstrap 5 non ha piu' e che compaiono in controller/viste custom dei clienti e
  nell'HTML salvato nel DB: `pull-*`, `hidden-xs`, `col-xs-*`, `btn-default/xs/
  block/flat`, `label*`, `panel*`, `help-block`, `has-error`, `well`, `close`,
  `dropdown-menu > li > a`... Da eliminare quando tutti i clienti sono
  aggiornati. Non aggiungere nulla di nuovo.
- **`CLAUDE.md`**: sezione "Standard UI" con le regole da seguire per il codice
  nuovo.

## Motivazione

L'incoerenza nasceva dal tema fatto per contenitori e da due framework in
conflitto. Con token + componenti globali basati sulle variabili di Bootstrap, un
campo e un pulsante sono uguali dappertutto e un aggiornamento di Bootstrap 5.x
non rompe nulla (le personalizzazioni sono variabili e classi `ch-*`, mai file di
Bootstrap modificati).

## Test

- Compilazione viste, 200 sulle pagine admin, log pulito, nessun errore JS in
  jsdom (vedi 206/208).
- Inventario prima/dopo (tabella sopra).
- **Non verificato a vista**: l'aspetto di campi, pulsanti, select2, datepicker e
  badge; i contrasti; le pagine dove `theme.css` ha regole di contesto (modale
  filtro, `#box_main`, wizard) potrebbero richiedere ritocchi; il widget Chat AI
  e i grafici (`chart*`) non sono stati normalizzati nei colori.

## Rischi e note

- **Rischio principale del rilascio unico**: differenze visive sottili in pagine
  che non ho potuto vedere. Giro manuale consigliato su: lista/form/dettaglio di 2
  moduli, filtro avanzato, esportazione/importazione, wizard del module generator
  (tutti i passi), dashboard (builder + show + Qlik), utenti/profilo/MFA,
  impostazioni, privilegi, login/licenza/404, pagine pubbliche Qlik e Chat AI,
  popup datamodal, con 3 ruoli diversi (accento).
- Ancora hardcoded (268 colori, 366 stili inline, 58 `<style>`): in chat, grafici,
  JS e stili inline per layout. Si riducono toccando i file (non un'attivita' a
  se').
- `box`/`small-box` restano (vedi 208).
- `help-block`, `form-group`, `has-error`, `checkbox`/`radio` sono usati dalla
  validazione e dai moduli generati: restano supportati (in `ch-components` /
  `ch-compat`).
- Test automatici: non lanciati. I test che controllano classi o testi HTML
  (es. `btn-default`, `fa fa-`) potrebbero dover essere aggiornati.

## Rollback

Ripristinare da git le viste e `theme.css`; rimuovere `ch-components.css` e
`ch-compat.css` e le righe corrispondenti in `partials/ch_head`.
