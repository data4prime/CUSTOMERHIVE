# 022 - Rimossa packages/.../database/ (migration/seeder legacy, superate da anni)

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Architettura / CRUDBooster
- **File/aree di codice coinvolte**:
  - `packages/crocodicstudio/crudbooster/src/CRUDBoosterServiceProvider.php`
  - `packages/crocodicstudio/crudbooster/src/database/` (rimossa interamente)

## Contesto

Ultimo dei template di installazione identificati nell'analisi di
`packages/.../src/` (insieme a [019](019-rimozione-localization-legacy.md)-[021](021-rimozione-configs-legacy.md)).
Riferimento solo pigro (`publishes([...],'cb_migration')`, non un
`mergeConfigFrom()` attivo — stesso schema di
[019](019-rimozione-localization-legacy.md)/[020](020-rimozione-userfiles-legacy.md),
non come [021](021-rimozione-configs-legacy.md)).

Confrontati puntualmente i contenuti prima di agire:
- **Migrations**: pacchetto 25 file, app reale (`database/migrations/`)
  111 file. **Zero migration esistono solo nel pacchetto** — tutte e 25
  sono già presenti per nome nell'app, che negli anni ne ha aggiunte
  altre 86 direttamente. Le migration già eseguite non vengono comunque
  ri-eseguite da Laravel, quindi anche un'eventuale differenza di
  contenuto tra file omonimi sarebbe irrilevante.
- **Seeders**: pacchetto 3 file, app 21. Un solo file esiste solo nel
  pacchetto: `Qlik_Sett.php`/`QlikSett` (seminava il gruppo "Qlik
  Configuration" in `cms_settings`). Verificato: **già rimosso
  esplicitamente** dalla chiamata in `database/seeders/DatabaseSeeder.php`
  reale, con un commento che lo documenta ("morto da quando la
  configurazione Qlik vive nella tabella qlik_confs... la migration
  2024_07_16_111255_delete_qlik_setting lo cancellava e questo seeder lo
  ricreava subito dopo"). Il file era rimasto dimenticato solo nella
  copia del pacchetto, non più referenziato da nessuna parte.

## Situazione prima

`packages/crocodicstudio/crudbooster/src/database/` (migrations/ +
seeders/, 28 file totali), referenziata da
`$this->publishes([__DIR__.'/database' => base_path('database')],'cb_migration')`
in `CRUDBoosterServiceProvider::boot()`.

## Situazione dopo

- Cartella `database/` cancellata interamente.
- Rimossa la riga `publishes([...],'cb_migration')` da
  `CRUDBoosterServiceProvider::boot()`.
- `database/migrations/`, `database/seeders/` dell'app non toccati.

## Motivazione

Chiude la pulizia dei template di installazione ridondanti in
`packages/.../src/`: nessuna delle migration/seeder del pacchetto era
ancora necessaria, essendo l'app reale già avanti di anni di sviluppo
diretto su `database/`.

## Test

- `php -l` su `CRUDBoosterServiceProvider.php`: nessun errore.
- `php artisan route:list`: 486 rotte, invariato.
- `curl` senza sessione su `/admin`, `/admin/logs`, `/admin/groups`:
  tutti 302, nessun 500.
- Confronto esplicito degli elenchi file (non solo a occhio): zero
  migration e un solo seeder (già morto e documentato come tale)
  esistevano solo nel pacchetto.

## Rischi e note

- Nessuno noto.

## Rollback

`git revert` del commit — ripristina la cartella e la riga `publishes()`,
nessun impatto su `database/migrations`/`database/seeders` dell'app.
