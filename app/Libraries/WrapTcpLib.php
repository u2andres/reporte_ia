<?php
namespace App\Libraries;

use TCPDF;
use Symfony\Component\Yaml\Yaml;

//============================================================+
// File name   : WrapTcpLib.php
// Begin       : 
// Last Update : 
//
// Description : 
// 
// Author: Andrés Zotelo
//
//============================================================+

// TODO 0 : esta en adjunto de ticket #24( todo_reporte_0.txt )
// --------

// 7/4/2015: Cambios hechos por Charly: Se agregó hash watermark en metodo ColoredTable

// TODO 1 : a) OK!!! => ver como hacer para "imprimir" un memo y que quede acomodado aun cuando ocupe + de 1 pagina.
// -------- a.1) OK!!! =>chequea si alguna celda es de lectura "memo", si es asi => se deja solo esa para usar como memo, 
//    laa otras celdas NO SE TIENEN EN CUENTA.
//    cell:
//      cell_1: 
//          - - - 
//        is_memo: true
// 
// b) OK!!! => agregar lo necesario para que h_margin['top'] sea "variable" de acuerdo con el header.
// b.1) NO SE HACE!!! => se lo hace agregando las "calc1top:" y "delta1top:" en "margin:"
//      NOTA : se QUITO el manejo anterior y se lo reemplaza agregando una "grilla" asociada en el "header"
//      ------ esta grilla sera la parte "variable" del header.
//  
// c) OK!!! => agregar el callback "de scaneo" para el "grupo de grillas"(group_grid) y tener entonces un foreach 
// c.1) OK!!! => para acceder a los datos en los callbacks usar : $pdf->get_dt1grpgrid();
// c.2) OK!!! => testear con los integrantes...
// c.3) ver de probar esto con todo lo nuevo agregado.
// c.4) agregar el manejo de "meter" de proceso cuando se trata de muchas paginas...
//      - ver como manejar esto con el sobretipo "reporteador".      
// 
// d) ver que al calcular el espacio para una grilla, si esta tiene "cabecera" => que la agregue al calculo.
// e) ver de agregar un flag para tener 2 tipos de grillas :
//    - que se "auto-acomoden", usando <table> ...</table0> de html y devolviendo esa "string"
//      las grillas usadas desde el yml son de este tipo
//    - que salgan "directas" tras la grilla previa y no hagan calculos de "acomodamiento".
//      ejemplo de esta es el header y footer actual
//    - definir un flag por grilla : is_auto1adj: true(xdf) para que se auto-ajuste 
//    - ver como es el manejo de variables y de cuales se dispone.
//    - en caso de definir un header / footer "auto-ajustable" ver que se calculen en forma automatica las alturas
//      de ambos.
//    - ver como funciona todo lo anterior y que debe armarse o no...
// f) armar un task para que genere un reporte "admin" en un modulo, en ppio con parametros similares al doctrine:generate-admin 
//    - basicos para armar lo minimo y que un reporte funcione
//    - ver como hacer para con "tasks" y "llamados a task" armar lo que sea necesario dentro del "file-system" de sf.
//    - ver como combinar esto con los yaml-tree y las configuraciones, tratar de definir algo que NO se complejice.
// g) OK!!! => se puede tener una "cabecera" variable
//    - si se coloca la "key" entre %'s( %<key>% ) => se busca la key en el $this->h_user y se coloca el valor "encontrado" en la cabecera.
// h) ver de configurar el listador contra un sobre-tipo "Reporter"...
//    - tener los metodos "pasados" a la tabla de "configuracion"( ws_cfg_t_yaml1tree )
//      - ver como conviene hacer, pasar la clase como se ha hacho y analizar funcionamiento, pros y cons.
//      - revisar en ppio los metodos :
//        - para "Manejo de configuracion desde yaml" :
//          - ... 
//        - para "Manejo de Parametros de zonas" :
//          - ... 
//    - usar lo anterior para poder "configurar" el sobre-tipo "reporteador".
//      - los "yml de configuracion"( xej : de .../apps/frontend/modules/listado/config/multi_grid_a4p.yml ) 
//        usados actually puede "pasarse" directamente a la tabla de "configuracion"( ws_cfg_t_yaml1tree )
// 
//      - los "callbacks de configuracion"( xej : de ...apps/frontend/modules/listado/lib/generarPdf.class.php )
//        usados actually tmb, pueden "pasarse" directamente a la tabla de "configuracion"( ws_cfg_t_yaml1tree )
//    - armar los ejemplos para los distintos "casos" de reportes y tener asi "explicados" las distintas posibilidades
//      del reporteador.
//    - ver de "achicar" el codigo, pasando a template de codigo los metodos de seteo y ajuste del reporte
//      - tras esto ver como queda y que conviene hacer...
//      - dado que en general generan hashes, usar el sobretipo "manejo de hash" para guardar.
//      - el sobretipo reporteador tendra "agrupados" bajo su ypath estos hashes. 
// h.1) ver de agregar los ejemplos desde el menu => Ejemplos de reportes
//      - crear un ypath : /vac_docente/ts_reportes/
//        - 06	ts_reportes	/vac_docente/ts_reportes/	Test de Reportes	Marcado	Configuración
//        - agregar bajo esta configuracion los reportes de test  
// 
// i) OK!!! => ver como corregir el manejo de las paginas para "multiples" listados
//    - se lo corrigio, ver como ha sido el manejo usado...
//    - se agrega una variable que lleve el "nª de pagina actual"
//    - se agrega tmb un callback de "inicio" y "fin"
//    - ver si con esto alcanza para el ajuste de las paginas.
// 
// j) se recuerda que el link para enviar el listado a otra tab es :
//    - en link :
//      link_to(__('Ver Sub-Directorio', array(), 'messages'), 
//              't1y_directory/scan1dir?id='.$ws_cfg_t_yaml1tree->getId(), 
//               array('target' => '_blank'));
// 
//    - en generator.yml :
//       - - - - - -  
//       - - - - - -  
//         object_actions:
//           - - - -  
//           - - - -  
//           verPdf: 
//             action: verPdf
//             label: 'Mostrar PDF'
//             params:
//               target: '_blank'

// - debug con info de "stack" : ..., $l_stack[true], $n_stack[4]);
// // ...
// $h_test = array(
//   // ...
//   );
// mylib1addi::getInstance()->doDebug($h_test, '$h_test(0) : ', true,  4);
// 

class WrapTcpLib extends TCPDF
{
  protected
    // si es preliminar, tiene marca de agua...
    $l_prelim,
    $c_text_watermark, // Charly: texto de la marca de agua
    $i_size_watermark, // Charly: tamaño de la letra de la marca de agua
    $h_header1zone,
    $h_footer1zone,
    // grupo de grillas...
    $h_grp1grid,
    // grillas...
    $h_grid,
    $key1grid, // indice(key) de la grilla que se esta procesando...
    // tras imprimir la "1er cabecera" del grid => l_head1grid es true
    $l_head1grid,
    $h_grid1zone,
    // ...
    $h_data,
    $h_cell,
    // grupos...
    $l_group,
    $h_group,
    $h_group1zone,
    $h_row1zone1all, // sera el row1zone "general"...
    // ...
    $wd_total,
    $h_row1zone,
    // datos de la linea...
    $h_row1data,
    // zona del reporte que se esta imprimiendo...
    $rpt1zone,
    // tiene cabeceras o no...
    $l_has1header,
    // 
    // tras imprimir la "cabecera de 1er hoja" => l_head1pg es true
    $l_head1pg,
    // 
    // zonas pre-inicio y posf-fin, del reporte que se esta imprimiendo...
    $h_pre1zone,
    $h_post1zone,
    // 
    // en true => si se definio una grilla "variable" en la "cabecera"
    $l_varible1grid,
    // 
    // pila para manejo de los ->sc_grid() con hojas "normales" y "libres"...
    $h_pila
    ;
    
  // hash disponible para el usuario...    
  protected $h_user;
  
  // -------------------
  // Custom User Hash...
  // -------------------

  public function gst_huser( $h_user = null, $key = null, $key1data = null )
  { // deprecated, usado en los listados "previos"
    // - VER DE REEMPLAZAR por ->gst_hreport() 
    if( !is_null($h_user) && is_array($h_user) )
    {
      $this->h_user = $h_user;
    }
    
    // ...
    if( !is_null($key) && !is_null($key1data) )
    {
      $this->h_user[$key] = $key1data;
    } 
    
    // ...
    if( !is_null($key) )
    {
      return( $this->h_user[$key] );
    } else
    {
      return( $this->h_user );
    }
  }

  // ------------------------
  // Custom "Var App" Hash...
  // ------------------------

