<?php

declare(strict_types=1);

namespace EdLugz\Daraja;

use EdLugz\Daraja\Console\InstallCommand;
use EdLugz\Daraja\Data\ClientCredential;
use Illuminate\Support\ServiceProvider;

final class DarajaServiceProvider extends ServiceProvider
{
    /**
     * Package paths.
     */
    private const string CONFIG_PATH = __DIR__ . '/../config/daraja.php';
    private const string MIGRATIONS_PATH = __DIR__ . '/database/migrations';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'daraja');

        $this->app->bind(Daraja::class, function ($app, array $params) {
            /** @var ClientCredential|null $cred */
            $cred = $params['credentials'] ?? ($params[0] ?? null);
            if (!$cred instanceof ClientCredential) {
                throw new InvalidArgumentException('ClientCredential required to build Daraja.');
            }

            return new Daraja($cred);
        });

        $this->app->alias(Daraja::class, 'daraja');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Auto-load migrations so the package works out of the box.
        if (is_dir(self::MIGRATIONS_PATH)) {
            $this->loadMigrationsFrom(self::MIGRATIONS_PATH);
        }

        // Only define publish groups when running in the console.
        if ($this->app->runningInConsole()) {
            // Config
            $this->publishes([
                self::CONFIG_PATH => config_path('daraja.php'),
            ], 'daraja-config');

            // Migrations (optional—users can publish if they want to edit them)
            if (is_dir(self::MIGRATIONS_PATH)) {
                $this->publishes([
                    self::MIGRATIONS_PATH => database_path('migrations'),
                ], 'daraja-migrations');
            }
        }

        $this->commands([
            InstallCommand::class,
        ]);
    }
}
