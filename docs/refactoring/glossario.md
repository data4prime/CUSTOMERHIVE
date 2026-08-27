# Glossario

Documento vivo: aggiungere un termine ogni volta che se ne introduce uno
nuovo (o non ovvio) in un file di `docs/refactoring/`.

## Architettura del progetto

**CRUDBooster**
: Pacchetto legacy vendorizzato in `packages/crocodicstudio/crudbooster`
(non installato da Packagist: è un fork del codice, mantenuto dentro questo
repo). Fa da ossatura dell'area admin: autenticazione, routing generato da
database, generatore di CRUD, sistema di menu. Non riceve aggiornamenti
upstream: è il principale ostacolo/rischio per l'upgrade di Laravel e per il
refactoring architetturale.

**Controller "di sistema" vs controller generato da interfaccia**
: CRUDBooster ha due tipi di controller, mai da mescolare. "Di sistema":
scritti a mano, versionati in questo repo (in `App\Http\Controllers\System`
dopo [006](006-controller-sistema-app-http-controllers-system.md), prima in
`packages/crocodicstudio/crudbooster/src/controllers/`) — le schermate
dell'admin panel di CRUDBooster stesso (utenti, gruppi, moduli, ecc.).
"Generato da interfaccia": creato a runtime dal module builder
(`ModulsController`/`CRUDBooster::generateController()`) quando un
utente crea un modulo custom dal pannello, scritto in
`app/Http/Controllers/` (gitignored, specifico di ogni ambiente/cliente,
mai in questo repo). Il contratto tra i due: ogni controller generato fa
`extends \crocodicstudio\crudbooster\controllers\CBController` con l'FQCN
letterale — quella classe (motore del CRUD, non spostata in
[006](006-controller-sistema-app-http-controllers-system.md)) deve restare
risolvibile con quel nome esatto per non rompere le installazioni già in
produzione.

**Tenant / multi-tenancy**
: Il progetto supporta più "tenant" (clienti/organizzazioni) sulla stessa
installazione, tabella `tenants`. Ogni utente (`cms_users`) appartiene a un
tenant; il login verifica che il dominio richiesto corrisponda al tenant
dell'utente (o che l'utente sia superadmin).

**Modulo (cms_moduls)**
: Un'entità/sezione amministrabile generata da CRUDBooster (es. gestione
utenti, gruppi, ecc.), configurata a runtime tramite la tabella `cms_moduls`
e collegata a un controller.

**Privilegio (cms_privileges)**
: Ruolo assegnato a un utente (`cms_users.id_cms_privileges`), a cui sono
associati permessi granulari per modulo tramite `cms_privileges_roles`.

**Guard (Laravel)**
: Meccanismo standard di Laravel per l'autenticazione (`Auth::`,
`auth` middleware). **Il progetto oggi non li usa**: l'auth dell'area admin è
gestita a mano da CRUDBooster tramite variabili di sessione
(`Session::put('admin_id', ...)`). Vedi
[`../login-e-licensing.md`](../login-e-licensing.md).

**CSRF**
: Protezione standard di Laravel contro le richieste cross-site forgery.
Oggi è **disabilitata globalmente** (`VerifyCsrfToken` commentato nel
middleware group `web` in `app/Http/Kernel.php`).

## Licensing

**License gate**
: I punti del codice che verificano la validità della licenza prima di
permettere un'azione (login, aggiunta tenant/utente). Vedi
[`../login-e-licensing.md`](../login-e-licensing.md).

**License server**
: Servizio esterno (`license.thecustomerhive.com`), separato da questo
progetto, che emette e valida le licenze. Ha mostrato bug/instabilità
durante lo sviluppo locale.

**`LICENSE-CHECK-DISABLED-DEV`**
: Tag usato nel codice (`LicenseHelper.php`) per marcare i punti dove il
controllo di licenza è stato temporaneamente disattivato per sviluppo
locale. Vedi [`../pre-push-checklist.md`](../pre-push-checklist.md).

## Qlik

**Qlik JWT login**
: Meccanismo di Single Sign-On verso Qlik Sense per l'embedding delle
dashboard nell'app. Distinto dal login degli utenti dell'app (vedi
`QlikHelper::getJWTToken*`, `public/js/qlik_*login*.js`).

## Processo di refactoring

**Test di caratterizzazione**
: Test scritto per fissare (e proteggere) il comportamento *attuale* del
codice, anche se non ideale, prima di modificarlo — a differenza di un test
che verifica un requisito. Serve da rete di sicurezza durante un refactoring
o un upgrade.

**Breaking change**
: Modifica che rompe la compatibilità con il comportamento precedente (API,
firma di funzione, formato dati, ecc.). Da segnalare sempre nella sezione
"Rischi e note" dei file di `docs/refactoring/`.

**LTS (Long Term Support)**
: Versione di Laravel con supporto esteso più a lungo. Riferimento utile
quando si decide a quale versione fare l'upgrade.

## Infrastruttura

**Bind mount (Docker)**
: In `docker-compose.yml`, il codice del progetto è montato nel container
`app` cosi' com'e' sul filesystem host — le modifiche sono immediate, ma su
Windows/Mac l'I/O su molti file attraverso il bind mount è lento. Vedi
[`../docker-local-dev.md`](../docker-local-dev.md).

**Volume Docker (nativo)**
: Storage gestito da Docker (non sul filesystem host), usato per `vendor/` e
le cache generate proprio per evitare la lentezza del bind mount.
