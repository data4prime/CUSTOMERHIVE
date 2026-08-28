# 044 - Bug: eliminazione privilegio bloccata e messaggio perso nel redirect

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Bug fix
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/PrivilegesController.php`
  - `app/Http/Middleware/CBBackend.php`

## Contesto

Segnalato dall'utente: da `/admin/privileges`, eliminando un record si
finisce su `/admin/statistic_builder/dashboard` e il record non viene
eliminato, senza alcun messaggio esplicativo.

Due bug distinti concorrono a questo comportamento:

1. **`PrivilegesController::getDelete()`** blocca la cancellazione di
   qualunque privilegio con `id < 4`, assumendo che gli id 1-3 siano
   sempre i 3 ruoli "di sistema" (superadmin/tenantadmin/standard).
   `database/seeders/CmsPrivilegesSeeder.php` in realtà crea **solo**
   l'id 1 ("Super Administrator"); qualunque altro privilegio creato
   durante i test finisce con id 2, 3, 4... e i primi (in questo
   ambiente: id 2 "test", id 3 "naemee") restano bloccati per errore
   dalla stessa regola pensata per proteggere solo il superadmin.

2. **Il messaggio di blocco si perde**: il redirect di blocco punta a
   `CRUDBooster::adminPath()` (root `/admin` nuda). La rotta
   `Route::get('/', function () {})` in `routes/crudbooster.php` non fa
   nulla di suo: è il middleware `CBBackend` che, vedendo una richiesta
   sulla root nuda, la rimanda sempre al modulo configurato come
   dashboard (qui: Statistic Builder). Questo secondo redirect non
   riflette il messaggio flash `message`/`message_type` impostato dal
   primo redirect (`CRUDBooster::redirect()`), quindi il messaggio non
   viene mai mostrato: la pagina di destinazione finale (la dashboard)
   non ha idea che ci fosse un messaggio da mostrare. Lo stesso pattern
   (`CRUDBooster::redirect(CRUDBooster::adminPath(), ...)`) è usato in
   ~14 controller per i messaggi di "accesso negato", quindi lo stesso
   difetto si presenta ogni volta che un'azione bloccata prova a
   spiegare perché, non solo qui.

## Situazione prima

```php
if ($id < 4) {
    //can't delete default roles
    ...
    CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.cant_delete_role'));
}
```

`CBBackend::handle()`: quando l'url richiesto è la root admin nuda,
redirige sempre al modulo dashboard configurato, senza preservare
alcun messaggio flash già presente in sessione.

## Situazione dopo

- **`PrivilegesController::getDelete()`**: la condizione è ora
  `if ($row->is_superadmin == 1)` — protegge solo il/i privilegio/i
  con il flag superadmin (l'unico che il seeder garantisce sempre
  presente), non un range di id arbitrario. I privilegi id 2/3/4 di
  test sono ora cancellabili normalmente.
- **`PrivilegesController::getDelete()`** (entrambi i rami di blocco,
  sia "accesso negato" sia "non puoi cancellare questo ruolo"): il
  redirect ora punta a `CRUDBooster::mainpath()` invece di
  `CRUDBooster::adminPath()` — cioè torna alla lista privilegi (la
  stessa pagina da cui si è tentata la cancellazione), non alla root
  admin nuda. Comportamento richiesto esplicitamente dall'utente dopo
  aver verificato che il banner "you can't delete this role!" ora
  compare correttamente, ma su una pagina diversa da quella di
  partenza. Stessa destinazione già usata dal redirect di successo
  (`trans("crudbooster.alert_delete_data_success")` un poco più sotto),
  quindi ora tutti e 3 gli esiti di `getDelete()` riportano coerentemente
  alla lista privilegi.
- **`CBBackend::handle()`**: prima di eseguire il redirect verso il
  modulo dashboard (quando qualcosa punta comunque alla root admin
  nuda, come ancora fanno gli altri ~14 controller), se in sessione
  c'è un messaggio flash (`session()->has('message')`) viene fatto un
  `session()->reflash()`, così il messaggio sopravvive un'altra
  richiesta e viene mostrato dal layout (`admin_template.blade.php`
  legge già `Session::get('message_type')`/`message` su qualunque
  pagina, dashboard inclusa). Per Privileges questo fix non è più
  strettamente necessario dopo il cambio a `mainpath()` sopra, ma resta
  utile per gli altri controller che ancora redirigono a `adminPath()`.

## Motivazione

- La regola sull'id era un'euristica fragile legata a un'assunzione
  sui dati (3 ruoli sempre seedati) che non corrisponde alla realtà di
  questo ambiente (e probabilmente di altri): l'unico invariante reale
  è "non si può restare senza alcun privilegio superadmin".
- Perdere silenziosamente il messaggio di errore su qualunque azione
  bloccata (non solo la delete dei privilegi) è un problema di UX
  trasversale: l'utente si ritrova su una pagina inattesa senza sapere
  perché l'azione non è andata a buon fine.

## Test

- `php -l` su entrambi i file: nessun errore.
- Verificato via query diretta che solo l'id 1 ha `is_superadmin = 1`
  in questo ambiente (id 2/3/4 = 0).
- `curl` senza sessione su `/admin/privileges`,
  `/admin/privileges/delete/2`, `/admin`: tutti 302 (redirect a
  login), nessun 500; conteggio righe `cms_privileges` invariato dopo
  la chiamata (nessuna cancellazione indebita da richiesta non
  autenticata).

**Non verificato visivamente in browser** (serve una sessione admin
autenticata reale per esercitare l'intero flusso: click su elimina →
redirect bloccato/riuscito → messaggio visibile) — lasciato al giro di
test manuale dell'utente.

## Rischi e note

- `session()->reflash()` riflasha *tutti* i dati flash correnti, non
  solo `message`/`message_type`. In questo punto del ciclo di vita
  della richiesta non ci sono altri dati flash rilevanti da questa
  stessa sessione, quindi è equivalente nella pratica a un
  `session()->keep(['message', 'message_type'])` più mirato, ma più
  semplice; se in futuro altri dati flash dovessero convivere con
  `message` in questo punto, valutare di restringere a `keep()`.
- Il fix del middleware è generico (non specifico a Privileges): risolve
  lo stesso problema per qualunque controller che usi
  `CRUDBooster::redirect(CRUDBooster::adminPath(), ...)` quando la
  dashboard configurata non è la root admin stessa.

## Rollback

`git revert` del commit — ripristina `$id < 4` e il redirect senza
reflash del middleware.
