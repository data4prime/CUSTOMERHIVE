# 114 - Dashboard a griglia libera: fix, nessun modo di raggiungerla dall'interfaccia

- **Data**: 2026-09-28
- **Stato**: Completato
- **Area**: Statistic Builder / Dashboard
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/StatisticBuilderController.php`

## Contesto

Le fasi 0-5 ([108](108-dashboard-griglia-fase0-dati-dataset.md)-[113](113-dashboard-griglia-fase5-vista-pubblica.md))
implementavano tutta la logica della nuova griglia, ma **nessuna dashboard
reale poteva mai raggiungerla dall'interfaccia**: le dashboard esistenti
restano `legacy_areas` per decisione esplicita (non distruttiva), e le
dashboard nuove create dal form "Aggiungi Nuova Statistica" ricadevano
comunque sul default `legacy_areas` della colonna, perché nessun punto
del codice impostava mai `layout_mode = 'grid'`. L'endpoint di
conversione (`postConvertToGrid()`, poi `getConvertToGrid()`) esisteva
ma non era linkato da nessuna parte della UI. Segnalato dall'utente
dopo aver aperto l'interfaccia e visto solo quella vecchia.

## Situazione prima

- `hook_before_add()` impostava solo lo slug: ogni nuova dashboard
  nasceva `legacy_areas`.
- `postConvertToGrid()` era un endpoint POST raggiungibile solo via
  AJAX/curl, senza alcun link/bottone nella lista o nel builder.

## Situazione dopo

- `hook_before_add()` imposta anche `layout_mode = 'grid'`: ogni nuova
  dashboard creata da ora in poi apre direttamente il nuovo editor (le
  esistenti restano `legacy_areas`, invariato).
- `postConvertToGrid()` → `getConvertToGrid()` (GET, non POST: coerente
  con le altre azioni amministrative già GET di questo controller, es.
  `getDeleteComponent()` — CSRF è comunque disabilitato globalmente nel
  progetto, non è un indebolimento introdotto qui), ora reindirizza al
  builder invece di restituire JSON.
- Nuovo `addaction` "Griglia libera" in `cbInit()`: bottone nella lista
  Statistic Builder, accanto a "Builder", su ogni riga.

## Motivazione

Il piano prevedeva esplicitamente "la nuova griglia libera è l'opzione
per le dashboard nuove" — non era stato tradotto in codice. Aggiungere
il link è il minimo indispensabile per rendere il lavoro delle fasi
0-5 effettivamente utilizzabile.

## Test

Verificato in browser con dati usa-e-getta (creati e poi eliminati):
creata una dashboard nuova dal form "Aggiungi Nuova Statistica" →
apre direttamente il nuovo editor a griglia; creata una dashboard
`legacy_areas` via SQL, cliccato il nuovo bottone "Griglia libera" →
convertita e reindirizzata al nuovo editor correttamente.

**Un tentativo di aggiungere `'showIf' => "[layout_mode] != 'grid'"`
per nascondere il bottone sulle dashboard già `grid` ha causato un 500
("Undefined constant") nell'`eval()` interno di CRUDBooster** — la
sintassi esatta per condizionare un addaction su un campo non standard
non è risultata affidabile in questo fork; rimosso, il bottone resta
sempre visibile (cliccarlo su una dashboard già `grid` è un no-op
innocuo, vedi `getConvertToGrid()`).

## Rischi e note

- Il form "Aggiungi Nuova Statistica" chiede ancora un "Layout" legacy
  obbligatorio, che viene salvato ma **ignorato** per una dashboard che
  parte già in modalità `grid` — piccola incoerenza di UX lasciata
  com'è per non ampliare ulteriormente lo scope di questo fix.

## Rollback

`git diff` di questo commit per tornare a `postConvertToGrid()` (POST,
JSON) senza addaction né default `grid` sulle nuove dashboard.
