<?php
declare(strict_types=1);

namespace EdLugz\Daraja\Console;

use Illuminate\Console\Command;

final class InstallCommand extends Command
{
    protected $signature = 'daraja:install
        {--force : Overwrite published files and run migrations in production}
        {--no-publish : Skip publishing config/migrations}
        {--no-migrate : Skip running database migrations}';

    protected $description = 'Publish Daraja config & migrations and run migrations.';

    public function handle(): int
    {
        $provider = "EdLugz\\Daraja\\DarajaServiceProvider";

        if (! $this->option('no-publish')) {
            $this->info('Publishing Daraja config & migrations…');

            // Preferred tags
            $this->call('vendor:publish', [
                '--provider' => $provider,
                '--tag'      => 'daraja-config',
                '--force'    => (bool) $this->option('force'),
            ]);

            $this->call('vendor:publish', [
                '--provider' => $provider,
                '--tag'      => 'daraja-migrations',
                '--force'    => false,
            ]);

            // Back-compat if older tag names exist in your provider
            $this->call('vendor:publish', [
                '--provider' => $provider,
                '--tag'      => 'config',
                '--force'    => (bool) $this->option('force'),
            ]);
            $this->call('vendor:publish', [
                '--provider' => $provider,
                '--tag'      => 'migrations',
                '--force'    => false,
            ]);
        }

        if (! $this->option('no-migrate')) {
            if ($this->laravel->environment('production') && ! $this->option('force')) {
                if (! $this->confirm('App is in production. Run migrations?', false)) {
                    $this->warn('Skipped running migrations.');
                    return self::SUCCESS;
                }
            }

            $this->info('Running migrations…');
            $this->call('migrate', ['--force' => (bool) $this->option('force')]);
        }

        $this->info('Daraja installed ✅');
        return self::SUCCESS;
    }
}
