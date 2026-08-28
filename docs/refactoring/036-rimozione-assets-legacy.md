# 036 - Rimossa packages/.../src/assets/ (già pubblicata e tracciata in public/)

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Housekeeping
- **File/aree di codice coinvolte**:
  - `app/Providers/CRUDBoosterServiceProvider.php`
  - `packages/crocodicstudio/crudbooster/src/assets/` (rimossa interamente)

## Contesto

Stesso schema di [019](019-rimozione-localization-legacy.md)/[020](020-rimozione-userfiles-legacy.md)/[022](022-rimozione-database-legacy.md):
`public/vendor/crudbooster/` (2055 file, 31 MB) è già pubblicata e **già
tracciata su git** — la copia live che l'app serve davvero via `asset()`.
Confronto file-per-file tra `packages/.../assets/` (2072 file) e
`public/vendor/crudbooster/`: **zero contenuto nel pacchetto assente da
public/**, una volta scartata una duplicazione ricorsiva
(`assets/assets/assets/...`, verosimilmente un vecchio bug da `cp -r`)
che rappresentava quasi tutta la differenza di conteggio file e non è
referenziata da nessuna parte (nessun `asset()` genera mai un path con
`assets/assets/` doppio).

**Nota emersa ma non affrontata qui**: la stessa duplicazione ricorsiva
esiste anche dentro `public/vendor/crudbooster/assets/assets/` (15 MB /
1015 file su 31 MB totali) — file live, tracciati su git, serviti in
produzione. Non toccata in questo intervento (tocca file effettivamente
serviti, non solo `packages/`, quindi più delicato) — segnalata come
possibile pulizia futura, lavoro UI/UX.

## Situazione prima

`packages/crocodicstudio/crudbooster/src/assets/` (32 MB), referenziata
da `$this->publishes([...], 'cb_asset')` in
`CRUDBoosterServiceProvider::boot()`.

## Situazione dopo

- Cartella cancellata interamente.
- Rimossa la riga `publishes([...],'cb_asset')` (path sorgente altrimenti
  inesistente).
- `public/vendor/crudbooster/` non toccata: resta l'unica fonte per gli
  asset, invariata.

## Motivazione

Nessun bisogno di ripubblicare nulla: tutto ciò che era realmente
necessario nel pacchetto era già live in `public/`. Elimina 32 MB di
codice sorgente morto (in gran parte duplicazione ricorsiva).

## Test

- `php -l` su `CRUDBoosterServiceProvider.php`: nessun errore.
- `php artisan route:list`: 486 rotte, invariato.
- `curl` senza sessione: `/admin` → 302, `/admin/login` → 200.
- **Verifica diretta degli asset serviti da `public/`**:
  `/vendor/crudbooster/ionic/css/ionicons.min.css` → 200,
  `/vendor/crudbooster/jquery.price_format.2.0.min.js` → 200 — entrambi
  usati rispettivamente in 9 e diverse view (template admin, pagine
  pubbliche chat_ai/qlik, popup datamodal), confermano che rimuovere il
  pacchetto non ha impatto sugli asset realmente serviti.

## Rischi e note

- La duplicazione ricorsiva dentro `public/vendor/crudbooster/assets/assets/`
  (15 MB) resta com'è — pulizia possibile ma fuori scope per questo
  intervento (tocca file live, non solo il pacchetto).
- Con questo, `packages/crocodicstudio/crudbooster/src/` contiene solo
  `views/` — ultimo pezzo, puro lavoro UI/UX.

## Rollback

`git revert` del commit — ripristina la cartella e la riga `publishes()`,
nessun impatto su `public/vendor/crudbooster/`.
