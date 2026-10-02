# 213 - Widget KPI: ripristino del CSS andato perso (icona del link enorme)

- **Data**: 2026-10-02
- **Stato**: Completato (da vedere a vista)
- **Area**: Frontend / Statistic Builder
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/smallbox.blade.php`

## Contesto

Nel widget "Contratti attivi" della dashboard (`/admin/statistic_builder/dashboard`)
l'icona a freccia del link in fondo alla card appariva enorme.

## Situazione prima

Nel `<style>` di `smallbox.blade.php` mancavano le regole di
`.kpi-indicator-label`, `-body`, `-value`, `-description`, `-footer`, `-link`,
`-link-icon` (presenti nel commit `2dbfa3b2`, perse nel lavoro non committato,
verosimilmente durante il passaggio dell'icona da Lucide a Bootstrap Icons, che
sostituiva la regola `.lucide-icon` subito prima). Senza `width/height` l'`<svg>`
inline del link prendeva tutta la larghezza disponibile; mancavano anche stile e
centraggio di valore, descrizione e link.

## Situazione dopo

Regole ripristinate dal commit, con i token `var(--ch-*)` senza fallback hex.
Confrontati i selettori CSS con quelli del commit: l'unica differenza è
`.lucide-icon` -> `.kpi-icon` (voluta).

## Motivazione

Ripristino di una regressione, nessun cambio di design.

## Test

- `view:cache` / `view:clear` OK.
- Verifica di casi simili: nessun altro `<svg>` inline senza dimensioni nelle
  viste in uso (solo `vendor/pagination/tailwind.blade.php`, non usata perché
  `AppServiceProvider` imposta `Paginator::useBootstrapFive()`); tutte le classi
  CSS dei widget hanno una regola. Non verificato a vista nel browser.

## Rischi e note

Il widget torna all'aspetto del commit (valore 30px, link in accent).

## Rollback

Togliere le regole aggiunte in coda al `<style>` di `smallbox.blade.php`.
