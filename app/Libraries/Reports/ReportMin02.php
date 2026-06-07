<?php
namespace App\Libraries\Reports;

use App\Libraries\WrapTcpLib;

// para los listados...
// 300 seconds = 5 minutes para el script...
ini_set('max_execution_time', 300);

/* 
// - TODO_01 :
//   - ver de tener una grilla con la cuadricula para ver como es la impresion
//     - configurar esta cuadricula para hacer los ajustes que se necesiten
//   - link a otra "page"...
//      link_to('Listado Pdf', $url_pdf, array('target' => '_blank')); 
//      
//      equivalente en generartor.yml : 
//      -------------------------------
//      object_actions:    
//        imprimirPdf:
//          label: 'Imprimir Pdf'
//          action: imprimirPdf
//          params:
//            target: '_blank'
//   
//   - corresponde al ticket #4813 : Listado de todos los establecimientos por área (para la aprobación de la Ministra)
//     - es continuacion del ticket #4667( Generación de listado de declaraciones juradas: POF - Planta comparativa )
//     - el "tag" del listado es : "min_pof_02"
//     - se lo maneja por "areas" => se lo hace desde el modulo "listado_area"
//     - es el 1º BOTON desde izq. => "Planta Completa Valorizada"

// - TODO_02 :
//   - PARA MIGRACION A LARAVEL => 
//     - pasar el metodo ->generarImpresion() al controlador...
//

// - TODO_03 :
//   - emular/mockear los siguientes metodos :
//     - PofPTable::getInstance()->get_sumt1cargo($codigo, $anio, $tipo_cargo); // , $rectificativa);
//     - PofPTable::getInstance()->get_hmix1cargo($codigo, $anio, true, $tipo_cargo, $subtotal,
//     - PofPTable::getInstance()->get_h1cargo1area($anio, $cod_area, $l_key, $h_totales);
// 

//      
*/

class ReportMin02
{
  public function __construct($h_listado = array()) 
  {
    !is_array($h_listado) ? $h_listado = array() : null;
    $this->h_listado = $h_listado;

    // $pg_size = 'LEGAL';
    $pg_size = array_key_exists('pg_size', $this->h_listado) ? $this->h_listado['pg_size'] : 'A4';
    $this->h_listado['pg_size'] = $pg_size;
    
    // segun si es legal o A4
    // - se guarda el ancho en mm
    $this->h_listado['pg_width'] = ($pg_size == 'LEGAL') ? 196: 190;
    
    $pg_orientation = array_key_exists('pg_orientation', $this->h_listado) ? $this->h_listado['pg_orientation'] : 'P';
    $this->h_listado['pg_orientation'] = $pg_orientation;

    $ycfg_file = array_key_exists('ycfg_file', $this->h_listado) ? $this->h_listado['ycfg_file'] : 'multi_grid';
    $this->h_listado['ycfg_file'] = $ycfg_file;
    
    $output_file = array_key_exists('output_file', $this->h_listado) ? $this->h_listado['output_file'] : 'reporte';
    $this->h_listado['output_file'] = $output_file;

    $destination = array_key_exists('destination', $this->h_listado) ? $this->h_listado['destination'] : 'I';
    $this->h_listado['destination'] = $destination;
    
    $watermark = array_key_exists('watermark', $this->h_listado) ? $this->h_listado['watermark'] : array();
    if( is_array($watermark) && array_key_exists('active', $watermark) && $watermark['active'] )
    { // esta activa la marca de agua...
      $l_prelim = true;
      $txt_wm   = array_key_exists('text', $watermark) ? $watermark['text'] : 'NO VALIDO - NO VALIDO - NO VALIDO - NO VALIDO';
      $size_wm  = array_key_exists('size', $watermark) ? $watermark['size'] : 20;
      $watermark['text'] = $txt_wm;
      $watermark['size'] = $size_wm;
    } else
    { // NO esta activa...
      $l_prelim = false;
    }
    $this->h_listado['watermark'] = $watermark;
    $this->h_listado['prelim']    = $l_prelim;
  }   
  
  public function generarImpresion()
  { // Pdf creation...
    // - metodo "original" para general la impresion  
    //   - pasarlo al controlador...
    
    // creacion del objeto de pdf...
    // -----------------------------
    $pdf = new WrapTcpLib($this->h_listado['pg_orientation'], 'mm', $this->h_listado['pg_size'], true, 'UTF-8', false);
    $pdf->gst_huser($this->h_listado);
  
    // ...
    $dir1yaml  = dirname(__FILE__) . '/../config/report/';
    $ycfg_file = $this->h_listado['ycfg_file'] . '_' . strtolower($this->h_listado['pg_size'] . 
      $this->h_listado['pg_orientation']) . '.yml';
    $pdf->ColoredTable($ycfg_file, $this->h_listado['prelim'], $dir1yaml, $this->h_listado['watermark']);
    
    // si es I( "muestra el reporte" )
    // - se lo "cambia" a S
    // - se devuelve la "string" y se lo muestra con el response desde el controlador...

    // Close and output PDF document
    if($this->h_listado['destination'] == 'F')
    {
      $pdf->Output($this->h_listado['output_file'] . '.pdf', $this->h_listado['destination']);
    } else
    { // si es I( "muestra el reporte" )
      // - se lo "manejo" como S
      //   - se devuelve la "string" y se lo muestra con el response desde el controlador...
      return $pdf->Output($this->h_listado['output_file'] . '.pdf', 'S');
    }
  }
  
  // ------------------------
  // funciones adicionales...
  // ------------------------

  static public function adj_fecha($fecha = null) 
  {
    return ( !is_null($fecha) ? ( substr($fecha, 8, 2) . '/' . substr($fecha, 5, 2) . '/' . substr($fecha, 0, 4) ) : '' );
  }

  static function get_cnt1data($pdf)
  { // trae el contador de datos... 
    return($pdf->get_cnt1data());
  }

  // ---------------------
  // MODIFICAR DESDE ACA...
  // ---------------------

  // ------------------
  // Header / footer...
  // ------------------

