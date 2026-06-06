<?php

namespace App\Providers;

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
        // tc-lib-pdf busca las fuentes (.json/.z) en la carpeta apuntada por
        // esta constante. Importarlas con: php artisan pdf:import-font
        if (! defined('K_PATH_FONTS')) {
            define('K_PATH_FONTS', storage_path('fonts'));
        }
    }
}
