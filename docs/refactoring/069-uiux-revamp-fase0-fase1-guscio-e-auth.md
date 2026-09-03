# 069 - Revamp UI/UX: Fase 0 (token/font) + Fase 1 (guscio condiviso + auth)

- **Data**: 2026-09-03
- **Stato**: Completato
- **Area**: UI/UX
- **File/aree di codice coinvolte**:
  - `public/css/theme.css` (nuovo)
  - `public/fonts/plus-jakarta-sans/plus-jakarta-sans-variable.woff2` (nuovo)
  - `resources/views/crudbooster/admin_template.blade.php`
  - `resources/views/crudbooster/header.blade.php`
  - `resources/views/crudbooster/login.blade.php`
  - `resources/views/crudbooster/lockscreen.blade.php`
  - `resources/views/crudbooster/forgot.blade.php`

## Contesto

Primo intervento del piano di revamp UI/UX ([[project-uiux-revamp-plan]]),
approvato a livello visivo con un mockup dedicato. Vincoli fissati con
l'utente: solo UI/UX (nessun backend, nessuna feature nuova), font Google
self-hosted, comportamento invariato. Pianificato con `EnterPlanMode`
prima di scrivere codice (piano in `.claude/plans/`, non nel repo).

## Fase 0 — token di design + font self-hosted

- `public/css/theme.css`: variabili CSS (`--ch-*`, palette/radius/ombre
  del mockup approvato), isolato da `custom.css` esistente per un
  rollback semplice (basta rimuovere il file + il link).
- **Font**: *Plus Jakarta Sans* è distribuito da Google come **variable
  font** (verificato: il CSS ufficiale serve lo stesso file per i pesi
  400/500/600/700/800 del sottoinsieme latino) - scaricato **un solo
  file** (`plus-jakarta-sans-variable.woff2`, 27 KB) da
  `fonts.gstatic.com` in `public/fonts/plus-jakarta-sans/`, dichiarato
  con `font-weight: 400 800` (intervallo, non 5 regole separate).
  Nessuna richiesta esterna a runtime.
