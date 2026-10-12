<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapHealthRoute();

        // Per GET /health si registra SOLO la rotta di salute: gli altri file
        // di rotte interrogano il DB (cms_moduls...) e scandiscono/istanziano
        // i controller custom a ogni richiesta, quindi con il DB giu' il
        // controllo impiegherebbe decine di secondi invece di rispondere 503.
        // Per ogni altro URL (e da riga di comando) non cambia nulla.
        if (! $this->app->runningInConsole() && in_array(request()->method(), ['GET', 'HEAD'], true) && request()->is('health')) {
            return;
        }

        $this->mapApiRoutes();

        $this->mapWebRoutes();

        $this->mapCrudboosterRoutes();

        //
    }

    /**
     * Endpoint di salute per l'orchestratore (GET /health).
     *
     * Senza gruppo di middleware: niente "web" (sessione/cookie), niente
     * CBBackend/EnforceModuleLicense, fuori da ADMIN_PATH.
     *
     * @return void
     */
    protected function mapHealthRoute()
    {
        Route::get('health', \App\Http\Controllers\System\HealthController::class);
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::group([
            'middleware' => 'web',
            'namespace' => $this->namespace,
        ], function ($router) {
            require base_path('routes/web.php');
        });
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::group([
            'middleware' => 'api',
            'namespace' => $this->namespace,
            'prefix' => 'api',
        ], function ($router) {
            require base_path('routes/api.php');
        });
    }

    /**
     * Define the CRUDBooster routes for the application.
     *
     * Non avvolto in un Route::group() esterno: ogni blocco in
     * routes/crudbooster.php definisce già i propri middleware/namespace/
     * prefix (spostato da packages/crocodicstudio/crudbooster/src/routes.php,
     * vedi docs/refactoring/031). Registrato per ultimo per preservare
     * l'ordine di caricamento di prima (CRUDBoosterServiceProvider::boot()
     * girava dopo RouteServiceProvider::boot()).
     *
     * @return void
     */
    protected function mapCrudboosterRoutes()
    {
        require base_path('routes/crudbooster.php');
    }
}
