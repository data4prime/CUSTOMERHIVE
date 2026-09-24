# 091 - Tre bug noti chiusi in un giro: `execute_api()`, `LogsController`, visibilità dashboard

- **Data**: 2026-09-24
- **Stato**: Completato
- **Area**: Bug fix / Sicurezza
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/ApiController.php` (`execute_api()`)
  - `app/Http/Controllers/System/LogsController.php`
  - `app/Http/Controllers/System/StatisticBuilderController.php` (`getShow()`, `getListComponent()`, `getViewComponent()`)
  - `tests/Feature/StatisticBuilderCrudTest.php`

Tre voci di backlog riprese insieme su richiesta esplicita dell'utente
("risolvi 5, 3 implementa il controllo di visibilità, 2 accettiamo così
intanto poi lo disattiveremo del tutto..., 1 verifica e sistema pure").

## 1) `execute_api()`: `Undefined variable $debug_mode_message`

### Situazione prima

Il bug segnalato ieri (086/README backlog, priorità alta) si riproduceva
su **qualunque** chiamata `api/`/`api2` in locale. Causa reale, non
quella ipotizzata ieri: `$debug_mode_message = 'You are in debug mode !';`
veniva assegnata alla riga 97 di `execute_api()`, **dopo** il controllo
"permalink esistente in `cms_apicustom`?" (righe 86-90) — un permalink
senza riga corrispondente fa `goto show;` **prima** di quell'assegnazione,
saltandola. Il blocco `show:` (raggiunto da quel goto e da altri 7 punti
del metodo, tutti dopo la riga 97) legge sempre
`$debug_mode_message` quando il setting `api_debug_mode = 'true'`.

In locale il problema era doppiamente mascherato: `cms_apicustom` è
**vuota** (0 righe) in questo ambiente di sviluppo (i 14 controller
generati su disco non hanno le righe di configurazione corrispondenti —
probabile disallineamento del dump locale, non verificato se sia così
anche altrove), quindi *ogni* permalink prendeva il ramo "non trovato";
e il setting `api_debug_mode` è impostato a `'true'` in questo ambiente.
Su un'installazione con `cms_apicustom` popolata correttamente e
`api_debug_mode` diverso da `'true'`, il bug non si manifesta quasi mai —
ma un permalink cancellato/rinominato con `api_debug_mode='true'` **lo
riproduce ovunque**, non solo in locale.

### Situazione dopo

Spostata l'assegnazione di `$debug_mode_message` prima del controllo
`!$row_api` (quindi prima di qualunque `goto show;` nel metodo). Nessun
altro cambiamento: la stringa non dipende da `$row_api`, spostarla prima
è sicuro per costruzione.

### Test

- `php -l`.
- `curl` su un permalink inesistente con `api_debug_mode='true'` (stato
  reale di questo ambiente): prima 500, ora 200 con il messaggio
  d'errore già previsto (`"Sorry this API endpoint is no longer
  available..."`).
- Verificato con un vero permalink configurato al volo
  (`cms_apicustom` seminata e poi ripulita) che il percorso normale
  (`aksi=list`) non è stato toccato dallo spostamento.

### Rischi e note

- **Trovato un secondo bug, distinto, verificando con un permalink
  reale**: i controller generati già presenti in locale (es.
  `ApiHiveController.php`) non impostano affatto `$this->controller`
  (proprietà usata da `execute_api()` alla riga ~529) — sembrano
  generati da una versione più vecchia di `CRUDBooster::generateAPI()`,
  precedente all'introduzione di quella proprietà. **Non corretto qui**
  (fuori scope, servono più informazioni su quanti/quali controller
  reali dei clienti ne soffrono prima di decidere come intervenire —
  es. un controllo difensivo in `execute_api()`, o rigenerare i
  controller interessati). Segnalato in backlog.
- Non chiarito se `cms_apicustom` vuota in locale sia una condizione
  voluta/nota o un disallineamento del dump — da verificare se serve
  testare per davvero le 14 API già presenti su disco.

## 2) Autenticazione `api/` esistente — decisione, nessun codice

Deciso con l'utente: **si accetta lo schema debole attuale per ora**.
Piano futuro (non ancora una data): quando i client saranno migrati su
`api2`, disattivare del tutto `api/` (rimuovere `CBAuthAPI`/
`authAPI()` e il primo gruppo di route in `routes/crudbooster.php`) e
tenere solo `api2`. Nessuna modifica di codice in questo intervento —
annotato qui e nel backlog per non perdere la decisione.

## 3) `LogsController`: stesso bug di 089 (Edit/Delete visibili al superadmin)

### Situazione prima

