<?php

namespace App\Console\Commands;

use Com\Tecnick\Pdf\Font\Import;
use Illuminate\Console\Command;

/**
 * Importa una fuente TrueType (.ttf) al formato que entiende tc-lib-pdf
 * (.json + .z) y la deja en storage/fonts, que es la carpeta apuntada por
 * la constante K_PATH_FONTS (ver App\Providers\AppServiceProvider).
 *
 * Uso:
 *   php artisan pdf:import-font                       (importa Arial por defecto)
 *   php artisan pdf:import-font "C:\Windows\Fonts\times.ttf"
 */
class ImportPdfFont extends Command
{
    protected $signature = 'pdf:import-font {ttf? : Ruta absoluta al archivo .ttf}';

    protected $description = 'Importa una fuente TTF al formato de tc-lib-pdf (storage/fonts)';

    public function handle(): int
    {
        $ttf = $this->argument('ttf') ?: 'C:\\Windows\\Fonts\\arial.ttf';

        if (! is_readable($ttf)) {
            $this->error("No se puede leer la fuente: {$ttf}");

            return self::FAILURE;
        }

        $outDir = storage_path('fonts');
        if (! is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        try {
            $import = new Import($ttf, $outDir . DIRECTORY_SEPARATOR);
            $name = $import->getFontName();
        } catch (\Throwable $e) {
            // Import lanza excepción si la fuente ya fue importada; lo informamos sin frenar.
            $this->warn('No se importó: ' . $e->getMessage());

            return self::SUCCESS;
        }

        $this->info("Fuente importada como '{$name}' en {$outDir}");
        $this->line("Generados: {$name}.json y {$name}.z");

        return self::SUCCESS;
    }
}
