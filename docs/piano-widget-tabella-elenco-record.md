# Piano: widget Tabella con configurazione guidata "Elenco record"

- **Data del piano**: 2026-09-29
- **Stato**: pianificato, NON iniziato (da riprendere)
- **Contesto**: dopo 154-157 (grafici linee/barre/aree con Query guidata e
  serie multiple) resta il widget Tabella (`table.blade.php`), che ha solo
  la SQL libera. Richiesta dell'utente: una tabella in cui si scelgono le
  colonne, quanti record per pagina ed eventuali filtri, rispettando ruoli
  e permessi. La configurazione e' la parte facile; il controllo permessi
  e' la parte critica.

## Decisioni gia' prese

1. **Solo tabelle `mg_*`** (moduli generati da interfaccia) nella prima
   versione. Niente `cms_users`, `groups`, `qlik_*`, tabelle senza modulo.
2. **Riusare la logica esistente** di visibilita' delle righe. Scelta
   implementativa: estrarre in un helper condiviso il blocco SQL gia'
   presente in `CBController::getModalData()` (intervento 070) invece di
   usare `ModuleHelper::can_view()` riga per riga in PHP - stessa regola,
   ma `LIMIT` e paginazione corretti. `can_view()` da solo non e'
   riusabile: `CRUDBooster::isRead()` legge il modulo *corrente*
   (`getModulePath()`), che nel widget e' `statistic_builder`.
3. **Chi non ha accesso**: il widget **sparisce del tutto** dalla
   dashboard (nessun messaggio).
4. **Colonne di sistema (`group`, `tenant`, `created_by`, ...) restano
   selezionabili** (decisione dell'utente, 2026-09-29). La denylist
   esistente per nomi tipo credenziale (`password`, `token`, `secret`,
   `api_key`, `hash`, `otp`, ...) resta invece sempre applicata.
5. **Righe con `created_by` NULL**: copiare il comportamento del popup
   datamodal (`whereNotIn` sui tenant admin -> le righe con `created_by`
   NULL risultano nascoste agli utenti standard), piu' restrittivo di
   `can_view()` (che le mostrerebbe). Da documentare come differenza nota.
   L'utente osserva che in teoria non dovrebbero esistere righe con
   `created_by` vuoto.

## Come funzionano oggi permessi e visibilita' (verificato nel codice)

- Privilegi per modulo: `cms_privileges_roles` (`id_cms_moduls`,
  `id_cms_privileges`, `is_read`, ...), in sessione come
  `admin_privileges_roles` con `path` del modulo.
- Modulo <-> tabella: `cms_moduls.table_name` / `path` / `controller`.
  Nel DB locale `mg_fatture` ha due righe modulo (id 20 e 22): regola
  scelta -> basta un modulo attivo e non cancellato con `is_read = 1` per
  il ruolo dell'utente.
- Visibilita' per riga (`ModuleHelper::can_view()`, usata da
  `CBController::getIndex()`): superadmin tutto; serve `is_read`; tenant
  admin -> righe con `tenant` uguale al suo; utente standard -> `group`
  tra i suoi gruppi E `tenant` uguale al suo, e niente righe create da
  tenant admin. Le colonne `created_by`/`group`/`tenant` esistono solo
  nelle tabelle `mg_*`.
- `isDashboardVisibleToCurrentUser()` guarda SOLO il menu della dashboard,
  mai i permessi del modulo dei dati: oggi un widget su una dashboard
  visibile mostrerebbe i dati anche a chi non puo' leggere quel modulo.
- Il registro dataset (`DashboardDatasetRegistry`) non fa nessun controllo
  di tenant/ruolo (oggi solo aggregati); TABLE_DENYLIST /
  COLUMN_DENYLIST_PATTERNS proteggono da credenziali, non da dati di altri
  tenant.
- `renderComponentPayload()` ha 3 punti d'uso: `renderDashboardShow()`,
  `getViewComponent()`, `getListComponentsGrid()`.
- `button_detail` / `global_privilege` di `can_view()` sono proprieta' del
  controller del modulo, non del DB: nel widget vengono ignorate
  (documentare).

## Fasi

