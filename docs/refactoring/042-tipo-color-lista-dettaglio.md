# 042 - Nuovo tipo di campo "color": swatch anche in lista e dettaglio

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Miglioramento UI/UX
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/default/type_components/color/{asset,component,component_detail}.blade.php`, `info.json` (nuovi)
  - `app/Http/Controllers/System/CBController.php`
  - `app/Http/Controllers/System/AdminTenantsController.php`

## Contesto

Seguito di [041](041-color-picker-tenants.md): l'utente ha chiesto che
anche le pagine di lista/dettaglio mostrino il colore (uno swatch)
invece del solo codice esadecimale in chiaro.

Invece di continuare a riusare il tipo `custom` (usato in 041 solo per il
form), creato un vero e proprio tipo di campo `color` — 35esimo tipo
disponibile in `type_components/`, riconosciuto anche dal generatore di
moduli (`glob()` in `ModulsController.php` lo trova automaticamente,
nessuna modifica necessaria lì). Motivo: il tipo `custom` renderizza
`$form['html']` così com'è (statico, richiede di ricalcolare a mano il
valore corrente in `cbInit()`, come fatto in 041); un tipo vero invece
riceve `$value` già calcolato per riga da `form_body.blade.php`/
`form_detail.blade.php`, esattamente come tutti gli altri tipi — più
semplice da usare e riutilizzabile da qualunque modulo futuro con un
campo colore, non solo Tenants.

## Situazione prima

- Form: tipo `custom` con HTML statico costruito a mano in `cbInit()`
  (richiedeva una query aggiuntiva per leggere il valore corrente).
- Lista: le 2 colonne colore erano commentate (mai mostrate).
- Dettaglio: nessuna vista dedicata (le colonne non erano nemmeno in
  lista).

## Situazione dopo

- **Nuovo tipo `color`**: `component.blade.php` (`<input type="color">`,
  stesso stile del tipo `text`), `component_detail.blade.php` (uno swatch
  colorato + il codice esadecimale accanto), `asset.blade.php` (vuoto,
  nessun JS/CSS extra necessario), `info.json` (per il generatore di
  moduli).
- **`CBController::getIndex()`**: nuovo flag `'color' => true` per le
  colonne — stesso punto/stesso stile già usato per `'image'`/`'download'`,
  trasforma il valore in uno swatch + testo prima di finire in tabella.
- **`AdminTenantsController.php`**: form semplificato a
  `'type' => 'color'` (rimossa la query manuale introdotta in 041, non
  più necessaria); le 2 colonne colore ora in lista con
  `'color' => true` (prima commentate).

## Motivazione

Uno swatch visivo è più leggibile di un codice esadecimale in chiaro,
sia in lista che in dettaglio — e farlo come tipo di campo vero (non un
hack con `custom`) lo rende disponibile a qualunque modulo futuro con un
campo colore, senza duplicare logica.

## Test

- `php -l` su tutti i file PHP toccati: nessun errore.
- Compilazione isolata dei 2 nuovi file Blade: OK.
- `view()->exists()` su `component`/`component_detail`: entrambi trovati;
  `asset.blade.php` presente.
- `glob()` sul generatore di moduli: **35 tipi**, `color` incluso
  (prima: 34).
- **Rendering diretto** (non solo esistenza file) di entrambi i template
  con un valore reale (`#4287f5`): form → `<input type='color' ...
  value='#4287f5' />` corretto; dettaglio → swatch colorato col
  `background-color` giusto + testo `#4287f5` accanto.
- **Test diretto della logica lista** (`'color' => true` in
  `CBController.php`): genera lo swatch HTML atteso con lo stesso colore.
- `php artisan view:clear` + `route:list`: 486 rotte, invariato.
- `curl` senza sessione su `/admin/tenants`, `/admin/tenants/edit/1`,
  `/admin/tenants/detail/1`: tutti 302, nessun 500.

**Non verificato visivamente in browser** — lasciato al giro di test
manuale dell'utente.

## Rischi e note

- Il nuovo tipo `color` è generico e riusabile: eventuali altri campi
  colore trovati in futuro in altri moduli possono usarlo direttamente,
  senza replicare l'hack di 041.
- La colonna "Login Background Image" resta commentata in lista (fuori
  scope: è un upload immagine, non un colore — non toccata).

## Rollback

`git revert` del commit — rimuove il nuovo tipo, il flag `color` in
`CBController.php`, e ripristina `AdminTenantsController.php` allo stato
di [041](041-color-picker-tenants.md).
