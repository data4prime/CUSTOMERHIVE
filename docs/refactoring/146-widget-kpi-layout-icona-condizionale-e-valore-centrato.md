# 146 - Widget Indicatore KPI: icona non riservata quando assente, valore centrato nello spazio libero

- **Data**: 2026-09-29
- **Stato**: Completato e verificato con render diretto della vista, non in browser su richiesta esplicita dell'utente
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/smallbox.blade.php`

## Contesto

L'utente ha chiesto di guardare la dashboard reale
(`/admin/statistic_builder/show/indicatori-kpi`, 14 widget con
combinazioni diverse di icona/descrizione/link presenti o assenti) e di
proporre un mockup che migliorasse la resa in tutti i casi, senza
aggiungere contenuti. Discusso e condiviso un mockup (Artifact) con la
proposta; l'utente ha chiesto di implementarla con un solo vincolo:
altezza e larghezza della card restano quelle scelte nel builder (mai un
valore fisso in CSS).

## Situazione prima

Osservato sulla dashboard reale (screenshot):
- La pastiglia icona (`.kpi-indicator-icon`, 34px + gap) veniva sempre
  renderizzata anche senza un'icona scelta (solo l'icona Lucide dentro
  era condizionale, da 139) - con il colore di default bianco (141),
  restava una pastiglia bianca invisibile su una card bianca, ma
  occupava comunque spazio spostando l'etichetta.
- Valore e descrizione erano diretti figli del contenitore flex della
  card (`gap: 10px`), il link in fondo con `margin-top: auto`: tutto lo
  spazio libero (quando la card e' piu' alta del contenuto, es. senza
  descrizione/link) si accumulava TRA la descrizione/il valore e il
  link, lasciando valore/descrizione ancorati in alto e un vuoto
  sproporzionato sotto - risultato disomogeneo tra widget con
  descrizione+link e widget senza.

## Situazione dopo

- La pastiglia icona (`<div class="kpi-indicator-icon">`, non solo
  l'icona Lucide al suo interno) e' condizionata a `!empty($config->icon)`
  per intero: se non c'e' un'icona, non occupa piu' spazio.
- Valore e descrizione sono raggruppati in un nuovo contenitore
  `.kpi-indicator-body` (`flex: 1; min-height: 0; display: flex;
  flex-direction: column; justify-content: center; gap: 4px;`), che si
  prende tutto lo spazio libero tra intestazione e link (o fino in
  fondo alla card, se non c'e' link) e vi centra il proprio contenuto -
  con o senza descrizione, con o senza link, il valore non resta piu'
  ancorato in alto con vuoto sotto.
- `.kpi-indicator-footer` non usa piu' `margin-top: auto` (ridondante
  ora che `.kpi-indicator-body` e' `flex: 1`: un solo meccanismo che
  decide lo spazio, non due).
- **Altezza/larghezza della card non toccate**: restano
  `height: 100%` sul contenitore (gia' cosi', riempie la cella
  scelta nel builder - vedi 108/137/142), nessun valore fisso in CSS.

## Motivazione

Fix diretto ai due problemi discussi nel mockup: spazio riservato per
un'icona assente, e spazio libero scaricato tutto sotto invece di
centrare il contenuto - a costo quasi zero (nessun nuovo elemento
visibile, solo riorganizzazione dei contenitori esistenti).

## Test

Non verificato in browser (regola esplicita dell'utente, vedi
`feedback_no_autonomous_browser_testing`) - verificato con un render
diretto della vista (bootstrap Laravel, script isolato eseguito e poi
rimosso, non la suite di test automatica - dato quanto successo in 144,
`php -l` da solo non basta quando si toccano commenti Blade) per tre
combinazioni (icona+descrizione+link, senza nessuno dei tre, icona+link
senza descrizione): confermato che `<div class="kpi-indicator-icon">`
compare solo quando l'icona e' impostata, `<p class="kpi-indicator-description">`
solo quando la descrizione e' impostata, `<div class="kpi-indicator-footer">`
solo quando il link e' impostato, e `<div class="kpi-indicator-body">`
compare sempre (contenitore del valore, sempre presente). Ispezionato
anche il commento Blade appena aggiunto per assicurarsi che sia chiuso
con `--}}`, non `*/` (lezione di 144).

## Rischi e note

Nessuno noto - nessun cambio di formato dati, solo riorganizzazione del
markup/CSS della card.

## Rollback

`git diff` di `smallbox.blade.php` per tornare alla pastiglia icona
sempre presente e al valore/descrizione ancorati in alto.