### Fase 1 - Nucleo di sicurezza: `DatasetAccessScope`
Nuova classe in `app/Dashboards/`.
- `canRead($table)`: superadmin -> true; altrimenti prefisso `mg_` e
  almeno un modulo attivo non cancellato con `is_read = 1` per
  `CRUDBooster::myPrivilegeId()`; altrimenti false.
- `applyRowScope($query, $table)`: sposta qui il blocco di
  `getModalData()` (tenant, gruppi per non-tenant-admin, esclusione righe
  create da tenant admin, `deleted_at IS NULL`).
- `getModalData()` chiama l'helper e deve restare **identico**:
  verificare confrontando `toSql()` + risultati prima/dopo per ogni tipo
  di utente (behavior-preserving, e' codice in produzione).

### Fase 2 - `DashboardDatasetRegistry::executeRows()`
- Parametri: dataset, colonne, filtri, `order_by`, `order_dir`, limite.
- Solo dataset `mg_*` che sono un modulo. Colonne, `order_by` e filtri
  riverificati contro la whitelist a ogni chiamata (come `execute()`);
  `order_by` con colonna non ammessa -> eccezione.
- Ordine di applicazione: prima lo scoping (Fase 1), poi i filtri del
  widget in AND - i filtri non sostituiscono mai la sicurezza.
- Tetto fisso 500 righe; paginazione lato client (DataTables). "Record
  per pagina" = lunghezza di pagina iniziale, non paginazione server.

### Fase 3 - Configurazione del widget Tabella
- Nuova modalita' "Elenco record" nel popup "Sorgente dati": tabella
  (solo dataset ammessi), colonne (selezione multipla), ordina per +
  direzione, record per pagina (10/25/50/100), filtri (riuso dei filtri
  esistenti di `_query_builder_fields`).
- Endpoint `dataset-options`: esporre i dataset "elenco" con le colonne;
  `postDatasetPreview`: gestire il caso (l'anteprima gira come
  superadmin, quindi mostra tutte le righe - dirlo nel testo di "Prova").
- `table.blade.php`: intestazioni dalle etichette delle colonne,
  `pageLength` dalla config. Modalita' "Query guidata" a 2 colonne e SQL
  libera restano invariate.

### Fase 4 - Il widget sparisce se l'utente non ha accesso
- In `renderComponentPayload()`: widget Tabella in modalita' "Elenco
  record" e `!canRead(tabella)` -> payload vuoto; i 3 punti d'uso lo
  scartano. Nessuna config del widget nel payload per chi non ha accesso.
- La griglia lascia un buco nella posizione del widget (gli altri non si
  spostano): accettabile per la v1.

### Fase 5 - Test (scriverli, lanciarli solo su richiesta esplicita)
- Ruoli: superadmin, tenant admin tenant A, tenant admin tenant B,
  utente standard con gruppo, senza gruppo, senza `is_read`: verificare
  numero di righe e assenza di dati di altri tenant.
- Confronto con `can_view()` sulle stesse righe, incluse quelle con
  `created_by` NULL (differenza nota, decisione 5).
- Rifiuto di: colonne della denylist, tabelle non `mg_*`, `order_by` non
  valido, colonne inesistenti.

### Fase 6 - Traduzioni e documentazione
- Testi in EN e IT (`trans('crudbooster....')`, mai hardcoded).
- Tre documenti in `docs/refactoring/` (helper di sicurezza, registro
  `executeRows`, widget/UI), scritti secondo `_template.md`, con
  aggiornamento dell'indice del README.

## Ordine di lavoro concordato
Fasi 1 e 2 con i relativi test, poi **pausa per far rivedere il nucleo di
sicurezza** all'utente prima di costruire l'interfaccia (Fasi 3 e 4).

## Rischi
- Fase 1 tocca `getModalData()`, codice in produzione: il confronto delle
  query prima/dopo e' obbligatorio.
- Un errore nel filtro puo' esporre dati di un altro tenant: la v1 e'
  volutamente restrittiva (solo `mg_*`, tetto righe, test prima della UI).
- Il "buco" nella griglia quando un widget e' nascosto.
- Verifica manuale utile con lo scenario di `piano-testing-manuale.md`
  (due tenant, utenti con ruoli diversi).
