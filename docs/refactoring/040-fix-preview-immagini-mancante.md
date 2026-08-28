# 040 - Fix: anteprima immagine mancante su Logo/Favicon (Tenants)

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Bug fix (pre-esistente, non causato dal refactoring di questa sessione)
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/AdminTenantsController.php`
  - `app/Http/Controllers/AdminArticoloController.php` (modulo custom cliente, **non tracciato/non committato** — fix solo locale per i test)

## Contesto

Segnalato dall'utente: su `/admin/tenants`, le colonne Logo e Favicon non
mostrano l'anteprima dell'immagine. Causa: `CBController::getIndex()`
renderizza una colonna come `<img>` con lightbox solo se la sua
definizione ha `"image" => true` — senza, mostra il valore grezzo (il
path del file). Il generatore di moduli aggiunge questo flag
automaticamente quando un campo immagine viene creato da interfaccia
(`ModulsController.php:827`); le colonne qui coinvolte risalgono
probabilmente a prima di quella logica, o sono state modificate a mano.

Verificato incrociando ogni campo form con validazione immagine
(`'validation' => 'image...'`) contro la colonna lista corrispondente, su
tutti i controller di sistema **e** sui moduli custom presenti in locale:
trovate altre 2 colonne senza il flag (Logo, Favicon in
`AdminTenantsController.php`) più una terza in un modulo custom cliente
(`AdminArticoloController.php`, campo "Immagine") — le altre 3 colonne
immagine esistenti nell'app (`Photo` in Users, `Immagine Categoria` e
`Immagine Prodotto` in due moduli custom) erano già corrette.

## Situazione prima

```php
$this->col[] = ["label" => "Logo", "name" => "logo"];
$this->col[] = ["label" => "Favicon", "name" => "favicon"];
```

## Situazione dopo

```php
$this->col[] = ["label" => "Logo", "name" => "logo", "image" => true];
$this->col[] = ["label" => "Favicon", "name" => "favicon", "image" => true];
```

Stessa correzione (`"image"=>true`) applicata anche ad
`AdminArticoloController.php` (campo "Immagine") — **solo in locale**:
è un modulo generato da interfaccia, per costruzione fuori dal
tracking di questo repo (specifico dell'ambiente cliente da cui proviene
la copia usata per i test). Non finirà nel commit; lo stesso fix va
applicato manualmente sull'ambiente reale del cliente quando lo si
aggiorna.

## Motivazione

Ripristina l'anteprima immagine attesa nella lista, comportamento
standard di CRUDBooster per le colonne di tipo immagine.

## Test

- `php -l` su entrambi i file: nessun errore.
- `php artisan route:list`: 486 rotte, invariato.
- `curl` senza sessione su `/admin/tenants`: 302, nessun 500.
- **Non verificato visivamente in browser** (serve una sessione
  autenticata) — lasciato al giro di test manuale dell'utente.

## Rischi e note

- `AdminArticoloController.php` non è tracciato in questo repo: il fix
  qui è solo per l'ambiente di test locale, da replicare manualmente sul
  cliente reale (o da includere nel prossimo aggiornamento di quel
  cliente).
- Non ancora verificato se lo stesso problema esiste in **altri** moduli
  custom cliente non presenti in questa copia locale — la scansione ha
  coperto solo ciò che è visibile in questo ambiente.

## Rollback

`git revert` del commit — ripristina le 2 colonne senza il flag in
`AdminTenantsController.php` (il fix su `AdminArticoloController.php`
non è comunque mai stato committato).