  public function gst_hreport( $key = null, $key1data = null, $h_report = null )
  { // usar esta en lugar de ->gst_huser()... 
    if( !is_null($h_report) && is_array($h_report) )
    {
      $this->h_user = $h_report;
    }

    // ...
    if( !is_null($key) && !is_null($key1data) )
    {
      !is_array($this->h_user) ? $this->h_user = array() : null;
      $this->h_user[$key] = $key1data;
    } 
    
    // ...
    if( !is_null($key) )
    {
      return( $this->h_user[$key] );
    } else
    {
      return( $this->h_user );
    }
  }

  // --------------
  // Custom gets...
  // --------------

  public function get_hgroup() 
  { // trae el ->h_group...
    $h_group = null;
    if( $this->l_group )
    {
      $h_group = $this->h_group;
    }
    return( $h_group );
  }

  public function get_hrow1data() 
  { // trae la linea de datos actual...
    return( $this->h_row1data );
  }

  public function get_rpt1zone() 
  { // trae la zona de impresion actual...
    // a) zonas del reporte :  'no_zone', 'header', 'footer', 'header_grp', 'footer_grp'
    // b) ver de completarlas
    return( $this->rpt1zone );
  }

  // ---------------------------
  // Custom Header and Footer...
  // ---------------------------

  public function Header() 
  { // Page header
    $this->rpt1zone = 'header';
    if (!empty($this->h_header1zone['callback']))
    { // hay un callback de header...
      call_user_func($this->h_header1zone['callback'], $this);
      
      // si hay una "grilla" definida la "muestra"...
      if (!empty($this->h_header1zone['grid']))
      { // hay un callback de header...
        // - y tmb una grilla asociada, se la imprime...
        
        // se ajusta este margen... 
        $h_margin = $this->getMargins();
        $this->SetX($h_margin['left']);
              
        // ...
        $this->l_varible1grid = true;

        $this->sc_grid($this->h_header1zone['grid']);
        
        // traigo la posicion "Y" actual y reajusto los "margenes"...
        $top      = (int) $this->GetY();
        $h_margin = $this->getMargins();
        
        // - setea el nuevo top...
        $this->SetMargins($h_margin['left'], $top, $h_margin['right']);
        $this->l_varible1grid = false;
      }      
    } else
    {
      parent::Header();
    }
    $this->rpt1zone = 'no_zone';
  }

  public function Footer() 
  { // Page footer
    $this->rpt1zone = 'footer';
    if (!empty($this->h_footer1zone['callback']))
    { // hay un callback de header...
      call_user_func($this->h_footer1zone['callback'], $this);
    } else
    {
      parent::Footer();
    }
    $this->rpt1zone = 'no_zone';
  }

  // ----------------------
  // metodos adicionales...
  // ----------------------

  /**
   * Returns the remaining page space from the given Y coordinate.
   *
   * @param float $y
   * @return float Remaining Y space in user unit.
   */
  public function getRemainingYPageSpace($y)
  { // get total height of the page in user units
    $page = $this->getPage();
    $totalHeight = $this->getPageHeight($page) / $this->getScaleFactor();
    $margin = $this->getMargins();
    return $totalHeight - $margin['bottom'] - $y;
  }

  /**
   * Returns the usable page height, which is the page height without top and bottom margin.
   *
   * @return float
   */
  public function getPageContentHeight()
  { // get total height of the page in user units
    $page = $this->getPage();    
    $totalHeight = $this->getPageHeight($page) / $this->getScaleFactor();
    $margin = $this->getMargins();
    return $totalHeight - $margin['bottom'] - $margin['top'];
  }

  /**
   * Returns the usable page width, which is the page width without left and right margin.
   *
   * @return float
   */
  public function getPageContentWidth()
  { // get total width of the page in user units
    $page = 1; // $this->getPage();    
    $totalWidth = $this->getPageWidth($page) / $this->getScaleFactor();
    $margin = $this->getMargins();
    return $totalWidth - $margin['left'] - $margin['right'];
  }
  
  public function ins_line2report($data, $width = null, $align = "L", $fill = 0, $border = 1)
  { // inserta una linea en la posicion actual...
    // a) ver de tener una "key" para traer los datos desde el yaml.
    // b) $data puede ser una string(actual) o un hash.
    // b.1) si es un hash debe procesarlo para tener en cuenta align, fill y border x/c/line.
    // b.2) las keys del hash son los parmetros sin el 'pesos'.
    if(is_null($width))
    { // si es null toma todo el ancho de la linea...
      $width = $this->wd_total;
    } 

    if (is_string($data))    
    { // se entro un texto...
      $data = trim($data);
      $lineHeight = $this->_GetCellHeight($data,$width);
      
      // ...
      $y = $this->GetY();
      $remainingPlace = $this->getRemainingYPageSpace($y);
      if ($lineHeight >= $remainingPlace) 
      { // nueva pagina...
        // - arma header...
        $this->AddPage();
        $this->mk_header1table();
      }
      
      // se imprime la linea, ver como cargar los "parametros" adicionales.
      $this->writeHTMLCell($width, $lineHeight, $data, $border, 0, $fill, true, $align);
      $this->Ln();
    } else
    { // se entro un hash de lineas de texto...
      $totalHeight = 0;
      foreach( $data as $key => $line )
      { // width/align/fill/border de linea...
        $data[$key]['data']   = trim($line['data']);
        $data[$key]['width']  = ( array_key_exists('width', $line) && !is_null($line['width']) ) ? $line['width'] : $width;
        $data[$key]['align']  = ( array_key_exists('align', $line) && !is_null($line['align']) ) ? $line['align'] : $align;
        $data[$key]['fill']   = ( array_key_exists('fill', $line) && !is_null($line['fill']) ) ? $line['fill'] : $fill;
        $data[$key]['border'] = ( array_key_exists('border', $line) && !is_null($line['border']) ) ? $line['border'] : $border;
        
        // calcula lo que ocupa...
        $lineHeight = $this->_GetCellHeight($data[$key]['data'], $data[$key]['width']);
        $data[$key]['ln1height'] = $lineHeight;
        $totalHeight += $lineHeight;
      }

      // ...
      $y = $this->GetY();
      $remainingPlace = $this->getRemainingYPageSpace($y);
      if ($totalHeight >= $remainingPlace) 
      { // nueva pagina...
        // - arma header...
        $this->AddPage();
        $this->mk_header1table();
      }
      
      // ...
      foreach( $data as $key => $line )
      { // width/align/fill/border de linea...
        $this->writeHTMLCell($line['width'], $line['ln1height'], '', '', $line['data'], $line['border'], 0, $line['fill'], true, $line['align']);
        $this->Ln();
      }
    }
  }

  // -------------------------------------
  // Manejo de configuracion desde yaml...
  // -------------------------------------

  protected function set_hparam1xdf($h_yaml) 
  { // arma los prametros default del pdfo...
    $h_param = $h_yaml['config1default'];
    if ( empty($h_param) )
    { 
      $h_param = array(
        "PDF_PAGE_FORMAT" => "A4",
        // # page orientation (P=portrait, L=landscape)
        "PDF_PAGE_ORIENTATION" => "P",
        // # document creator
        "PDF_CREATOR" => "Ministerio de Educación",
        // # document author
        "PDF_AUTHOR" => "Ministerio de Educación",
        // # header title
        "PDF_HEADER_TITLE" => "",
        // # header description string
        "PDF_HEADER_STRING" => "",
        // # image logo
        "PDF_HEADER_LOGO" => "logo_ciudad.png",
        // # header logo image width [mm]
        "PDF_HEADER_LOGO_WIDTH" => 15,
        // # document unit of measure [pt=point, mm=millimeter, cm=centimeter, in=inch]
        "PDF_UNIT" => "mm",
        // # header margin
        "PDF_MARGIN_HEADER" => "10",
        // # footer margin
        "PDF_MARGIN_FOOTER" => "10",
        // # top margin
        "PDF_MARGIN_TOP" => "35",
        // # bottom margin
        "PDF_MARGIN_BOTTOM" => "10",
        // # left margin
        "PDF_MARGIN_LEFT" => "10",
        // # right margin
        "PDF_MARGIN_RIGHT" => "10",
        // # default main font name
        "PDF_FONT_NAME_MAIN" => "times",
        // # default main font size
        "PDF_FONT_SIZE_MAIN" => "10",
        // # default data font name
        "PDF_FONT_NAME_DATA" => "times",
        // # default data font size
        "PDF_FONT_SIZE_DATA" => "8",
        // # default monospaced font name
        "PDF_FONT_MONOSPACED" => "times",
        // # Ratio used to scale the images
        "PDF_IMAGE_SCALE_RATIO" => "4",
        // # adicionales...
        // # --------------
        "PDF_TITLE"    => "TCPDF Tutorial - TCPDF + MySQL",  
        "PDF_KEYWORDS" => "TCPDF, PDF, example, test, mysql",
        "PDF_SUBJECT"  => "TCPDF, PDF, example, test, mysql"
      );
    }  
    return( $h_param );
  }
  