  static public function mk_header($pdf)
  {
    $h_user = $pdf->gst_huser();
   
    // ...
    $hmargin = $pdf->getMargins();
    $width   = $pdf->getPageContentWidth();
    
    // ...
    $margin1left = $hmargin['left'];
    $n_page      = $pdf->getAliasNumPage() . '/' . $pdf-> getAliasNbPages();
    $time        = date('d/m/Y');
    
    // ...
    $h_header = $h_user['h_header'];
    
    // manejo de nº de pagina...
    // -------------------------
    $in_longproc = array_key_exists('in_longproc', $h_user) && $h_user['in_longproc'];
    if($in_longproc)
    { // proceso "multiple"( proceso "largo" )...

      // - se usara este hash para manejar los nª de pagina...
      // - tmb para los parametros "get" adicionales...
      $tag     = $h_user['tag'];
      $yp_prev = '/procesos/h_page/';
      $h_page  = mylongprocActions::gst_hget1tag($tag, null, $yp_prev);
      
      // pagina actual del reporte...
      $n_act1page = $pdf->getPage();

      // pagina inicial x c/reporte...
      $n_init1page = array_key_exists('n_page', $h_page) ? $h_page['n_page'] : 1;
      
      // guardo la page a "imprimir"...
      $n_page1primt = $n_init1page + $n_act1page - 1;
      $h_page['n_page1primt'] = $n_page1primt;
      mylongprocActions::gst_hget1tag($tag, $h_page, $yp_prev);
    }
    
    // se imprime el logo...
    // ---------------------     
    // se procesa 1ro el logo para que la ubique ok...
    $headerdata = $pdf->getHeaderData();
    $pdf->Image(K_PATH_IMAGES . $headerdata['logo'], '', '', $headerdata['logo_width']);

    // // ...
    // $h_test = array(
    //   // ...
    //   'K_PATH_IMAGES' => K_PATH_IMAGES,
    //   'headerdata'    => $headerdata,
    //   );
    // $c_val2ck = "\n" . '$h_test(0) : ' . print_r( $h_test, TRUE);
    // $c_file = dirname(__FILE__).'/../../../storage/logs/info-debug.txt';
    // file_put_contents( $c_file, $c_val2ck, FILE_APPEND);
    // 
    //   [K_PATH_IMAGES] => C:\eid\Xampp_82\htdocs\desarrollo\Lrvl_curso_reporte\vendor\tecnickcom\tcpdf/ 
    //   [headerdata] => Array
    //       (
    //           [logo] => logo_ciudad.png
    //           [logo_width] => 15
    //           [title] => GOBIERNO DE LA CIUDAD DE BUENOS AIRES
    //           [string] => "DIRECCIÓN GENERAL DE PERSONAL DOCENTE Y NO DOCENTES"</br> 'Gerencia Operativa de Recursos Humanos Docentes.'
    //   
    //           [text_color] => Array
    //               (
    //                   [0] => 0
    //                   [1] => 0
    //                   [2] => 0
    //               )
    //   
    //           [line_color] => Array
    //               (
    //                   [0] => 0
    //                   [1] => 0
    //                   [2] => 0
    //               )
    //   
    //       )
    
    // ...
    $yh   = 10; // y inicial
    $n_dh = 5;  // separacion entre lineas...

    // ...
    foreach(array(1, 2, 3, 4) as $renglon)
    {
      // left...
      $pdf->SetFont("times", "", 8);
      $pdf->writeHTMLCell(0, 0, $margin1left + 18, $yh + ( $n_dh * ($renglon - 1) ), $h_header['l' . $renglon], 0, 0, 0, true, 'L');

      // right...     
      $pdf->SetFont("times", "", 8);
      if(!is_array($h_header['r' . $renglon]))
      {
        $pdf->writeHTMLCell(35, 0, $margin1left + $width - 40, $yh + ( $n_dh * ($renglon - 1) ), $h_header['r' . $renglon], 0, 0, 0, true, 'R');
      } else
      { // traego el "espacio hacia izq"( que arriba esta fijo en 40 )...
        $toleft = $h_header['r' . $renglon]['toleft'];
        $detail = $h_header['r' . $renglon]['detail'];
        $pdf->writeHTMLCell($toleft - 5, 0, $margin1left + $width - $toleft, $yh + ( $n_dh * ($renglon - 1) ), $detail, 0, 0, 0, true, 'R');
      }
    }

    // linea final...
    // --------------
    $renglon = 5;
    $pdf->Line($margin1left, $yh + ( $n_dh * ($renglon - 1) ), $margin1left + $width, $yh + ( $n_dh * ($renglon - 1) ));

    // se hace esto para ubicar el puntero "Y" donde corresponde...
    // - ejecuto un ->writeHTMLCell() QUE IMPRIME "EN BLANCO"...
    $pdf->writeHTMLCell(0, 0, $margin1left + 18, $yh + ( $n_dh * ($renglon - 1) ), '', 0, 0, 0, true, 'L');
  }
  
