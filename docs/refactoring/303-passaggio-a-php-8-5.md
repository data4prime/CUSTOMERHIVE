# 303 - Passaggio a PHP 8.5

- **Data**: 2026-10-12
- **Stato**: Completato (test e build prod verdi in locale; CI non ancora eseguita)
- **Area**: Dipendenze / Docker / CI
- **File/aree di codice coinvolte**:
  - `composer.json` (`php`: `^8.3` → `^8.5`), `composer.lock` (solo content-hash)
  - `docker/Dockerfile` (sviluppo), `docker/Dockerfile.prod` (produzione, in
    più `opcache` tolto da `docker-php-ext-install`)
  - `.github/workflows/deploy.yml` (step Setup PHP)

## Contesto

Laravel era già a `^13.0` (installata 13.29.0 in locale, 13.35.0 nel log di
CI). Laravel 13 richiede PHP `^8.3`, quindi il salto a 8.5 è una scelta del
progetto, non un requisito del framework: si allinea runtime, immagine di
produzione e CI sulla versione più recente supportata.

## Situazione prima

PHP 8.3 ovunque: `composer.json` (`^8.3`), `php:8.3-apache` in entrambi i
Dockerfile, `php-version: '8.3'` nella CI.

## Situazione dopo

PHP 8.5 ovunque: `composer.json` `^8.5`, `php:8.5-apache` nei due Dockerfile,
CI su `'8.5'`. `composer update --lock` ha riscritto solo il content-hash del
lock: nessuna dipendenza è cambiata di versione (tutte già compatibili con
8.5).

## Motivazione

Versione di PHP supportata più a lungo, allineamento di dev/CI/produzione.
Con `composer.json` a `^8.5` un ambiente rimasto su 8.3/8.4 rifiuta
l'installazione invece di fallire a runtime.

## Test

Eseguiti in locale (Docker, immagine ricostruita, PHP 8.5.11):
- build dell'immagine di sviluppo con tutte le estensioni (gd, intl, zip,
  pdo_mysql, pdo_sqlite, ecc.);
- `composer update --lock`: nessuna modifica ai pacchetti;
- `php artisan about` e `route:list` senza errori;
- `php -l` (con `error_reporting=-1`) su `app/`, `config/`, `routes/`,
  `database/`: nessun errore né deprecazione a tempo di compilazione.

- suite di test completa su PHP 8.5: 272 passati, 1 saltato (885 asserzioni);
- build di `Dockerfile.prod`: **al primo tentativo falliva**, perché da PHP 8.5
  OPcache è compilato nel core e `docker-php-ext-install opcache` termina con
  `cp: cannot stat 'modules/*'`. Corretto togliendo `opcache` dall'elenco e
  aggiungendo al controllo finale `function_exists("opcache_get_status")`
  (`prod.ini` imposta solo direttive `opcache.*`, non carica l'estensione). Dopo
  la correzione la build riesce e nell'immagine risultano PHP 8.5.11,
  `opcache.enable=1` e tutte le estensioni (pdo_mysql, gd, intl, zip, bcmath,
  mbstring, exif, pcntl).

NON verificato: esecuzione della CI, avvio completo dell'istanza di produzione
(entrypoint, migrazioni) con la nuova immagine, deprecazioni a runtime sui
flussi reali (login, licensing, CRUD, export Excel/PDF, upload immagini).

## Rischi e note

- **Controller/moduli custom dei clienti** (`app/Http/Controllers/` fuori da
  `System/`, `storage/app/custom-controllers`) non sono nel repo e non sono
  stati controllati con 8.5: da lintare con `php -l` su PHP 8.5 prima di
  aggiornare ciascun cliente.
- Le istanze già in produzione restano su 8.3 finché non vengono ricostruite
  con la nuova immagine; l'aggiornamento è per-cliente.
- `composer audit` segnala 3 advisory su 2 pacchetti: non legate a questo
  intervento, da valutare a parte.

## Rollback

Ripristinare i quattro file (`composer.json`, i due Dockerfile, `deploy.yml`)
a `8.3`/`^8.3`, `docker compose build app`, `composer update --lock`.
