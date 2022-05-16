<?php

namespace Armincms\Fields;

use Laravel\Nova\Nova;
use Laravel\Nova\Events\ServingNova;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class FieldServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Nova::serving(function (ServingNova $event) {
            Nova::script('armincms-belongs-to-many', __DIR__.'/../dist/js/field.js');
            Nova::style('armincms-belongs-to-many', __DIR__.'/../dist/css/field.css');
        });

        $this->app->booted(function() {
            $this->routes();
        });
    } 

    /**
     * Register the tool's routes.
     *
     * @return void
     */
    protected function routes()
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware(['nova'])
                ->prefix('nova-api/armincms') 
                ->namespace(__NAMESPACE__.'\\Http\\Controllers')
                ->group(__DIR__.'/../routes/api.php');

        // Route::middleware(['nova', Authorize::class])
        // ->prefix('nova-vendor/armincms')
        // ->namespace(__NAMESPACE__.'\\Http\\Controllers')
        // ->group(__DIR__.'/../routes/api.php');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
