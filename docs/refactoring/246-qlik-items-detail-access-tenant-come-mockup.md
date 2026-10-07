# 246 - Item Qlik: dettaglio, gruppi e tenant autorizzati come il mockup

- **Data**: 2026-10-07
- **Stato**: Completato (verificato a vista nel browser locale)
- **Area**: Frontend / Qlik
- **File/aree di codice coinvolte**:
  - `resources/views/qlik_items/form.blade.php`, `form_detail.blade.php`
  - `resources/views/qlik_items/access.blade.php`, `tenant.blade.php`, nuovo `_relation.blade.php`
  - `app/Http/Controllers/System/AdminQlikItemsController.php` (`access`, `tenant`)
  - `resources/lang/{en,it}/crudbooster.php`

## Contesto

Seguito di 244/245. Pagine `admin/qlik_items/detail/{id}`, `access/{id}`, `tenant/{id}`
ancora con lo stile vecchio rispetto al mockup "Confronto UI CustomerHive".

## Situazione prima

- **detail**: le righe "App Qlik" e "ID foglio" stavano in una tabella a parte
  (`table-striped`, etichette in grassetto), diversa dalle righe dei campi sotto.
- **access / tenant**: card con form inline (campo "Name" con datamodal + "Description" in
  sola lettura), pulsanti "Indietro" e "Aggiungi membro" (testo sbagliato), tabella a
  strisce con cestino rosso diretto. Titoli in inglese scritti nel controller
  ("Authorize Group", "Authorize Tenant") e nella vista ("Authorized Tenants").

## Situazione dopo

- **detail**: le due righe sono le prime della tabella `#table-detail` (stesso stile
  degli altri campi); il badge "mancante" usa `ch-pill ch-pill-warn`.
- **access / tenant**: stessa struttura delle pagine relazione di tenant/gruppi
  (`tenants/group.blade.php`): testata con briciole "Qlik Items / titolo item", titolo con
  conteggio, pulsante "Aggiungi gruppo/tenant" che apre un selettore in modale con ricerca,
  tabella `rel-table`, rimozione con conferma sulla riga. Partial condiviso
  `qlik_items/_relation.blade.php`.
- Il controller passa anche `available_groups` / `available_tenants` (non ancora
  autorizzati) e seleziona esplicitamente `id, name, description` (prima il join
  restituiva tutte le colonne). Titoli pagina da `trans()`.
- Nuove chiavi en+it: `qlik_item_access_title`, `qlik_item_tenant_title`,
  `qlik_item_add_group_title`, `qlik_item_add_tenant_title`, `adm_no_tenants_available`.
- Invariati: endpoint (`{id}/auth`, `{id}/add_tenant`, `{id}/deauth/{gid}`,
  `{id}/remove_tenant/{tid}`), campo POST `name`, `return_url`/`ref_mainpath`, permessi
  (solo superadmin). Il blocco `forms` del controller resta (non più usato dalla vista).

## Motivazione

Coerenza con lo standard UI e col mockup; il testo "Aggiungi membro" su pagine di
gruppi/tenant era un residuo; riuso dei componenti già presenti (`groups._page_head`,
`groups._remove`, `rel-table`).

## Test

A vista su item 149: detail con le righe allineate; access e tenant con testata, conteggio,
tabella e selettore (non elenca il gruppo già autorizzato). `php -l` su controller e file
lingua. Non verificati: aggiunta e rimozione effettive (click non eseguiti per non scrivere
dati), messaggio "nessun disponibile", item senza app Qlik, tema scuro.

## Rischi e note

L'avviso "Select an element to add..." (`/alert/1`) non si può più produrre dall'UI (il
selettore sceglie sempre una voce); la rotta resta. Il titolo H1 della pagina è il titolo
"Aggiungi un gruppo/tenant a questo elemento", come già nelle pagine relazione dei tenant.

## Rollback

Ripristinare i file elencati dalla versione precedente (git) e rimuovere le chiavi lingua nuove.
