<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CRUDBoosterServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(resource_path('views/crudbooster'), 'crudbooster');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register('Unisharp\Laravelfilemanager\LaravelFilemanagerServiceProvider');
    }
}
