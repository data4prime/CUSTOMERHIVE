# 165 - license.json isolato nei test (niente più file root:root)

- **Data**: 2026-09-30
- **Stato**: Completato (test non ancora rieseguiti)
- **Area**: Licensing, Test
- **File/aree di codice coinvolte**:
  - `config/filesystems.php` (disco `license`)
  - `app/Services/ConnectorService.php`
  - `tests/Unit/Services/ConnectorServiceTest.php`

## Contesto

In locale, dopo aver aggiunto il modulo Qlik alla licenza sul license server,
il modulo non compariva nemmeno rifacendo il login. I log mostravano che il
server restituiva correttamente `Qlik`, ma `storage/app/license.json` restava
alla versione vecchia (solo `n8n`).

## Situazione prima

`ConnectorServiceTest` cancella e ricrea il vero `storage/app/license.json`
(setUp/tearDown). I test girano come root (`docker compose exec`), quindi il
file ricreato era `root:root` 644. Apache gira come `www-data` e non poteva
sovrascriverlo: `Storage::put()` non lancia eccezioni, falliva in silenzio e
`canLicenseLogin()` continuava a usare la licenza stale.

## Situazione dopo

- Il disco `license` in `APP_ENV=testing` punta a
  `storage/framework/testing/license`; in tutti gli altri ambienti resta
  `storage/app/` (invariato).
- `getLicenseFromFile()` legge il path dal disco (`Storage::disk('license')->path()`)
  invece di `storage_path('app/license.json')` hardcoded, così lettura e
  scrittura usano sempre lo stesso file.
- `writeLicense()` logga un errore se `put()` fallisce, invece di ignorarlo.
- `ConnectorServiceTest` usa il path del disco (con mkdir della cartella).

## Motivazione

I test non devono toccare il file di licenza reale usato dall'app. Isolare il
file elimina la causa alla radice (non serve ricordarsi di `-u www-data` o di
cancellare il file dopo ogni run). Il log in caso di put() fallito rende
visibile un eventuale problema di permessi futuro.

## Test

Solo `php -l` sui tre file. La suite NON è stata rieseguita.
Da verificare al prossimo run: altri test che in `testing` chiamano
`isActiveQlik()`/`getLicenseFromFile()` ora non trovano più il file reale e
potrebbero tentare una chiamata al license server (Http non fakato).

## Rischi e note

- In produzione/dev/staging nessun cambiamento di comportamento (disco e path
  invariati), tranne il nuovo log d'errore.
- Il `license.json` root:root già presente in locale va cancellato una volta:
  `docker compose exec app rm storage/app/license.json`.

## Rollback

Ripristinare `root` del disco in `config/filesystems.php`, il path hardcoded in
`getLicenseFromFile()` e il test allo stato precedente (git diff dei tre file).
