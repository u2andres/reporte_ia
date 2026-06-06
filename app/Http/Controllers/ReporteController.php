<?php

namespace App\Http\Controllers;

use Com\Tecnick\Pdf\Tcpdf;
use Illuminate\Http\Response;
use App\Libraries\WrapTcpLib;
use App\Libraries\Reports\ReportMin02;

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
        // tc-lib-pdf busca sus fuentes (.json/.z) en K_PATH_FONTS.
        // Se define aquí (y no globalmente) para no chocar con el TCPDF
        // clásico que usa WrapTcpLib. Las constantes define() son por-request.
        if (! defined('K_PATH_FONTS')) {
            define('K_PATH_FONTS', storage_path('fonts'));
        }

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

    /**
     * Genera el reporte de cursos usando WrapTcpLib (TCPDF clásico).
     *
     * Toda la definición del reporte está en el YAML
     * app/config/report/cursos.yml y los datos en
     * App\Libraries\Reports\CursoReportData.
     */
    public function cursos(): Response
    {
        // IMPORTANTE: no definir K_PATH_FONTS aquí. El TCPDF clásico se
        // autoconfigura a sus fuentes bundled (helvetica, etc.).
        $pdf = new WrapTcpLib('P', 'mm', 'A4', true, 'UTF-8');

        // Arma el reporte completo a partir del YAML.
        $pdf->ColoredTable('cursos.yml');

        // 'S' devuelve el PDF como string (sin enviarlo directamente).
        $raw = $pdf->Output('cursos.pdf', 'S');

        return response($raw, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="cursos.pdf"',
        ]);
    }

    /**
     * Genera el reporte de cursos usando WrapTcpLib (TCPDF clásico).
     *
     * Toda la definición del reporte está en el YAML
     * app/config/report/multig_min02_a4p.yml y los datos en
     * App\Libraries\Reports\ReportMin02
     */
    public function rpt_min02(): Response
    {
        // IMPORTANTE: no definir K_PATH_FONTS aquí. El TCPDF clásico se
        // autoconfigura a sus fuentes bundled (helvetica, etc.).

        // $pdf = new WrapTcpLib('P', 'mm', 'A4', true, 'UTF-8');
        // 
        // // Arma el reporte completo a partir del YAML.
        // $pdf->ColoredTable('cursos.yml');
        // 
        // // 'S' devuelve el PDF como string (sin enviarlo directamente).
        // $raw = $pdf->Output('cursos.pdf', 'S');

        // ajusta el yaml de configuracion segun el listado...
        // ----------------------------------------------------
        $h_genPdf = array(
          // para "configuracion" del listado...
          'pg_size'        => 'A4',
          'pg_orientation' => 'p',
          'ycfg_file'      => $ycfg_file, 
          // 
          // - ORIGINAL, el destinatario sera un "archivo"...
          // 'output_file'    => $uploaded_to . '/' . $tmp_file, // xdf => 'reporte'
          // 'destination'    => 'F',
          // 
          // - xahora para TEST, 
          // devuelve el PDF como string => 'S'
          'output_file'    => 'rpt_min02',
          //   - sin enviarlo directamente
          'destination'    => 'S',
          // 
          'watermark'      => array( 
            'active' => false,
            'text'   => 'NO VALIDO - NO VALIDO - NO VALIDO - NO VALIDO',
            'size'   => 20,
            ),
          // propios del "listado"...
          'h_header'    => $h_header,
          'h_estab'     => $h_estab,
          'tag'         => $tag,
          'anio'        => $anio_hasta,
          'anio_previo' => $anio_hasta - 1,
          // para hacer que la "cabecera" del "Grado", se corresponda con la modalidad del establecimiento...
          'tipo_curso'  => $tipo_curso,
          // 
          // SE USA ESTE PARA "HARCODERAR" el año 2020 como "2021"...
          // - SOLO para el boton 1
          'anio_header' => $anio_header,
          // 
          // para proceso "largo"...
          // - se usara para "ajustar" el numero de pagina de las que se vayan agregando...
          'in_longproc' => true,
          // 
          // codigo del ultimo "establecimiento" del loop...
          'cod_last1estab' => $cod_last1estab,
          // 
          // codigo del area...
          'cod_area' => $cod_area,
          // 
          // para rectificativa...
          'rectificativa' => $periodo,
          'h_dt1area'     => $h_dt1area,
          // 
          // para detalle de cabecera en columna "aprobado_ant" y "pedido"
          // - SOLO para 5to boton( min_pof_03 )...
          'aprobado_anterior' => $aprobado_ant,
          'aprobado_actual'   => $aprobado,
          );
        $genPdf = new ReportMin02($h_genPdf);
        $raw    = $genPdf->generarImpresion();
        
        // ... 
        return response($raw, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="rpt_min02.pdf"',
        ]);
    }

}