  static public function mk_footer($pdf)
  { // // completar el footer...
    // // if( $pdf->getPage() == 1 )
    // // { // en la 1er pagina, hace esto
    //    $pg_size = $pdf->gst_huser( null, 'pg_size');
    //    $nw = $pg_size == 'LEGAL' ? 196: 190;
    //    $c_titulo = 'Firma y Sello del Director del Establecimiento&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    //    $margin1left = 10;
    //    $y = $pdf->GetY() ;
    //    $pdf->writeHTMLCell($nw, 0, $margin1left, $y, $c_titulo, 0, 0, 0, true, 'R');
    //    
    //    // se coloca la linea punteada...
    //    $c_punt = '................................................................................................';
    //    $pdf->writeHTMLCell($nw, 0, $margin1left, $y - 3, $c_punt, 0, 0, 0, true, 'R');
    //    
    // /*  otras posibilidades de pie de página..
    // $c_fecha = 'Fecha';
    // $c_firma = 'Firma y Aclaración del Responsable';
    // $c_firma_docente = 'Firma y Aclaración del Docente';
    // $margin1left = 10;
    // $y = $pdf->GetY();
    // $pdf->writeHTMLCell(050, 0, $margin1left, $y, $c_firma_docente, 0, 0, 0, true, 'C');
    // $pdf->writeHTMLCell(190, 0, $margin1left, $y, $c_firma, 0, 0, 0, true, 'C');
    // $pdf->writeHTMLCell(160, 0, $margin1left, $y, $c_fecha, 0, 0, 0, true, 'R');
    //  */
    // 
    // // }
    
    // ...
    $h_user = $pdf->gst_huser();
    
    // ...
    $margin1left = 10;    
    $pg_width    = $h_user['pg_width'];
    
    // ...
    $y = $pdf->GetY();

    // linea de test... 
    // $pdf->Line($margin1left, $y - 50, $margin1left + $pg_width, $y - 50);
    
    // fecha y hora, a izquierda...
    $fecha = 'Fecha ' . self::adj_fecha(date('c')) . ' - Hora ' . date('H:i');
    $pdf->writeHTMLCell($pg_width / 2, 0, $margin1left, $y, $fecha, 0, 0, 0, true, 'L');
    
    // titulo y pagina, a derecha...
    $title = 'Planta Comparativa Valorizada';

    // manejo de nº de pagina...
    // -------------------------
    $in_longproc = array_key_exists('in_longproc', $h_user) && $h_user['in_longproc'];
    if(!$in_longproc)
    { // proceso "simple"...
      $pg = 'Página ' . $pdf->getPage();
    } else
    { // proceso "multiple"( proceso "largo" )...

      // - se usara este hash para manejar los nª de pagina...
      // - tmb para los parametros "get" adicionales...
      $tag     = $h_user['tag'];
      $yp_prev = '/procesos/h_page/';
      $h_page  = mylongprocActions::gst_hget1tag($tag, null, $yp_prev);

      // ajusto la pagina actual en "r1"...
      $pg = 'Página ' . $h_page['n_page1primt'];
    }
    $pdf->writeHTMLCell($pg_width / 2, 0, $margin1left, $y, $title . ' - ' . $pg, 0, 0, 0, true, 'R');
  }

  // ------------------
  // HOJAS CON DATOS...
  // ------------------

  static public function cbk_firma($pdf)
  { // grilla de firmas...
    // -------------------
    $data = array();

    // data a usar...
    $h_estab = $pdf->gst_huser( null, 'h_estab');
    
    // $style = 'style="border:1px solid black;"';
    $style = '';
    
    $c_mensaje = <<<EOD
<br/>  
<br/>  
<table $style align="left" width="100%" cellpadding="0" cellspacing="0">
 
 <tr>
  <td width="100%">&nbsp;</td>
 </tr>
 <tr>
  <td width="100%">&nbsp;</td>
 </tr>
 <tr>
  <td width="100%">&nbsp;</td>
 </tr>
 <tr>
  <td width="100%">&nbsp;</td>
 </tr>
 <tr>
  <td width="100%">&nbsp;</td>
 </tr>
 <tr>
  <td width="34%" align="center">.........................................................................</td>
  <td width="32%">&nbsp;</td>
  <td width="34%">&nbsp;</td>

 </tr>
 <tr>
  <td width="34%" align="center"><strong>Firma y Sello</strong></td>
  <td width="32%">&nbsp;</td>
  <td width="34%">&nbsp;</td>
 </tr>

 <tr>
  <td width="100%">&nbsp;</td> </tr>
 <tr>
  <td width="100%">&nbsp;</td> </tr>
 <tr>
  <td width="100%">&nbsp;</td> </tr>
 <tr>
  <td width="100%">&nbsp;</td> </tr>
 <tr>
  <td width="100%">&nbsp;</td> </tr>
 <tr>
  <td width="34%" align="center">.........................................................................</td>
  <td width="32%" align="center">.........................................................................</td>  
  <td width="34%" align="center">.........................................................................</td>
 </tr>
 <tr>
  <td width="34%" align="center"><strong>Director Establecimiento</strong></td>
  <td width="32%" align="center"><strong>Supervisor Escolar</strong></td>
  <td width="34%" align="center"><strong>Supervisor de Materias Especiales</strong></td>  
 </tr>
</table>
 
EOD;

    $data[] = array( 'dt_mensaje' => $c_mensaje );
    
    // ...
    return( $data );
  }

  static public function cbk_estab($pdf) 
  { // grilla con los datos del establecimiento...
    // -------------------------------------------
    $data = array();
    
    $h_estab = $pdf->gst_huser( null, 'h_estab');
    
    // ...
    $codigo    = $h_estab['codigo'   ];
    $nombre    = $h_estab['nombre'   ];
    $cue       = $h_estab['cue'      ];
    $de        = $h_estab['de'       ];
    $escuela   = $h_estab['escuela'  ];
    $modalidad = $h_estab['modalidad'];
    $area      = $h_estab['area'     ];
    $direccion = $h_estab['direccion'];
    
    // sin bordes...
    //$style = 'style="border:1px solid black;"';
    $style = '';
    
    // ...
    $c_mensaje = <<<EOD
<br/>
<br/>      
<table $style align="left" width="100%" cellpadding="0" cellspacing="0">   

 <tr>
  <td width="25%">D.E.:&nbsp;<strong>$de</strong></td>

  <td width="25%">Escuela:&nbsp;<strong>$escuela</strong></td>

  <td width="25%">Código Establecimiento:&nbsp;<strong>$codigo</strong></td>

  <td width="25%">C.U.E.:&nbsp;<strong>$cue</strong></td> 
 </tr>

 <tr>
  <td width="50%" colspan="2">Área:&nbsp;<strong>$area</strong></td>
  <td width="100%" colspan="2">Modalidad:&nbsp;<strong>$modalidad</strong></td>
 </tr>

 <tr>
  <td width="50%">&nbsp;<strong>$nombre</strong></td>
  <td width="50%">Dirección:&nbsp;<strong>$direccion</strong></td>
 </tr>

 <tr>
  <td width="100%">&nbsp;</td>
 </tr>

</table>
 
EOD;
    
    array_push($data, array('dt_mensaje' => $c_mensaje));
    return( $data );
  }
  
