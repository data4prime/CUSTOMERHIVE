# 021 - Rimossa packages/.../configs/ (mergeConfigFrom + publishes rimossi)

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Architettura / CRUDBooster
- **File/aree di codice coinvolte**:
  - `packages/crocodicstudio/crudbooster/src/CRUDBoosterServiceProvider.php`
  - `packages/crocodicstudio/crudbooster/src/configs/` (rimossa interamente)

## Contesto

Diverso da [019](019-rimozione-localization-legacy.md)/[020](020-rimozione-userfiles-legacy.md):
`configs/crudbooster.php` aveva **due** riferimenti in
`CRUDBoosterServiceProvider`, non uno solo:
1. `$this->publishes([...],'cb_config')` — pigro, stesso schema degli
   altri (sicuro).
2. `$this->mergeConfigFrom(__DIR__.'/configs/crudbooster.php','crudbooster')`
   — **eseguito ad ogni richiesta** (non solo su un `vendor:publish`
   manuale): Laravel fa un `require` diretto del file. Cancellare il file
   lasciando questa riga avrebbe rotto l'app su ogni richiesta, non solo
   su un comando eseguito a mano.

Verificato prima di agire: diff tra `config/crudbooster.php` (vero,
tracciato su git) e la copia del pacchetto → **un'unica differenza**, la
chiave `'API_PATH' => 'api'` presente solo nel pacchetto. Verificato che
`config('crudbooster.API_PATH')` **non viene letto da nessuna parte** nel
repo — chiave morta, il `mergeConfigFrom()` non stava colmando nessun
buco funzionale reale.

## Situazione prima

`packages/crocodicstudio/crudbooster/src/configs/crudbooster.php`
(22 chiavi), referenziato da `publishes()` e da `mergeConfigFrom()` in
`CRUDBoosterServiceProvider`.

## Situazione dopo

- Cartella `configs/` cancellata interamente.
- Rimossa la riga `publishes([...],'cb_config')` e la riga
  `mergeConfigFrom(...)` da `CRUDBoosterServiceProvider`.
- `config/crudbooster.php` (21 chiavi, senza `API_PATH`) non toccato:
  resta l'unica fonte di configurazione per `crudbooster.*`.

## Motivazione

Elimina l'ultimo pezzo redundante di "template di installazione" del
pacchetto identificato nell'analisi di `packages/.../src/` (insieme a
[019](019-rimozione-localization-legacy.md)/[020](020-rimozione-userfiles-legacy.md)),
gestendo però con attenzione la differenza chiave rispetto a quei due
interventi: qui andava rimossa anche la chiamata attiva
(`mergeConfigFrom`), non solo il file e un `publishes()` pigro.

## Test

- `php -l` su `CRUDBoosterServiceProvider.php`: nessun errore.
- `php artisan config:clear`: ok (nessuna cache di config residua da un
  file ora cancellato).
- `php artisan route:list`: 486 rotte, invariato.
- `curl` senza sessione su `/admin`, `/admin/logs`, `/admin/groups`:
  tutti 302, nessun 500.
- Verifica diretta via bootstrap completo dell'app:
  `config('crudbooster.ADMIN_PATH')` → `'admin'`,
  `config('crudbooster.UPLOAD_TYPES')` → invariato, entrambi corretti;
  `config('crudbooster.API_PATH')` → `null` (atteso: chiave mai usata,
  persa senza conseguenze).

## Rischi e note

- Se in futuro emergesse un uso di `API_PATH` non trovato da questa
  analisi (es. in un modulo custom di un cliente non presente in questo
  repo), andrebbe aggiunto manualmente a `config/crudbooster.php` — non
  probabile: la chiave non è mai comparsa in nessun controller/vista
  tracciato né generato.

## Rollback

`git revert` del commit — ripristina la cartella e le 2 righe in
`CRUDBoosterServiceProvider`, nessun impatto su `config/crudbooster.php`.
