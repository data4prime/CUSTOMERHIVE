# Piano — dashboard statistiche con griglia libera drag & resize

Obiettivo finale: sostituire l'attuale "Statistic Builder" (layout HTML
scritto a mano, un widget per area fissa, jQuery UI 1.11.4 + Morris.js/Raphael)
con un editor a griglia libera in stile Grafana/Retool — widget
trascinabili e ridimensionabili liberamente, grafici moderni, query dei
widget editabili sia in SQL libero (utenti esperti) sia tramite un query
builder guidato (dataset predefiniti). Riferimento visivo: mockup
artboard "Idea 1" e "Idea 1 — Editor layout (builder)" (Artifact privato,
non condiviso).

**Stato**: piano discusso e concordato (28/09/2026, dopo revisione dei
mockup), nessuna implementazione ancora iniziata.

## Stato attuale (riassunto — analisi completa nella chat che ha originato questo piano)

- `cms_statistics` (una riga = una dashboard) → FK a `dashboard_layouts`,
  il cui `code_layout` è **HTML scritto a mano in un campo TinyMCE**
  (`DashboardLayoutController.php`): `<div id='area1' class='col-sm-3
  connectedSortable'>`. Nessun concetto di griglia, solo colonne Bootstrap
  cablate nel testo.
- `cms_statistic_components` (una riga = un widget): `component_name`,
  `area_name`, `sorting`, `config` (JSON con la **query SQL letterale**
  del widget, eseguita via `DB::select()` in `getViewComponent()`).
  Nessuna colonna di posizione/dimensione: non può esisterne bisogno,
  perché ogni area accetta **un solo widget** (drop rifiutato via
  `sortable('cancel')` se un'area ne avrebbe più di uno) e la dimensione
  viene solo dalla classe `col-sm-N` scritta nel layout condiviso.
- Stack: jQuery UI 1.11.4 (drag, niente resize), Morris.js 0.5.1 +
  Raphael 2.1.0 per i grafici (abbandonate dal ~2014), tutto da CDN.
  **Nessuna build JS** nel progetto (niente `package.json`/webpack/vite):
  tutto è `<script>` CDN o file vendorizzati sotto
  `public/vendor/crudbooster/assets/`.
- Permessi: builder e scrittura widget già ristretti a superadmin
  (`docs/refactoring/079-statistic-builder-privilegi-componenti.md`,
  `091`); la query SQL per-widget resta comunque eseguita letteralmente.

## Decisioni prese

| Aspetto | Decisione |
|---|---|
| Dashboard legacy esistenti | **serve un percorso di conversione**, non restano intoccate per sempre |
| Modalità di conversione | **automatica euristica**: alla prima conversione si calcolano x/y/w/h di partenza dai `col-sm-N`/aree attuali; l'admin aggiusta dopo se non va bene |
| Editor query dati | **entrambe le modalità**: SQL libero in sidebar (come oggi, solo spostato dalla modale) per gli utenti esperti, **più** un query builder guidato (dataset predefiniti) per gli altri |
| Sorgenti dati del builder guidato | **dataset curati a livello di codice** (registry), non introspezione dinamica dello schema DB |
| Permessi sul nuovo editor | **resta tutto superadmin-only**, stesso perimetro di oggi — nessun nuovo ruolo intermedio per ora |
| Salvataggio | **autosave immediato** ad ogni drag/resize/modifica, stesso comportamento di oggi (niente stato di bozza con "Salva"/"Annulla" espliciti, nonostante il mockup lo suggerisse) |
| Editing widget | **inline nella sidebar destra**, mai in una modale — sostituisce il modale attuale (`#modal-statistic`) solo per le dashboard in modalità griglia |
| Stack frontend | **solo script vendorizzati/CDN**, coerente con oggi — niente build step npm/vite introdotto per questa feature |
| Grafici nuovi widget | libreria moderna (ApexCharts o Chart.js 4, da confermare in Fase 4) al posto di Morris/Raphael, **solo per i nuovi widget**, mai in sostituzione di quelli legacy |

## Perché queste scelte

- **Conversione automatica invece di intoccabile o manuale da zero**: le
  dashboard dei clienti sono contenuto reale (query, nomi, layout scelti
  da un umano) — ricrearle da zero a mano sarebbe lavoro perso e un
  disincentivo a migrare. L'euristica parte comunque da un dato semplice
  da mappare 1:1 (`col-sm-N` è già una griglia a 12 colonne).
- **Doppia modalità SQL + builder guidato**: la SQL libera esiste già oggi
  ed è usata da chi la sa scrivere — toglierla sarebbe una regressione
  per chi già la usa. Il builder guidato è additivo, non sostitutivo, e
  in più riduce (per chi lo usa) il rischio già segnalato di SQL
  arbitraria nei widget.
- **Dataset curati a codice, non introspezione DB**: coerente con come il
  progetto gestisce già le personalizzazioni per cliente (moduli/
  controller custom scritti e revisionati da noi) — un dataset nuovo è
  una modifica di codice deliberata, non un'esposizione automatica di
  tabelle che potrebbero contenere colonne non pensate per un report.
- **Tutto superadmin-only per ora**: stesso perimetro già esistente e già
  irrobustito (079/091) — allargarlo è un intervento di permessi a sé,
  da valutare solo se emerge un bisogno reale, non preventivamente.
- **Autosave invece di bozza+salva**: comportamento visibile già
  consolidato (l'utente sa già che ogni drag è "salvato subito"); un
  editor con bozza esplicita cambierebbe un'aspettativa consolidata senza
  un motivo che la giustifichi.
- **Niente build JS**: introdurre npm/vite tocca build/deploy/Docker, non
  solo la UI della dashboard — è un intervento infrastrutturale a sé,
  fuori scope qui. gridstack.js (o equivalente) si vendorizza come
  jQuery UI/Morris oggi.

## Modello dati (nuove colonne, additive)

- `cms_statistics.layout_mode` — stringa, default `'legacy_areas'`,
  valori `legacy_areas` \| `grid`. Tutte le righe esistenti restano
  `legacy_areas` dopo la migration: zero cambio di comportamento finché
  nessuno converte esplicitamente una dashboard.
- `cms_statistic_components.pos_x` / `pos_y` / `width` / `height` —
  interi nullable, unità di griglia (12 colonne, come oggi Bootstrap).
  `area_name`/`sorting` **non vengono rimossi**: restano per le dashboard
  ancora `legacy_areas` e come riferimento per la conversione.
- `cms_statistic_components.config` (JSON, colonna già esistente) guadagna
  una chiave `mode` (`sql` \| `builder`): con `sql` il comportamento è
  identico a oggi (`config.sql` eseguito letteralmente, solo spostato di
  UI); con `builder` la config contiene parametri strutturati (dataset,
  metrica, aggregazione, filtri, group by) risolti a runtime contro il
  registry — **mai** SQL testuale generato e salvato.
- Nuovo registry dataset (`config/dashboard_datasets.php` o classi sotto
  `App\Dashboards\Datasets\`): per ciascun dataset, query di base
  (Eloquent/query builder, mai stringa concatenata), metriche/filtri/
  group-by consentiti con etichette leggibili. Estendibile per cliente
  con lo stesso pattern già usato per moduli/controller custom.

## Fasi

### Fase 0 — Modello dati e registry dataset

- [ ] Migration additiva: `layout_mode` su `cms_statistics`, `pos_x`/
      `pos_y`/`width`/`height` su `cms_statistic_components`
- [ ] Registry dataset scheletro con 1-2 dataset reali (es. "Fatturato
      mensile", "Ticket aperti") per validare la forma prima di
      generalizzare ad altri
- [ ] **Verifica**: nessuna dashboard esistente cambia comportamento
      (tutte le righe restano `legacy_areas`, nessuna vista già in
      produzione viene toccata dalla migration)

### Fase 1 — Conversione automatica legacy → griglia

- [ ] Servizio di conversione: riusa il parsing HTML già presente in
      `DashboardLayoutController` (`hasClass`/`aggiungiIdAElemTd`) per
      leggere la larghezza `col-sm-N` di ciascuna area del layout
      assegnato (o della griglia di default a 9 aree)
- [ ] Per ogni widget della dashboard (ordinati per area poi `sorting`),
      calcola `pos_x`/`pos_y`/`width`/`height` con un bin-packing semplice
      a 12 colonne (larghezza ereditata dall'area di provenienza, altezza
      di default per tipo di widget, a capo automatico), poi imposta
      `layout_mode = 'grid'`
- [ ] Trigger: azione esplicita ("Passa alla nuova griglia") nella pagina
      builder di una dashboard `legacy_areas`, mai automatica in
      background — conferma testuale obbligatoria, operazione loggata
      (`CRUDBooster::insertLog`), non distruttiva (`area_name`/`sorting`/
      `dashboard_layouts` restano intatti)
- [ ] **Verifica**: conversione manuale su 2-3 dashboard reali del DB dev
      con layout diversi (griglia di default e almeno un layout
      personalizzato) prima di considerarla affidabile

### Fase 2 — Backend per l'editor a griglia

- [ ] Estendere `StatisticBuilderController` in modo additivo (i metodi
      legacy restano intatti e usati solo dalla vista legacy):
      `getBuilder` rende la nuova vista solo se `layout_mode = grid`;
      nuova `getListComponentsGrid` (fetch bulk con HTML widget
      pre-renderizzato in un'unica chiamata, al posto degli N+1 round-trip
      attuali); `postUpdateComponentPosition` (autosave su x/y/w/h,
      stesso pattern di oggi); `postAddComponent` esteso per accettare
      x/y/w/h iniziali dal punto di drop
- [ ] `getDatasetOptions`/`getDatasetPreview` per il query builder guidato
      (solo superadmin, sola lettura)
- [ ] `postSaveComponent` esteso per accettare `config.mode = 'builder'`
      accanto a `'sql'`, con validazione di dataset/metrica/filtri contro
      il registry (mai eseguire combinazioni non riconosciute)
- [ ] **Verifica**: gli endpoint legacy (`postUpdateAreaComponent` ecc.)
      restano chiamati solo dalla vista legacy, nessuna sovrapposizione
      con i nuovi

### Fase 3 — Editor a griglia, frontend (il mockup)

- [ ] Vendorizzare gridstack.js (statico o CDN, stesso pattern di jQuery
      UI/Morris oggi) sotto `public/vendor/crudbooster/assets/`
- [ ] Nuova vista `builder_grid.blade.php`: palette widget a sinistra
      (drag esterno verso gridstack), griglia centrale con drag/resize
      nativi, autosave a ogni evento `change` (debounced)
- [ ] Sidebar destra sempre inline, mai modale: stesso partial di oggi
      per tipo di widget, con un toggle in cima ("Query SQL" / "Query
      guidata") per i tipi che interrogano dati — SQL libera è la
      textarea di oggi spostata qui, guidata è select dataset/metrica/
      filtri popolate da `getDatasetOptions` con anteprima live via
      `getDatasetPreview` (senza salvare)
- [ ] Stepper larghezza/altezza in sidebar sincronizzati
      bidirezionalmente con gridstack (drag aggiorna gli stepper e
      viceversa)
- [ ] **Verifica manuale in dev**: aggiungere/spostare/ridimensionare
      widget, editare in entrambe le modalità SQL e guidata, refresh
      pagina e controllo persistenza

### Fase 4 — Nuovi widget grafico

- [ ] Nuovi `component_name` (es. `chartline_v2`/`chartbar_v2`/
      `chartarea_v2`) con partial dedicati su ApexCharts o Chart.js 4 (da
      confermare), raggiungibili solo dalla palette della nuova griglia
- [ ] I partial legacy (Morris/Raphael) restano invariati e usati solo
      dalle dashboard ancora `legacy_areas`
- [ ] **Verifica**: nessuna dashboard esistente referenzia i nuovi
      `component_name` (sono nuovi, mai scritti su dashboard esistenti)

### Fase 5 — Vista pubblica in modalità griglia

- [ ] `getShow`/`getDashboard`: se `layout_mode = grid`, renderizzano un
      template di sola visualizzazione (stessa griglia CSS, niente
      drag/resize/palette/sidebar), riusando l'endpoint bulk della Fase 2
      per evitare gli N+1 round-trip presenti oggi anche sulle dashboard
      legacy
- [ ] **Verifica**: tempo di caricamento con più widget confrontato
      prima/dopo; permessi (`isDashboardVisibleToCurrentUser`) invariati

## Documentazione e regole di rollout

- Ogni fase, una volta implementata, va registrata in
  `docs/refactoring/` con il prossimo numero libero (non in questo
  piano, che resta la visione d'insieme).
- Nessun test automatico lanciato senza richiesta esplicita, nessun
  commit/push senza richiesta esplicita (regole ferme del repo, vedi
  `CLAUDE.md`).
- Rollout per singolo cliente resta manuale/asincrono come da processo
  attuale: le dashboard di un cliente restano `legacy_areas` finché non
  viene aggiornato alla versione con questa feature e qualcuno converte
  esplicitamente una dashboard.
