# 255 - API Generator: tab come nel mockup e popover Summernote visibili

- **Data**: 2026-10-07
- **Stato**: Completato (verificato a vista nel browser locale)
- **Area**: Frontend / API Generator, editor wysiwyg
- **File/aree di codice coinvolte**:
  - `public/css/ch-api.css` (`.api-tabs`)
  - `public/css/ch-components.css` (`.note-popover.popover`)

## Contesto

In `admin/api_generator/generator`: (1) le tab "Endpoint / Chiave Segreta API / Nuovo endpoint"
vanno come nel mockup; (2) sotto i pulsanti Annulla / Salva endpoint comparivano due barre
vuote e una piccola icona cestino.

## Situazione prima

- Tab con sottolineatura (stile "linea"); nel mockup sono a segmenti.
- Le barre vuote erano i due popover di Summernote (link e immagine, `.note-popover.popover`)
  che l'editor della Descrizione aggiunge in fondo al `<body>`. Il CSS di Summernote è scritto
  per Bootstrap 3, dove `.popover` è nascosto finché non serve; con Bootstrap 5 restavano
  visibili (`display: block`, posizione statica) sotto il footer della pagina. Lo stesso
  succede con ogni campo di tipo wysiwyg dei moduli.

## Situazione dopo

- `.api-tabs`: contenitore a pillola (fondo `--ch-thead`, bordo, raggio) con voce attiva
  bianca in rilievo, come nel mockup. Il partial `api_nav` è condiviso da tutte le pagine API
  (elenco, chiavi, editor, token): cambiano tutte insieme.
- `.note-popover.popover { display: none; position: absolute; z-index: 1070 }`: l'`display` in
  linea che Summernote imposta con `show()`/`hide()` prevale, quindi i popover compaiono
  ancora quando servono (non provato a mano).

## Motivazione

Allineamento al mockup; eliminare un difetto visivo reale (bug) presente in ogni pagina con
l'editor wysiwyg di Summernote.

## Test

A vista su `api_generator/generator`: tab a segmenti; nessun elemento sotto il footer
(`display: none` verificato sui due popover). Non verificato: l'apertura dei popover di
Summernote cliccando link/immagine nella Descrizione; gli altri campi wysiwyg dei moduli;
tema scuro.

## Rischi e note

La regola è globale ma riguarda solo `.note-popover.popover`. Se un popover Summernote non
si aprisse più, basta togliere la regola.

## Rollback

Ripristinare `.api-tabs` in `ch-api.css` (stile a sottolineatura) e rimuovere la regola
`.note-popover.popover` da `ch-components.css`.
