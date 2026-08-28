<?php

/*
 * Alias di compatibilita' per le 5 classi "motore" del pacchetto CRUDBooster
 * vendorizzato, spostate in App\Http\Controllers\System (docs/refactoring/
 * 012-015). Necessario perche' controller generati da interfaccia in
 * produzione (fuori da questo repo) estendono ancora il vecchio FQCN per
 * nome letterale, es. `extends \crocodicstudio\crudbooster\controllers\CBController`.
 *
 * Caricato eagerly su ogni request/comando tramite l'entry "files" di
 * composer.json (autoload/autoload-dev), cosi' non serve piu' nessun file
 * fisico dentro packages/crocodicstudio/crudbooster/src/controllers/ per
 * mantenere questi alias.
 *
 * Da rimuovere solo quando ogni cliente attivo e' stato aggiornato almeno
 * una volta con `php artisan crudbooster:migrate-legacy-extends --apply`
 * (docs/refactoring/016) — vedi docs/refactoring/README.md.
 */

class_alias(\App\Http\Controllers\System\Controller::class, 'crocodicstudio\crudbooster\controllers\Controller');
class_alias(\App\Http\Controllers\System\CBController::class, 'crocodicstudio\crudbooster\controllers\CBController');
class_alias(\App\Http\Controllers\System\ApiController::class, 'crocodicstudio\crudbooster\controllers\ApiController');
class_alias(\App\Http\Controllers\System\ExportData::class, 'crocodicstudio\crudbooster\controllers\ExportData');
class_alias(\App\Http\Controllers\System\ImportData::class, 'crocodicstudio\crudbooster\controllers\ImportData');
