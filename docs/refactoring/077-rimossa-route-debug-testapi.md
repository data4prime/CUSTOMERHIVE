# 077 - Rimossa la route di debug pubblica `/testapi`

- **Data**: 2026-09-24
- **Stato**: Completato
- **Area**: Sicurezza
- **File/aree di codice coinvolte**:
  - `routes/web.php`

## Contesto

Notata durante [076](076-guard-licenza-e-login-moduli-qlik-chatai.md)
guardando `route:list`, messa nel backlog come priorità alta, ripresa su
richiesta.

## Situazione prima

In cima a `routes/web.php`:

```php
use App\Http\Controllers\ApiListTenantsController;
...
Route::get('/testapi' , function(){
    $test = new ApiListTenantsController();
    dd($test);
});
```

Route pubblica (solo middleware `web`, nessun login) che istanzia un
controller e ne fa il `dd()`. `ApiListTenantsController` è un controller
generato da API Generator per ambiente (vive in `app/Http/Controllers/`,
gitignored):
- dove non esiste (es. ambiente locale) → 500 `Class not found`;
- dove esiste → dump pubblico dell'oggetto controller (configurazione
  interna del modulo) a chiunque apra l'URL.

Nessun riferimento alla route altrove nel repo (codice, viste, doc).

## Situazione dopo

Route e relativo `use` rimossi. `/testapi` → 404.

## Motivazione

Codice di debug rimasto in produzione, raggiungibile senza autenticazione:
nessuna funzione legittima, solo superficie di esposizione.

## Test

- Prima: `GET /testapi` da non loggato → 500 (classe assente in locale).
- Dopo: 404. `php -l routes/web.php` pulito.

## Rischi e note

Nessun uso noto. Se qualcuno la usasse per debug manuale su un ambiente,
può istanziare il controller da `php artisan tinker`.

## Rollback

Ripristinare le 4 righe della route e il `use` dal git history.
