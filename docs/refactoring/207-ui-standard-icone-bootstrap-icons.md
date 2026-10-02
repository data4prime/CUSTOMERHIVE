# 207 - Standard UI: icone Bootstrap Icons

- **Data**: 2026-10-02
- **Stato**: Completato (da provare a mano nel browser)
- **Area**: Frontend
- **File/aree di codice coinvolte**:
  - `app/Helpers/IconMap.php` (nuovo), `app/Helpers/Fontawesome.php`
  - `public/css/ch-icons-compat.css` (generato), `local-scripts/gen-icons-compat.php`, `local-scripts/migrate-fa-to-bi.php` (nuovi)
  - `public/vendor/bootstrap-icons/` (nuovo)
  - 171 file in `resources/views`, `app/Helpers`, `app/Http/Controllers/System`, `app/Services`, `public/js`
  - picker icone di menu/moduli (`components/list_icon`, `module_generator/step1`) e widget Small Box
  - eliminati: `public/css/icons-lucide.css`, `public/vendor/lucide/`, `public/vendor/crudbooster/ionic/`

## Contesto

Decisione del 2026-10-02: un solo set di icone, **Bootstrap Icons** (1.13.1),
"un solo ecosistema" con Bootstrap. Vedi 206 per il piano generale.

## Situazione prima

- FontAwesome 4 (~545 usi in 121 file), reskinnato via CSS mask con SVG Lucide
  per 82 nomi (`icons-lucide.css`, interventi 128/129), Ionicons (2 usi), e
  Lucide diretto nel widget Small Box (75 SVG in `public/vendor/lucide/`, nome
  salvato nella config del widget nel DB dei dashboard).
- Il selettore icone di menu e moduli proponeva 693 nomi FontAwesome 4 e salvava
  `fa fa-<nome>` nel DB (con un a-capo dentro il valore: `fa\n fa-music`, bug del
  markup dell'`<option>`).
- Menu, moduli, controller custom dei clienti contengono `fa fa-*` salvati o
  scritti a mano.

## Situazione dopo

- **Mappa unica** `IconMap::map()` (693 nomi FA4 -> Bootstrap Icons, tutti
  validati contro `bootstrap-icons.json`) e `IconMap::lucideMap()` (i nomi
  Lucide del widget Small Box). Metodi `toBi()` (classe salvata -> `bi bi-*`) e
  `biClass()` (nome nudo -> classe).
- **Compatibilita'**: `ch-icons-compat.css` (generato dalla mappa con
  `docker compose exec -T app php local-scripts/gen-icons-compat.php`) fa
  renderizzare con il font di Bootstrap Icons anche le classi `fa fa-*` che non
  si possono riscrivere: valori nel DB dei clienti, loro controller/viste custom.
  Nomi senza corrispondenza mostrano `bi-app` invece di un riquadro vuoto. Resta
  finche' tutti i clienti non sono migrati.
- **Codice del progetto migrato** con `local-scripts/migrate-fa-to-bi.php`
  (riusabile sui file di un cliente): 647 sostituzioni, i modificatori diventano
  `ch-icon-fw`, `ch-spin`, `ch-icon-lg`, `ch-icon-2x..5x` (in `ch-components.css`).
  Rifatti a mano i punti dinamici (chevron del menu a tendina, `button_selected`
  dei controller custom tramite `IconMap::biClass()`, titolo pagina e voci di
  menu tramite `IconMap::toBi()`).
- **Selettori icone** di menu e moduli: propongono i nomi di Bootstrap Icons,
  salvano `bi bi-<nome>` su una riga sola, preselezionano anche le icone gia'
  salvate come `fa fa-*` (via mappa). Nuova chiave `crudbooster.icon_select`
  (it/en).
- **Widget Small Box**: non usa piu' maschere SVG Lucide; il nome salvato
  (anche i vecchi nomi Lucide, tradotti con `lucideMap()`) diventa la classe
  `bi bi-<nome>`; il picker elenca Bootstrap Icons. Nuova chiave
  `statistic_builder_smallbox_icon_help` (it/en).
- Eliminati Lucide, Ionicons e la reskin FA->Lucide (`icons-lucide.css`).

## Motivazione

Un set unico, vettoriale, in locale, con la stessa famiglia di Bootstrap. La
mappa dei nomi evita di toccare i dati dei clienti. Il cambio visivo (da Lucide
a Bootstrap Icons) e' voluto.

## Test

- Mappa: tutti i 693 nomi FA4 mappati, tutti i nomi `bi-*` esistenti nel JSON
  di Bootstrap Icons, tutti i 75 nomi Lucide risolvono.
- Vista compilata, pagine admin 200 senza errori; nessun errore JS in jsdom.
- Trovato e corretto durante la verifica: il motore dei widget sostituisce i
  token `[name]`, `[icon]`... **anche dentro gli script** del layout; una
  variabile JS `name` con `iconMap[name]` veniva trasformata in
  `iconMap[Contratti attivi]`. Nello script del Small Box la variabile e' ora
  `glyph` (nota nel codice). Regola: nei layout dei widget mai variabili JS con
  lo stesso nome di una chiave di config.
- Non verificato: resa visiva delle icone nel browser; nomi FA con significato
  non esatto in Bootstrap Icons (es. `fa-glass` -> `cup-straw`, `fa-ship`,
  `fa-bomb`, loghi senza equivalente -> `bi-app`).

## Rischi e note

- Moduli custom dei clienti: continuano a funzionare grazie alla compatibilita'.
- Il nome della classe `App\Helpers\Fontawesome` resta (richiamata da controller
  custom) ma restituisce l'elenco di Bootstrap Icons.
- Sed e backslash: durante il lavoro e' capitato piu' volte che uno `sed` perdesse
  i `\` di `\App\Helpers\IconMap` (sintassi valida, classe inesistente a runtime).
  Per modifiche con namespace usare Edit o uno script PHP.

## Rollback

Ripristinare da git i file toccati e gli asset eliminati; cancellare
`IconMap.php`, `ch-icons-compat.css`, `public/vendor/bootstrap-icons/`.