  // -----------------------------------
  // MANEJO DE CARGOS DE "CONDUCCION"...
  // -----------------------------------
    
  static public function cbk_head1conduccion($pdf)
  { // cabecera de la grilla de la planta de conduccion...
    // ---------------------------------------------------
    $data = array();
    
    // ... 
    $anio = $pdf->gst_huser( null, 'anio');

    // ...
    $h_estab = $pdf->gst_huser( null, 'h_estab');
    $codigo  = $h_estab['codigo'];

    // ...
    $tipo_cargo = 'C';
    
    // ...
    $rectificativa = $pdf->gst_huser( null, 'rectificativa');
    
    // del año actual...
    // - trae la suma...
    // $sum_data = PofPTable::getInstance()->get_sumt1cargo($codigo, $anio, $tipo_cargo); // , $rectificativa);

    // del año previo...
    // - trae la suma...
    // $sum_data1prev = PofPTable::getInstance()->get_sumt1cargo($codigo, $anio - 1, $tipo_cargo); // , $rectificativa);

    $sum_data      = 90;
    $sum_data1prev = 0 ;
    
    // ...
    $h_merge = array();
    if(($sum_data > 0) || ($sum_data1prev > 0)) 
    { // hay cargos y turnos, continua...
      $style     = '';
      $titulo    = 'Conducción';
      $c_mensaje = <<<EOD
<br/>      
<table $style align="left" width="100%" cellpadding="0" cellspacing="0">   

 <tr>
  <td width="100%">Tipo de Cargo :&nbsp;<strong>$titulo</strong></td>
 </tr>
</table>
 
EOD;
    
      array_push($h_merge, array('dt_mensaje' => $c_mensaje));
    }
    return( $h_merge );    
  }

  static public function cbk_conduccion($pdf) 
  { // grilla con los datos de la planta de conduccion...
    // --------------------------------------------------
    $data = array();

    // ... 
    $anio = $pdf->gst_huser( null, 'anio');

    // ...
    $h_estab = $pdf->gst_huser( null, 'h_estab');
    $codigo  = $h_estab['codigo'];

    // ...
    $tipo_cargo = 'C';

    // ...
    $rectificativa = $pdf->gst_huser( null, 'rectificativa');
    
    // ...
    $l_diff  = false;
    // $h_merge = PofPTable::getInstance()->get_hmix1cargo($codigo, $anio, true, $tipo_cargo, $subtotal, 
    //   $l_diff, null, true); // , $rectificativa);

    $subtotal = 0;
    $h_merge  = array(
      '0000_75_C' => array(
              'cargo_id' => 75,
              'cargo1d' => '75 - SUPERVISOR D.G.E.G.P EDUCACION INCLUSIVA',
              'puntaje' => null,
              'turno_id' => 'C',
              'turno1d' => 'Completo',
              'cnt_previa' => 0,
              'cnt_actual' => 1,
              'diff' => 1      ,
              'valoriz' => 0   ,
          ),
      '0000_607_C' => array(
              'cargo_id' => 607,
              'cargo1d' => '607 - SUPERVISOR  D.G.E.G.P PRIMARIO',
              'puntaje' => null,
              'turno_id' => 'C',
              'turno1d' => 'Completo',
              'cnt_previa' => 0 ,
              'cnt_actual' => 23,
              'diff' => 23      ,
              'valoriz' => 0    ,
          ),
      '0000_606_C' => array(
              'cargo_id' => 606,
              'cargo1d' => '606 - SUPERVISOR  D.G.E.G.P. TERCIARIA',
              'puntaje' => null,
              'turno_id' => 'C',
              'turno1d' => 'Completo',
              'cnt_previa' => 0 ,
              'cnt_actual' => 10,
              'diff' => 10      ,
              'valoriz' => 0    ,
          ),
      '0000_605_C' => array(
              'cargo_id' => 605,
              'cargo1d' => '605 - SUPERVISOR  D.G.E.G.P. MEDIA',
              'puntaje' => null,
              'turno_id' => 'C',
              'turno1d' => 'Completo',
              'cnt_previa' => 0 ,
              'cnt_actual' => 18,
              'diff' => 18      ,
              'valoriz' => 0    ,
          ),
      '0000_53_C' => array(
              'cargo_id' => 53,
              'cargo1d' => '53 - SUPERVISOR D.G.E.G.P REGISTRO INSTITUCIONES EDUCATIVAS ASISTENCIALES',
              'puntaje' => null,
              'turno_id' => 'C',
              'turno1d' => 'Completo',
              'cnt_previa' => 0,
              'cnt_actual' => 5,
              'diff' => 5      ,
              'valoriz' => 0   ,
          ),
      '0000_3743_C' => array(
              'cargo_id' => 3743,
              'cargo1d' => '3743 - SUPERVISOR D.G.E.G.P. DE EDUCACIÓN SUPERIOR SALUD',
              'puntaje' => null,
              'turno_id' => 'C',
              'turno1d' => 'Completo',
              'cnt_previa' => 0,
              'cnt_actual' => 1,
              'diff' => 1      ,
              'valoriz' => 0   ,
          ),
      '0000_2988_C' => array(
              'cargo_id' => 2988,
              'cargo1d' => '2988 - SUPERVISOR D.G.E.G.P. DE ORGANIZACIÓN ESCOLAR',
              'puntaje' => null,
              'turno_id' => 'C',
              'turno1d' => 'Completo',
              'cnt_previa' => 0 ,
              'cnt_actual' => 11,
              'diff' => 11      ,
              'valoriz' => 0    ,
          ),
      '0000_2987_C' => array(
              'cargo_id' => 2987,
              'cargo1d' => '2987 - SUPERVISOR D.G.E.G.P. TÉCNICO PEDAGÓGICA',
              'puntaje' => null,
              'turno_id' => 'C',
              'turno1d' => 'Completo',
              'cnt_previa' => 0,
              'cnt_actual' => 6,
              'diff' => 6      ,
              'valoriz' => 0   ,
          ),
      '0000_2857_C' => array(
              'cargo_id' => 2857,
              'cargo1d' => '2857 - SUPERVISOR D.G.E.G.P. DE LA EDUCACIÓN ESPECIAL',
              'puntaje' => null,
              'turno_id' => 'C',
              'turno1d' => 'Completo',
              'cnt_previa' => 0,
              'cnt_actual' => 4,
              'diff' => 4      ,
              'valoriz' => 0   ,
          ),
      '0000_2856_C' => array(
              'cargo_id' => 2856,
              'cargo1d' => '2856 - SUPERVISOR D.G.E.G.P. EDUCACIÓN INICIAL',
              'puntaje' => null,
              'turno_id' => 'C',
              'turno1d' => 'Completo',
              'cnt_previa' => 0  ,
              'cnt_actual' => 11 ,
              'diff' => 11       ,
              'valoriz' => 0     ,
          ),
      );
      
    if(count($h_merge) > 0)
    { // agrego el subtotal...
      $pdf->gst_huser( null, 'subtotal_' . $tipo_cargo, $subtotal);
    }
    
    // ...
    return( $h_merge ); 
  }

