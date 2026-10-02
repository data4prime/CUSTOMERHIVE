# 206 - Standard UI: librerie in locale e Bootstrap 5.3.8

- **Data**: 2026-10-02
- **Stato**: Completato (da provare a mano nel browser; vedi sezione Test)
- **Area**: Frontend / Dipendenze
- **File/aree di codice coinvolte**:
  - `public/vendor/bootstrap/` (nuovo: CSS, bundle JS con Popper, CSS RTL)
  - `public/vendor/bootstrap-icons/` (nuovo, vedi 207)
  - `public/vendor/jquery/`, `public/vendor/plugins/`, `public/vendor/libs/` (nuovi)
  - `resources/views/crudbooster/partials/ch_head.blade.php`, `ch_scripts.blade.php`, `ch_icons.blade.php` (nuovi)
  - `resources/views/crudbooster/admin_template.blade.php`, `admin_template_plugins.blade.php`
  - tutte le pagine standalone (login, licenza, 404, builder, popup datamodal, pagine pubbliche Qlik/Chat AI, documentazione API)

## Contesto

Primo passo del piano "standard UI unico su Bootstrap 5" (decisioni del
2026-10-02: Bootstrap ultima versione 5.x + Bootstrap Icons, tutto servito in
locale perche' alcuni clienti sono senza Internet, un solo rilascio, niente
cambio della libreria di charting). Il sistema precedente caricava Bootstrap
5.3.3 da CDN sopra AdminLTE 2 (che e' Bootstrap 3) e un'altra decina di
librerie da CDN diversi.

## Situazione prima

- Bootstrap 5.3.3 da `cdn.jsdelivr.net` con SRI, Popper separato, ripetuto in
  piu' pagine ciascuna con la sua copia.
- Da CDN: jQuery UI 1.14 e 1.11.4 (due versioni, in due punti), interact.js
  (senza versione: "latest"), Raphael 2.1.0 e Morris 0.5.1 (cdnjs), gridstack
  9 (jsdelivr), ApexCharts (senza versione), Summernote 0.8.2 (che esiste gia'
  in locale), font Manrope (Google Fonts), Source Sans Pro (`@import` dentro
  `AdminLTE.min.css`), RTL da `cdn.rawgit.com`.
- Pagine standalone (licenza, 404, popup datamodal, builder legacy, pagine
  pubbliche) con head diversi tra loro: Bootstrap 3 + AdminLTE + FontAwesome,
  oppure Bootstrap 5 da CDN, oppure nessuno.
- DataTables 1.10.7 (2015) con l'integrazione per Bootstrap 3.

## Situazione dopo

- **Bootstrap 5.3.8** (ultima stabile al 2026-10-02; non esiste una 6 stabile)
  in `public/vendor/bootstrap/`, versione fissata, bundle con Popper, file
  `.rtl` per le lingue `ar`/`fa`.
- Tre partial, usati da tutte le pagine:
  - `partials/ch_head` - CSS in ordine: Bootstrap, Bootstrap Icons,
    `ch-icons-compat`, `ch-layout`, `ch-compat`, `main.css`, `custom.css`,
    `theme.css`, `ch-components`; cache-busting con `filemtime` (prima
    `?r=time()` a ogni richiesta, che impediva la cache);
  - `partials/ch_scripts` - jQuery 3.7.1, jQuery UI (opzionale, **prima** di
    Bootstrap come prima: cosi' `tooltip`/`button` restano quelli di
    Bootstrap), Bootstrap bundle, `ch-shell.js` (opzionale);
  - `partials/ch_icons` - solo le icone, per le pagine di accesso che usano
    soltanto `theme.css`.
- In locale e con versione fissata (`public/vendor/libs/`): jQuery UI 1.14.0
  (+ tema base), interact.js 1.10.28, Raphael 2.1.0, Morris 0.5.1, gridstack
  9.5.1, ApexCharts 7.8.0 (la versione che il CDN serviva oggi).
- **DataTables 1.13.11** (ultima 1.x) con l'integrazione ufficiale Bootstrap 5
  (`public/vendor/plugins/datatables/`).
- Plugin usati (datepicker, daterangepicker, timepicker, DataTables) spostati
  fuori dalla cartella AdminLTE in `public/vendor/plugins/`; jQuery 3.7.1 in
  `public/vendor/jquery/`. Tutte le pagine ora usano jQuery 3.7.1 (prima
  alcune pagine standalone erano su jQuery 2.2.3).
- Rimossi: Google Fonts (Manrope), `@import` Source Sans Pro, rawgit,
  html5shiv/respond (IE), il `require.js` commentato, `maximum-scale=1,
  user-scalable=no` dal viewport (zoom ora consentito).
- Pagine ricondotte ai partial: layout principale, builder legacy
  (`statistic_builder/layout`), popup datamodal (8 file), licenza, 404, pagine
  pubbliche Qlik (`public`, `fullscreen_view_saas`) e Chat AI, documentazione
  API pubblica.

## Motivazione

Una sola versione di ogni libreria, nessuna richiesta esterna a runtime (utile
on-premise e con CSP restrittive), niente piu' CDN non versionati (interact.js
e ApexCharts potevano cambiare da un giorno all'altro), cache del browser
efficace.

## Test

- `php artisan view:clear && php artisan view:cache` (tutte le viste compilano).
- Pagina di login, 404 e 25+ pagine admin reali richieste in locale con una
  sessione di test: tutte 200, nessun errore nel log (esclusi i noti timeout
  del license server locale); tutti i file CSS/JS/font/immagini referenziati
  rispondono 200; unici riferimenti esterni rimasti: link alla guida
  (`help.thecustomerhive.com`), file manager (vedi Rischi).
- Pagine caricate in jsdom (esegue davvero tutti gli script): nessun errore JS
  su form, profilo, impostazioni, privilegi, builder dei dashboard; il guscio
  risponde (toggle sidebar, albero menu, collasso box).
- **Non verificato**: aspetto reale nel browser, DataTables 1.13 sui widget
  Tabella con configurazioni dei clienti, stampa/PDF.
- Suite di test automatici NON lanciata.

## Rischi e note

- **File manager** (`resources/views/vendor/laravel-filemanager`, pacchetto
  UniSharp): resta con Bootstrap 3, FontAwesome 4, jQuery 1.11 e bootbox da CDN
  - e' un mini-app a parte (popup di TinyMCE/CKEditor). Senza Internet non si
  carica gia' oggi. Da trattare in un intervento a se'.
- ApexCharts 7.8.0 e' la versione che il CDN serviva il 2026-10-02: se i widget
  `chart*_v2` mostrassero differenze, verificare prima questa versione.
- I dashboard legacy mostrano `$ is not defined` nella console: **preesistente**
  (script inline dei widget eseguiti prima che jQuery, in fondo alla pagina, sia
  caricato); non toccato.
- RTL: per `ar`/`fa` ora si usa `bootstrap.rtl.min.css` di Bootstrap 5 (prima
  il vecchio `bootstrap-rtl` per BS3). Le lingue RTL non sono nei file lingua
  del progetto (solo `en` e `it`).

## Rollback

`git checkout` dei file `resources/views/**` e `public/css/**` toccati e
ripristino da git di `public/vendor/crudbooster/assets/adminlte/` (la cartella
tracciata e' stata eliminata, vedi 208). Le cartelle nuove sotto
`public/vendor/` si possono cancellare. Nota: nel working tree c'era lavoro non
ancora committato (wizard 193-205) sugli stessi file: committare a passi.