Segnalato in 089 come bug più ampio non corretto: `LogsController`
disattiva esplicitamente `button_edit`/`button_delete`, ma con lo stile
azioni di default (`button_icon`) `ModuleHelper::can_edit()`/
`can_delete()` li ignorano per il superadmin ("admin can always see
everything").

### Situazione dopo

`LogsController::cbInit()` usa ora `button_action_style =
"button_icon_strict"` (introdotto in 089), che rispetta davvero i flag
del modulo per chiunque. `button_detail` non toccato (resta `true`,
default: il dettaglio di un log è comunque consultabile, solo
edit/delete non hanno senso su un log).

### Test

- `php -l`.
- `/admin/logs` (sessione superadmin): 0 `btn-edit`/`btn-delete` reali
  per riga (contati con un pattern esatto sulla classe CSS, per
  escludere falsi positivi come `.btn-delete-selected` del bulk-edit);
  `btn-detail` invariato (20, una per riga).

## 4) Visibilità delle dashboard per ruolo (`StatisticBuilderController`)

### Situazione prima

Vedi "Rischi e note" in
[079](079-statistic-builder-privilegi-componenti.md): `getShow($slug)`
apriva qualunque dashboard a qualunque utente loggato che conoscesse lo
slug; `getListComponent($id, $area)`/`getViewComponent($componentID)`,
raggiungibili direttamente per URL con l'id/componentID numerico (non
solo dopo `getShow()`), servivano di conseguenza i widget (ed
eseguivano davvero la loro query SQL, per `getViewComponent()`) di
qualunque dashboard a chiunque.

### Situazione dopo

Nuovo metodo privato condiviso, `isDashboardVisibleToCurrentUser($idCmsStatistics)`:
- `true` per il superadmin, sempre.
- Altrimenti, vero solo se il ruolo dell'utente ha un menu
  (`cms_menus`, `type='Statistic'`, `path='statistic_builder/show/{slug}'`)
  assegnato tramite `cms_menus_privileges` — **stesso identico
  meccanismo** con cui un menu del genere è già reso visibile in
  sidebar oggi, non uno nuovo.

Applicato a tutti e tre i punti di lettura:
- `getShow($slug)`: nega con lo stesso pattern `CRUDBooster::redirect()
  + insertLog()` già usato altrove nel controller.
- `getListComponent()`: nega con `response()->json(['components' =>
  []], 403)` (stessa forma di risposta attesa dal JS, solo vuota).
- `getViewComponent()`: nega con `response()->json(['error' =>
  ...], 403)` — copre anche, come effetto collaterale necessario (va
  comunque caricato `$component` per sapere a quale dashboard
  appartiene), il caso "componentID inesistente" prima non gestito
  (era in backlog da 067/079 come null-safety mai fatta).

**Comportamento visibile che cambia**: un utente non superadmin il cui
ruolo non ha un menu verso una data dashboard non può più aprirla né
vederne i widget (prima poteva, se conosceva/indovinava lo slug o
l'id). I "link condivisi" restano validi solo se la dashboard è
effettivamente nel menu del ruolo di chi apre il link — non più per
chiunque sia semplicemente loggato.

### Test

Manuali sull'ambiente Docker locale (sessione superadmin + un utente
Standard di prova creato e poi eliminato):
- `php -l`.
- Utente Standard **senza** menu verso una dashboard esistente:
  `getShow` → 302 (negato); `list-component` → 403, `{"components":[]}`;
  `view-component` di un componente reale di quella dashboard → 403.
- Stesso utente **dopo** avergli concesso un menu (`cms_menus` +
  `cms_menus_privileges`) verso quella dashboard: tutti e tre → 200,
  con dati reali.
- Superadmin: invariato, 200 su tutto, con o senza menu.
- **Bug trovato scrivendo il fix**: il metodo condiviso usava
  `$this->table` per risalire allo slug della dashboard — vale solo
  se `cbInit()` è già girata (`getShow()` chiama `cbLoader()` prima,
  ma `getListComponent()`/`getViewComponent()` no), quindi da lì
  `$this->table` era vuota → query SQL malformata (`` select `slug`
  where `id` = 1 `` senza `FROM`) → 500. Corretto usando il nome
  tabella letterale (`'cms_statistics'`) invece di `$this->table`.
- Scritti (non eseguiti, regola del progetto) test in
  `StatisticBuilderCrudTest.php`: 6 nuovi + 1 esistente aggiornato
  (`test_getlistcomponent_per_non_superadmin_non_espone_config`
  richiedeva già un utente senza alcun menu verso la dashboard di test —
  con questo fix quel path ora nega prima di arrivare al controllo che
  quel test verifica, quindi gli è stato concesso il menu per
  preservarne l'intento originale).

### Rischi e note

- Il join usa `cms_menus.path` costruito come stringa
  (`'statistic_builder/show/' . $slug`) — identico a come
  `MenusController::hook_before_add()`/`hook_before_edit()` lo scrivono
  per un menu di tipo Statistic. Se quel formato cambiasse in futuro
  andrebbe aggiornato in entrambi i punti.
- Non copre un eventuale caso "link condiviso con un utente esterno al
  ruolo ma autorizzato ad-hoc" (non esiste oggi un meccanismo del
  genere in questo progetto) — se dovesse servire, è un intervento a
  parte.
- Prima del prossimo giro di aggiornamento clienti: verificare che
  nessun cliente reale avesse dashboard aperte via link condiviso da
  ruoli senza il menu corrispondente — con questo fix smetterebbero di
  funzionare (comportamento corretto rispetto al modello dei permessi,
  ma un cambiamento visibile per chi li usava).

## Rollback

Tre fix indipendenti, ognuno revertibile da solo via `git revert` sul
file specifico — nessuna migration né dato coinvolto in nessuno dei tre.
