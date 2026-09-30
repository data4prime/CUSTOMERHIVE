# 159 - `DashboardDatasetRegistry::executeRows()` (Fase 2 widget "Elenco record")

- **Data**: 2026-09-30
- **Stato**: Completato
- **Area**: Sicurezza / Dashboard
- **File/aree di codice coinvolte**:
  - `app/Dashboards/DashboardDatasetRegistry.php` (nuovo `executeRows()`, `ROWS_MAX_LIMIT`)
  - `app/Dashboards/DatasetAccessScope.php` (nuovo `hasModule()`)
  - piano: `docs/piano-widget-tabella-elenco-record.md`; nucleo di sicurezza: 158

## Contesto

Fase 2 del piano. Serve un punto unico che, dato un dataset, colonne,
filtri e ordinamento scelti nel widget, restituisca righe vere rispettando
ruoli e permessi. Il registro oggi offre solo `execute()`, che produce
aggregati `{label, value}` senza alcun controllo di tenant/ruolo.

## Situazione prima

`execute()` e' l'unico modo di leggere dati dal registro: whitelist di
metriche/dimensioni/filtri, ma nessuno scoping utente. Adatto ad aggregati,
non a mostrare righe grezze.

## Situazione dopo

`executeRows(string $datasetKey, array $params)` con `params`:
`columns[]`, `filters`, `order_by`, `order_dir`, `limit`. Restituisce
`['columns' => [nome => etichetta], 'rows' => [...]]`. Ordine dei controlli:

1. dataset esistente, tabella `mg_*` **e** con modulo attivo non cancellato
   (`DatasetAccessScope::hasModule()`), altrimenti `InvalidArgumentException`;
2. `DatasetAccessScope::canRead()`, altrimenti `AuthorizationException`;
3. colonne, `order_by`, `order_dir` verificati contro la whitelist del
   dataset (le colonne di `filters`, gia' senza colonne tipo credenziale);
4. query: `deleted_at IS NULL` -> `applyRowScope()` (scoping utente, 158) ->
   filtri del widget in AND (stesso `applyFilter()` di `execute()`, stessi
   operatori/validazioni) -> select delle sole colonne richieste -> ordine
   -> `LIMIT`.

Tetto fisso `ROWS_MAX_LIMIT = 500` (limite richiesto clampato tra 1 e 500);
senza `order_by` ordina per `id` desc se la tabella ha `id`. `execute()` e
il resto del registro non sono toccati.

## Motivazione

Scoping e whitelist in un unico posto, applicati prima dei filtri: un
filtro del widget non puo' mai allargare cio' che l'utente vede.

## Test

Verifica manuale con script temporaneo nel container Docker (nessuna suite
lanciata). Dati di prova inseriti dentro una transazione poi annullata con
rollback (0 righe residue verificate): 8 righe su `mg_fatture` tra tenant
9/10, gruppi diversi, create da tenant admin / utente standard / NULL, una
cancellata (`deleted_at`).

- superadmin: tutte le 7 righe attive; tenant admin t9: 5 (A-E); utente
  standard t9 g7: solo T-B; tenant admin t10: 2 (F, G); utente standard
  t10 g10: solo T-F.
- Standard t9 senza filtri: solo tenant 9. Filtro `tenant = 10` -> 0 righe
  (il filtro non allarga lo scope).
- Righe con `created_by` NULL: visibili al tenant admin, nascoste
  all'utente standard (decisione 5 del piano).
- Rifiutati con eccezione: colonna fuori whitelist, colonna inesistente,
  nessuna colonna, `order_by` non valido (anche con tentativo di
  injection), `order_dir` non valida, filtro su colonna non ammessa, dataset
  curato non `mg_*`, dataset sconosciuto; `AuthorizationException` per
  utente con `is_read` tolto (dentro la transazione).
- `limit` 99999 -> tetto 500 (313 righe reali), `limit` 3 -> 3.

Non verificato: colonne con nome tipo credenziale (nessuna nelle `mg_*`
locali; la regola e' quella di `allowedColumns()`, gia' usata da
`execute()`). Test automatici per ruolo: Fase 5.

## Rischi e note

- Nessun consumatore ancora: il codice non e' raggiungibile da UI/route
  finche' non arrivano le Fasi 3-4, quindi nessun cambio di comportamento
  visibile.
- `canRead()` di un utente non superadmin dipende dalla sessione
  (`admin_privileges`): va chiamato in una richiesta autenticata.
- L'anteprima del builder gira come superadmin e mostra tutte le righe
  (da dichiarare nella UI, Fase 3).

## Rollback

Rimuovere `executeRows()`, `ROWS_MAX_LIMIT` e i due `use` aggiunti in
`DashboardDatasetRegistry.php`, e `hasModule()` da `DatasetAccessScope.php`.
