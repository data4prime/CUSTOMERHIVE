# 126 - Widget "Indicatore KPI" (smallbox): restyle moderno + campo Descrizione

- **Data**: 2026-09-28
- **Stato**: Completato
- **Area**: Statistic Builder / Dashboard / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/smallbox.blade.php`
  - `resources/lang/it/crudbooster.php`, `resources/lang/en/crudbooster.php`

## Contesto

Il widget `smallbox` è l'"Indicatore KPI" della palette del builder a
griglia (`builder_grid.blade.php:177`) e del builder legacy
(`layout.blade.php`). Dopo un mockup esplorativo (Artifact separato, non
nel repo) di una versione più moderna in stile SaaS, l'utente ha chiesto
di applicare lo stile al widget reale e di aggiungere un campo
"Descrizione" — senza percentuale/andamento/sparkline, dato che il
widget reale mostra un solo valore SQL corrente, senza storico con cui
calcolare una variazione.

## Situazione prima

Il ramo `layout` disegnava un blocco AdminLTE `.small-box` a sfondo
pieno (`background-color: [color]`) con titolo/valore in bianco,
un'icona Ionicons semi-trasparente in overlay e un link opzionale in
stile `.small-box-footer`. Nessun campo per un testo descrittivo
accessorio. Nessuna delle stringhe della form di configurazione usava
`trans()` (pre-esistente, non toccato in questo intervento se non per
il nuovo campo).

## Situazione dopo

- Nuova card chiara (bordo hairline, angoli arrotondati, leggero
  sollevamento/ombra all'hover) che riusa le custom property `--ch-*`
  già definite in `public/css/theme.css` (Fase 6a, stesse variabili
  usate dal restyle di Panel Area/Module Panel/Chart/Table) invece di
  introdurre una palette a parte.
- Icona in una pastiglia con sfondo ottenuto da `color-mix()` sul colore
  configurato dall'utente (`[color]`, invariato come campo) al 14%,
  cosi' il tint si adatta automaticamente a qualunque colore scelto,
  sullo stesso principio delle coppie `--ch-accent/--ch-accent-soft`
  già esistenti per i colori fissi del tema.
- Valore SQL in testo scuro (prima bianco su sfondo colorato).
- Nuovo campo **Descrizione** (`config[description]`, opzionale, testo
  libero): mostrato come didascalia sotto il valore solo se valorizzato
  (`@if(!empty($config->description))`, stesso pattern già usato per
  `[link]`). Label e help text passano da `trans('crudbooster....')`
  (nuove chiavi in `it`/`en`), a differenza delle altre label del form
  (pre-esistenti, fuori scope).
- Link opzionale (`[link]`) ristilizzato con freccia SVG inline al
  posto dell'icona Font Awesome, stesso comportamento (etichetta
  personalizzabile, fallback "Dettagli").
- Overlay di modifica/eliminazione (`.action`, pencil/trash) invariato:
  stessa struttura, stesse classi (`btn-edit-component`/
  `btn-delete-component`) usate dal JS del builder, ancora nascosto
  fuori da `statistic_builder/builder` dallo stesso script `<script
  defer>` di prima.

## Motivazione

- Riuso dei design token `--ch-*` già in produzione invece di una nuova
  scala colori: coerenza visiva con gli altri widget già ristilizzati
  (Fase 6a) e con il resto del pannello admin, zero colori "a caso".
- `color-mix()` per la pastiglia icona invece di una tinta fissa: il
  colore è un campo libero dell'utente (color picker), non uno dei
  ruoli fissi del tema — serviva un modo per ottenere un tint coerente
  qualunque hex venga scelto, senza calcolarlo lato PHP.
- Niente percentuale/sparkline nel widget reale (a differenza del
  mockup esplorativo): il dato disponibile è un singolo valore SQL
  corrente, senza serie storica da cui derivare una variazione — averla
  finta/hardcoded sarebbe stato fuorviante.
- `description` escapato esplicitamente con `e()` nel ramo
  `showFunction` (a differenza di `name`/`link`/`link_label`, mai
  passati per `e()`): è pensato per una frase più lunga e libera,
  sembrava ragionevole non allargare la superficie XSS pre-esistente
  aggiungendo un altro campo non escapato.

## Test

- `docker compose exec app php -l` sul blade e su entrambi i file
  lingua: nessun errore di sintassi.
- **Non verificato in browser** in questa sessione (nessun giro manuale
  sul builder griglia/legacy per controllare resa visiva, color-mix()
  nel browser effettivamente usato, o salvataggio/lettura del nuovo
  campo `description` da un widget reale) — da fare come prossimo
  passo prima di considerare l'intervento pienamente verificato.

## Rischi e note

- `color-mix()` richiede un browser evergreen recente (Chrome/Edge/
  Firefox moderni); non verificato su target più vecchi — coerente con
  l'uso interno via browser aggiornati già assunto altrove nel progetto.
- Le altre label della form di configurazione (Name, Icon By Ionicons,
  Color, Link, Etichetta del link, Count) restano hardcoded/non
  tradotte: pre-esistente, fuori scope di questo intervento.
- Cambiamento di comportamento visibile (non solo interno): l'aspetto
  di ogni widget "Indicatore KPI" già esistente cambia per tutti gli
  utenti/clienti non appena questo file arriva in produzione — nessuna
  perdita di dati o di configurazione, ma va comunicato come modifica
  visibile, non solo un refactor interno.

## Rollback

`git checkout` sui tre file elencati sopra (blade + due file lingua)
riporta il widget alla card piena a sfondo colorato e rimuove il campo
Descrizione (i valori eventualmente già salvati in `config->description`
sui widget esistenti restano nel DB ma smettono di essere mostrati,
finché il file non viene ripristinato).