  protected function get_cfg4yaml($yaml1f, $dir1yaml) 
  {
    $file = $dir1yaml . $yaml1f;
    if (!file_exists($file))
    {
      throw(new \RuntimeException('You need to set a valid yaml : "' . $file .'" don\'t exist.'));
    }
    $h_yaml = Yaml::parseFile($file);
    
    // se agrega el archivo yaml...
    $h_yaml['file_yaml'] = $file;

    // parametros default... 
    $h_param = $this->set_hparam1xdf($h_yaml); 
    
    // ... 
    $this->SetCreator($h_param['PDF_CREATOR']);
    $this->SetAuthor($h_param['PDF_AUTHOR']);
    $this->SetTitle($h_param['PDF_TITLE']);
    $this->SetSubject($h_param['PDF_SUBJECT']);
    $this->SetKeywords($h_param['PDF_KEYWORDS']);

    // set default header data
    $this->SetHeaderData($h_param['PDF_HEADER_LOGO'], $h_param['PDF_HEADER_LOGO_WIDTH'], $h_param['PDF_HEADER_TITLE'], $h_param['PDF_HEADER_STRING']);
     
    // set header and footer fonts
    $this->setHeaderFont(Array($h_param['PDF_FONT_NAME_MAIN'], '', $h_param['PDF_FONT_SIZE_MAIN']));
    $this->setFooterFont(Array($h_param['PDF_FONT_NAME_DATA'], '', $h_param['PDF_FONT_SIZE_DATA']));
     
    // set default monospaced font
    $this->SetDefaultMonospacedFont($h_param['PDF_FONT_MONOSPACED']);
    
    // para performance...
    $this->setFontSubsetting(false);
    
    // set image scale factor
    $this->setImageScale($h_param['PDF_IMAGE_SCALE_RATIO']);
    
    // OJO!!! => LOS QUE SIGUEN, TIENEN que estar definidos en el yaml...
    // ------
    // ajusta los margenes...
    $h_margin = $h_yaml['margin'];
    if( empty($h_margin) )
    { // usa desde el default( h_param )...
     $h_margin['left'] = $h_param['PDF_MARGIN_LEFT'];
     $h_margin['top']  = $h_param['PDF_MARGIN_TOP'];
     
     $h_margin['right']  = $h_param['PDF_MARGIN_RIGHT'];
     $h_margin['bottom'] = $h_param['PDF_MARGIN_BOTTOM'];
     
     $h_margin['header'] = $h_param['PDF_MARGIN_HEADER'];
     $h_margin['footer'] = $h_param['PDF_MARGIN_FOOTER']; 
    } 
    
    // set margins
    $this->SetMargins($h_margin['left'], $h_margin['top'], $h_margin['right']);
    $this->SetHeaderMargin($h_margin['header']);
    $this->SetFooterMargin($h_margin['footer']);
     
    // quita los auto page breaks
    // hace un chequeo "propio" del salto de pagina...
    $this->SetAutoPageBreak(false, $h_margin['bottom']);

    // zone-params de header, footer, row, group y grid...
    // - ver que hacer si NO existe alguno
    //   - ver de donde "tomar" el "default"...
    $this->h_header1zone = $h_yaml['header1zone'];
    $this->h_footer1zone = $h_yaml['footer1zone'];
    $this->h_row1zone    = $h_yaml['row1zone'];

    // tmb se carga el "general"...
    $this->h_row1zone1all = $h_yaml['row1zone'];
    
    // ...
    $this->h_group1zone = $h_yaml['group1zone'];
    $this->h_grid1zone  = $h_yaml['grid1zone'];
    
    // pre y post "zone" del reporte...
    $this->h_pre1zone  = array_key_exists('pre1zone',  $h_yaml) && 
      array_key_exists('callback',  $h_yaml['pre1zone']) && !empty($h_yaml['pre1zone']['callback']) ? 
      $h_yaml['pre1zone']  : null;
    $this->h_post1zone = array_key_exists('post1zone', $h_yaml) && 
      array_key_exists('callback',  $h_yaml['post1zone']) && !empty($h_yaml['post1zone']['callback']) ? 
      $h_yaml['post1zone'] : null;
        
    // trae las grillas componentes del listado...
    $this->h_grid = is_array($h_yaml['grid']) ? $h_yaml['grid'] : array();
    
    // trae los datos del grupo de grillas...
    $this->h_grp1grid = array_key_exists('grp1grid', $h_yaml) ? $h_yaml['grp1grid'] : null;
    return($h_yaml);
  }

  // ---------------------
  // Manejo de Cabecera...
  // ---------------------

  protected function gen_water1mark() 
  { // marca de agua para "preliminar"...
    $x_prev = $this->GetX();
    $y_prev = $this->GetY();
     
    // si es preliminar imprime la marca de agua...
    // imprimir la marca de no valido...
    $c_watermark = $this->c_text_watermark;
    $i_size_watemark = $this->i_size_watermark;
     
    // se blanquea el texto...
    $this->SetTextColor( 160 );    
       
    // ...
    $a_yw = array( 1 => 50, 2 => 100, 3 => 150, 4 => 200, 5 => 250 ); 
    $this->SetFont('times', '', $i_size_watemark);
    foreach( $a_yw as $y )
    {
      $this->writeHTMLCell(0, 0, 0, $y, $c_watermark, 0, 0, 0,true, 'C');
    }

    // vuelve a la posicion previa, al font y parametros de la linea...
    $this->SetX($x_prev);
    $this->SetY($y_prev);
    $this->set_row1param();
  }

  protected function hd_head1variable($value)
  { // se hace esto para que la cabecera sea "variable"
    if((substr($value, 0, 1) == '%' ) && (substr($value, -1) == '%' ))
    { // quita los %'s...
      $key = substr($value, 1);
      $key = trim(substr($key, 0, -1));
      
      $l_key = ($key <> '');
      if($l_key)
      { // devuelvo el valor de la key...
        array_key_exists($key, $this->h_user) ? $value = $this->h_user[$key] : null;
      } 
    }
    return($value);    
  }

  protected function mk_header1table($group = null, $l_fit1remain = false) 
  { // arma el header para las "celdas"
    // - usa el ->h_cell...
    
    // ...
    $this->l_head1pg   = true;
    $this->l_head1grid = true;

    // ...
    $this->set_header1param($l_fit1remain);
    
    // este es el evento donde debe manejar la Cabecera de grupo...
    if ( !is_null($group) && !empty($group['callback_bog'])) 
    { // hay un callback de inicio de grupo...
      // zona actual...
      $this->rpt1zone = 'header_grp';
      $this->set_group1param();
      call_user_func($group['callback_bog'], $this, $group);
      $this->set_header1param();
      $this->rpt1zone = 'no_zone';
    }

    // chequea si imprime...
    if ($this->l_has1header)
    {
      foreach( $this->h_cell as $cell )
      {
        $header = $this->hd_head1variable($cell['header']);
        if ( $this->h_header1zone['l1cell'] )
        { 
          $this->Cell($cell['width'], $this->h_header1zone['min1height'], $header, $cell['border_header'], 0, $cell['align_header'], 1);
        } else
        {  
          $this->writeHTMLCell($cell['width'], $this->h_header1zone['min1height'], '', '', $header, $cell['border_header'], 0, 1, true, $cell['align_header']);
        }
      }
      $this->Ln();
    }
    
    // Row color and font restoration, 
    $this->set_row1param();

    if ($this->l_prelim) 
    { // agrega las lineas de "no valido"...
      $this->gen_water1mark();
    }
  }

  // --------------------------------
  // Manejo de Parametros de zonas...
  // --------------------------------

