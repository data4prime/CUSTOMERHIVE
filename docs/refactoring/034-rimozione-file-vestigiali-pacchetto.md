# 034 - Rimossi gli ultimi file vestigiali di packages/crocodicstudio/crudbooster/

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Documentazione / Housekeeping
- **File/aree di codice coinvolte**:
  - `packages/crocodicstudio/crudbooster/.codeclimate.yml` (eliminato)
  - `packages/crocodicstudio/crudbooster/.gitignore` (eliminato)
  - `packages/crocodicstudio/crudbooster/composer.json` (eliminato)
  - `packages/crocodicstudio/crudbooster/README.md` (eliminato)

## Contesto

Ultimi 4 file rimasti fuori da `src/` nel pacchetto, residui di quando
`crocodicstudio/crudbooster` era un repository Composer autonomo:
- `composer.json`: **mai letto da Composer** — il `composer.json` alla
  radice del repo non ha una sezione `"repositories"` che tratti questo
  come pacchetto installato, mappa direttamente
  `crocodicstudio\crudbooster\` → `packages/.../src` nel proprio
  `autoload.psr-4`.
- `.gitignore`: regole (`.idea`, `/vendor`, `*.iml`, ecc.) pensate per
  quando la cartella era radice di un progetto Composer a sé — inerte in
  questo workflow (nessun `composer install` viene mai eseguito lì
  dentro).
- `.codeclimate.yml`: CodeClimate legge la configurazione solo dalla
  radice del repository collegato al servizio — non esiste un
  `.codeclimate.yml` alla radice di CustomerHive, e nessun riferimento a
  CodeClimate in tutto il repo (CI, badge, docs).
- `README.md`: il README originale del progetto upstream
  `crocodic-studio/crudbooster` (badge Packagist/Scrutinizer-CI,
  descrizione del pacchetto standalone), non di CustomerHive.

## Situazione prima

I 4 file sopra, unico contenuto di
`packages/crocodicstudio/crudbooster/` oltre a `src/`.

## Situazione dopo

- Tutti e 4 cancellati. `packages/crocodicstudio/crudbooster/` contiene
  ora solo `src/` (a sua volta solo `assets/`, `fonts/`, `views/`).

## Motivazione

Chiude la pulizia del pacchetto vendorizzato: nessun file rimasto che non
sia effettivamente usato dall'applicazione.

## Test

- `composer dump-autoload`: nessun errore (confermava che
  `composer.json` nested non serviva a nulla).
- `php artisan route:list`: 486 rotte, invariato.
- `curl` senza sessione su `/admin` (302) e `/admin/login` (200): nessun
  500.

## Rischi e note

- Nessuno noto.

## Rollback

`git revert` del commit — ripristina i 4 file.
