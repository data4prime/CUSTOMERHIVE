# 102 - MFA: badge di stato senza forma/colore, poi sovrapposizione con "System Information"

- **Data**: 2026-09-25
- **Stato**: Completato
- **Area**: Auth / UI
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/default/form.blade.php`
  - `resources/views/crudbooster/mfa_backup_codes.blade.php`

## Contesto

Segnalato dall'utente: bug di interfaccia su `/admin/users/profile`. Il
badge di stato MFA ("Two-factor authentication is/is not active.")
appariva come testo su sfondo grigio piatto, senza forma di "pillola" ne'
colore (verde/grigio) - sembrava testo selezionato col mouse.

## Situazione prima

Le fasi 1 e 4 (`096`) usavano `<span class="label label-success">`/
`<span class="label label-default">`, copiato da `api_key.blade.php` — una
vista piu' vecchia del progetto. Verificato con `getComputedStyle()` nel
browser: `padding: 0px`, `border-radius: 0px` — le classi `.label`/
`.label-success`/`.label-default` sono di **Bootstrap 3** (rimosse in
Bootstrap 4+), mentre questo progetto carica **Bootstrap 5.3.3** da CDN
come foglio di stile principale (confermato da `document.styleSheets`) —
`api_key.blade.php` e' evidentemente una vista mai migrata, che
probabilmente ha lo stesso difetto passato inosservato.

## Situazione dopo

Sostituito con la convenzione Bootstrap 5 **gia' usata altrove nel
progetto** (`header.blade.php`, `api_documentation.blade.php`):
`badge bg-success` / `badge bg-secondary`.

## Test

- Verificato dal vivo in browser: badge ora renderizzato come pillola con
  sfondo pieno (verde per "attiva", grigio per "non attiva").
- Confermato via `curl` che anche la pagina dei backup codes mostra
  `badge bg-success` nel markup restituito.
- Nessun errore in console del browser sulla pagina.

## Rischi e note

Lo stesso difetto (`label label-*` invece di `badge bg-*`) e' presente
anche in `api_key.blade.php` (non toccato: fuori dallo scope di questa
segnalazione, riguarda una pagina diversa non collegata all'MFA).

## Rollback

Ripristinare `label label-success`/`label label-default` (comportamento
visivo rotto, non consigliato).

## Seguito lo stesso giorno: sovrapposizione con "System Information"

Dopo il fix del badge, segnalato dall'utente che la card "Two-Factor
Authentication" si sovrapponeva leggermente alla card "Profile"/"System
Information" sopra di essa (non un problema di stile, di posizionamento
reale).

**Causa radice trovata** (bug preesistente, non introdotto da questo
lavoro - vedi anche la voce aggiunta al Backlog in `README.md`):
`resources/views/crudbooster/default/form_body.blade.php` apre un box
collassabile "System Information" (`<div class="row"><div class="col-sm-12
col-md-12"><div class="box box-info collapsed-box">...<div class="box-body
no-padding">`) quando incontra un campo di nome `tenant`, e si aspetta di
richiuderlo (`</div></div></div></div>`) quando incontra un campo di nome
`group`. `AdminCmsUsersController` (modulo Users, quindi anche
`getProfile()`) definisce `tenant` ma **non** `group` nel proprio
`cbInit()` — quei div non si chiudono mai, e inglobano tutto cio' che
segue (box-footer, `</form>`, la chiusura della card Profile, e la mia
intera sezione MFA) dentro quel contenitore, nascosto via CSS finche' non
lo si espande.

**Scoperta piu' seria durante l'indagine**: verificato con
`offsetParent === null` via JS che il pulsante "Save" della pagina
profilo e' **gia' oggi invisibile/non cliccabile** per lo stesso motivo -
nessun utente puo' salvare Nome/Email da quella pagina. Un primo tentativo
di sistemare la chiusura dei div alla radice (chiudere anche su `tenant`
quando `group` non esiste nel form) ha **confermato e ingigantito** il
problema: il pulsante "Save" rimane comunque nascosto (e' dentro al box
collassato, indipendentemente da quanto sia ben formato l'HTML), MA in
piu' rivela anche i campi Primary Group, Expiry date, Status, Photo,
Language, Password - probabilmente non tutti pensati per essere
auto-modificabili da un utente sul proprio profilo. **Questo tentativo e'
stato annullato** (non committato) prima di procedere oltre, perche'
cambia comportamento visibile della pagina profilo in modo molto piu'
ampio di quanto richiesto, e va deciso esplicitamente con l'utente prima
di procedere (aggiunto al Backlog di `README.md` invece di essere
risolto qui).

**Fix applicato invece, scoped solo a questa pagina**: in
`resources/views/crudbooster/default/form.blade.php`, subito prima di
aprire la sezione MFA (dentro il gia' esistente
`@if(CRUDBooster::getCurrentMethod() == 'getProfile')`), chiusi
manualmente i 4 div lasciati aperti (`</div></div></div></div>`) prima di
aprire la card "Two-Factor Authentication". Questo:
- non tocca `form_body.blade.php` (usato da ogni form CRUD del progetto -
  zero rischio per altre pagine/moduli);
- non cambia la visibilita' del pulsante "Save" ne' di nessun altro campo
  del profilo (restano esattamente come sono oggi - bug pre-esistente,
  segnalato ma non risolto qui);
- rende la sezione MFA un vero sibling `.card`, correttamente staccata.

Verificato via JS (`getBoundingClientRect()`): "Profile" (bottom 457) e
"Two-Factor Authentication" (top 481) non si sovrappongono piu', stesso
parent DOM diretto. Nessun errore in console.