  protected function hd_row1param($row1zone, $l_reset = false)
  { // row params...
    // - si l_reset es true => "toma" los datos desde el "row1zone1all"( que es el definido como "general" )
    
    // Colors, line width and bold font
    // # celeste claro...
    // fillcolor:  [ 224, 235, 255 ]
    // # negro...
    // textcolor:  0 
    // font:       [ 'helvetica', '', 8 ]
    // # lineas en rojo...  
    // # drawcolor:  [ 128, 0, 0 ]
    // # en negro...    
    // drawcolor:  [ 0, 0, 0 ]
    // # si true, usa fillcolor alternativamente c/c/fila...
    // l_fill: true

    // ... 
    if($l_reset)
    {
      $row1zone = $this->h_row1zone1all;
      is_null($row1zone) ? $this->h_row1zone = array() : null;
    } else
    {
      (is_null($this->h_row1zone) && is_null($row1zone)) ? $this->h_row1zone = array() : null;
    }
    
    $l_ck1basic = true;
    if(!is_null($row1zone))
    {
      if( array_key_exists( 'fillcolor', $row1zone ) && !is_null($row1zone['fillcolor']) )
      {
        $this->h_row1zone['fillcolor'] = $row1zone['fillcolor'];
        $l_ck1basic = false;
      }
    }
    if($l_ck1basic)
    {
      if( !array_key_exists( 'fillcolor', $this->h_row1zone ) || is_null($this->h_row1zone['fillcolor']) )
      {
        $this->h_row1zone['fillcolor'] = array( 224, 235, 255 );
      }
    }
    
    $l_ck1basic = true;
    if(!is_null($row1zone))
    {
      if( array_key_exists( 'textcolor', $row1zone ) && !is_null($row1zone['textcolor']) )
      {
        $this->h_row1zone['textcolor'] = $row1zone['textcolor'];
        $l_ck1basic = false;
      }
    }
    if($l_ck1basic)
    {
      if( !array_key_exists( 'textcolor', $this->h_row1zone ) || is_null($this->h_row1zone['textcolor']) )
      {
        $this->h_row1zone['textcolor'] = 0;
      }
    }

    $l_ck1basic = true;
    if(!is_null($row1zone))
    {
      if( array_key_exists( 'font', $row1zone ) && !is_null($row1zone['font']) )
      {
        $this->h_row1zone['font'] = $row1zone['font'];
        $l_ck1basic = false;
      }
    }
    if($l_ck1basic)
    {
      if( !array_key_exists( 'font', $this->h_row1zone ) || is_null($this->h_row1zone['font']) )
      {
        $this->h_row1zone['font'] = array('helvetica', '', 8);
      }
    }
    
    $l_ck1basic = true;
    if(!is_null($row1zone))
    {
      if( array_key_exists( 'drawcolor', $row1zone ) && !is_null($row1zone['drawcolor']) )
      {
        $this->h_row1zone['drawcolor'] = $row1zone['drawcolor'];
        $l_ck1basic = false;
      }
    }
    if($l_ck1basic)
    {
      if( !array_key_exists( 'drawcolor', $this->h_row1zone ) || is_null($this->h_row1zone['drawcolor']) )
      {
        $this->h_row1zone['drawcolor'] = array( 0, 0, 0 );
      }
    }
    
    $l_ck1basic = true;
    if(!is_null($row1zone))
    {
      if( array_key_exists( 'linewidth', $row1zone ) && !is_null($row1zone['linewidth']) )
      {
        $this->h_row1zone['linewidth'] = $row1zone['linewidth'];
        $l_ck1basic = false;
      }
    }
    if($l_ck1basic)
    {
      if( !array_key_exists( 'linewidth', $this->h_row1zone ) || is_null($this->h_row1zone['linewidth']) )
      {
        $this->h_row1zone['linewidth'] = 0.1;
      }
    }

    // border(xdf tiene)/align(xdf left)....
    $cellborder = 1;
    $textAlign  = "L"; 

    // ...
    foreach( $this->h_cell as $key => $cell )
    { // border/align celda...
      if ( !array_key_exists( 'border_data', $cell ) || is_null($cell['border_data']) )
      {
        $this->h_cell[$key]['border_data'] = $cellborder;
      }
      
      if ( !array_key_exists( 'align_data', $cell ) || is_null($cell['align_data']) )
      {
        $this->h_cell[$key]['align_data'] = $textAlign;
      }
    }
    
    // flag de cambio de fondo linea a linea...    
    if ( !array_key_exists('l_fill', $this->h_row1zone) || is_null($this->h_row1zone['l_fill']) )
    { // xdf hay cambio ln a ln...
      $this->h_row1zone['l_fill'] = true;
    }
  }

  protected function set_row1param() 
  { // row params...
    // # celeste claro...
    $a_param = $this->h_row1zone['fillcolor'];
    $this->SetFillColor($a_param[0], $a_param[1], $a_param[2]);
    $this->SetTextColor($this->h_row1zone['textcolor']);
    $a_param = $this->h_row1zone['font'];
    $this->SetFont($a_param[0], $a_param[1], $a_param[2]);

    // ...    
    $a_param = $this->h_row1zone['drawcolor'];
    $this->SetDrawColor($a_param[0], $a_param[1], $a_param[2]);
    $this->SetLineWidth($this->h_row1zone['linewidth']);
  }

  protected function hd_header1param()
  { // header params...
    // Colors, line width and bold font
    
    // # rojo...
    // # fillcolor:  [ 255, 0, 0 ]
    // # WhiteSmoke
    // fillcolor:  [ 245, 245, 245 ]
    // 
    // # blanco...
    // # textcolor:  255  
    // # negro...
    // textcolor:  0 
    // 
    // # lineas en rojo...  
    // # drawcolor:  [ 128, 0, 0 ]
    // # en negro...    
    // drawcolor:  [ 0, 0, 0 ]
    // linewidth:  0.1
    // font:       [ 'helvetica', 'B', 8 ]
    // 
    // callback:   [ 'listadosActions', 'mk_header1liqui' ]
    
    // ... 
    is_null($this->h_header1zone) ? $this->h_header1zone = array() : null;    
    if( !array_key_exists( 'fillcolor', $this->h_header1zone ) || is_null($this->h_header1zone['fillcolor']) )
    {
      $this->h_header1zone['fillcolor'] = array( 245, 245, 245 );
    }
    if( !array_key_exists( 'textcolor', $this->h_header1zone ) || is_null($this->h_header1zone['textcolor']) )
    {
      $this->h_header1zone['textcolor'] = 0;
    }
    if( !array_key_exists( 'font', $this->h_header1zone ) || is_null($this->h_header1zone['font']) )
    {
      $this->h_header1zone['font'] = array('helvetica', 'B', 8);
    }
    if( !array_key_exists( 'drawcolor', $this->h_header1zone ) || is_null($this->h_header1zone['drawcolor']) )
    {
      $this->h_header1zone['drawcolor'] = array( 0, 0, 0 );
    }
    if( !array_key_exists( 'linewidth', $this->h_header1zone ) || is_null($this->h_header1zone['linewidth']) )
    {
      $this->h_header1zone['linewidth'] = 0.1;
    }
    if( !array_key_exists( 'callback', $this->h_header1zone ) )
    {
      $this->h_header1zone['callback'] = null;
    }
    if( !is_null($this->h_header1zone) )
    { // se definio el callback de header...
      if( !array_key_exists( 'grid', $this->h_header1zone ) )
      { // NO hay grilla asociada...
        $this->h_header1zone['grid'] = null;
      }
    } else
    { // NO hay grilla asociada...
      $this->h_header1zone['grid'] = null;  
    }

    // xdf en true => ->Cell(), usa una celda...
    // si es false => ->MultiCell(), usa multiples celldas...
    if( !array_key_exists( 'l1cell', $this->h_header1zone ) )
    {
      $this->h_header1zone['l1cell'] = true;
    }
    
    // se ajusta la altura minima...
    if( !array_key_exists( 'min1height', $this->h_header1zone ) )
    {
      $this->h_header1zone['min1height'] = 7;
    }
    
    // border(xdf tiene)/align(xdf center)....
    $cellborder = 1;
    $textAlign  = "C"; 
    
    // ...
    $this->wd_total     = 0;
    $this->l_has1header = false;    
    
    // ...
    foreach( $this->h_cell as $key => $cell )
    { // border/align celda...
      if ( !array_key_exists( 'border_header', $cell ) || is_null($cell['border_header']) )
      {
        $this->h_cell[$key]['border_header'] = $cellborder;
      }
      
      if ( !array_key_exists( 'align_header', $cell ) || is_null($cell['align_header']) )
      {
        $this->h_cell[$key]['align_header'] = $textAlign;
      }
      $this->wd_total += $cell['width'];
      
      // para imprimir la cabecera, tiene que tener algun header <> null
      if ( !array_key_exists( 'header', $cell ) )
      {
        $this->h_cell[$key]['header'] = null;
      } else
      {
        $this->l_has1header = ( $this->l_has1header || !is_null($cell['header']) );
        if (!is_null($cell['header']))
        { 
          $this->h_cell[$key]['header'] = trim($cell['header']);
        }
      }
    }
  }

