# 325 - uploads/*: la rotta non serve file reali, 500 diventa 404 (B1b)

- **Data**: 2026-10-30
- **Stato**: Completato (41 test verdi nel file, suite completa non lanciata)
- **Area**: Sicurezza / Rotte / File
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/FileController.php` (`getPreview`)
  - `tests/Feature/PublicSystemRoutesAuthTest.php` (due test nuovi + `use Illuminate\Support\Facades\Storage;`)

## Contesto

Seguito del [324](324-rotte-custom-sotto-login.md) (B1, rotte custom di
sistema sotto login). Nel 324 la rotta
`GET uploads/{one?}/{two?}/{three?}/{four?}/{five?}` (nome
`fileControllerPreview`, solo middleware `web`) era stata lasciata pubblica,
rimandando a "B1b" la verifica: prima di metterla dietro login bisognava
capire chi genera URL `uploads/...` e se i file sono raggiungibili
direttamente, aggirando la rotta.

## Situazione prima

Verificato in locale con `curl` da ospite, su un file reale
(`storage/app/public/uploads/1/2026-08/qtcdi.png`):

- `CRUDBooster::uploadFile()` salva con `Storage::putFileAs` sul disco di
  default `local` (`config/filesystems.php`, root `storage/app/public`) e
  restituisce `/storage/uploads/<id>/<anno-mese>/<file>`. Quell'URL è servito
  direttamente da Apache tramite il symlink `public/storage` ->
  `storage/app/public`, **senza passare da Laravel**: `GET
  /storage/uploads/1/2026-08/qtcdi.png` da ospite = 200.
- La rotta `uploads/*`: `FileController::getPreview` controllava l'esistenza
  con `Storage::exists` (cioè in `storage/app/public/uploads/...`) ma apriva
  il file con `storage_path('app/'.$path)` (`storage/app/uploads/...`,
  cartella inesistente). Per i file reali `GET /uploads/1/2026-08/qtcdi.png`
  = **500** (`FileNotFoundException` di Symfony File). Per file inesistenti
  404.
- Nel repo nessun punto costruisce URL con `route('fileControllerPreview')` o
  con `uploads/` come URL pubblico (`assetThumbnail` manipola solo un path
  salvato). La rotta è un residuo del CRUDBooster upstream (dove il disco
  aveva root `storage/app`), rimasto dopo il cambio di root del disco `local`
  nel fork.

Conseguenza: mettere `uploads/*` dietro login non proteggerebbe nulla (i file
escono da `/storage/uploads/...`) e la rotta è di fatto morta.

## Situazione dopo

In `getPreview` la condizione `if (! Storage::exists($fullFilePath))` è
diventata:

```php
if (! Storage::exists($fullFilePath) || ! is_file($fullStoragePath)) {
```

con un commento che spiega il motivo. Nessun altro cambio nel controller.

- `GET /uploads/1/2026-08/qtcdi.png` da ospite = 404 (prima 500).
- `GET /storage/uploads/...` resta 200, invariato.
- Se il file esiste in entrambe le cartelle la rotta lo serve come prima.
- Il middleware resta `web` (`test_uploads_ha_ancora_solo_il_middleware_web`
  invariato).

**Cambio di comportamento visibile**: solo 500 -> 404 su `GET uploads/...`
quando il file esiste solo in `storage/app/public`. Nessuna nuova
esposizione, nessun cambio a login, licensing, permessi o dati.

## Motivazione

La persona ha scelto l'opzione A: sistemare solo la rotta, senza aprire nuove
esposizioni. Un 500 su un percorso raggiungibile da chiunque è rumore nei log
e un comportamento scorretto; 404 è la risposta coerente con il fatto che la
rotta non serve quei file.

Alternative rimandate a dopo:

- **B**: servire i file via Laravel con controllo accessi. Richiede di cambiare
  gli URL salvati, quindi una migrazione dati e il processo di aggiornamento
  per-cliente.
- **C**: bloccare l'accesso diretto solo a una parte dei file (es. allegati),
  lasciando pubblici loghi/avatar.

## Test

- Due test nuovi in `tests/Feature/PublicSystemRoutesAuthTest.php`:
  - `test_uploads_con_file_solo_nella_cartella_pubblica_risponde_404` (file
    creato con `Storage::put` in `storage/app/public/uploads/phpunit-b1b/`,
    atteso 404; pulizia in `finally`);
  - `test_uploads_con_file_in_entrambe_le_cartelle_viene_servito` (file anche
    in `storage/app/uploads/phpunit-b1b/`, atteso 200: non regressione;
    aggiunto su suggerimento del reviewer).
- Verificato davvero: `php -l` ok sui due file; prova manuale con `curl` da
  ospite in locale (prima 500, dopo 404, `/storage/...` sempre 200);
  review di `ch-reviewer`.
- **Eseguito** (30/10, nel container, su richiesta della persona):
  `php artisan test tests/Feature/PublicSystemRoutesAuthTest.php` → 41 test
  verdi (94 asserzioni), compresi i due nuovi; nessuna cartella `phpunit-b1b`
  rimasta nello storage. La suite completa non è stata lanciata. Pint segnala
  solo `line_ending`/`no_extra_blank` già presenti nel file, non toccati.
- Review: nessun rilievo bloccante; nessun caso che prima serviva un file
  smette di funzionare; il traversal `..` è invariato (Flysystem lo rifiuta
  dentro `Storage::exists`, prima di `is_file`); la condizione aggiunta può
  solo restringere.

## Rischi e note

- `/storage/uploads/...` **resta pubblico** per chiunque conosca l'URL. I nomi
  sono `md5(str_random)` solo con `$encrypt=true`; altrimenti sono lo slug del
  nome originale in `uploads/{id_utente}/{anno-mese}/`, quindi prevedibili.
  Questo intervento non cambia nulla in merito: è il tema delle opzioni B/C,
  ancora aperte.
- Lanciato solo il file di test toccato, non la suite completa (vedi sopra).
- I test creano file nel disco `local` reale (non fake), in
  `storage/app/public/uploads/phpunit-b1b/` e
  `storage/app/uploads/phpunit-b1b/`, con pulizia in `finally`. Se girano come
  root nel container, le cartelle create risultano `root:root`.
- Suggerimento del reviewer non recepito: la riga 52 ricostruisce
  `storage_path('app/'.$fullFilePath)` invece di usare `$fullStoragePath`;
  innocuo, lasciato per tenere il diff piccolo. Le righe vuote con spazi
  preesistenti nel controller sono ignorate.
- Il doc 324 e il suo piano citano `uploads` come "B1b da fare": ora è chiuso
  nella forma A.

## Rollback

Ripristinare in `FileController::getPreview` la condizione originale
`if (! Storage::exists($fullFilePath))` (e togliere il commento), e rimuovere
i due test e l'import `Storage` da
`tests/Feature/PublicSystemRoutesAuthTest.php`. Nessuna migrazione da
annullare.
