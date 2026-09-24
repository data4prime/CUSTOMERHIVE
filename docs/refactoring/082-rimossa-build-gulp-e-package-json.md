# 082 - Rimossi build gulp/elixir morta e `package.json` inutilizzati

- **Data**: 2026-09-24
- **Stato**: Completato
- **Area**: Dipendenze
- **File/aree di codice coinvolte**:
  - `package.json`, `gulpfile.js` (rimossi)
  - `public/vendor/crudbooster/assets/datetimepicker-master/package.json`,
    `public/vendor/crudbooster/assets/assets/datetimepicker-master/package.json` (rimossi)
  - `docker-compose.yml` (servizio `node` rimosso)
  - `docs/docker-local-dev.md`, `docs/deploy-manuale-server.md`

## Contesto

Richiesta di capire gli avvisi di GitHub Dependabot (70 nel backlog, da
agosto). `composer audit` sul `composer.lock` attuale ne trova 1 sola lato
PHP (vedi [083](083-firebase-php-jwt-7.md)): il resto degli avvisi arriva
dai `package.json` del repo, che Dependabot legge anche senza lockfile.

## Situazione prima

- `package.json` in root: solo `devDependencies` di una build del 2016-2017
  (gulp 3, laravel-elixir 6, vue 2, lodash, jquery 3.1, bootstrap-sass 3),
  tutte con vulnerabilità note. Pilotata da `gulpfile.js` (compila
  `resources/assets/sass/app.scss` → `public/css/app.css` e
  `resources/assets/js/app.js` → `public/js/app.js`).
- La build è **morta**: nessuna vista carica `css/app.css` né `js/app.js`
  (gli unici `js/app.js` referenziati sono quello vendorizzato di AdminLTE,
  non un output di questa build); nessun uso di `elixir()`/`mix()`; sui
  server non gira mai `npm install` (vedi `docs/deploy-manuale-server.md`).
  Già notato durante il revamp UI (069): gulp/elixir "non pilota nulla in
  produzione".
- `docker-compose.yml` aveva un servizio facoltativo `node` (profilo
  `assets`, `node:14-alpine`, `npm install && npm run dev`) solo per questa
  build.
- Due `package.json` dentro le copie vendorizzate del plugin jQuery
  datetimepicker: sono i file di progetto dell'autore originale
  (`devDependencies` `concat-cli`, `uglifyjs` 2.x con vulnerabilità note),
  non servono a caricare il plugin (in pagina finisce solo il `.js` già
  minificato).

## Situazione dopo

Rimossi i 3 `package.json`, `gulpfile.js` e il servizio `node` da
`docker-compose.yml`; aggiornate le due guide che li citavano.

Nessun cambiamento visibile: nulla di quanto rimosso gira sul sito o nel
container `app`.

## Motivazione

Elimina la fonte della grande maggioranza degli avvisi Dependabot senza
alcun rischio reale da correggere (strumenti di build mai eseguiti), così
che gli avvisi rimasti siano quelli che contano.

## Test

- Verificato che nessuna vista/helper/config referenzi gli output della
  build o i file rimossi.
- `docker compose config` valido dopo la modifica.
- App locale: login e pagine principali invariate.

## Rischi e note

- Lasciati (pulizia separata, non generano avvisi): sorgenti morti
  `resources/assets/{js,sass}/` e output compilati storici
  `public/css/app.css`, `public/js/app.js`.
- **Punto cieco invariato**: le librerie JS copiate a mano in
  `public/vendor/` (jQuery 2.2.3 di AdminLTE, plugin vari) non hanno un
  manifest, quindi Dependabot non le vede. Sono quelle che finiscono davvero
  nel browser: servirebbe un censimento dedicato.
- Se un giorno servisse di nuovo una build frontend, si ripartirebbe da un
  tool moderno (Vite), non da gulp 3.

## Rollback

Ripristinare i file dal git history.