  protected function set_header1param($l_fit1remain = false) 
  { // header params...
    $a_param = $this->h_header1zone['fillcolor'];
    $this->SetFillColor($a_param[0], $a_param[1], $a_param[2]);
    $this->SetTextColor($this->h_header1zone['textcolor']);
    $a_param = $this->h_header1zone['font'];
    $this->SetFont($a_param[0], $a_param[1], $a_param[2]);
    
    // ...    
    $a_param = $this->h_header1zone['drawcolor'];
    $this->SetDrawColor($a_param[0], $a_param[1], $a_param[2]);
    $this->SetLineWidth($this->h_header1zone['linewidth']);
    
    if ($l_fit1remain)
    { // chequea si cabe en lo que resta...
      if( $this->l_has1header )
      { // hay header, calcula su height...
        $y = $this->GetY();
        $remainingPlace = $this->getRemainingYPageSpace($y);
        
        // ...
        $maxRowHeight = $this->_GetHeaderHeight();
        if ($maxRowHeight >= $remainingPlace) 
        { // nueva pagina...
          $this->AddPage();
        }
      }
    }
  }

  protected function hd_footer1param() 
  { // footer params...
    // igual que los de header...
    is_null($this->h_footer1zone) ? $this->h_footer1zone = array() : null;        
    if( !array_key_exists( 'fillcolor', $this->h_footer1zone ) || is_null($this->h_footer1zone['fillcolor']) )
    {
      $this->h_footer1zone['fillcolor'] = array( 245, 245, 245 );
    }
    if( !array_key_exists( 'textcolor', $this->h_footer1zone ) || is_null($this->h_footer1zone['textcolor']) )
    {
      $this->h_footer1zone['textcolor'] = 0;
    }
    if( !array_key_exists( 'font', $this->h_footer1zone ) || is_null($this->h_footer1zone['font']) )
    {
      $this->h_footer1zone['font'] = array('helvetica', 'B', 8);
    }
    if( !array_key_exists( 'drawcolor', $this->h_footer1zone ) || is_null($this->h_footer1zone['drawcolor']) )
    {
      $this->h_footer1zone['drawcolor'] = array( 0, 0, 0 );
    }
    if( !array_key_exists( 'linewidth', $this->h_footer1zone ) || is_null($this->h_footer1zone['linewidth']) )
    {
      $this->h_footer1zone['linewidth'] = 0.1;
    }
    if( !array_key_exists( 'callback', $this->h_footer1zone ) )
    {
      $this->h_footer1zone['callback'] = null;
    }
  }

  protected function set_footer1param() 
  { // footer params...
    $a_param = $this->h_footer1zone['fillcolor'];
    $this->SetFillColor($a_param[0], $a_param[1], $a_param[2]);
    $this->SetTextColor($this->h_footer1zone['textcolor']);
    $a_param = $this->h_footer1zone['font'];
    $this->SetFont($a_param[0], $a_param[1], $a_param[2]);
    
    // ...    
    $a_param = $this->h_footer1zone['drawcolor'];
    $this->SetDrawColor($a_param[0], $a_param[1], $a_param[2]);
    $this->SetLineWidth($this->h_footer1zone['linewidth']);
  }

  protected function hd_group1param() 
  { // group params...
    // igual que los de header...
    is_null($this->h_group1zone) ? $this->h_group1zone = array() : null;
    if( !array_key_exists( 'fillcolor', $this->h_group1zone ) || is_null($this->h_group1zone['fillcolor']) )
    {
      $this->h_group1zone['fillcolor'] = array( 245, 245, 245 );
    }
    if( !array_key_exists( 'textcolor', $this->h_group1zone ) || is_null($this->h_group1zone['textcolor']) )
    {
      $this->h_group1zone['textcolor'] = 0;
    }
    if( !array_key_exists( 'font', $this->h_group1zone ) || is_null($this->h_group1zone['font']) )
    {
      $this->h_group1zone['font'] = array('helvetica', 'B', 8);
    }
    if( !array_key_exists( 'drawcolor', $this->h_group1zone ) || is_null($this->h_group1zone['drawcolor']) )
    {
      $this->h_group1zone['drawcolor'] = array( 0, 0, 0 );
    }
    if( !array_key_exists( 'linewidth', $this->h_group1zone ) || is_null($this->h_group1zone['linewidth']) )
    {
      $this->h_group1zone['linewidth'] = 0.1;
    }
  }

  protected function set_group1param() 
  { // group params...
    $a_param = $this->h_group1zone['fillcolor'];
    $this->SetFillColor($a_param[0], $a_param[1], $a_param[2]);
    $this->SetTextColor($this->h_group1zone['textcolor']);
    $a_param = $this->h_group1zone['font'];
    $this->SetFont($a_param[0], $a_param[1], $a_param[2]);
    
    // ...    
    $a_param = $this->h_group1zone['drawcolor'];
    $this->SetDrawColor($a_param[0], $a_param[1], $a_param[2]);
    $this->SetLineWidth($this->h_group1zone['linewidth']);
  }

  protected function hd_grid1param($l_force1newpg = false) 
  { // grid params...
    is_null($this->h_grid1zone) ? $this->h_grid1zone = array() : null;        
    if( !array_key_exists( 'fillcolor', $this->h_grid1zone ) || is_null($this->h_grid1zone['fillcolor']) )
    {
      $this->h_grid1zone['fillcolor'] = array( 245, 245, 245 );
    }
    if( !array_key_exists( 'textcolor', $this->h_grid1zone ) || is_null($this->h_grid1zone['textcolor']) )
    {
      $this->h_grid1zone['textcolor'] = 0;
    }
    if( !array_key_exists( 'font', $this->h_grid1zone ) || is_null($this->h_grid1zone['font']) )
    {
      $this->h_grid1zone['font'] = array('helvetica', 'B', 8);
    }
    if( !array_key_exists( 'drawcolor', $this->h_grid1zone ) || is_null($this->h_grid1zone['drawcolor']) )
    {
      $this->h_grid1zone['drawcolor'] = array( 0, 0, 0 );
    }
    if( !array_key_exists( 'linewidth', $this->h_grid1zone ) || is_null($this->h_grid1zone['linewidth']) )
    {
      $this->h_grid1zone['linewidth'] = 0.1;
    }
    if( !array_key_exists( 'callback', $this->h_grid1zone ) )
    {
      $this->h_grid1zone['callback'] = null;
    }
    
    // se agrega este para forzar una nueva pagina con la grid...
    $this->h_grid1zone['l_force1newpg'] = $l_force1newpg;
  }

  protected function set_grid1param() 
  { // grid params...
    $a_param = $this->h_grid1zone['fillcolor'];
    $this->SetFillColor($a_param[0], $a_param[1], $a_param[2]);
    $this->SetTextColor($this->h_grid1zone['textcolor']);
    $a_param = $this->h_grid1zone['font'];
    $this->SetFont($a_param[0], $a_param[1], $a_param[2]);
    
    // ...    
    $a_param = $this->h_grid1zone['drawcolor'];
    $this->SetDrawColor($a_param[0], $a_param[1], $a_param[2]);
    $this->SetLineWidth($this->h_grid1zone['linewidth']);
  }

  // ---------------------------
  // manejo del fin de pagina...
  // ---------------------------

  public function ck_ln1page($c_data, $n_width, $l_only1check = false) 
  { // calcula la "falta de espacio" para la linea actual
    // - se usara para los callbacks en callback_bog, callback_eog
    // - se supone que :
    //   - se imprimio la 1er pagina
    // - se toma desde este punto...
    // - se usara en forma "publica"...
    // - si $l_only1check es true => NO 

    // a) cheuea si hay suficiente espacio...
    $y = $this->GetY();
    $remainingPlace = $this->getRemainingYPageSpace($y);
    
    // ...
    $cellHeight   = $this->_GetCellHeight(trim($c_data),$n_width);
    $maxRowHeight = (float) $cellHeight;
    $l_nospace    = ($maxRowHeight >= $remainingPlace);
    
    // ...
    if(!$l_only1check)
    { // xdf, agrega hoja si "falta espacio"...
      if ($l_nospace) 
      { // NO hay suficiente espacio, crea una nueva hoja...
        // - arma header...
        $this->AddPage();
        $this->mk_header1table();
      }
      return($maxRowHeight);
    } else
    { // si NO HAY espacio => false
      // si HAY espacio    => maxRowHeight  
      return( !$l_nospace ? $maxRowHeight : false);
    }
  }  

