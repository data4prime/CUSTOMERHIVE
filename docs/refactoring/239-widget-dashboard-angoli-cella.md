# 239 - Widget dashboard: niente angoli interni visibili nella cella

- **Data**: 2026-10-07
- **Stato**: Completato (da verificare a vista)
- **Area**: Frontend / Dashboard (Statistic Builder)
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/show_grid.blade.php`
  - `resources/views/crudbooster/statistic_builder/builder_grid.blade.php`

## Contesto

Segnalazione: alcuni widget delle dashboard mostrano angoli che non
dovrebbero vedersi, a differenza del mockup di confronto UI, dove ogni
widget è un unico riquadro arrotondato.

## Situazione prima

Il riquadro visibile è disegnato dalla cella (`.ch-grid-view-cell` /
`.grid-stack-item-content`: bordo 1px, raggio 12px, `overflow: auto`).
Dentro, il widget ha un raggio proprio: `.small-box` 10px, `.card` e
`.kpi-indicator-card` fino a `--ch-radius-lg`. Raggi diversi tra cella e
widget lasciano intravedere gli angoli dello sfondo della cella (o del
widget stesso) dietro quelli del widget.

## Situazione dopo

Dentro la cella, `.small-box`, `.card` e `.kpi-indicator-card` figli
diretti di `.border-box` hanno `border-radius: 0`: gli angoli arrotondati
li disegna solo la cella, che ritaglia i figli con `overflow`.

Correzione dopo prova a vista (la prima versione non bastava): la regola
`body.ch-shell.ch-ui2 .card.card-default` (ch-ui2.css) batteva la mia per
specificità e dava alla card raggio 20px, bordo e ombra propri; selettori
ora più specifici e, nelle celle, `.card.card-default` senza bordo né
ombra. Inoltre il widget "Modulo incorporato": l'iframe aveva raggio 14px
e bordo 1px da `.border-box iframe` (theme.css) e la pagina incorporata
aveva sfondo lavanda (`.wrapper`) su una card bianca. Ora
`.border-box .ch-module-frame` ha raggio/bordo 0 (`panelcustom.blade.php`)
e in `ch-ui2.css` body/`.wrapper` in modalità `ch-embed` sono trasparenti.

## Motivazione

Un solo raggio, quello della cella, come nel mockup. Modifica solo CSS,
scoped alle due pagine a griglia; le dashboard legacy (`theme.css`,
`.border-box` senza cella) non cambiano.

## Test

Solo lettura del codice: nessuna prova a vista né suite di test. Da
verificare in dashboard (vista pubblica e builder) con KPI, Small Box,
grafici e tabella.

## Rischi e note

Cambia l'aspetto (solo gli angoli). Se il difetto segnalato fosse un altro
(es. un iframe Qlik o una tabella che sporge), questa modifica non lo
copre: servirebbe sapere quale widget e in quale pagina.

## Rollback

Rimuovere le due regole `border-radius: 0` aggiunte nei due file.