- **`theme_color` (select chiusa a 12 skin AdminLTE in
  `privileges.blade.php`) rimappato su un accento colore** invece di
  essere ignorato (deciso con l'utente): `admin_template.blade.php`
  traduce il valore in sessione in `data-role-accent` sul `<body>`
  (`blue`/`yellow`/`green`/`purple`/`red`/`black`, le varianti `-light`
  condividono lo stesso accento della base - nel nuovo design non esiste
  una sidebar scura), `theme.css` lo mappa su variabili CSS
  (`--ch-role-accent*`) riusate per lo stato attivo della sidebar/link
  del footer. Valore non riconosciuto o assente → nessun override,
  accento indigo di default.

## Fase 1 — guscio condiviso

- **`admin_template.blade.php`**: aggiunto `theme.css`, classe `ch-shell`
  sul `<body>` al posto della classe `skin-*`, calcolo di
  `data-role-accent`. Struttura HTML invariata (header/sidebar/
  content-wrapper/footer) - restyle interamente via CSS mirato alle
  classi AdminLTE esistenti (`.main-header`, `.main-sidebar`,
  `.sidebar-menu`, `.treeview-menu`, `.main-footer`...), **nessuna
  modifica strutturale** a `sidebar.blade.php`/`footer.blade.php` (zero
  righe toccate in questi due file: il restyle è tutto in `theme.css`).
  Sidebar: stessi link e stesso meccanismo espandi/comprimi per ogni
  modulo (`Add New X` + `List X`), nessun link rimosso rispetto a prima.
- **`header.blade.php`**: rimosso l'attributo morto
  `data-bs-toggle="offcanvas"` sul bottone toggle sidebar (verificato: il
  collapse è gestito da `dist/js/app.js` di AdminLTE tramite la classe
  `.sidebar-toggle`, non da Bootstrap 5 - nessun elemento `.offcanvas`
  esiste, l'attributo non faceva nulla). Nessun'altra modifica.
- **`login.blade.php` / `lockscreen.blade.php` / `forgot.blade.php`**:
  riscritte (pannello split screen scuro + form per login/forgot, card
  centrata per lockscreen), Bootstrap 3.3.2/jQuery 2.2.3 rimossi (nessun
  componente JS Bootstrap usato su queste 3 pagine, verificato).
  **Branding per-tenant/globale preservato** (`CRUDBooster::
  getBackgroundColor()`/`getBackgroundImage()`/`frontColor()`/
  `getLogo()`/`getFavicon()`, con override per-tenant dove già
  esisteva) - stessi valori, stesso meccanismo. **Scoperta durante la
  verifica visiva**: applicare questi valori incondizionatamente (come
  faceva il codice originale) mostra i default di sistema
  (`#dddddd`/`main-bg.jpg`/`#666666`) quando nessuno ha personalizzato
  nulla - stonano col nuovo pannello scuro. Corretto confrontando il
  valore risolto con il default calcolato nello stesso Blade (nessuna
  modifica al controller): la personalizzazione reale vince sempre
  (invariata), il default di sistema no (resta il pannello scuro del
  nuovo design).

## Verifica

- Suite automatica: 172/172 test passano (nessuna copertura automatica
  esiste per l'aspetto UI - i test che toccano queste pagine, es.
  `LoginTest.php`, verificano redirect/sessione, non markup).
- **Verifica manuale in Docker locale** (via browser): login (con lo
  sfondo/colore già presenti in questo ambiente dev, verificato che
  vincano sul default), dashboard con sidebar/header/footer restilizzati,
  lockscreen, forgot password - tutti funzionanti, nessuna regressione
  osservata su logout/redirect/form.

## Rischi e note

- **Correzione (2026-09-03, segnalato dall'utente)**: la nota precedente
  in questo stesso documento ("il toggle sidebar non funziona, bug
  preesistente non introdotto da questo intervento") era **sbagliata**.
  Causa reale verificata nel sorgente: la copia vendorizzata/personalizzata
  di `dist/js/app.js` di AdminLTE in questo progetto usa letteralmente
  `sidebarToggleSelector: "[data-bs-toggle='offcanvas']"` (vedi il file
  stesso) come selettore per agganciare il click che comprime/espande la
  sidebar - **non** è markup morto di Bootstrap 5 come avevo concluso.
  Rimuovendo quell'attributo da `header.blade.php` durante questo stesso
  intervento avevo rotto io il toggle. Ripristinato l'attributo
  (con un commento nel Blade per non ripetere l'errore) - verificato che
  ora comprima/espanda correttamente in entrambe le direzioni.
- **Bug reale corretto nello stesso giro**: i pulsanti icona
  dell'header (help/notifiche) impostati a `width:36px;height:36px`
  fissi con un selettore troppo generico (`.nav > li > a`) che, per
  specificità CSS, vinceva anche sul tentativo di escludere
  `.user-menu` - schiacciava avatar+nome utente+freccia nello stesso
  riquadro 36×36, tagliando il testo "Super Admin". Corretto escludendo
  `.user-menu` direttamente nel selettore di base
  (`li:not(.user-menu) > a`) invece di provare a sovrascriverlo dopo.
- Il font Plus Jakarta Sans self-hosted copre solo il sottoinsieme
  latino (sufficiente per italiano/inglese, tutti i caratteri accentati
  usati nell'app rientrano in Latin-1 Supplement) - lingue con altri
  alfabeti (arabo/persiano, già gestite a parte via CSS RTL) non
  useranno questo font, ricadono sul fallback di sistema.

## Rollback

`git revert` del commit. Il restyle è additivo/isolato (`theme.css` +
`ch-shell` + markup delle 3 pagine auth): revert ripristina l'aspetto
AdminLTE originale senza impatto su dati o logica applicativa.

Vedi anche [[project-uiux-revamp-plan]].