  protected function ck_row1page($row) 
  { // calcula la "falta de espacio" para la linea actual
    // - chequea si es la hoja inicial...
    if (!$this->l_head1pg)
    { // no se imprimio la cabecera de la 1er pagina...
      // nueva pagina...
      // - arma header...
      $this->AddPage();
      $this->mk_header1table();
    } elseif (!$this->l_head1grid)
    { // hay cambio de grilla y NO se imprimio la cabecera..
      // - si se definio un salto de hoja x el cambio de grid...
      //   - nueva pagina...
      if($this->h_grid1zone['l_force1newpg'])
      {
        $this->AddPage();
        $this->mk_header1table();
      } else
      {
        $this->mk_header1table(null, true);
      }      
    }
    
    // a) cheuea si hay suficiente espacio...
    $y = $this->GetY();
    $remainingPlace = $this->getRemainingYPageSpace($y);
    
    // buscar la height de la fila...
    $maxRowHeight = $this->_GetRowHeight($row);
    if ($maxRowHeight >= $remainingPlace) 
    { // NO hay suficiente espacio...
      // nueva pagina...
      // - arma header...
      $this->AddPage();
      $this->mk_header1table();
    }
    return($maxRowHeight);
  } 

  protected function ck_group1page($row) 
  { // hay algun grupo definido...

    if (!$this->l_head1pg)
    { // no se imprimio la cabecera de 1er pagina...
      // nueva pagina...
      // - arma header...
      $this->AddPage();
      $this->mk_header1table();
    }
    
    foreach( $this->h_group as $key => $group)
    { // loop through groups
      // chequeo si hay cambio de grupo...
      $key1data   = $group['key1data'];
      $data1group = trim($row[$key1data]);
      
      if(!array_key_exists('count', $group))
      { // NO existe el count...
        // - inicia un nuevo grupo...
        $this->h_group[$key]['data']  = $data1group;
        $this->h_group[$key]['count'] = 1;

        if (!empty($group['callback_bog'])) 
        { // hay un callback de inicio de grupo...
          // zona actual...
          $this->rpt1zone = 'header_grp';
          $this->set_group1param();
          $l_first = true;
          call_user_func($group['callback_bog'], $this, $group, $l_first);
          $this->set_row1param();
          $this->rpt1zone = 'no_zone';
        }
      } elseif( $data1group == $group['data'] )
      { // continua con el grupo...
        $this->h_group[$key]['count'] += 1;
      } else
      { // cambia de grupo...
        if (!empty($group['callback_eog'])) 
        { // hay un callback de fin de grupo...
          // zona actual...
          $this->rpt1zone = 'footer_grp';
          $this->set_group1param();
          $l_last = false;          
          call_user_func($group['callback_eog'], $this, $group, $l_last);
          $this->set_row1param();
          $this->rpt1zone = 'no_zone';
        }
        
        // nuevos valores...
        $this->h_group[$key]['data']  = $data1group;
        $this->h_group[$key]['count'] = 1;
        
        // ...
        $c_data       = '';
        $l_only1check = true;
        $n_width      = $this->getPageContentWidth();
        $l_nospace    = ($this->ck_ln1page($c_data, $n_width, $l_only1check) === false);
        if(!$l_nospace)
        { // hace esto, si TIENE espacio...
          // - sino lo DEJA para que lo haga el ->ck_row1page()
          // si hay salto de pagina lo hace aca...
          $group['l1eofpage'] ? $this->AddPage() : null;
         
          // imprime la cabecera...
          $this->mk_header1table($group);
        }
      }
    }
  } 

  protected function ck_eopage($row, $key1grid) 
  { // manejo del chequeo de zonas...
    
    // a) grupo...
    if ($this->l_group)
    { // zona de grupo...
      $this->ck_group1page($row);
    }

    // // b) grilla...
    // //    hay callback para la zona de grillas, ver luego que significa esto...
    // if ( !is_null($this->h_grid1zone['callback']) )
    // { // zona de grilla...
    //   $this->rpt1zone = 'grid';
    // 
    //   // ...
    //   $this->set_grid1param();
    //   call_user_func($this->h_grid1zone['callback'], $this, $key1grid);
    //   $this->set_row1param();
    //   $this->rpt1zone = 'no_zone';
    // }

    // c) chequeo por "falta de espacio"...
    $maxRowHeight = $this->ck_row1page($row);
    return($maxRowHeight);
  }    

  /**
   * Calc/return the rows max cell height
   * and cell width
   *
   * @param array $row array of row cell data
   * @return the calculated max row height height
   * @author Bretton Eveleigh
   * @access protected
   */
  protected function _GetRowHeight($row)
  { 
    $maxRowHeight = 0;
    foreach( $this->h_cell as $cell )
    { // loop through cells in row
      // get the cell width
      $cellWidth = $cell['width'];
      $key1data  = $cell['key1data'];

      // a text string, could be HTML...
      $l_calc = !array_key_exists( $key1data, $row );
      if (!$l_calc)
      {
        $cellData = trim($row[$key1data]);
      } else
      {
        if(array_key_exists('callback', $cell))
        {
          $cellData = call_user_func($cell['callback'], $this, $row, true);
        } else
        {
          $cellData = ''; // 'Sin cbk : ' . $key1data;
        }
      }
      $cellHeight = $this->_GetCellHeight($cellData,$cellWidth);
      if($cellHeight > $maxRowHeight) 
        $maxRowHeight = (float) $cellHeight;
    }
    return($maxRowHeight);
  }

  protected function _GetHeaderHeight()
  { 
    $maxRowHeight = 0;
    foreach( $this->h_cell as $cell )
    { // loop through header cells
      // get the cell width
      $cellWidth  = $cell['width'];
      $cellData   = ( is_null($cell['header']) || $this->h_header1zone['l1cell'] ) ? '' : $cell['header'];
      $cellHeight = $this->_GetCellHeight($cellData, $cellWidth, $this->h_header1zone['min1height']);
      if($cellHeight > $maxRowHeight) 
        $maxRowHeight = (float) $cellHeight;
    }
    return($maxRowHeight);
  }

  /**
   * Calc/return the cells height based on cell text length
   * and cell width
   *
   * @param string $cellData the table cell text
   * @param float $cellWidth the cell width
   * @return the calculated cell height
   * @author Bretton Eveleigh
   * @access protected
   * @since 0.4 (2010-01-10)
   */
  protected function _GetCellHeight($cellData, $cellWidth, $min1height = 2)
  {
    $this->startTransaction();
    $cellTopY = 0;
    $this->SetY($cellTopY);

    $this->writeHTMLCell($cellWidth, $min1height, $this->x, $this->y, $cellData, 1, 2, 0, true, 'L');
    $cellBottomY = $this->y;
    $this->rollbackTransaction(true);
    
    // ...
    $cellHeight = $cellBottomY - $cellTopY;
    return($cellHeight);
  }

  // ---------------------------------------
  // manejo de impresion de las "grillas"...
  // ---------------------------------------

  protected function sc_data1row($data, $fill, $key1grid)
  { // scan de c/row...
    // - maneja un hash de datos( $data )...

    // flag de cambio de fondo linea a linea...    
    $l_ch1fill = $this->h_row1zone['l_fill'];
    foreach($data as $row) 
    {
      $this->h_row1data = $row;
      $maxRowHeight = $this->ck_eopage($row, $key1grid);
      foreach( $this->h_cell as $cell )
      {
        $key1data = $cell['key1data'];
        
        // detecto si es un campo calculado...
        $l_calc = !array_key_exists( $key1data, $row );
        if(!$l_calc)
        {
          $cellData = trim($row[$key1data]);
        } else
        {
          if(array_key_exists('callback', $cell))
          {
            $cellData = call_user_func($cell['callback'], $this, $row, true);
          } else
          {
            $cellData = ''; // 'Sin cbk : ' . $key1data;
          }
        }
        $this->writeHTMLCell($cell['width'], $maxRowHeight, '', '', $cellData, $cell['border_data'], 0, $fill, true, $cell['align_data']);
      }
      $this->Ln();
            
      // ...
      $l_ch1fill ? $fill = !$fill : null;         
    }
  }

  protected function sc_callback1row($callback4query, $n_limit, $fill, $key1grid)
  { // scan de c/row...
    // - usa callbacks para armar el query de los datos...

    // flag de cambio de fondo linea a linea...    
    $l_ch1fill = $this->h_row1zone['l_fill'];
    
    // traigo el query desde el callback...
    $q     = call_user_func($callback4query, $this);
    $total = $q->count();
    for ($offset = 0; $offset < $total; $offset += $n_limit )
    { // se recorre usando el limit...
      $data = $q->limit($n_limit)->offset($offset)->execute(array(), Doctrine_Core::HYDRATE_ARRAY);  
   
      // ...
      foreach($data as $row) 
      {
        $this->h_row1data = $row;
        $maxRowHeight = $this->ck_eopage($row, $key1grid);
        foreach( $this->h_cell as $cell )
        {
          $key1data = $cell['key1data'];
          
          // detecto si es un campo calculado...
          $l_calc = !array_key_exists($key1data, $row);
          if(!$l_calc)
          {
            $cellData = trim($row[$key1data]);
          } else
          {
            $cellData = call_user_func($cell['callback'], $this, $row, true);
          }
          $this->writeHTMLCell($cell['width'], $maxRowHeight, '', '', $cellData, $cell['border_data'], 0, $fill, true, $cell['align_data']);
        }
        $this->Ln();
              
        $l_ch1fill ? $fill = !$fill : null;            
      }
    }
  }

