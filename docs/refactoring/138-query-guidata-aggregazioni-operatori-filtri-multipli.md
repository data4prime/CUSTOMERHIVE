# 138 - Query guidata: aggregazioni extra, operatori di filtro e filtri multipli

- **Data**: 2026-09-29
- **Stato**: Completato (verificato con uno script PHP isolato contro il DB reale, non in browser su richiesta esplicita dell'utente)
- **Area**: Statistic Builder / Backend + Frontend
- **File/aree di codice coinvolte**:
  - `app/Dashboards/DashboardDatasetRegistry.php`
  - `resources/views/crudbooster/statistic_builder/components/_query_builder_fields.blade.php`

## Contesto

Richiesta esplicita dell'utente, discussa e confermata prima di
implementare: estendere la "Query guidata" del widget Indicatore KPI
oltre al solo "Conteggio righe" (tolto tutto il resto in 133 "finche' non
fosse servito altro" - ora serve, per i KPI su importi/date discussi
insieme all'utente sui dati di test di contratti/fatture). Quattro punti
concordati:
1. Piu' aggregazioni (Somma/Media/Minimo/Massimo) sulle colonne numeriche
2. Operatori di filtro oltre alla sola uguaglianza, adattivi al tipo
   della colonna (numerica/testo/data)
3. Filtro data relativo ("ultimi N giorni", per ora senza preset fissi
   ne' range esplicito - scelta dell'utente tra le opzioni proposte)
4. Filtri multipli in AND, senza limite di quantita' (scelta dell'utente)

Prima di implementare, domande di chiarimento fatte via AskUserQuestion
(risposte riportate qui perche' guidano scelte non ovvie dal codice):
- Retrocompatibilita' con i filtri gia' salvati nel vecchio formato
  (`{colonna: valore}`, solo uguaglianza) → **mantenuta**, nessuna
  migrazione dei dati richiesta.
- Operatori adattivi al tipo di colonna (non la stessa lista per tutte)
  → **si'**.
- Filtro data → **solo "ultimi N giorni"**, niente preset fissi
  (oggi/settimana/mese) ne' range esplicito.
- Numero di filtri combinabili → **illimitati** (pulsante "Aggiungi
  filtro" libero, non un tetto fisso).

## Situazione prima

`DashboardDatasetRegistry::buildMetrics()` generava solo la metrica
`count` (conteggio righe sulla PK); `buildFilters()` restituiva una
mappa `colonna => etichetta` (solo stringa, nessuna informazione sul
tipo); il pannello (`_query_builder_fields.blade.php`) offriva un solo
filtro (select colonna + input valore, sempre uguaglianza implicita -
vedi anche 135, che ne aveva sistemato il salvataggio) senza alcun
operatore.

## Situazione dopo

**Backend** (`DashboardDatasetRegistry.php`):
- `NUMERIC_TYPES` (tipi MySQL trattati come numerici, incluso `tinyint`
  per i flag 0/1 come `pagata`) e `columnTypeCategory()` (numeric/date/
  text), usata sia per le metriche sia per gli operatori.
- `buildMetrics()` genera ora, oltre a `count`, `{sum,avg,min,max}:<colonna>`
  per ogni colonna numerica del dataset (es. `sum:importo` → "Somma
  Importo"). Solo per i dataset auto-generati dalle tabelle reali - i due
  dataset curati a mano (`admin_users`, `system_activity`) restano
  invariati (pensati per un controllo fine, non generico).
- `buildFilters()` restituisce ora `{label, type}` per colonna invece
  della sola etichetta; `optionsForFrontend()` la propaga al frontend.
- `OPERATORS_BY_TYPE`: whitelist server-side per categoria (numeric: `=
  != > < >= <=`; text: `= != contains`; date: `= != last_days`) -
  `execute()` la riverifica sempre, mai fidarsi della UI.
- `normalizeFilters()` (pubblico): accetta sia il formato nuovo (lista
  di `{column, operator, value}`, un filtro per riga del pannello) sia
  quello vecchio (mappa `colonna => valore`, sempre uguaglianza) -
  riusato anche dal blade per precompilare le righe con i filtri gia'
  salvati, un solo punto che decide come interpretarli.
- `applyFilter()`: applica un filtro validato, con gestione dedicata per
  `contains` (LIKE con `%`/`_`/`\` del valore letterale escapati via
  `addcslashes()`, altrimenti un valore utente come "50%" verrebbe
  interpretato come wildcard) e `last_days` (confronto con
  `DATE_SUB(CURDATE(), INTERVAL N DAY)`, N sempre castato a intero
  positivo prima di finire in una `DB::raw()` - mai un valore non
  validato interpolato senza binding).
- Filtri multipli: `execute()` itera la lista normalizzata applicandoli
  tutti in AND (gia' cosi' prima per il singolo filtro, ora generalizzato
  a N).

**Frontend** (`_query_builder_fields.blade.php`):
- La sezione "Filtro" diventa "Filtri", con righe multiple costruite via
  JS (`addFilterRow()`/`reindexFilterRows()`), ciascuna Colonna +
  Operatore (ripopolato in base al tipo della colonna scelta) + Valore,
  piu' un pulsante "Rimuovi" per riga e "+ Aggiungi filtro" per
  aggiungerne altre (nessun limite).
- `name=` di ogni campo assegnato dinamicamente come
  `config[filters][N][column|operator|value]` (N = posizione della riga,
  riassegnata ad ogni aggiunta/rimozione cosi' l'array resta sempre una
  lista sequenziale lato server - necessario perche' `normalizeFilters()`
  distingue i due formati anche in base a questo).
- Cambiare colonna in una riga ripopola i suoi operatori disponibili;
  l'input valore diventa `type="number"` per colonne numeriche e per
  l'operatore "ultimi N giorni" (solo aiuto visivo, la validazione vera
  resta lato server).
- Al primo caricamento le righe si ricostruiscono dai filtri gia' salvati
  (via `DashboardDatasetRegistry::normalizeFilters()`, chiamato anche dal
  blade); a un cambio di dataset scelto dall'utente si riparte da zero
  righe (le colonne del dataset precedente potrebbero non esistere nel
  nuovo).
- Il pulsante "Prova" (anteprima) raccoglie tutte le righe e le manda
  come lista di `{column,operator,value}` allo stesso endpoint
  `dataset-preview` di prima, che gia' passa `filters` cosi' com'e' a
  `execute()` - nessuna modifica al controller necessaria.

## Motivazione

Sblocca direttamente i KPI su importi/quantita' e periodi discussi con
l'utente (fatturato totale, incassato, fatture recenti, contratti con
importo sopra soglia...) senza dover scrivere SQL libera, mantenendo lo
stesso livello di whitelist/validazione server-side gia' presente prima
(niente si fida di cio' che la UI mostra).

## Test

Non verificato in browser (regola esplicita dell'utente, vedi
`feedback_no_autonomous_browser_testing`). Verificato con uno script PHP
isolato (bootstrap Laravel completo, eseguito e poi rimosso, non la
suite di test automatica) contro il DB di sviluppo reale:
- `normalizeFilters()` sia sul formato vecchio (`{"pagata":"0"}` →
  `[{column:pagata,operator:=,value:0}]`) sia sul nuovo;
- `optionsForFrontend()` per `mg_fatture`: metriche `sum/avg/min/max`
  generate per ogni colonna numerica, filtri con `type` corretto -
  scoperto (non introdotto) che `mg_fatture.data_fattura` e' in realta'
  `varchar` nello schema reale (nonostante il form dica `type=>'date'`),
  quindi classificata `text`, non `date` - quirk preesistente, non
  toccato in questo intervento;
- `execute()` con due filtri in AND (`importo > 500` e `pagata = 1`,
  metrica `sum:importo`) → **10.070**, verificato a mano sui dati di test
  (coincide);
- `execute()` con operatore `contains` su colonna testo (`nome` di
  `mg_contratti` contiene "Contratto") → **10** (tutti i contratti di
  test, corretto);
- `execute()` con operatore `last_days` su una vera colonna data
  (`created_at` di `mg_fatture`, ultimi 365 giorni) → **15** (tutte le
  fatture di test, corretto);
- operatore non consentito per il tipo di colonna (`contains` su
  `importo`, numerica) → `InvalidArgumentException` correttamente
  sollevata (whitelist server-side verificata).

`php -l` su entrambi i file modificati. Da confermare visivamente
dall'utente: aprire il pannello "Sorgente dati" di un widget KPI in
modalita' Query guidata, aggiungere piu' filtri con operatori diversi,
salvare, ricaricare e verificare che restino precompilati correttamente.

## Rischi e note

- Le metriche extra vengono generate per **ogni** colonna numerica del
  dataset, incluse le chiavi esterne/tecniche (`id`, `tenant`, `group`,
  `created_by`...) - non esclusa apposta: non era stato chiesto di
  filtrarle, e gia' oggi quelle stesse colonne compaiono come filtri
  disponibili. Un ripulimento della lista (es. nascondere `sum`/`avg` su
  colonne che sembrano id/chiave esterna) e' rimandabile a un intervento
  dedicato se in pratica risulta rumoroso nella select "Metrica".
- Il filtro data relativo resta intenzionalmente limitato a "ultimi N
  giorni" (scelta esplicita dell'utente): niente preset fissi (oggi/
  mese/mese scorso) ne' range esplicito per ora.
- Formato di salvataggio cambiato (lista invece di mappa) solo per i
  *nuovi* salvataggi; i widget configurati prima di questo intervento
  restano leggibili as-is grazie a `normalizeFilters()`.

## Rollback

`git diff` di `DashboardDatasetRegistry.php` e
`_query_builder_fields.blade.php` per tornare al solo "Conteggio righe"
con un filtro singolo a uguaglianza.
