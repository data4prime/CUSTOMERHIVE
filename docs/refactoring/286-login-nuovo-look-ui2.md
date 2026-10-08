# 286 - Pagina di login: nuovo look del mockup (ui2)

- **Data**: 2026-10-08
- **Stato**: Completato (non verificato a vista)
- **Area**: Frontend / Auth
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/login.blade.php`
  - `resources/views/crudbooster/forgot.blade.php`, `reset_password.blade.php`, `lockscreen.blade.php`, `mfa_*.blade.php`, `license.blade.php` (stesso look, aggiunto dopo)
  - `resources/views/crudbooster/partials/ch_auth_art.blade.php` (pannello esagoni condiviso)
  - `public/css/ch-ui2.css`

## Contesto

Il mockup "Confronto UI CustomerHive" (pannello "Proposta") ridisegna il
login: pannello artistico con esagoni a sinistra, form senza card a destra.
Il nuovo look è già attivabile con il flag `CH_UI_V2` (`ch-ui2.css`,
interventi 234-237), ma la pagina di login era standalone e non lo caricava.

## Situazione prima

Login a colonna singola: sfondo scuro con bagliori arancio/salvia, card "a
vetro" centrata, logo in alto al centro. Solo `theme.css`.

## Situazione dopo

Con `CH_UI_V2=true`: `login.blade.php` carica `ch-ui2.css`, mette
`ch-ui2` sul body e aggiunge un pannello decorativo (griglia 9x9 di esagoni
in SVG, generata in Blade) su gradiente viola/arancio. Layout a due colonne
54/46, logo in alto a sinistra, titolo 36px, campi alti 50px, bottone con
gradiente e glow, copyright sotto il form. Sotto 900px il pannello sparisce e
resta il form a colonna singola. Lo stesso look è applicato a `forgot`
(`/admin/forgot`), `reset_password`, `lockscreen`, le tre pagine MFA e `license` tramite il partial
`ch_auth_art`. Le regole CSS sono tutte scoped a `body.ch-auth.ch-ui2`;
nessun altra pagina cambia.
Con `CH_UI_V2=false` la pagina è identica a prima (markup e CSS invariati).

## Motivazione

Allineare il login al resto del nuovo look senza toccare form, route o
logica di autenticazione (stessi campi, stesso `postLogin`).

## Test

- Render della vista con `UI_V2` attivo: 81 poligoni (9x9), nessun errore Blade.
- NON verificato a vista nel browser.

## Rischi e note

- Il logo è il `$logo` dinamico per tenant: sul pannello scuro va bene, sotto
  900px finisce su sfondo chiaro (un logo chiaro lì sarebbe poco leggibile).
- Niente variante scura del login (il mockup non la prevede).

## Rollback

`CH_UI_V2=false` + `php artisan config:clear`, oppure ripristinare i due file.
