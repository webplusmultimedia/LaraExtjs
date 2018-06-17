<?php

namespace Webplusm\LaraExtjs;

use Illuminate\Support\ServiceProvider;

class LaraExtjsServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'webplusm');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'webplusm');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {

            // Publishing the configuration file.
            $this->publishes([
                __DIR__.'/../config/laraextjs.php' => config_path('laraextjs.php'),
            ], 'laraextjs.config');

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => base_path('resources/views/vendor/webplusm'),
            ], 'laraextjs.views');*/

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/webplusm'),
            ], 'laraextjs.views');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/webplusm'),
            ], 'laraextjs.views');*/

            // Registering package commands.
            // $this->commands([]);
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laraextjs.php', 'laraextjs');

        // Register the service the package provides.
        $this->app->singleton('laraextjs', function ($app) {
            return new LaraExtjs;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['laraextjs'];
    }
}