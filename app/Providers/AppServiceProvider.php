<?php

namespace App\Providers;

use App\Models\GrIncrement;
use App\Observers\GrIncrementObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Growth Review: auto-initiate Comeback Plan when a 0% increment is finalized
        GrIncrement::observe(GrIncrementObserver::class);

        // Force HTTPS in production/cloud environment
        if (env('FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }

        // Make APP_URL track the actual host on every request so subdomains
        // (e.g. ddtech.miraix.in) generate asset()/url() pointing to themselves,
        // not the .env hardcoded root domain. Skip in CLI context.
        if (!$this->app->runningInConsole() && request() && request()->getHost()) {
            // Use getHttpHost() so a non-standard port (e.g. :8000 under
            // `artisan serve` locally) is preserved; getHost() drops the port
            // and breaks asset()/url() on local dev.
            $host   = request()->getHttpHost();
            $scheme = request()->isSecure() ? 'https' : (env('FORCE_HTTPS', false) ? 'https' : 'http');
            $root   = $scheme . '://' . $host;
            URL::forceRootUrl($root);
            config(['app.url' => $root]);

            // Ensure Storage::url() also uses the per-request host (filesystems.php
            // reads APP_URL only at config load time, so override disk URLs here).
            $storageUrl = rtrim($root, '/') . '/storage';
            config([
                'filesystems.app_url' => rtrim($root, '/'),
                'filesystems.disks.public.url' => $storageUrl,
                'filesystems.disks.local.url' => $storageUrl,
            ]);
        }
    }
}
