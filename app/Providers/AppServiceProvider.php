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
        // NOTA: NO definir K_PATH_FONTS globalmente aquí.
        // tc-lib-pdf y el TCPDF clásico comparten esa constante pero usan
        // formatos de fuente distintos. Cada controlador la define (o no)
        // según la librería que use:
        //   - tc-lib-pdf  -> ReporteController define K_PATH_FONTS = storage/fonts
        //   - TCPDF clásico (WrapTcpLib) -> se autoconfigura a sus fuentes bundled
    }
}
