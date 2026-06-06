<?php

namespace App\Http\Controllers;

use Com\Tecnick\Pdf\Tcpdf;
use Illuminate\Http\Response;

class ReporteController extends Controller
{
    /**
     * Genera un PDF de prueba con tc-lib-pdf y lo muestra en el navegador.
     *
     * Antes de probar, importá una fuente una sola vez:
     *   php artisan pdf:import-font
     */
    public function test(): Response
    {
        $fontFile = storage_path('fonts' . DIRECTORY_SEPARATOR . 'arial.json');
        if (! is_readable($fontFile)) {
            abort(500, "Falta la fuente. Ejecutá: php artisan pdf:import-font");
        }

        $pdf = new Tcpdf();

        $pdf->setCreator('Lrvl_curso_reporte');
        $pdf->setAuthor('Sistema de Reportes');
        $pdf->setTitle('Reporte de prueba');
        $pdf->setSubject('Prueba de tc-lib-pdf');
        $pdf->setPDFFilename('reporte_prueba.pdf');

        // Cargamos la fuente importada y la agregamos a la página.
        $font = $pdf->font->insert($pdf->pon, 'arial', '', 12);

        $pdf->addPage();
        $pdf->page->addContent($font['out']);

        $fecha = now()->format('d/m/Y H:i');
        $html = <<<HTML
            <h1>Reporte de prueba</h1>
            <p>PDF generado con <b>tc-lib-pdf</b> desde Laravel.</p>
            <p>Fecha de emisión: {$fecha}</p>
            <table border="1" cellpadding="4">
                <tr><th>Curso</th><th>Inscriptos</th><th>Aprobados</th></tr>
                <tr><td>Laravel Básico</td><td>30</td><td>27</td></tr>
                <tr><td>PHP Avanzado</td><td>18</td><td>15</td></tr>
            </table>
            HTML;

        $pdf->addHTMLCell(html: $html, posx: 15, posy: 15, width: 180);

        $raw = $pdf->getOutPDFString();

        // Mostrar en el navegador (usar 'attachment' para forzar descarga).
        return response($raw, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="reporte_prueba.pdf"',
        ]);
    }
}
