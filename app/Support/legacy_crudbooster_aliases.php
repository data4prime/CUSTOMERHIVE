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

/*
 * Modelli spostati in App\Models (piano 336, ADR A17): il vecchio nome resta
 * valido per i controller custom dei clienti (`use App\User;`, `instanceof`,
 * `extends`). Un alias per ogni modello spostato, aggiunto nel batch che lo
 * sposta; da rimuovere solo quando ogni cliente e' stato aggiornato.
 */
class_alias(\App\Models\User::class, 'App\User');
class_alias(\App\Models\Role::class, 'App\Role');
class_alias(\App\Models\UsersGroup::class, 'App\UsersGroup');
class_alias(\App\Models\Tenant::class, 'App\Tenant');
class_alias(\App\Models\Group::class, 'App\Group');
class_alias(\App\Models\GroupTenants::class, 'App\GroupTenants');
class_alias(\App\Models\MenuTenants::class, 'App\MenuTenants');
class_alias(\App\Models\MenuGroups::class, 'App\MenuGroups');
class_alias(\App\Models\ModuleTenants::class, 'App\ModuleTenants');
class_alias(\App\Models\TenantsAllowed::class, 'App\TenantsAllowed');
class_alias(\App\Models\ItemsAllowed::class, 'App\ItemsAllowed');
class_alias(\App\Models\ChatAIGroupAllowed::class, 'App\ChatAIGroupAllowed');
class_alias(\App\Models\ChatAITenantAllowed::class, 'App\ChatAITenantAllowed');
class_alias(\App\Models\Modules::class, 'App\Modules');
class_alias(\App\Models\AccessLog::class, 'App\AccessLog');
class_alias(\App\Models\Log::class, 'App\Log');
class_alias(\App\Models\QlikSyncRunRecord::class, 'App\QlikSyncRunRecord');
class_alias(\App\Models\QlikSyncRun::class, 'App\QlikSyncRun');
class_alias(\App\Models\ChatAIConf::class, 'App\ChatAIConf');
class_alias(\App\Models\QlikItem::class, 'App\QlikItem');
class_alias(\App\Models\Menu::class, 'App\Menu');
