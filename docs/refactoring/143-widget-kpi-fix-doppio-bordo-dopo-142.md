# 143 - Widget Indicatore KPI: fix doppio bordo comparso dopo la rimozione del padding (142)

- **Data**: 2026-09-29
- **Stato**: Completato (non verificato in browser, su richiesta esplicita dell'utente)
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/smallbox.blade.php`

## Contesto

Segnalato dall'utente subito dopo 142 (rimozione del padding tra widget
e contenitore): "ora si vedono i due bordi". 142 aveva tolto lo spazio
tra il bordo del widget e quello della cella che lo contiene, ma non
il fatto che entrambi disegnassero un proprio bordo/sfondo - con lo
spazio tolto, i due bordi ora combaciano esattamente nello stesso
rettangolo invece di essere separati, rendendo il doppio bordo (prima
solo percepibile come "spazio strano in mezzo") visibile come tale.

## Situazione prima

`.kpi-indicator-card` (in `smallbox.blade.php`) ha un proprio
`background`/`border`/`border-radius`, oltre a quello gia' fornito dal
contenitore generico esterno (`.grid-stack-item-content` nel builder,
`.ch-grid-view-cell` nella vista pubblica - stesso bianco, bordo
`#E4E7EC` simile, raggio 12px) che lo racchiude. Prima di 142 il padding
di 4px tra i due nascondeva parzialmente la sovrapposizione (i due bordi
erano separati da uno spazio bianco); tolto quello spazio, restano due
bordi arrotondati praticamente identici uno attaccato all'altro.

## Situazione dopo

`.kpi-indicator-card` non disegna piu' un proprio `background`/`border`:
resta solo il bordo/sfondo/ombra del contenitore esterno, che fa gia' da
bordo visibile per qualunque widget (anche quelli senza stile proprio,
vedi il placeholder `_empty_widget_state.blade.php`, che infatti non ha
mai avuto un proprio bordo/sfondo per lo stesso motivo). Mantenuto
`border-radius` sulla card (senza bordo/sfondo non disegna nulla di per
se', ma fa si' che l'ombra al passaggio del mouse - `:hover` - resti
arrotondata invece che con angoli vivi, coerente con l'angolo
arrotondato del contenitore che la racchiude).

## Motivazione

Fix diretto del sintomo segnalato: un solo bordo visibile invece di due
sovrapposti. Corretto solo `smallbox.blade.php` perche' e' l'unico
widget con questo problema - verificato che nessun altro componente
sotto `statistic_builder/components/` definisce un proprio
`border-radius`/bordo proprio (`_empty_widget_state.blade.php`, l'unico
altro file con `border-radius`, lo usa solo per una piccola icona
circolare interna, non per un bordo che si sovrapponga al contenitore).

## Test

Non verificato in browser (regola esplicita dell'utente, vedi
`feedback_no_autonomous_browser_testing`). Verificato solo `php -l` e,
leggendo il codice, che nessun altro widget ha lo stesso problema. Da
confermare visivamente dall'utente: aprire builder e dashboard pubblica
e controllare che il bordo dell'Indicatore KPI sia uno solo.

## Rischi e note

Nessuno noto - la card interna continua a fornire padding/layout/hover,
solo bordo/sfondo ridondanti rimossi.

## Rollback

`git diff` di `smallbox.blade.php` per ripristinare bordo/sfondo propri
della card (tornando al doppio bordo).