  public function ColoredTable($yaml1f = 'listado.yml', $l_prelim = false, $dir1yaml = null, $watermark = null)
  { // inicializa con yml...
    if ( is_null($dir1yaml) )
    {
      $dir1yaml = dirname(__FILE__).'/config/report/';
    }

    if ( is_null($watermark) )
    {
      $watermark = array( 'text' => 'NO VALIDO - NO VALIDO - NO VALIDO - NO VALIDO', 'size' => 20 );
    }
    
    // configura el reporte...
    $this->get_cfg4yaml($yaml1f, $dir1yaml);

    // si es preliminar o no...
    $this->l_prelim = $l_prelim;
    
    // establece el texto de la marca de agua
    $this->c_text_watermark = $watermark['text'];
    
    // establece el tamaño de letra de la marca de agua
    $this->i_size_watermark = $watermark['size'];

    // NO se imprimio nada...
    $this->l_head1pg = false;

    // ejecutar un callback de "INICIO DE REPORTE"...
    if (!is_null($this->h_pre1zone))
    { // hay un callback de header...
      call_user_func($this->h_pre1zone['callback'], $this);
    }
    
    // ...
    if (is_null($this->h_grp1grid))
    { // scan "simple" de las grillas...
      $this->sc_grid();
    } else
    { // hay un "grupo de grillas"
      $callback1hash = $this->h_grp1grid['callback1hash'];

      // para doctrine query...
      $callback1query = $this->h_grp1grid['callback1query'];
      if(empty($callback1hash) && empty($callback1query))
      { // scan "simple" de las grillas...
        $this->sc_grid();
      } else
      { // scan del "grupo de grillas"
        $this->h_grp1grid['grp1data'] = null;
        $this->h_grp1grid['cnt'] = 0;
        if (!is_null($callback1hash))
        { // con un callback que maneja un hash de datos...
          $a_scan = call_user_func($callback1hash, $this);
          $this->sc_grp1array($a_scan); 
        } else
        { // con un callback que maneja un query de los datos...
          $n_limit = $this->h_grp1grid['n_limit'];
          $this->sc_grp1query($callback1query, $n_limit);
        }
      }
    }

    // ejecutar un callback de "FIN DE REPORTE"...
    if (!is_null($this->h_post1zone))
    { // hay un callback de header...
      call_user_func($this->h_post1zone['callback'], $this);
    }
  }

  public function get_key1grid() 
  { // trae la key de la grilla que se esta procesando...
    // - se ajusta en ->adj_anycell()
    // - ver si es necesario tenerlo en cuenta en algun otro metodo...
    return($this->key1grid);
  }
  
  protected function adj_anycell($key1grid, $h_grid1item, $l_hash, $callback1query, $n_limit, $data)
  { // ajuste de las celdas...
    // -----------------------
    // - las celdas estan vinculados a los datos...
    // - xlo tanto a c/grilla...
    
    // ...
    $this->key1grid = $key1grid;
    
    // flag para forzar nueva pagina...
    $l_force1newpg = (array_key_exists('l_force1newpg', $this->h_data) && ( $this->h_data['l_force1newpg']));

    // ajuste de las celdas...
    // -----------------------
    $this->h_cell = is_array($h_grid1item['cell']) ? $h_grid1item['cell'] : array();
    
    // chequea si alguna celda es de lectura "memo"...
    // deja solo esa como "activa"...
    $is_memo = false;
    foreach( $this->h_cell as $cell)
    {
      $is_memo = (array_key_exists('is_memo', $cell) && ($cell['is_memo']));
      if($is_memo)
      { // sale...
        $cell1memo = $cell;
        $cell1memo['align_data'] = 'J';
        break;
      }
    }
    
    if($is_memo)
    { // deja solo la cell del memo...
      $this->h_cell = array($cell1memo);
      
      // tiene break automatico...
      $this->SetAutoPageBreak(true, $h_margin['bottom']);
    }
        
    // ajuste de los grupos...
    // -----------------------
    // - los grupos estan vinculados a los datos...
    // - xlo tanto a c/grilla...
    $this->h_group  = array_key_exists('group', $h_grid1item) ? $h_grid1item['group'] : null;
    $this->l_group  = !empty($this->h_group);
    $this->rpt1zone = 'no_zone';

    // ajuste de los parametros de zonas...
    // ------------------------------------
    $this->hd_header1param();
    $this->hd_footer1param();

    // ...
    if (!array_key_exists('row1zone', $h_grid1item))
    { // usa los parametros "generales"...
      $row1zone = null;
      $l_reset  = true;
    } else
    { // usa los parametros definidos en el "grid"...
      $row1zone = $h_grid1item['row1zone'];
      $l_reset  = false;
    }
    $this->hd_row1param($row1zone, $l_reset);
    
    $this->hd_group1param();
    $this->hd_grid1param($l_force1newpg); 

    // cambio de fondo linea a linea...
    $fill = 0;

    // se busca la fuente de datos...
    if ($l_hash)
    { // con un callback que maneja un hash de datos...
      $this->sc_data1row($data, $fill, $key1grid);
    } else
    { // usa callbacks para armar el query de los datos...
      $this->sc_callback1row($callback1query, $n_limit, $fill, $key1grid);
    }

    // hay algun grupo/s definido/s...
    if ($this->l_group)
    { // cierre de todos los grupos definidos para la grilla...
      // zona actual...
      $this->rpt1zone = 'footer_grp';
      $this->set_group1param();
      foreach( $this->h_group as $key => $group)
      { // loop through groups
        if (!empty($group['callback_eog'])) 
        { // hay un callback de fin de grupo...
          $l_last = true;
          call_user_func($group['callback_eog'], $this, $group, $l_last);
        }
      }
      $this->set_row1param();
      $this->rpt1zone = 'no_zone';
    }
    
    // "limpio" este data...
    $this->key1grid = null;
  }

  protected function sc_grid($h_grid = null)
  { // scan de las grillas...
    // - se tiene 2 manejos seguna el tipo de grilla( normal o libre ) : 
    //   - usando la grilla del listado( $this->h_grid ) 
    //   - usando una grilla "libre"( xej la asociada al "header" )
    //     - cuando $h_grid es <> null
    // - si se entra una grilla "libre" 
    //   - se guarda el ->h_cell para "devolverlo" al final del ->sc_grid()

    // ajuste de pila al "entrar"...
    // -----------------------------
    if(is_null($h_grid))
    { // grilla "normal"...
      $h_grid = $this->h_grid;
    } else
    { // grilla "libre"...
      // - guarda estos "datas"...
      $this->h_pila = array(
        'h_cell'       => $this->h_cell, 
        'l_group'      => $this->l_group,
        'h_group'      => $this->h_group,
        'l_has1header' =>$this->l_has1header,
        );
    }
    
    // xdf de la key "data"...
    $h_data1xdf = array(
      'callback1hash'   => null,
      'n_limit'         => 100,
      'callback1query'  => null,
      'callback1count'  => null,
      );
    
    // ...
    foreach( $h_grid as $key1grid => $h_grid1item )
    { // loop through grid items...
      // nueva grilla...
      
      if(!$this->l_varible1grid)
      { // grilla "normal"...
        // - sin cabecera en nueva grilla...
        $this->l_head1grid = false;
      } else
      { // en grilla "variable" sobre cabecera...
        // - se imprimio la 1er pagina
        // - hay cabecera en esta grilla...
        $this->l_head1pg   = true;
        $this->l_head1grid = true;
      }
     
      // ajuste de las datos...
      // ----------------------
      $this->h_data = array_merge($h_data1xdf, $h_grid1item['data']);
      
      // como hash...
      $callback1hash = $this->h_data['callback1hash'];
      
      // como doctrine query...
      $callback1query = $this->h_data['callback1query'];
      $n_limit        = $this->h_data['n_limit'];
      
      // se busca la fuente de datos...
      $l_hash = !is_null($callback1hash);
      if ($l_hash)
      { // con un callback que maneja un hash de datos...
        $data = call_user_func($callback1hash, $this);
        if(!is_array($data) || (count($data) == 0))
        { // NO tiene datos...
          // -----------------
          continue;
        }
      } else
      { // usa callbacks para armar el query de los datos...
        $q = call_user_func($callback1query, $this);
        if($q->count() == 0)
        { // NO tiene datos...
          // -----------------
          continue;
        }
      }

      // ...
      $this->adj_anycell($key1grid, $h_grid1item, $l_hash, $callback1query, $n_limit, $data);
    }
    
    // ajuste de pila al "salir"...
    // -----------------------------
    if( is_array($this->h_pila) && array_key_exists('h_cell', $this->h_pila))
    { // vuelve estos "datas" al estado "previo"...
      // - vacia ->h_pila
      $this->h_cell       = $this->h_pila['h_cell'];
      $this->l_group      = $this->h_pila['l_group'];
      $this->h_group      = $this->h_pila['h_group'];
      $this->l_has1header = $this->h_pila['l_has1header'];
      $this->h_pila = array();
    }
  }
  
