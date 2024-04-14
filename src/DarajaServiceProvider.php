<?php

namespace EdLugz\Daraja;

use Illuminate\Support\ServiceProvider;

class DarajaServiceProvider extends ServiceProvider
{
    /**
     * Package path to config.
     */
    const CONFIG_PATH = __DIR__.'/../config/daraja.php';

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            self::CONFIG_PATH => config_path('daraja.php'),
        ], 'config');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/daraja.php', 'daraja');

        // Register the service the package provides.
        $this->app->singleton('daraja', function ($app) {
            return new Daraja();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['daraja'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/daraja.php' => config_path('daraja.php'),
        ], 'daraja.config');
    }
}
