# 016 - Comando artisan per migrare i controller custom dei clienti al nuovo FQCN

- **Data**: 2026-08-28
- **Stato**: Completato (tooling pronto, non ancora usato su nessun cliente)
- **Area**: Architettura / CRUDBooster / Tooling
- **File/aree di codice coinvolte**:
  - `app/Console/Commands/MigrateLegacyCrudboosterExtends.php` (nuovo)
  - `app/Console/Kernel.php` (registrazione comando)

## Contesto

Le 5 classi motore sono state spostate in `App\Http\Controllers\System`
mantenendo il vecchio FQCN risolvibile tramite `class_alias()`
([012](012-controller-motore-shim-class-alias.md)-[015](015-cbcontroller-shim-class-alias.md)).
Gli shim restano necessari finché esistono, in qualunque ambiente cliente,
controller generati da interfaccia con `extends \crocodicstudio\crudbooster\controllers\CBController`
(o `ApiController`) letterale.

Discusso con l'utente: il processo di aggiornamento di un cliente prevede
già di copiare a mano, nella nuova versione di CustomerHive, i file
caricati e i controller generati da interfaccia del cliente. Quel momento
è l'occasione naturale per riscrivere anche l'`extends`, ma **solo se lo
si fa sistematicamente per ogni cliente** — se anche un solo cliente non
viene mai passato per questo passo, gli shim in `packages/.../controllers/`
restano necessari per sempre. Verificato sui 46 controller custom presenti
in locale (copie reali di un cliente, ignorati da git): pattern
letterale identico su tutti, generato meccanicamente da CRUDBooster —
adatto a un find-replace automatico.

## Situazione dopo

Nuovo comando artisan:

```
php artisan crudbooster:migrate-legacy-extends [path=app/Http/Controllers] [--apply]
```

- Scansiona ricorsivamente la cartella indicata (default:
  `app/Http/Controllers`), **escludendo sempre `System/`** (i controller
  di sistema tracciati in questo repo, fuori scope per questo tool).
- Riscrive `extends \crocodicstudio\crudbooster\controllers\CBController` →
  `extends \App\Http\Controllers\System\CBController` (idem per
  `ApiController`), tollerante a backslash iniziale opzionale.
- **Dry-run di default**: senza `--apply` mostra solo l'anteprima di cosa
  cambierebbe, nessun file scritto. Con `--apply` scrive davvero e lancia
  `php -l` su ogni file modificato, segnalando eventuali errori di
  sintassi introdotti (in teoria impossibile con questo pattern di
  sostituzione, ma verificato comunque per sicurezza).
- Idempotente: un file già migrato (o mai stato sul vecchio FQCN) non
  viene toccato in run successivi.

## Motivazione

Rende il passo "aggiorna l'`extends` dei controller del cliente" un
comando ripetibile e verificabile invece di un'operazione manuale a
rischio di dimenticanza o refuso, mantenendo il dry-run come default per
non modificare mai nulla senza revisione esplicita.

## Test

- `php -l` sul comando stesso: nessun errore di sintassi.
- **Dry-run reale** su `app/Http/Controllers` (i 46 controller custom
  presenti in locale, copie di un cliente reale): 46 file con match
  (tutti `CBController`/`ApiController`, 1x ciascuno, coerente con quanto
  già mappato in [006](006-controller-sistema-app-http-controllers-system.md)),
  6 invariati, **nessun file scritto** (comportamento dry-run confermato).
  I file reali del cliente non sono stati toccati.
- **Test `--apply` su copie usa-e-getta**, non sui file reali: copiate 2
  delle controller reali (`AdminAutoController.php`, `ApiHiveController.php`)
  in una cartella temporanea fuori tracciamento git, eseguito `--apply`:
  entrambe riscritte correttamente
  (`extends \App\Http\Controllers\System\CBController` /
  `...\ApiController`), nessun fallimento di `php -l`. Rieseguito una
  seconda volta sulla stessa cartella: 0 match (idempotenza confermata).
  Cartella temporanea poi rimossa.

## Rischi e note

- Il comando **non è mai stato eseguito con `--apply` su un ambiente
  cliente reale** — solo su copie usa-e-getta in locale. Il primo uso
  reale dovrebbe comunque partire da dry-run per revisionare l'elenco dei
  file prima di applicare.
- Copre solo `CBController`/`ApiController` (gli unici due delle 5 classi
  motore referenziati da fuori per FQCN letterale, come già verificato in
  [012](012-controller-motore-shim-class-alias.md)/[013](013-importdata-exportdata-shim-class-alias.md)).
  Non tocca `Controller`, `ExportData`, `ImportData` (mai estese da
  controller custom).
- Gli shim `class_alias()` in `packages/.../controllers/` restano
  necessari finché non tutti i clienti sono stati migrati con questo
  comando almeno una volta — vedi
  [`README.md`](README.md#roadmap-uscita-da-crudbooster-packages). Non
  cancellare quella cartella finché questo non è confermato per ogni
  cliente attivo.

## Rollback

`git revert` del commit — solo aggiunta di un comando artisan, nessun
comportamento esistente modificato (il comando non viene eseguito
automaticamente da nulla).
