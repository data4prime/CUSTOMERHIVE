# 163 - Modulo incorporato: iframe con layout ridotto e pulsante "Apri a pagina intera"

- **Data**: 2026-09-30
- **Stato**: Completato e verificato in locale (rendering lato server); da provare in browser
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/panelcustom.blade.php`
  - `resources/views/crudbooster/statistic_builder/builder_grid.blade.php`
  - `resources/views/crudbooster/statistic_builder/layout.blade.php`
  - `resources/lang/en/crudbooster.php`, `resources/lang/it/crudbooster.php`

## Contesto

Fase 4 del piano iniziato con [161](161-modulo-incorporato-robustezza-e-traduzioni.md)
e [162](162-modulo-incorporato-modalita-embed-fasi-1-3.md). Il widget
"Modulo incorporato" (`panelcustom`) viene usato per *lavorare* sul
modulo (aprire e salvare record). Decisioni dell'utente: altezza e
larghezza le decide il builder; sessione scaduta → login a pagina
intera (già in 162); pulsante per aprire il modulo a pagina intera.

## Situazione prima

Il widget scaricava la pagina completa del modulo con `$.get`, ne
ritagliava `#content_section` e lo iniettava nella dashboard: script del
modulo nel contesto della dashboard (collisioni di id/variabili),
qualunque azione (paginazione, filtri, link, salvataggio) portava fuori
dalla dashboard, e una risposta senza `#content_section` (redirect al
login, errore) lasciava il widget bloccato su "loading". Il testo
"Back To List Data" veniva sostituito con "Go to" (non funzionava in
italiano).

## Situazione dopo

- `showFunction`: al posto di `$.get` + scraping, un
  `<iframe class="ch-module-frame" src="{route}?embed=1" loading="lazy">`
  con `title` = nome del widget (escapato). Modulo inesistente →
  "Modulo non disponibile" come in 161.
- **Dimensioni**: la card è un flex column che riempie l'altezza della
  cella (`.border-box`/`.card` erano già `height:100%` in builder e
  dashboard a griglia), il body (senza padding) e l'iframe riempiono la
  card: altezza e larghezza seguono quelle scelte nel builder. Nelle
  dashboard legacy ad aree (nessuna altezza definita) l'iframe ha
  altezza fissa di 600px, con scroll interno.
- **Pulsante "Apri a pagina intera"** nell'intestazione della card, a
  destra del titolo: link alla stessa route senza `embed`, nella stessa
  scheda. Assente se il modulo non esiste più. Nuova chiave
  `module_widget_open_full` (en/it).
- **Builder** (`builder_grid.blade.php`, builder legacy
  `layout.blade.php`): `pointer-events: none` su iframe e link. Nel
  builder a griglia l'intera card è l'handle di drag e l'area di
  selezione, un iframe interattivo li renderebbe inutilizzabili; il
  modulo è lì solo un'anteprima, interattivo nella dashboard. (Il
  builder legacy è l'unico a estendere `layout.blade.php`: la dashboard
  legacy di sola lettura non è toccata.)
- Rimosse le chiavi `module_widget_loading` e `module_widget_error_*`
  introdotte in 161 (servivano solo allo script di scraping, non più
  presente).
- **Select "Modulo da mostrare" ricercabile** (richiesta successiva
  dell'utente): select2 con lo stesso schema già usato per l'icona
  (`smallbox`) e il dataset (`_query_builder_fields`) — CSS e script
  inclusi nella form di configurazione, caricamento pigro condiviso
  (`window.__chEnsureSelect2`), `dropdownParent` solo se esiste la
  modale del builder legacy. Messaggi "nessun risultato"/"ricerca in
  corso" tradotti (`module_widget_no_results`, `module_widget_searching`).
  Le opzioni erano già ordinate per etichetta; il confronto è ora
  `strnatcasecmp` (insensibile alle maiuscole, numeri in ordine naturale).
  Il valore salvato (nome della route) non cambia.
- La dashboard di sola lettura (che nel codice si chiama "vista
  pubblica", ma è dietro login come tutto l'admin) mostra il widget, come
  prima: è lì che gli utenti lavorano.

## Motivazione

Isolamento completo (nessuna collisione di JS/CSS), le azioni restano
dentro il widget, nessuna dipendenza fragile da `#content_section`,
dimensioni governate dal builder. Alternativa scartata: endpoint che
restituisce solo il frammento HTML (risolveva lo scraping ma non
collisioni né navigazione fuori dal widget).

## Test

Manuali, in locale (Docker), rendering lato server:
- componente con modulo valido → iframe `…/admin/users?embed=1`, link
  "Apri a pagina intera" (it/en), nessun segnaposto `[name]`/`[value]`
  rimasto;
- modulo inesistente → messaggio "Modulo non disponibile", nessun link;
- widget non configurato → stato vuoto come prima;
- titolo con apice e `<script>` → escapato nell'attributo;
- widget reale del DB di sviluppo ("Modulo OdV") passato da
  `renderComponentPayload()`: HTML finale corretto.
- `php -l` sui file lingua, `view:clear`.
- Form di configurazione: 34 opzioni (licenza dev), ordinate
  alfabeticamente, quella salvata risulta selezionata; select con id,
  init select2, CSS e messaggi (it/en) presenti; file select2 esistenti
  in `public/vendor/crudbooster/assets/select2/`.

**Non verificato** (serve un browser): ricerca nel select (sidebar del
builder a griglia e modale del builder legacy), riempimento dell'altezza
dell'iframe nella cella, flusso completo aprire/salvare/eliminare record
nel widget, comportamento dei link generati da JS, scadenza sessione
dentro l'iframe, drag/selezione del widget nei due builder con
`pointer-events: none`. Suite di test non lanciata.

## Rischi e note

- **Cambia il comportamento visibile**: prima un click su paginazione/
  filtri/link portava fuori dalla dashboard, ora resta nel widget; il
  modulo ha una propria barra di scorrimento interna.
- Il server di produzione/staging non deve mandare `X-Frame-Options` o
  `frame-ancestors` restrittivi, altrimenti l'iframe non compare (nel
  repo non ce ne sono; i server non sono stati controllati): da
  verificare prima del deploy.
- Nelle dashboard legacy ad aree l'altezza è fissa (600px), non più
  "quanto il contenuto".
- Il titolo del modulo compare anche dentro l'iframe (insieme ai suoi
  pulsanti d'azione, necessari per lavorare): piccolo doppione con il
  titolo del widget.

## Rollback

Ripristinare `panelcustom.blade.php` (torna lo scraping) e le chiavi
lingua; le regole `pointer-events` nei due builder sono innocue anche
lasciate. Le modifiche lato server di 162 non dipendono da questa.
