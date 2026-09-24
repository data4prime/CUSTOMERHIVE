# 089 - `button_edit`/`button_detail=false` ignorati per il superadmin con lo stile azioni di default

> **Nota**: il bug analogo su `LogsController`, citato sotto come "non
> corretto di proposito", è stato risolto lo stesso giorno in
> [091](091-bug-noti-execute-api-logscontroller-visibilita-dashboard.md)
> su richiesta esplicita dell'utente.

- **Data**: 2026-09-24
- **Stato**: Completato
- **Area**: Bug fix / UI
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/components/action.blade.php` (nuovo stile `button_icon_strict`)
  - `app/Http/Controllers/System/ApiTokensController.php`

## Contesto

Segnalato dall'utente: `/admin/api_tokens` mostrava comunque i pulsanti
Edit e Detail/View per ogni riga, nonostante `ApiTokensController`
imposti esplicitamente `button_edit = false` e `button_detail = false`.

## Situazione prima

`resources/views/crudbooster/components/action.blade.php` rende i
pulsanti azione per riga in modo diverso a seconda di
`$button_action_style`:
- `'button_text'`, `'button_icon_text'`, `'dropdown'`: controllano
  `CRUDBooster::isRead()/isUpdate()/isDelete() && $button_detail/
  $button_edit/$button_delete` — rispettano il flag del modulo.
- **`@else` (stile di default)**: usato quando lo stile è `'button_icon'`
  (il valore di default di `CBController::$button_action_style`, quindi
  quello che ha *ogni* modulo che non lo sovrascrive esplicitamente) —
  usa invece `ModuleHelper::can_view()`/`can_edit()`/`can_delete()`, che
  iniziano tutti con `if (CRUDBooster::isSuperadmin()) { return true; }`
  ("admin can always see everything") **prima** di controllare il flag
  del modulo. Per un superadmin, `button_edit`/`button_detail`/
  `button_delete` del modulo vengono quindi **sempre ignorati**.

Verificato che è un comportamento diffuso, non specifico al modulo
appena creato: `LogsController` disattiva esplicitamente `button_edit`/
`button_delete` (commento: "I log non si modificano ne' si eliminano")
ma non imposta uno stile diverso da quello di default — stesso bug,
presente da prima di questa sessione.

## Situazione dopo

Aggiunto un **nuovo stile**, `button_icon_strict`: stessa resa grafica
(icone, nessun testo) dello stile di default, ma con le stesse
condizioni di `'button_text'`/`'button_icon_text'`/`'dropdown'`
(`CRUDBooster::isRead()/isUpdate()/isDelete() && $button_xxx`) — rispetta
davvero il flag del modulo, **anche per il superadmin**. `ApiTokensController`
lo usa ora al posto dello stile di default.

**Lo stile di default (`'button_icon'`, quello di ogni altro modulo)
non è stato toccato**: `LogsController` e chiunque altro continua a
comportarsi esattamente come prima (compreso il bug per il superadmin) —
intervento scelto per non introdurre un cambiamento di comportamento in
tutti gli altri moduli senza che sia stato chiesto.

## Motivazione

Il modulo Token API non ha un vero "modifica"/"dettaglio" (un token
Sanctum è solo nome+hash+scadenza, niente da modificare, niente da
mostrare che non sia già in lista) — mostrare comunque quei pulsanti al
superadmin è fuorviante (portano a una pagina che nega comunque
l'accesso, vedi `getEdit()`/`postEditSave()` già negati in
`ApiTokensController`). Un nuovo stile opt-in, invece di correggere lo
stile di default, evita di cambiare comportamento per l'intera
applicazione (es. `LogsController`, dove il superadmin *forse* si
aspetta di poter comunque intervenire in emergenza — non chiarito,
quindi non toccato).

## Test

- `php -l` su entrambi i file.
- `/admin/api_tokens` (sessione superadmin reale): `btn-edit` e
  `btn-detail` → 0 occorrenze; `btn-delete` → 3 (una per token in
  lista, comportamento invariato). L'unico `fa-pencil` residuo nella
  pagina è nel modale "Modifica multipla" (bulk edit), già
  irraggiungibile perché `button_bulk_action = false`.

## Rischi e note

- **Bug più ampio, non corretto ovunque di proposito**: qualunque altro
  modulo con `button_edit`/`button_detail`/`button_delete = false` e
  stile di default (`LogsController` è l'unico altro caso trovato oggi)
  mostra comunque quei pulsanti a un superadmin. Se si vuole lo stesso
  comportamento anche lì, va cambiato esplicitamente lo stile a
  `button_icon_strict` — non fatto qui perché cambierebbe comportamento
  visibile su un modulo che nessuno ha chiesto di toccare.

## Rollback

`git revert` del commit — nessuna migration né dato coinvolto, solo
template di rendering e un flag di configurazione del controller.
