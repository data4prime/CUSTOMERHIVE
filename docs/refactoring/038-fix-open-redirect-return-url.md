# 038 - Fix: open redirect su CRUDBooster::redirect() / return_url

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Sicurezza
- **File/aree di codice coinvolte**:
  - `app/Helpers/CRUDBooster.php`

## Contesto

Emerso analizzando (su richiesta dell'utente) perché il parametro
`return_url` compare nell'URL di pagine come `/admin/tenants/add`. Quel
parametro arriva dalla query string, senza nessuna validazione, fino a
`CRUDBooster::redirect($to, ...)` — chiamata 23 volte solo in
`CBController.php` (che tutti i moduli, inclusi quelli custom dei
clienti, ereditano), oltre che da altri controller di sistema.

## Situazione prima

```php
public static function redirect($to, $message, $type = 'warning')
{
    // ...
    $resp = redirect($to)->with([...]);
    // ...
}
```

Nessun controllo su `$to`. Un link tipo
`.../admin/tenants/add?return_url=https://sito-malevolo.esempio.com`
mandato a un admin autenticato, se il form veniva salvato, lo rimandava
al sito esterno — open redirect, sfruttabile per phishing (il link
iniziale è sul dominio vero e fidato).

## Situazione dopo

Aggiunto `CRUDBooster::sanitizeRedirectUrl($to)`, chiamato all'inizio di
`redirect()`: ammesso solo se `$to` è un path relativo che comincia con
un solo `/` (esclude i protocol-relative `//host/...`, che puntano a un
host esterno) oppure un URL assoluto con lo stesso host della richiesta
corrente (`Request::getHost()`); in ogni altro caso (host esterno,
schema `javascript:`, stringa vuota) si ricade su
`CRUDBooster::adminPath()`.

Un solo punto modificato: protegge automaticamente tutte le chiamate a
`CRUDBooster::redirect()` in tutto il progetto, inclusi i moduli custom
dei clienti (che chiamano lo stesso helper condiviso, mai reimplementato
per modulo) — comportamento identico per ogni uso legittimo, cambia solo
per input malevoli.

## Motivazione

Vulnerabilità reale, sfruttabile senza autenticazione preventiva (basta
convincere un admin già loggato a cliccare un link e salvare un form).
Fix mirato e a basso impatto: nessuna modifica al comportamento per URL
legittimi (relativi o stesso host), che sono l'unico caso realmente
usato oggi (`return_url` generato sempre da `Request::fullUrl()` sullo
stesso host).

## Test

- `php -l`: nessun errore (stessa deprecation preesistente, non
  collegata).
- Test diretto di `sanitizeRedirectUrl()` via reflection, simulando una
  richiesta su `localhost:8080`, con 7 casi: path relativo (invariato),
  path relativo con query string (invariato), URL assoluto stesso host
  (invariato), URL assoluto host esterno (fallback), URL protocol-relative
  verso host esterno (fallback), schema `javascript:` (fallback), stringa
  vuota (fallback) — tutti e 7 con l'esito atteso.
- `php artisan route:list`: 486 rotte, invariato.
- `curl` senza sessione su `/admin`, `/admin/tenants`: 302, nessun 500.

## Rischi e note

- `CRUDBooster::redirectBack()` (usa `$_SERVER['HTTP_REFERER']`, non
  `return_url`) non è stata toccata — fuori dallo scope di questo fix,
  vettore diverso e più marginale (richiede controllare l'header Referer,
  non un semplice link).
- Non affronta la questione più ampia discussa con l'utente (spostare
  `return_url` fuori dalla query string con un meccanismo a token) —
  rimandata, questo fix la rende comunque non più sfruttabile nel
  frattempo.

## Rollback

`git revert` del commit — nessuna migrazione o cambio di schema
coinvolto.
