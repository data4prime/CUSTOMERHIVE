# 172 - Widget della dashboard: stessa impostazione a card dell'Indicatore KPI

- **Data**: 2026-09-30
- **Stato**: Completato (non verificato a occhio nel browser, vedi "Test")
- **Area**: UI/UX (Statistic Builder)
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/_widget_card_style.blade.php` (nuovo)
  - incluso da `show_grid.blade.php`, `builder_grid.blade.php`, `index.blade.php` (legacy)

## Contesto

Richiesta: tutti i widget devono avere lo stile fatto per gli Indicatori KPI
(`smallbox.blade.php`, `.kpi-indicator-card`, docs 126/143/146).

## Situazione prima

Solo l'Indicatore KPI aveva la sua impostazione: 18px di respiro interno,
titolo come etichetta (13px, peso 600, colore secondario), ombra al hover. Gli
altri widget a card (Tabella, Grafici a linee/aree/barre, Pannello, Modulo, sia
v1 sia v2) usavano `.card.card-default` con header a 13px/700 e una riga di
separazione sotto, padding stretto (`10px 6px` nella vista pubblica), nessun
effetto al hover. Il widget Qlik è solo un iframe senza card.

## Situazione dopo

Un unico partial di stile, incluso una volta per pagina (non in ogni
componente: i widget arrivano anche via AJAX dentro contenitori già in pagina),
applica a `div.border-box > .card.card-default`:

- header come l'etichetta del KPI: `18px 18px 0`, 13px/600, colore secondario,
  niente riga di separazione;
- body con `10px 18px 18px` (anche la Tabella, che prima era a filo via
  `.no-padding`);
- ombra al passaggio del mouse (`--ch-shadow-md`);
- Modulo incorporato: body resta a filo (contiene l'iframe del modulo), header
  con 10px sotto.

Fallback su tutti i `var()`: `builder_grid` è standalone senza `theme.css`.
Nessun markup dei widget modificato.

## Motivazione

Coerenza tra i widget della stessa dashboard con una sola fonte di verità.
Scelte deliberate: niente `transform` al hover (il KPI ha un `translateY(-1px)`,
ma dentro un widget possono esserci iframe/tabelle/elementi fixed); niente
bordo/sfondo propri (li disegna già il contenitore esterno, vedi 143); nessun
`display:flex` sulla card (cambierebbe come vengono ritagliati i contenuti alti,
oggi già gestito dal contenitore).

## Test

- Compilazione Blade + `php -l` del compilato di `show_grid`, `builder_grid`,
  `index` e del partial; verificata la presenza dell'include in tutti e tre.
- **Non verificato a occhio nel browser**: da controllare una dashboard per
  ciascun tipo di widget (Tabella con molte righe, grafici a linee/aree/barre,
  Pannello, Modulo incorporato, Qlik), sia in builder sia nella vista pubblica
  e in una dashboard legacy a aree.

## Rischi e note

- Chi ha tabelle larghe vede 18px di margine a destra/sinistra invece di zero:
  più area utile occupata dal padding.
- L'Indicatore KPI ha anche icona, colore per widget, descrizione e link in
  fondo: qui **non** replicati sugli altri widget (richiederebbero nuovi campi
  di configurazione nel builder).
- Widget Qlik (iframe senza card) invariato.

## Rollback

Togliere le tre righe `@include('crudbooster::statistic_builder.components._widget_card_style')`
(o cancellare il partial).
