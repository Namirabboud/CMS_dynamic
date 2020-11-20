<?php

namespace Eve\Dynamic;

use Illuminate\Routing\Controller;
use Illuminate\Support\ServiceProvider;

class DynamicRouteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make(Controllers\GeneralController::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->loadMigrationsFrom(__DIR__.'/migrations');


        $this->publishes([
            //__DIR__.'/views' => base_path('resources/views/eve/cms'),
            __DIR__.'/Seeders' => base_path('database/seeders'),
        ]);

    }
}