  static public function cbk_subt1conduccion($pdf)
  { // grilla con los datos de los "subtotales" para la planta de conduccion...
    // ------------------------------------------------------------------------
    $data = array();
    
    // ... 
    $anio = $pdf->gst_huser( null, 'anio');

    // ...
    $h_estab = $pdf->gst_huser( null, 'h_estab');
    $codigo  = $h_estab['codigo'];

    // ...
    $tipo_cargo = 'C';

    // ...
    $rectificativa = $pdf->gst_huser( null, 'rectificativa');
    
    // del año actual...
    // - trae la suma...
    // $sum_data = PofPTable::getInstance()->get_sumt1cargo($codigo, $anio, $tipo_cargo); // , $rectificativa);

    // del año previo...
    // - trae la suma...
    // $sum_data1prev = PofPTable::getInstance()->get_sumt1cargo($codigo, $anio - 1, $tipo_cargo); // , $rectificativa);

    $sum_data      = 90;
    $sum_data1prev = 0 ;
    
    // ...
    $h_merge = array();    
    if(($sum_data > 0) || ($sum_data1prev > 0)) 
    { // hay cargos y turnos, continua...
      $key = 'subtotal'; 
      $h_merge[$key] = array(
        // cargo...
        'cargo_id' => '',
        'cargo1d'  => '',
        // turno...
        'turno_id' => '',
        'turno1d'  => '<strong>SUBTOTALES</strong>',        
        // cantidades...
        'cnt_previa' => '<strong>' . $sum_data1prev . '</strong>',
        'cnt_actual' => '<strong>' . $sum_data . '</strong>',
        );
    
      // ...
      $diff     = $sum_data - $sum_data1prev;
      $subtotal = $pdf->gst_huser( null, 'subtotal_' . $tipo_cargo);
      $h_merge[$key]['diff']    = '<strong>' . $diff . '</strong>';
      $h_merge[$key]['valoriz'] = '<strong>' . $subtotal . '</strong>';
    }
    return( $h_merge );    
  }

  // -----------------------------------
  // MANEJO DE CARGOS DE "EJECUCION"...
  // -----------------------------------
    
  static public function cbk_head1ejecucion($pdf)
  { // cabecera de la grilla de la planta de ejecucion...
    // ---------------------------------------------------
    $data = array();
    
    // ... 
    $anio = $pdf->gst_huser( null, 'anio');

    // ...
    $h_estab = $pdf->gst_huser( null, 'h_estab');
    $codigo  = $h_estab['codigo'];

    // ...
    $tipo_cargo = 'E';

    // ...
    $rectificativa = $pdf->gst_huser( null, 'rectificativa');
    
    // del año actual...
    // - trae la suma...
    // $sum_data = PofPTable::getInstance()->get_sumt1cargo($codigo, $anio, $tipo_cargo); // , $rectificativa);

    // del año previo...
    // - trae la suma...
    // $sum_data1prev = PofPTable::getInstance()->get_sumt1cargo($codigo, $anio - 1, $tipo_cargo); // , $rectificativa);

    $sum_data      = 0;
    $sum_data1prev = 0;

    // ...
    $h_merge = array();
    if(($sum_data > 0) || ($sum_data1prev > 0)) 
    { // hay cargos y turnos, continua...
      $style     = '';
      $titulo    = 'Ejecución';
      $c_mensaje = <<<EOD
<br/>      
<table $style align="left" width="100%" cellpadding="0" cellspacing="0">   

 <tr>
  <td width="100%">Tipo de Cargo :&nbsp;<strong>$titulo</strong></td>
 </tr>
</table>
 
EOD;
    
      array_push($h_merge, array('dt_mensaje' => $c_mensaje));
    }
    return( $h_merge );    
  }

  static public function cbk_ejecucion($pdf)
  { // grilla con los datos de la planta de ejecucion...
    // -------------------------------------------------
    $data = array();
    
    // ... 
    $anio = $pdf->gst_huser( null, 'anio');

    // ...
    $h_estab = $pdf->gst_huser( null, 'h_estab');
    $codigo  = $h_estab['codigo'];
    
    // ...
    $tipo_cargo = 'E';

    // ...
    $rectificativa = $pdf->gst_huser( null, 'rectificativa');
    
    // ...
    $l_diff  = false;
    // $h_merge = PofPTable::getInstance()->get_hmix1cargo($codigo, $anio, true, $tipo_cargo, $subtotal, 
    //   $l_diff, null, true); // , $rectificativa);
    $h_merge = array();  
    if(count($h_merge) > 0)
    { // agrego el subtotal...
      $pdf->gst_huser( null, 'subtotal_' . $tipo_cargo, $subtotal);
    }

    return( $h_merge );    
  }

