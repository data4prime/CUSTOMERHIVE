# 202 - Module generator: eliminazione modulo, pulizia dei residui

- **Data**: 2026-10-02
- **Stato**: Completato
- **Area**: Backend / Module generator
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/ModulsController.php` (`getDelete()`,
    `hook_before_delete()`, nuovi helper privati)

## Contesto

Analisi di `admin/module_generator/delete/{id}` per capire se, eliminando un
modulo e ricreandolo con lo stesso nome, restano residui o si creano problemi.
Ricreare funziona (nome unico controllato ignorando i soft-deleted, nuovo id =
`max(id)+1` calcolato anche sulle righe soft-deleted, controller rigenerato,
tabella `mg_*` droppata), ma l'eliminazione lasciava residui e aveva alcuni
difetti.

## Situazione prima

- Soft delete di `cms_moduls` (colonna `deleted_at`, ereditato dal `getDelete`
  generico di CRUDBooster; il modulo non e' comunque ripristinabile perche'
  controller, tabella e menu vengono eliminati). Utile solo perche' l'id non
  viene riusato.
- Residui non puliti: `cms_privileges_roles`, `module_tenants`, pivot dei menu
  (`cms_menus_privileges`, `menu_tenants`, `menu_groups`), sottomenu.
- Menu cancellati con `path LIKE '%<controller>%'`: per sottostringa, quindi
  eliminando `AdminOrdini` si cancellavano anche i menu di
  `AdminOrdiniArchivio`; i menu di tipo "Module" (`<path>?m=<id>`) non venivano
  toccati e restavano appesi a un modulo eliminato (es. in locale i menu 7 e 8).
- Ordine: drop tabella, poi menu/controller, poi soft delete, senza
  transazione: un errore a meta' lasciava la tabella persa e il modulo ancora
  presente.
- `$alert` usata con `.=` senza essere inizializzata nel caso tabella `mg_`
  con join da un altro modulo: in PHP 8 diventa un'eccezione (500).
- Dopo l'eliminazione la sessione dell'utente non veniva aggiornata.
- `hook_before_delete()` (richiamato anche dall'azione di massa di
  `CBController::postActionSelected()` con un array di id) assumeva un id
  singolo.

## Situazione dopo

- Soft delete **mantenuto** (decisione presa: nessun cambio di comportamento
  visibile sulle righe storiche).
- Nuovo `cleanupModuleRecords()`: in transazione, insieme alla soft delete,
  elimina menu del modulo, pivot (`cms_menus_privileges`, `menu_tenants`,
  `menu_groups`), `cms_privileges_roles` e `module_tenants`; i sottomenu
  passano al livello principale (`parent_id = 0`).
- Nuovo `moduleMenuIds()`: match esatto sul nome del controller seguito da
  `Get`/`Post`/`Put`/`Delete`/`@` (o fine stringa), piu' le voci di tipo
  "Module" con `path = <path modulo>?m=<id>`.
- Ordine: transazione su DB -> drop tabella (se consentito) -> unlink del
  controller (`basename()`) -> refresh della sessione -> `hook_after_delete`.
  Il DDL fa commit implicito in MySQL, per questo il drop sta fuori dalla
  transazione, dopo.
- `$alert = ''` inizializzata.
- `hook_before_delete()` accetta id singolo o array.
- **Comportamento visibile che cambia**: i sottomenu di un menu eliminato
  diventano voci di primo livello; i menu "Module" del modulo eliminato
  spariscono (prima restavano orfani).

## Motivazione

Eliminare e ricreare un modulo deve lasciare il DB pulito, senza che permessi
o tenant del vecchio modulo possano ricomparire e senza cancellare menu di
altri moduli. Alternativa scartata: passare a hard delete (cambierebbe i dati
storici gia' in produzione; eventualmente intervento separato con migrazione).

## Test

- `php -l` su `ModulsController.php`: ok.
- Verifica in sola lettura su dati locali dello script di match dei menu:
  `AdminAziendeController` -> menu 16; `AdminContrattiController` e
  `AdminFattureController` -> nessuno (i menu 7 e 8 sono gia' orfani, `?m=`
  con id non piu' esistenti); un controller `AdminAzienda` (prefisso) non
  matcha `AdminAziendeControllerGetIndex`.
- Test automatici **non eseguiti** (su richiesta esplicita soltanto). Il test
  esistente `test_getdelete_cancella_un_modulo_non_protetto` copre il percorso
  base; non esistono test per i residui ne' per il caso prefisso.
- Non verificato: eliminazione reale dal browser, azione di massa sui moduli.

## Rischi e note

- Il controllo dei join (scansione con `eval()` dei controller `mg_*`) non e'
  stato toccato: continua a non vedere controller custom dei clienti, widget,
  API, Qlik, foreign key.
- I menu orfani gia' presenti (es. 7 e 8 in locale) non vengono ripuliti
  retroattivamente; servirebbe una pulizia una tantum a parte.
- Se il drop fallisce dopo il commit, il modulo e' gia' eliminato ma la tabella
  resta: lato dati e' la direzione sicura.

## Rollback

Ripristinare `ModulsController.php` alla versione precedente (nessuna
migrazione, nessun cambio di schema).
