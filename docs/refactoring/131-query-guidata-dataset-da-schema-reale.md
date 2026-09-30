# 131 - Query guidata: dataset generati dallo schema reale del DB (non più solo 2 a mano)

- **Data**: 2026-09-28
- **Stato**: Completato e verificato in browser
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `app/Dashboards/DashboardDatasetRegistry.php`
  - `app/Http/Controllers/System/StatisticBuilderController.php` (`renderComponentPayload()`)
  - `resources/views/crudbooster/statistic_builder/components/smallbox.blade.php`

## Contesto

Richiesta dell'utente: configurare la query di un widget Indicatore KPI è
poco intuitivo per chi non conosce i nomi di tabelle/colonne. Discusse 3
alternative, scelta la "query builder visuale sullo schema reale" (vedi
conversazione): invece di scrivere SQL a mano, l'utente sceglie tabella →
metrica/aggregazione → raggruppamento → filtro da delle select.
L'infrastruttura UI per questo ("Query guidata") esisteva già dalla Fase 3
del piano dashboard a griglia ([111](111-dashboard-griglia-fase3-editor-frontend.md))
ma era stata tolta da tutti i widget in [124](124-dashboard-griglia-rifinitura-builder-e-vista-pubblica.md)
perché offriva solo 2 dataset scritti a mano, poco utili ("prima bisogna
pensarci bene" - nota dell'utente in quell'intervento).

## Situazione prima

`DashboardDatasetRegistry::all()` restituiva solo 2 dataset scritti a
mano (`admin_users` su `cms_users`, `system_activity` su `cms_logs`) —
nessuno dei due utile per il caso reale (Ordini di Vendita, Contratti,
Fatture, tutti moduli custom generati da Module Generator). Il pannello
"Query guidata"/toggle non era incluso in nessun widget (rimosso in 124),
quindi irraggiungibile dall'interfaccia nonostante il backend esistesse.

## Situazione dopo

- `DashboardDatasetRegistry::all()` ora unisce i 2 dataset curati con un
  dataset **generato automaticamente per ogni tabella reale del DB** non
  esclusa dalle regole di sicurezza (vedi sotto) — introspezione via
  `information_schema`, mai da input dell'utente finale. Per ogni tabella:
  metrica "Conteggio righe" sempre presente + somma/media/massimo/minimo
  per ogni colonna numerica + massimo/minimo per ogni colonna data;
  dimensioni/filtri = tutte le colonne rimaste (le colonne data offrono
  solo le varianti aggregate mese/giorno, mai il valore esatto). Le
  etichette tolgono i prefissi `mg_`/`cms_` per leggibilità (es. tabella
  `mg_ordini_vendita` → "Tabella: Ordini vendita").
- **Regole di sicurezza** (sostituiscono la whitelist di sole 2 tabelle):
  1. `TABLE_DENYLIST` esplicita: tabelle di sistema/sicurezza mai esposte
     (`migrations`, `password_resets`, `personal_access_tokens`, `license`,
     `cms_apikey`, le 4 tabelle `mfa_*`, `cms_settings` — contiene
     potenzialmente una password SMTP, vedi gotcha email in CLAUDE.md —
     `cms_email_queues`, `cms_email_templates`, `cms_apicustom`,
     `chat_ai_history`, `qlik_confs`, `chatai_confs`, `log`).
  2. Le tabelle `cms_*` sono escluse di default (ossatura interna
     CRUDBooster: moduli, menu, privilegi...), salvo una piccola
     allowlist esplicita (`cms_users`, `cms_logs`, `cms_notifications`).
  3. Qualunque colonna il cui nome contiene `password`/`token`/`secret`/
     `api_key`/`hash`/`otp`/`credential`/... è esclusa da qualunque
     tabella restante, anche solo come filtro/raggruppamento — il rischio
     non è "chi può costruire la query" (solo superadmin) ma "quali
     valori grezzi finiscono in un'etichetta di un grafico su una
     dashboard condivisa anche a ruoli meno privilegiati" (vedi
     [091](091-bug-noti-execute-api-logscontroller-visibilita-dashboard.md)).
  4. Solo tabelle `BASE TABLE` (mai `VIEW`): esclude automaticamente 2
     viste legacy trovate in locale (`users`, `attributes`, residuo di
     un'integrazione Qlik precedente).
  - `execute()` (invariato) riverifica comunque tabella/colonna/
    aggregazione contro il dataset risolto ad ogni chiamata, mai fidandosi
    di ciò che il form ha mandato.
- Reintrodotto il toggle "SQL libera / Query guidata" nel widget
  Indicatore KPI (`smallbox.blade.php`), tolto in 124: ora ha dataset
  utili da mostrare.
- **Bug corretto in `renderComponentPayload()`**: passando dalla modalità
  SQL alla modalità builder, se il campo `sql` conteneva ancora testo da
  prima (mai svuotato: resta nel form, solo nascosto), il ciclo generico
  sostituiva `[sql]` con il vecchio risultato SQL *prima* che il blocco
  dedicato alla modalità builder potesse sostituirlo con il risultato del
  dataset — trovando `[sql]` già consumato, non sostituiva nulla, e il
  widget restava fermo al vecchio valore SQL. Corretto saltando la
  sostituzione generica della chiave `sql` quando `mode === 'builder'`.

Verificato in browser (Docker locale, dashboard "test2", widget "Numero
utenti"): dataset "Tabella: Ordini vendita" mostra correttamente Contratti/
Fatture/Ordini vendita/Tenants/Groups tra le opzioni (e non tabelle di
sistema/sicurezza); anteprima "Conteggio righe" → 123 (verificato uguale a
`SELECT COUNT(*) FROM mg_ordini_vendita`); anteprima "Somma di Importo" →
154950 (verificato uguale a `SELECT SUM(importo) FROM mg_ordini_vendita`);
salvato e ricaricata la pagina → il widget mostra 154950 dal vero dataset,
non più il valore SQL precedente. Configurazione di test poi ripristinata
all'originale (SQL libera, `select count(*) from cms_users`).

## Motivazione

Vedi conversazione: un utente che configura un widget spesso non conosce i
nomi esatti di tabelle/colonne. Generare i dataset dallo schema reale
invece di scriverli a mano copre automaticamente ogni tabella di business
(inclusi i moduli custom per cliente, tutti generati con lo stesso schema
prevedibile da Module Generator) senza il lavoro manuale di catalogarle
una per una - il costo si sposta dallo scrivere ogni dataset al mantenere
poche regole di esclusione.

## Test

Vedi "Situazione dopo" - verifica end-to-end in browser con dati reali
(non solo lettura del codice): selezione dataset, anteprima con 2
aggregazioni diverse confrontate col valore vero via query diretta sul
DB, salvataggio, ricaricamento pagina, ripristino della configurazione di
test. Non eseguita la suite di test automatici (non richiesta
esplicitamente).

## Rischi e note

- **Tabelle "di plumbing" ancora visibili nell'elenco** (es. `group_tenants`,
  `menu_groups`, `items_allowed`, `qlikapps_groups`...): non sensibili (solo
  chiavi numeriche di collegamento), ma poco utili e un po' di rumore
  nella select. Non aggiunte alla denylist per non renderla enorme e
  fragile da mantenere — se danno fastidio in uso reale, si aggiungono
  alla `TABLE_DENYLIST` una alla volta.
- **`TABLE_DENYLIST`/`COLUMN_DENYLIST_PATTERNS` sono liste scritte oggi,
  da rivedere se in futuro compaiono nuove tabelle/colonne sensibili**
  (es. un nuovo modulo custom cliente con una colonna `pin`/`chiave` non
  ancora coperta dai pattern attuali) - stesso tipo di manutenzione
  incrementale già presente altrove nel progetto (es. denylist icone).
  Aggiunto anche il pattern-matching su viste (`VIEW` sempre escluse) come
  ulteriore rete di sicurezza automatica.
- Solo superadmin può costruire/salvare un widget (`isSuperadmin()` sugli
  endpoint `dataset-options`/`dataset-preview`, invariato) - non cambiato
  in questo intervento, la dashboard risultante può comunque essere
  condivisa con ruoli meno privilegiati.
- Il fix in `renderComponentPayload()` tocca il rendering di *tutti* i
  widget generici (`table`, `chartline_v2`, `chartbar_v2`, `chartarea_v2`,
  non solo `smallbox`), dato che la funzione è condivisa - comportamento
  cambiato solo per il caso `mode === 'builder'` con un vecchio valore
  `sql` residuo, prima silenziosamente rotto.

## Rollback

Ripristinare `DashboardDatasetRegistry::all()` alla sola `curated()` (già
isolata come metodo separato), rimuovere i due `@include` aggiunti in
`smallbox.blade.php`, e il salto della chiave `sql` in
`renderComponentPayload()` (torna comunque innocuo se la modalità
builder non è più raggiungibile da nessuna UI).