  static public function cbk_subt1ejecucion($pdf)
  { // grilla con los datos de los "subtotales" para la planta de ejecucion...
    // -----------------------------------------------------------------------
    $data = array();
    
    // ... 
    $anio = $pdf->gst_huser( null, 'anio');

    // ...
    $h_estab = $pdf->gst_huser( null, 'h_estab');
    $codigo  = $h_estab['codigo'];

    // ...
    $tipo_cargo = 'E';

    // ...
    $rectificativa = $pdf->gst_huser( null, 'rectificativa');
    
    // del año actual...
    // - trae la suma...
    // $sum_data = PofPTable::getInstance()->get_sumt1cargo($codigo, $anio, $tipo_cargo); // , $rectificativa);

    // del año previo...
    // - trae la suma...
    // $sum_data1prev = PofPTable::getInstance()->get_sumt1cargo($codigo, $anio - 1, $tipo_cargo); // , $rectificativa);

    $sum_data      = 0;
    $sum_data1prev = 0;
    
    // ...
    $h_merge = array();    
    if(($sum_data > 0) || ($sum_data1prev > 0)) 
    { // hay cargos y turnos, continua...
      $key = 'subtotal'; 
      $h_merge[$key] = array(
        // cargo...
        'cargo_id' => '',
        'cargo1d'  => '',
        // turno...
        'turno_id' => '',
        'turno1d'  => '<strong>SUBTOTALES</strong>',
        // cantidades...
        'cnt_previa' => '<strong>' . $sum_data1prev . '</strong>',
        'cnt_actual' => '<strong>' . $sum_data . '</strong>',
        );
    
      // ...
      $diff     = $sum_data - $sum_data1prev;
      $subtotal = $pdf->gst_huser( null, 'subtotal_' . $tipo_cargo);      
      $h_merge[$key]['diff']    = '<strong>' . $diff . '</strong>';
      $h_merge[$key]['valoriz'] = '<strong>' . $subtotal . '</strong>';
    }
    return( $h_merge );    
  }

  // --------------------------------------
  // MANEJO DE CARGOS DE "HORAS CATEDRA"...
  // --------------------------------------
    
  static public function cbk_head1hcatedra($pdf)
  { // cabecera de la grilla de la planta de hs catedra...
    // ---------------------------------------------------
    $data = array();
    
    // ... 
    $anio = $pdf->gst_huser( null, 'anio');

    // ...
    $h_estab = $pdf->gst_huser( null, 'h_estab');
    $codigo  = $h_estab['codigo'];

    // ...
    $tipo_cargo = 'H';

    // ...
    $rectificativa = $pdf->gst_huser( null, 'rectificativa');
    
    // del año actual...
    // - trae la suma...
    // $sum_data = PofPTable::getInstance()->get_sumt1cargo($codigo, $anio, $tipo_cargo); // , $rectificativa);

    // del año previo...
    // - trae la suma...
    // $sum_data1prev = PofPTable::getInstance()->get_sumt1cargo($codigo, $anio - 1, $tipo_cargo); // , $rectificativa);

    $sum_data      = 0;
    $sum_data1prev = 0;
    
    // ...
    $h_merge = array();
    if(($sum_data > 0) || ($sum_data1prev > 0)) 
    { // hay cargos y turnos, continua...
      $style     = '';
      $titulo    = 'Horas Cátedra';
      $c_mensaje = <<<EOD
<br/>      
<table $style align="left" width="100%" cellpadding="0" cellspacing="0">   

 <tr>
  <td width="100%">Tipo de Cargo :&nbsp;<strong>$titulo</strong></td>
 </tr>
</table>
 
EOD;
    
      array_push($h_merge, array('dt_mensaje' => $c_mensaje));
    }
    return( $h_merge );    
  }

  static public function cbk_hcatedra($pdf) 
  { // grilla con los datos de la planta de horas catedra...
    // -----------------------------------------------------
    $data = array();
    
    // ... 
    $anio = $pdf->gst_huser( null, 'anio');

    // ...
    $h_estab = $pdf->gst_huser( null, 'h_estab');
    $codigo  = $h_estab['codigo'];

    // ...
    $tipo_cargo = 'H';

    // ...
    $rectificativa = $pdf->gst_huser( null, 'rectificativa');
    
    // ...
    $l_diff  = false;
    // $h_merge = PofPTable::getInstance()->get_hmix1cargo($codigo, $anio, true, $tipo_cargo, $subtotal,
    //   $l_diff, null, true); // , $rectificativa);
    $h_merge = array();  
    if(count($h_merge) > 0)
    { // agrego el subtotal...
      $pdf->gst_huser( null, 'subtotal_' . $tipo_cargo, $subtotal);
    }
    
    return( $h_merge );    
  }  

  static public function cbk_subt1hcatedra($pdf)
  { // grilla con los datos de los "subtotales" para la planta de horas catedra...
    // ---------------------------------------------------------------------------
    $data = array();
    
    // ... 
    $anio = $pdf->gst_huser( null, 'anio');

    // ...
    $h_estab = $pdf->gst_huser( null, 'h_estab');
    $codigo  = $h_estab['codigo'];

    // ...
    $tipo_cargo = 'H';

    // ...
    $rectificativa = $pdf->gst_huser( null, 'rectificativa');
    
    // del año actual...
    // - trae la suma...
    // $sum_data = PofPTable::getInstance()->get_sumt1cargo($codigo, $anio, $tipo_cargo); // , $rectificativa);

    // del año previo...
    // - trae la suma...
    // $sum_data1prev = PofPTable::getInstance()->get_sumt1cargo($codigo, $anio - 1, $tipo_cargo); // , $rectificativa);

    $sum_data      = 0;
    $sum_data1prev = 0;
    
    // ...
    $h_merge = array();    
    if(($sum_data > 0) || ($sum_data1prev > 0)) 
    { // hay cargos y turnos, continua...
      $key = 'subtotal'; 
      $h_merge[$key] = array(
        // cargo...
        'cargo_id' => '',
        'cargo1d'  => '',
        // turno...
        'turno_id' => '',
        'turno1d'  => '<strong>SUBTOTALES</strong>',
        // cantidades...
        'cnt_previa' => '<strong>' . $sum_data1prev . '</strong>',
        'cnt_actual' => '<strong>' . $sum_data . '</strong>',
        );
    
      // ...
      $diff     = $sum_data - $sum_data1prev;
      $subtotal = $pdf->gst_huser( null, 'subtotal_' . $tipo_cargo);
      $h_merge[$key]['diff']    = '<strong>' . $diff . '</strong>';
      $h_merge[$key]['valoriz'] = '<strong>' . $subtotal . '</strong>';
    }
    return( $h_merge );    
  }

