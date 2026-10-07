# 249 - Impostazioni "Application Setting": anteprima come nel mockup

- **Data**: 2026-10-07
- **Stato**: Completato (verificato a vista nel browser locale)
- **Area**: Frontend / Impostazioni
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/setting.blade.php`
  - nuovo `resources/views/crudbooster/partials/app_setting_preview.blade.php`
  - `public/css/ch-components.css` (`.ch-aprev*`; rimossi i `.ch-setprev*` dell'intervento 237)
  - `resources/lang/{en,it}/crudbooster.php`

## Contesto

Seguito di 248. Nel mockup "Confronto UI CustomerHive" l'anteprima di
`admin/settings/show?group=Application+Setting` è una card a destra del form con scheda
del browser, finestra con sidebar, formato carta e modalità debug API. Nella pagina reale
c'era la prima versione (intervento 237): un riquadro dentro la card, sopra i campi, con
solo scheda e sidebar.

## Situazione prima

Blocco inline in `setting.blade.php` ("Anteprima": scheda con favicon e nome + sidebar con
logo), script inline per nome/logo/favicon; stili `.ch-setprev*`.

## Situazione dopo

- Pagina a due colonne (`.ch-setgrid`, già introdotta in 248): form a sinistra, card
  "Anteprima applicazione" a destra, riconosciuta dai campi `appname` + `logo`.
- La card mostra: scheda ("Nome: Dashboard", favicon), finestra con sidebar (logo) e barre,
  "Formato carta" (foglio con proporzioni di Legal/Letter/Ledger, A4 per gli altri) e
  "Modalità debug API" (Attivo/Disattivo).
- Si aggiorna senza salvare: nome mentre si digita, logo e favicon alla scelta del file,
  formato carta e debug API al cambio del menu.
- Rimossi blocco inline e CSS `.ch-setprev*` (sostituiti). Nuove chiavi en+it
  `setting_app_preview_*`; `setting_preview` resta ma non è più usata.
- Invariati: campi, salvataggio, altri gruppi di impostazioni.

## Motivazione

Allineamento al mockup; anteprima più informativa (carta e debug) e coerente con quella
di "Login Register Style".

## Test

A vista: card a destra; cambiando nome, formato carta (Letter → foglio più alto) e debug
API (false → "Disattivo") l'anteprima si aggiorna senza salvare. Non verificati: logo e
favicon caricati davvero dal file system, selezione di un file nuovo, tema scuro, formati
carta diversi da Letter.

## Rischi e note

L'anteprima è indicativa. Il testo "Dashboard" nella scheda è una chiave di lingua, non il
titolo reale della pagina.

## Rollback

Ripristinare `setting.blade.php` e `ch-components.css` dalla versione precedente (git) e
rimuovere il partial e le chiavi `setting_app_preview_*`.
