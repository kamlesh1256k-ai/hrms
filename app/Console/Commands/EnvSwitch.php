<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EnvSwitch extends Command
{
    protected $signature = 'env:switch {environment : The environment to switch to (local, cloud, or jemini)}';

    protected $description = 'Switch between local, cloud, and jemini environment configurations';

    public function handle()
    {
        $env = $this->argument('environment');

        if (!in_array($env, ['local', 'cloud', 'jemini'])) {
            $this->error("Invalid environment: {$env}. Use 'local', 'cloud', or 'jemini'.");
            return 1;
        }

        $sourceFile = base_path(".env.{$env}");
        $targetFile = base_path('.env');

        if (!file_exists($sourceFile)) {
            $this->error("Environment file not found: .env.{$env}");
            return 1;
        }

        // Backup current .env
        $backupFile = base_path('.env.backup');
        if (file_exists($targetFile)) {
            copy($targetFile, $backupFile);
            $this->info('Current .env backed up to .env.backup');
        }

        // Copy environment file
        copy($sourceFile, $targetFile);

        $this->info("Switched to '{$env}' environment successfully!");
        $this->newLine();

        if ($env === 'local') {
            $this->table(['Setting', 'Value'], [
                ['APP_ENV', 'local'],
                ['APP_DEBUG', 'true'],
                ['APP_URL', 'http://localhost/hrms'],
                ['DB_HOST', '127.0.0.1'],
                ['DB_DATABASE', 'hrms'],
                ['SESSION_SECURE_COOKIE', 'false'],
            ]);
        } elseif ($env === 'cloud') {
            $this->table(['Setting', 'Value'], [
                ['APP_ENV', 'production'],
                ['APP_DEBUG', 'false'],
                ['APP_URL', 'https://miraix.in/'],
                ['DB_DATABASE', 'u658412463_hrm_soft'],
                ['SESSION_SECURE_COOKIE', 'true'],
                ['FORCE_HTTPS', 'true'],
            ]);
        } else {
            $this->table(['Setting', 'Value'], [
                ['APP_ENV', 'production'],
                ['APP_DEBUG', 'false'],
                ['APP_URL', 'https://jemini.co.in'],
                ['SESSION_SECURE_COOKIE', 'true'],
                ['FORCE_HTTPS', 'true'],
            ]);
        }

        $this->newLine();
        $this->info('Run "php artisan config:clear" to apply changes.');

        return 0;
    }
}