  static public function cbk_total($pdf)
  { // grilla con los datos del "total" ...
    // ------------------------------------
    $data = array();
    
    // ... 
    $anio = $pdf->gst_huser( null, 'anio');

    // ...
    $h_estab = $pdf->gst_huser( null, 'h_estab');
    $codigo  = $h_estab['codigo'];

    // ...
    $rectificativa = $pdf->gst_huser( null, 'rectificativa');
    
    // del año actual...
    // - trae la suma...
    // $sum_data = PofPTable::getInstance()->get_sumt1cargo($codigo, $anio, null); // , $rectificativa);

    // del año previo...
    // - trae la suma...
    // $sum_data1prev = PofPTable::getInstance()->get_sumt1cargo($codigo, $anio - 1, null); // , $rectificativa);
    
    // ...
    $sum_data      = 90;
    $sum_data1prev = null;
    
    // ...
    $h_merge = array();    
    if(($sum_data > 0) || ($sum_data1prev > 0)) 
    { // hay cargos y turnos, continua...
      $key = 'subtotal'; 
      $h_merge[$key] = array(
        // cargo...
        'cargo_id' => '',
        'cargo1d'  => '',
        // turno...
        'turno_id' => '',
        'turno1d'  => '<strong>TOTAL</strong>',
        // cantidades...
        'cnt_previa' => '<strong>' . $sum_data1prev . '</strong>',
        'cnt_actual' => '<strong>' . $sum_data . '</strong>',
        );
    
      // ...
      $diff  = $sum_data - $sum_data1prev;
      $total = 0;
      
      // ...
      $h_user = $pdf->gst_huser();
      foreach(array('C', 'E', 'H') as $tipo_cargo)
      {
        if(array_key_exists('subtotal_' . $tipo_cargo, $h_user))
        {
          $total += $h_user['subtotal_' . $tipo_cargo];
        }
      }
      $h_merge[$key]['diff']    = '<strong>' . $diff . '</strong>';
      $h_merge[$key]['valoriz'] = '<strong>' . $total . '</strong>';
    }
    return( $h_merge );    
  }