  // --------------------------------------------------
  // Calculo y expansion de texto en un "ancho" dado... 
  // --------------------------------------------------
  
  /**
   * Calc/return the cells difference in length based on cell text length
   * and cell width
   *
   * @param string $cellData the table cell text
   * @param float $cellWidth the cell width
   * @return the calculated cell differnce.
   */
  protected function _GetCellLength($cellData, $cellWidth, $min1height = 1)
  {
    $this->startTransaction();
    $cellLeftX = 0;
    $this->SetX($cellLeftX);

    // /**
	  //  * Allows to preserve some HTML formatting (limited support).<br />
	  //  * IMPORTANT: The HTML must be well formatted - try to clean-up it using an application like HTML-Tidy before submitting.
	  //  * Supported tags are: a, b, blockquote, br, dd, del, div, dl, dt, em, font, h1, h2, h3, h4, h5, h6, hr, i, img, li, ol, p, pre, small, span, strong, sub, sup, table, tcpdf, td, th, thead, tr, tt, u, ul
	  //  * NOTE: all the HTML attributes must be enclosed in double-quote.
	  //  * @param $html (string) text to display
	  //  * @param $ln (boolean) if true add a new line after text (default = true)
	  //  * @param $fill (boolean) Indicates if the background must be painted (true) or transparent (false).
	  //  * @param $reseth (boolean) if true reset the last cell height (default false).
	  //  * @param $cell (boolean) if true add the current left (or right for RTL) padding to each Write (default false).
	  //  * @param $align (string) Allows to center or align the text. Possible values are:<ul><li>L : left align</li><li>C : center</li><li>R : right align</li><li>'' : empty string : left for LTR or right for RTL</li></ul>
	  //  * @public
	  //  */
	  // public function writeHTML($html, $ln=true, $fill=false, $reseth=false, $cell=false, $align='')
	  $this->writeHTML($cellData, false, false, false, false, 'L');
    
    $cellRightX = $this->x;
    $this->rollbackTransaction(true);
    
    // ...
    $cellDiff = $cellWidth - ($cellRightX - $cellLeftX);
    return($cellDiff);
  }
  
  public function calc_str1fitting($c_data, $cellWidth, $c_noend = '...')
  { // calcula si una string($c_data) "encaja" en $cellWidth...

    if($this->_GetCellLength($c_data, $cellWidth) >= 0)
    { // ENTRA toda la string => NO hay que seguir...
      return $c_data;
    }
    
    // separo c_data en "palabras"...
    // - se toma los siguientes separadores : ' ', ',', ';', '(', ')', ':', '.'
    $h_pos = array();
    $mask  = ' ,;():.';
    
    // ...
    $c_rest = $c_data;
    $n_len  = strlen($c_rest);
    while( ($n_pos = strcspn($c_rest, $mask)) <> $n_len)
    { // ejecuta el while
      // - mientras NO llegue al final de la c_data
      $h_pos[] = $n_pos;
      
      // ...
      $c_rest = substr($c_rest, $n_pos + 1);
      $n_len  = strlen($c_rest);
    }
    
    // con $h_pos, $c_rest => puede "reconstruirse" la $c_data "por palabras"...
    // - xej : 
    //   - $c_data = 'Período correspondiente a los primeros pedidos sin resolución cargados al sistema de Gestión Pof, mediante procesos de migración, elevación y aprobación masiva de pedidos';
    //     - $h_pos = array(25) { 
    //         [0]=> int(8) 
    //         [1]=> int(15) 
    //         [2]=> int(1) 
    //         [3]=> int(3) 
    //         [4]=> int(8) 
    //         [5]=> int(7) 
    //         [6]=> int(3) 
    //         [7]=> int(11) 
    //         [8]=> int(8) 
    //         [9]=> int(2) 
    //         [10]=> int(7)
    //         [11]=> int(2) 
    //         [12]=> int(8) 
    //         [13]=> int(3) 
    //         [14]=> int(0) 
    //         [15]=> int(8) 
    //         [16]=> int(8) 
    //         [17]=> int(2) 
    //         [18]=> int(10) 
    //         [19]=> int(0) 
    //         [20]=> int(10) 
    //         [21]=> int(1) 
    //         [22]=> int(11) 
    //         [23]=> int(6) 
    //         [24]=> int(2) }
    //     - $c_rest = 'pedidos'
    //     - $h_input = array(25) { 
    //         [0]=> string(8) "Período" 
    //         [1]=> string(15) "correspondiente" 
    //         [2]=> string(1) "a" 
    //         [3]=> string(3) "los" 
    //         [4]=> string(8) "primeros" 
    //         [5]=> string(7) "pedidos" 
    //         [6]=> string(3) "sin" 
    //         [7]=> string(11) "resolución" 
    //         [8]=> string(8) "cargados" 
    //         [9]=> string(2) "al" 
    //         [10]=> string(7) "sistema" 
    //         [11]=> string(2) "de" 
    //         [12]=> string(8) "Gestión" 
    //         [13]=> string(3) "Pof" 
    //         [14]=> string(0) "" 
    //         [15]=> string(8) "mediante" 
    //         [16]=> string(8) "procesos" 
    //         [17]=> string(2) "de" 
    //         [18]=> string(10) "migración" 
    //         [19]=> string(0) "" 
    //         [20]=> string(10) "elevación" 
    //         [21]=> string(1) "y" 
    //         [22]=> string(11) "aprobación" 
    //         [23]=> string(6) "masiva" 
    //         [24]=> string(2) "de" }    
    // ...
    $h_input = array();
    $c_rest  = $c_data;
    $c_prev  = '';
    $l_fit   = true;
    foreach($h_pos as $key => $pos)
    { 
      $c_input = $c_prev . substr($c_rest, 0, $pos);
      
      // se chequea palabra x palabra, si entra o no...
      if($this->_GetCellLength($c_input . $c_noend, $cellWidth) < 0)
      { // NO entra toma la "previa"...
        $c_input = $c_prev . $c_noend;
        ($this->_GetCellLength($c_input, $cellWidth) < 0) ? $c_input = $c_prev : null;
        break; 
      }
      
      // ...
      $c_sep  = substr($c_rest, $pos, 1);
      $c_prev = $c_input . $c_sep;      
      $c_rest = substr($c_rest, $pos + 1);
    }

    // string que "entra" en la celda pedida...
    return $c_input;
  }

  // -------------------------------
  // manejo de "grupo de grillas"...
  // -------------------------------

  protected function sc_grp1array($a_scan)
  { // scan "multiple" de grids sobre array...
    $n = 0;
    foreach($a_scan as $dt1grp) 
    { 
      $n++;
      $this->h_grp1grid['grp1data'] = $dt1grp;
      $this->h_grp1grid['cnt'] = $n;
      $this->sc_grid();
    }
  }

  protected function sc_grp1query($callback1query, $n_limit)
  { // scan "multiple" de grids sobre query...
    $q = call_user_func($callback4query, $this);
    $total = $q->count();
    $n = 0;    
    for ($offset = 0; $offset < $total; $offset += $n_limit )
    { // se recorre usando el limit...
      $a_scan = $q->limit($n_limit)->offset($offset)->execute(array(), Doctrine_Core::HYDRATE_ARRAY);  
      foreach($a_scan as $dt1grp) 
      {
        $n++;
        $this->h_grp1grid['grp1data'] = $dt1grp;
        $this->h_grp1grid['cnt'] = $n;
        $this->sc_grid();        
      }
    }
  }

  public function get_dt1grpgrid() 
  {
    return($this->h_grp1grid['grp1data']);
  }
  
  public function get_cnt1grpgrid() 
  {
    return($this->h_grp1grid['cnt']);
  }

}
