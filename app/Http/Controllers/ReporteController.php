<?php

namespace App\Http\Controllers;

use Com\Tecnick\Pdf\Tcpdf;
use Illuminate\Http\Response;
use App\Libraries\WrapTcpLib;
use App\Libraries\Reports\ReportMin02;
use App\Models\AreaPofP;
use App\Models\DistritoEscolarPofP;
use App\Models\EstablecimientoPofP;
use App\Models\ModalidadPofP;
use Illuminate\Http\Request;

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
     * app/Libraries/config/report/cursos.yml y los datos en
     * App\Libraries\Reports\CursoReportData.
     */
    public function cursos(): Response
    {
        // IMPORTANTE: no definir K_PATH_FONTS aquí. El TCPDF clásico se
        // autoconfigura a sus fuentes bundled (helvetica, etc.).
        $pdf = new WrapTcpLib('P', 'mm', 'A4', true, 'UTF-8');

        // Directorio de los YAML de reportes (explícito, para no depender
        // del default interno de WrapTcpLib).
        $dirYaml = app_path('Libraries' . DIRECTORY_SEPARATOR . 'config'
            . DIRECTORY_SEPARATOR . 'report') . DIRECTORY_SEPARATOR;

        // Arma el reporte completo a partir del YAML.
        $pdf->ColoredTable('cursos.yml', false, $dirYaml);

        // 'S' devuelve el PDF como string (sin enviarlo directamente).
        $raw = $pdf->Output('cursos.pdf', 'S');

        return response($raw, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="cursos.pdf"',
        ]);
    }

    /**
     * Genera el reporte de "Planta Completa Valorizada" (min_pof_02)
     * usando el orquestador ReportMin02 sobre WrapTcpLib (TCPDF clásico).
     *
     * Definición del reporte en el YAML
     * app/Libraries/config/report/multig_min02_a4p.yml y los datos
     * (mockeados) en App\Libraries\Reports\ReportMin02.
     */
    public function rpt_min02(Request $request, $estab = null, $anio = null): Response
    {
        // IMPORTANTE: no definir K_PATH_FONTS aquí. El TCPDF clásico se
        // autoconfigura a sus fuentes bundled (helvetica, etc.).

        // El logo de la cabecera (mk_header) se busca en K_PATH_IMAGES.
        // Se define aquí, apuntando a un directorio del proyecto, para no
        // depender de vendor/ (que composer puede sobrescribir/borrar).
        if (! defined('K_PATH_IMAGES')) {
            define('K_PATH_IMAGES', resource_path('reports' . DIRECTORY_SEPARATOR . 'images') . DIRECTORY_SEPARATOR);
        }

        // $pdf = new WrapTcpLib('P', 'mm', 'A4', true, 'UTF-8');
        // 
        // // Arma el reporte completo a partir del YAML.
        // $pdf->ColoredTable('cursos.yml');
        // 
        // // 'S' devuelve el PDF como string (sin enviarlo directamente).
        // $raw = $pdf->Output('cursos.pdf', 'S');

        // ajusta el yaml de configuracion segun el listado...
        // ----------------------------------------------------
        /**
         * ir al yaml de configuracion :
           - C:\eid\Xampp_82\htdocs\desarrollo\Lrvl_curso_reporte\app\Libraries\config\report\multig_min02_a4p.yml
         */
        
        // ...        
        // ----------------------------------------------------------------
        // Parámetros estab/anio: por ruta (/reporte/min_02/{estab}/{anio})
        // o por query string (?estab=&anio=). Defaults: 1400 / 2020.
        // ----------------------------------------------------------------
        $estab = (int) ($estab ?? $request->query('estab', 1400));
        $anio  = (int) ($anio  ?? $request->query('anio', 2020));

        // Datos reales del establecimiento (MySQL, conexión 'doctrine').
        $estabRow = EstablecimientoPofP::find($estab);
        if (! $estabRow) {
            abort(404, "No existe el establecimiento {$estab}.");
        }

        // Nombres reales desde los catálogos (MySQL): Modalidad (664), Área
        // (650) y Distrito Escolar (657). Si falta el dato, cae a un fallback.

        // Modalidad (catálogo 664): nombre + área asociada.
        $cod_modalidad   = $estabRow->c658_664_id;
        $modRow          = $cod_modalidad ? ModalidadPofP::find($cod_modalidad) : null;
        $modalidadNombre = $modRow?->c664_descripcion ?? (string) $estabRow->modalidad;

        // El área se deriva de la modalidad (c664_650_id); el establecimiento
        // suele traerla vacía. Cae al campo del establecimiento si falta.
        $cod_area = $modRow?->c664_650_id ?: (string) $estabRow->area;
        $area     = ($cod_area !== ''
            ? AreaPofP::find($cod_area)?->c650_descripcion
            : null) ?? ('Área ' . $cod_area);

        // El "D.E." real es c657_de del distrito, no la FK c658_657_id.
        $distrito = $estabRow->c658_657_id
            ? DistritoEscolarPofP::find($estabRow->c658_657_id)
            : null;
        $deNumero = $distrito?->c657_de ?? $estabRow->c658_657_id;

        $ycfg_file      = 'multig_min02';
        $tag            = 'min_pof_02';
        $anio_previo    = $anio - 1;
        $tipo_curso     = 'Curso';
        $anio_header    = $anio;
        $in_longproc    = false;
        $cod_last1estab = $estab;

        $rectificativa  = '';
        $periodo        = '';

        // header : 
        // - 2 columnas y 4 renglones => seran : 1l/1r, ..., 4l/4r
        $det_periodo = 'Pof al 06/06/2026';
        $h_header    = array(
          'l1' => 'MINISTERIO DE EDUCACIÓN',
          'l2' => 'SUBSECRETARIA DE GESTIÓN ECONÓMICO FINANCIERA Y ADMINISTRACIÓN DE RECURSOS',
          'l3' => 'DIRECCIÓN GENERAL DE ADMINISTRACIÓN DE RECURSOS',
          'l4' => '',
          'r1' => array('detail' => '<strong>' . 'Área: ' . $area . '</strong>', 'toleft' => 60),
          'r2' => 'Planta Completa Valorizada',
          'r3' => '<strong>' . 'Año '. $anio_header . '</strong>',
          'r4' => array( 'detail' => '<strong>' . $det_periodo . '</strong>', 'toleft' => 250),
          );
        
        // datos del establecimiento...
        // - en adultos(A)
        // $h_estab = array(
        //    'id'             => 270,
        //    'nombre'         => 'DIRECCIÓN DEL ÁREA DEL ADULTO Y DEL ADOLESCENTE',
        //    'cue'            => '',
        //    'escuela'        => '',
        //    'direccion'      => 'CARLOS H PERETTE 770, 3º PISO, EDIFICIO WALSH',
        //    'codigo'         => 270,
        //    'cod_modalidad'  => 4,
        //    'modalidad'      => 'Nivel Primario Adultos',
        //    'cod_area'       => 'A',
        //    'area'           => 'Adultos',
        //    'cod_tipo_curso' => 13,
        //    'tipo_curso'     => 'Ciclo / Curso',
        //    'cod_de'         => 1,
        //    'de'             => 1,
        //    );
        // 
        // // ...
        // $h_dt1area = array(
        //   270,
        //   );

        // datos del establecimiento (desde el registro real en MySQL)...
        $h_estab = array(
           'id'             => $estabRow->c658_id,
           'nombre'         => $estabRow->c658_nombre,
           'cue'            => $estabRow->c658_cue,
           'escuela'        => $estabRow->c658_escuela,
           'direccion'      => $estabRow->c658_direccion,
           'codigo'         => $estabRow->c658_id,
           'cod_modalidad'  => $cod_modalidad,
           'modalidad'      => $modalidadNombre,          // nombre real (catálogo 664)
           'cod_area'       => $cod_area,
           'area'           => $area,                     // nombre real (catálogo 650)
           'cod_tipo_curso' => null,
           'tipo_curso'     => $tipo_curso,
           'cod_de'         => $deNumero,
           'de'             => $deNumero,                 // nº de D.E. real (catálogo 657)
           );

        // ...
        $h_dt1area = array(
          $estab,
          );
        
        $aprobado_ant = null;
        $aprobado     = null;
        
        // ...
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
          'anio'        => $anio,
          'anio_previo' => $anio_previo,
          // para hacer que la "cabecera" del "Grado", se corresponda con la modalidad del establecimiento...
          'tipo_curso'  => $tipo_curso,
          // 
          // SE USA ESTE PARA "HARCODERAR" el año 2020 como "2021"...
          // - SOLO para el boton 1
          'anio_header' => $anio_header,
          // 
          // para proceso "largo"...
          // - se usara para "ajustar" el numero de pagina de las que se vayan agregando...
          // - xahora en false => NO HAY MULTIPLES ESTABLECIMIENTOS...
          'in_longproc' => false,
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
            'Content-Disposition' => "inline; filename=\"rpt_min02_{$estab}_{$anio}.pdf\"",
        ]);
    }

    /**
     * Página demo (layout longproc): genera rpt_min02 para varios
     * establecimientos mostrando una barra de progreso (jQuery UI).
     */
    public function min02Demo(): \Illuminate\View\View
    {
        return view('reportes.min02_demo', [
            'ejemplos' => [1400, 3510, 3520],
            'anio'     => 2020,
        ]);
    }

}