  static public function cbk_total1final($pdf)
  { // grilla con los datos del "total" y "mensual" por area...
    // --------------------------------------------------------
    // - ticket #4979
    //   - Agregar al final del listado de "Planta completa valorizada" un total general por área del PI mensual y 
    //     del PI anual. Estos totales deben coincidir con los totales Mensual PI y Anual PI respectivamente, 
    //     mostrados al final del listado "Resumen completo valorizado por año".
    $data = array();
    
    // ... 
    $anio     = $pdf->gst_huser( null, 'anio');
    $cod_area = $pdf->gst_huser( null, 'cod_area');
    
    // ...
    $h_estab = $pdf->gst_huser( null, 'h_estab');
    $codigo  = $h_estab['codigo'];

    // codigo del ultimo establecimiento...
    $cod_last1estab = $pdf->gst_huser( null, 'cod_last1estab');
    if($codigo == $cod_last1estab)
    { // se esta en el ultimo establecimiento...

      // ...
      $rectificativa = $pdf->gst_huser( null, 'rectificativa');
      if(!is_numeric($rectificativa))
      { // normal...
        // - se trae los datos...
        $l_key  = true;
        // $h_data = PofPTable::getInstance()->get_h1cargo1area($anio, $cod_area, $l_key, $h_totales);
        $h_data = array(
          '2311_3299' => array(
                  'de_9' => 0,
                  'cargo_id' => 3299,
                  'cargo1d' => 'SUPERVISOR',
                  'cod_de' => 9,
                  'puntaje' => 2311.00,
                  'total' => 0,
                  'pj_mes' => 0.00,
                  'pj_anio' => 0.00,
                  'pj_pesos' => 0.00,
              ),
          '0000_53' => array(
                  'de_9' => 5,
                  'cargo_id' => 53,
                  'cargo1d' => 'SUPERVISOR D.G.E.G.P REGISTRO INSTITUCIONES EDUCATIVAS ASISTENCIALES',
                  'cod_de' => 9,
                  'puntaje' => 0.00,
                  'total' => 5,
                  'pj_mes' => 0.00,
                  'pj_anio' => 0.00,
                  'pj_pesos' => 0.00,
              ),
          '0000_75' => array(
                  'de_9' => 1     ,
                  'cargo_id' => 75,
                  'cargo1d' => 'SUPERVISOR D.G.E.G.P EDUCACION INCLUSIVA',
                  'cod_de' => 9     ,
                  'puntaje' => 0.00 ,
                  'total' => 1      ,
                  'pj_mes' => 0.00  ,
                  'pj_anio' => 0.00 ,
                  'pj_pesos' => 0.00,
              ),
          '0000_605' => array(
                  'de_9' => 18      ,
                  'cargo_id' => 605 ,
                  'cargo1d' => 'SUPERVISOR  D.G.E.G.P. MEDIA',
                  'cod_de' => 9     ,
                  'puntaje' => 0.00 ,
                  'total' => 18     ,
                  'pj_mes' => 0.00  ,
                  'pj_anio' => 0.00 ,
                  'pj_pesos' => 0.00,
              ),
          '0000_606' => array(
                  'de_9' => 10      ,
                  'cargo_id' => 606 ,
                  'cargo1d' => 'SUPERVISOR  D.G.E.G.P. TERCIARIA',
                  'cod_de' => 9     ,
                  'puntaje' => 0.00 ,
                  'total' => 10     ,
                  'pj_mes' => 0.00  ,
                  'pj_anio' => 0.00 ,
                  'pj_pesos' => 0.00,
              ),
          '0000_607' => array(
                  'de_9' => 23     ,
                  'cargo_id' => 607,
                  'cargo1d' => 'SUPERVISOR  D.G.E.G.P PRIMARIO',
                  'cod_de' => 9     ,
                  'puntaje' => 0.00 ,
                  'total' => 23     ,
                  'pj_mes' => 0.00  ,
                  'pj_anio' => 0.00 ,
                  'pj_pesos' => 0.00,
              ),
          '0000_2856' => array(
                  'de_9' => 11      ,
                  'cargo_id' => 2856,
                  'cargo1d' => 'SUPERVISOR D.G.E.G.P. EDUCACIÓN INICIAL',
                  'cod_de' => 9      ,
                  'puntaje' => 0.00  ,
                  'total' => 11      ,
                  'pj_mes' => 0.00   ,
                  'pj_anio' => 0.00  ,
                  'pj_pesos' => 0.00 ,
              ),
          '0000_2857' => array(
                  'de_9' => 4       ,
                  'cargo_id' => 2857,
                  'cargo1d' => 'SUPERVISOR D.G.E.G.P. DE LA EDUCACIÓN ESPECIAL',
                  'cod_de' => 9     ,
                  'puntaje' => 0.00 ,
                  'total' => 4      ,
                  'pj_mes' => 0.00  ,
                  'pj_anio' => 0.00 ,
                  'pj_pesos' => 0.00,
              ),
          '0000_2987' => array(
                  'de_9' => 6       ,
                  'cargo_id' => 2987,
                  'cargo1d' => 'SUPERVISOR D.G.E.G.P. TÉCNICO PEDAGÓGICA',
                  'cod_de' => 9      ,
                  'puntaje' => 0.00  ,
                  'total' => 6       ,
                  'pj_mes' => 0.00   ,
                  'pj_anio' => 0.00  ,
                  'pj_pesos' => 0.00 ,
              ),
          '0000_2988' => array(
                  'de_9' => 11      ,
                  'cargo_id' => 2988,
                  'cargo1d' => 'SUPERVISOR D.G.E.G.P. DE ORGANIZACIÓN ESCOLAR',
                  'cod_de' => 9      ,
                  'puntaje' => 0.00  ,
                  'total' => 11      ,
                  'pj_mes' => 0.00   ,
                  'pj_anio' => 0.00  ,
                  'pj_pesos' => 0.00 ,
              ),
          '0000_3743' => array(
                  'de_9' => 1        ,
                  'cargo_id' => 3743 ,
                  'cargo1d' => 'SUPERVISOR D.G.E.G.P. DE EDUCACIÓN SUPERIOR SALUD',
                  'cod_de' => 9      ,
                  'puntaje' => 0.00  ,
                  'total' => 1       ,
                  'pj_mes' => 0.00   ,
                  'pj_anio' => 0.00  ,
                  'pj_pesos' => 0.00 ,
              ),
        );
        
        // ...
        $h_totales = array(
            'mensual_pj'  => 0,
            'anual_pj'    => 0,
            'anual_pesos' => 0,
            );

      } else
      { // rectificativa...
        // - se trae los datos...
        $h_dt1area = $pdf->gst_huser( null, 'h_dt1area');
      
        $l_key  = true;
        // $h_data = PofPTable::getInstance()->get_h1cargo1area($anio, $cod_area, $l_key, $h_totales,
        //   null, $rectificativa, $h_dt1area);
        $h_data = array();
      }
      
      // $h_totales : Array
      // (
      //     [mensual_pj] => 1885066
      //     [anual_pj] => 22620792
      //     [anual_pesos] => 0
      // )

      // $style = 'style="border:1px solid black;"';
      $style = '';

      $pi_mensual = number_format($h_totales['mensual_pj'], 2, ',', '.');
      $pi_anual   = number_format($h_totales['anual_pj'], 2, ',', '.');
      
      // ... 
      $c_mensaje = <<<EOD
<br/>
<br/>      
<table $style align="left" width="100%" cellpadding="0" cellspacing="0">   

 <tr>
  <td width="100%">TOTAL GENERAL POR ÁREA DEL PI MENSUAL :&nbsp;<strong>$pi_mensual</strong></td>
 </tr>

 <tr>
  <td width="100%">TOTAL GENERAL POR ÁREA DEL PI ANUAL :&nbsp;<strong>$pi_anual</strong></td>
 </tr>

 <tr>
  <td width="100%">&nbsp;</td>
 </tr>

</table>
 
EOD;

      $data[] = array( 'dt_mensaje' => $c_mensaje );
    }
    return( $data );    
  }

  static public function hd_end1report($pdf)
  { // se ejecuta al "final" del reporte...
    // - coordinar con ->mk_header() para manejar las paginas con "multi-reportes"
    //   - ver de guardar el total de paginas" en el "cache" 
    
    // ...
    $h_listado  = $pdf->gst_hreport();    

    // ...
    $in_longproc = array_key_exists('in_longproc', $h_listado) && $h_listado['in_longproc'];
    if($in_longproc)
    { // proceso "multiple"( proceso "largo" )...
      
      // - se usara este hash para manejar los nª de pagina...
      $tag     = $h_listado['tag'];
      $yp_prev = '/procesos/h_page/';
      $h_page  = mylongprocActions::gst_hget1tag($tag, null, $yp_prev);

      // nº total de paginas del reporte...
      $n_tot1page = $pdf->getPage();

      // nª de paginas "inicial" del reporte "anterior"...
      $n_init1page = array_key_exists('n_page', $h_page) ? $h_page['n_page'] : 1;

      // - carga el nª de pagina "inicial" para proximo listado...
      $h_page['n_page'] = $n_init1page + $n_tot1page;
      mylongprocActions::gst_hget1tag($tag, $h_page, $yp_prev);
    }
  }

}
