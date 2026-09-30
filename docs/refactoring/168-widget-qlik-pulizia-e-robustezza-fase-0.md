# 168 - Widget Qlik: pulizia codice morto e robustezza (Fase 0)

- **Data**: 2026-09-30
- **Stato**: Completato
- **Area**: Qlik / Frontend
- **File/aree di codice coinvolte**:
  - `public/js/qlik_login_widget.js`
  - `public/js/qlik_login_widget_obj.js`
  - `public/js/qlik_login_widget_objop.js`
  - `resources/views/mashup.blade.php`
  - `resources/views/mashup_objects.blade.php`

## Contesto

Analisi (sola lettura) del widget Qlik dashboard (`/mashup/...` e
`/mashup-objects/...`) per ridurre codice ripetuto e aumentare la stabilità.
Questo intervento è la **Fase 0** del piano emerso: solo pulizia e robustezza
minima, nessun cambio di contratto (nomi file JS, variabili globali lette dai
JS e firme PHP restano invariati, perché i clienti potrebbero includerli per
nome).

Decisioni della persona che guida il lavoro:
- `xrfkey` **resta statico** (`0123456789abcdef`) nei JS On-Premise.
- La modalità **Ticket non sarà più supportata**: il codice resta ma è
  nascosto (il form conf accetta già solo `JWT`, `QlikConfController.php:53`).

## Situazione prima

- `qlik_login_widget_obj.js`: circa metà file era un `main()` commentato.
- `qlik_login_widget.js`: `objectsOptions` mai chiamata, `var x = document.cookie`
  inutile; `main()` senza `try/catch` (se un fetch o `response.json()` su
  `qrs/about` falliva, il widget restava su "Loading..." per sempre).
- `obj.js`/`objop.js`: `alert(error.message)` dentro un iframe di dashboard;
  `appdoc` e `hidden_object`/`hidden_app`/`mashup_object` usati senza null-check;
  in `objop.js` una chiamata `qlik.getAppList` il cui risultato non veniva usato.
- Blade mashup: blocchi `console.log` commentati, `$js_defer`/`$param` calcolati
  e mai usati, un `<div>` non chiuso in `mashup_objects`, `ticket_data` stampato
  senza escape, 4 chiamate a `getTicketFromConf` (funzione che **non esiste più**
  in `QlikHelper`: una conf Ticket andava in errore fatale).

## Situazione dopo

- JS: codice morto rimosso; `main()`/`mainOP()` avvolti in `try/catch` con
  messaggio d'errore mostrato al posto di "Loading..." (`showWidgetError`);
  `alert()` sostituito da `console.error`; null-check su `appdoc` e sugli
  elementi del documento padre; `response.json()` inutile rimosso (la chiamata
  a `qrs/about` serve solo a ottenere il cookie di sessione). Rimossa la
  `getAppList` inutilizzata.
- Blade: rimossi commenti di debug e variabili inutilizzate; `</div>` chiuso;
  `ticket_data` passa da `json_encode`; il ramo Ticket di `mashup_objects` si
  attiva solo se `getTicketFromConf` esiste (`method_exists`), una sola
  chiamata invece di quattro, `$token3` inizializzato.

## Motivazione

Meno codice morto da mantenere e nessun blocco silenzioso su errore di rete o
di login. Nessuna riscrittura: il flusso di login/caricamento è invariato.

## Test

- Compilate con Blade le due viste e verificato `php -l` sull'output: OK.
- **Non verificato**: i JS non sono stati eseguiti né lintati (Node non è
  disponibile sull'host né nel container) e non è stato fatto test nel browser.
  Da provare a mano: widget in dashboard (SaaS e On-Premise JWT), pagina di
  configurazione widget (select oggetti popolata e oggetto salvato
  preselezionato).

## Rischi e note

- Cambio visibile (solo in casi d'errore): un fetch fallito ora mostra il
  messaggio d'errore nel widget invece di restare su "Loading..."; se
  `qrs/about` risponde con un corpo non JSON il widget ora prosegue (prima si
  fermava senza messaggio).
- Una conf legacy con auth Ticket non va più in errore fatale in
  `mashup_objects`, ma non funziona comunque (funzioni Ticket assenti).
- Restano fuori (fasi successive): JS condiviso `qlik_common.js` con shim,
  helper PHP unico per token/config, race di `view_saas.blade.php`, `exp` nel
  JWT On-Premise, controlli tenant sulle route `/mashup*`, null-check di
  `$conf` in `AdminQlikItemsController` (content_view/Hub/QMC), rami Ticket in
  Hub/QMC, testi hardcoded da portare in `trans()`.

## Rollback

`git checkout -- <file>` sui cinque file elencati (nessuna migrazione, nessun
cambio di dati).
