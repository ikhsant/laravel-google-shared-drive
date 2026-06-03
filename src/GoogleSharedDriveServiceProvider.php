<?php

namespace Ikhsant\LaravelGoogleSharedDrive;

use Ikhsant\LaravelGoogleSharedDrive\Services\GoogleDriveService;
use Illuminate\Support\ServiceProvider;

class GoogleSharedDriveServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/google-shared-drive.php', 'google-shared-drive'
        );

        $this->app->singleton(GoogleDriveService::class, function ($app) {
            return new GoogleDriveService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/google-shared-drive.php' => config_path('google-shared-drive.php'),
            ], 'google-shared-drive-config');
        }
    }
}
