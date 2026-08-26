# 001 - Refactoring auth: guard Laravel additivo (Fase 1)

- **Data**: 2026-08-26
- **Stato**: Completato (questa fase — è la prima di un percorso più lungo)
- **Area**: Auth
- **File/aree di codice coinvolte**:
  - `packages/crocodicstudio/crudbooster/src/controllers/AdminController.php` (`postLogin()`)
  - `tests/Feature/LoginTest.php`
  - (già esistenti, non toccati in questa fase: `app/User.php`, `app/Role.php`, `config/auth.php`)

## Contesto

CustomerHive è già in produzione presso clienti reali (vedi memoria di
progetto): ogni modifica all'auth deve essere a **rischio zero**, verificata
prima di procedere oltre. L'obiettivo finale (discusso e concordato) è
sostituire l'autenticazione custom di CRUDBooster (basata su variabili di
sessione scritte/lette manualmente in decine di punti) con i guard nativi
di Laravel, senza mai avere un momento in cui il comportamento reale cambia
in un colpo solo — vedi la strategia additiva ("strangler fig") discussa in
sessione.

Fase 0 (ricognizione, precedente a questo intervento) ha trovato che il
terreno era già in parte preparato: `App\User` esiste già, estende
`Illuminate\Foundation\Auth\User` (già `Authenticatable`) ed è già mappato
su `$table = 'cms_users'`; `App\Role` è già mappato su `cms_privileges`;
`config/auth.php` ha già il guard `web` di default configurato
correttamente (sessione + provider Eloquent su `App\User`). Nessuno di
questi era però mai stato collegato al vero flusso di login. La
ricognizione ha anche trovato solo 42 file distinti nel repo che leggono lo
stato di auth direttamente (`CRUDBooster::myId()` e simili), quasi tutti
dentro il pacchetto CRUDBooster stesso — zero nei 52 controller custom
dell'app.

## Situazione prima

`AdminController::postLogin()`, dopo aver verificato password/stato/tenant
a mano, scriveva solo variabili di sessione custom (`Session::put('admin_id',
...)` e altre 8) — nessuna integrazione con `Auth::` di Laravel. Ogni punto
del codice che deve sapere "chi è l'utente loggato" lo fa leggendo quelle
chiavi di sessione direttamente (o tramite gli helper statici di
`CRUDBooster`), non tramite `Auth::user()`/`Auth::check()`.

## Situazione dopo

Nel ramo di successo di `postLogin()`, **in aggiunta** a tutto il codice
esistente (nessuna riga rimossa), viene ora anche chiamato:

```php
$userModel = \App\User::find($users->id);
if ($userModel) {
    Auth::login($userModel);
}
```

Questo popola il guard `web` standard di Laravel, utilizzabile da subito
con `Auth::check()`, `Auth::user()`, `Auth::id()`. Le chiavi di sessione
legacy (`admin_id` ecc.) continuano ad essere scritte esattamente come
prima — tutto il codice esistente che le legge non si accorge di nulla.

## Motivazione

Passo minimo, reversibile e verificabile per iniziare a rendere disponibile
l'auth standard di Laravel, senza toccare (ancora) nessuno dei 42 punti che
oggi leggono lo stato legacy. Propedeutico alla Fase 3 (migrazione punto per
punto dei call site verso `Auth::`), che potrà procedere file per file una
volta che il guard è popolato in modo affidabile.

## Test

- I 12 test esistenti (login + `CBBackend` + canary) sono stati eseguiti
  **senza modificarne le asserzioni esistenti** prima di aggiungere la
  chiamata ad `Auth::login()`, e restano tutti verdi dopo — prova diretta
  che il cambiamento è additivo e non ha alterato il comportamento
  osservabile precedente.
- Aggiunte nuove asserzioni (non sostitutive) in `LoginTest.php`:
  - `test_login_con_credenziali_corrette_riesce` e
    `test_superadmin_bypassa_il_controllo_tenant`: verificano anche
    `Auth::check()` e `Auth::id()`.
  - `test_login_con_password_sbagliata_fallisce`: verifica `$this->assertGuest()`
    (nessuna autenticazione sul percorso di fallimento).
  - `test_dopo_il_login_si_accede_a_una_pagina_protetta`: verifica che
    `Auth::check()` resti vero anche sulla richiesta successiva (persistenza
    tra richieste, non solo nella risposta immediata del login).
- **Verifica manuale sull'app reale** (non solo sul DB di test): login
  completo via `curl` contro l'ambiente Docker locale, fino al rendering
  effettivo della dashboard (HTTP 200, titolo corretto). Durante questa
  verifica si è scoperto che il DB di sviluppo locale (`customerhive`,
  diverso da `customerhive_testing` usato dai test automatici) si era
  svuotato durante le tante ricostruzioni dei container in sessione — non
  collegato a questa modifica, risolto con un re-seed pulito
  (`migrate:fresh` + `db:seed`).

## Rischi e note

- `\App\User::find($users->id)` viene eseguito con una query aggiuntiva
  (l'utente era già stato caricato come `stdClass` via `DB::table()`, qui
  viene ricaricato come modello Eloquent) — costo trascurabile su un login,
  operazione non ripetuta ad ogni richiesta.
- Se `\App\User::find()` non trovasse la riga (non dovrebbe mai succedere,
  stesso id appena usato per l'autenticazione) il codice non fa nulla di
  diverso da prima: nessuna eccezione, il `Auth::login()` viene
  semplicemente saltato, il comportamento legacy (session-based) resta
  l'unico usato per quella richiesta.
- Nessuno dei 42 punti che leggono lo stato di auth è stato toccato — sono
  ancora tutti sul meccanismo legacy. Questa fase da sola non semplifica
  ancora nulla, prepara solo il terreno per la Fase 3.

## Rollback

Rimuovere le 5 righe aggiunte in `postLogin()` (il blocco `$userModel =
...` / `Auth::login($userModel)`) e l'import `use Illuminate\Support\Facades\Auth;`.
Nessun altro file applicativo dipende da questa chiamata, quindi la rimozione
è priva di effetti collaterali. Le asserzioni aggiunte nei test andrebbero
rimosse insieme.
