<?php
$_____INICIO_archivo = null;

$a1_INFO_DB = null;
/*
// - INFO :
//   - se agrego la db(sqlite)"database.sqlite", usada en el commit :
//     - (siguiente-de) => 9ee7de05bd433f729d12a926e41b0cc29bb9c461
//   - se debe agregar en :
//     - /database/database.sqlite
//   - OJO!!! => solo funciona SEGURO en este commit...

// 
*/

$a2_INFO_PRESENTACION = null;
/*
// - INFO :
//   - informe basico :
//     - en Gdrive : /Andrewtech/Informe del Curso de IA
         - https://docs.google.com/document/d/1QUwMYfU7Ap8wmu-cq4E5iyQ5ez3E7oYXQD9NxBv8lWw/edit?tab=t.0
//     - en pdf :
         - c:\eid\Xampp_82\htdocs\desarrollo\Lrvl_curso_reporte\_doc\Informe-del-Curso-de-IA.pdf
//     - en ppt :
         - c:\eid\Xampp_82\htdocs\desarrollo\Lrvl_curso_reporte\_doc\Informe-del-Curso-de-IA.pptx
//   - desde el ppt "anterior" => se genero este html con la "estetica" de Gestion Pof
       - c:\eid\Xampp_82\htdocs\desarrollo\Lrvl_curso_reporte\_doc\PRESENTACION_IA_ANDRES.html
// 

// 
*/
$a2a_INFO_BASICA_GITHUB = null;
/*
// - Acà les dejo un el repo donde en main està el proyecto migrado y en la rama init està el proyecto inicial
     - https://github.com/udeci/registration_ia_laravel
// 
// - revisar lo hecho por el profesor aca y ver que tomar de el...
// - se los "instalo" en : 
//   - c:\eid\Xampp_82\htdocs\desarrollo\Lrvl_registration_ia
//     - en la rama "init" => se lo ejecuta desde la url : 
         - http://localhost/desarrollo/Lrvl_registration_ia/index.php
//

// - ver como tener un proyecto en Github, recuperar el usuario que ya se tiene disponible
//   - con amzotelo@gmail.com
//   - ver que puede hacerse y como conviene actuar.
// 

// - ver como hacer las conversiones de forma "personal" y sacar conclusiones...
//   - revisar los cambios hecho en la rama "main" y ver de establecer los metodos "propios" a seguir...
// 

//    
*/


$a3_INFO_EVAL_CURSO_IA = null;
/*
// - Chequeo de la info para la presentacion final del Curso IA
//   - revisar las clases, sus prompts y recomendaciones
//     - ver donde implementar para testear
//   - analizar la posiblilidad de dockerizar lo que se documente 
//   - ver que herramientas se usaron y cuales pueden implementarse en el proyecto usado
// 

// - Recursos de Unicaba :
     - https://campus.udelaciudad.edu.ar/course/view.php?id=5177
// - Actividad :
     - https://campus.udelaciudad.edu.ar/course/view.php?id=5177
//     - Actividad 1 
         - c:\eid\Xampp_82\htdocs\desarrollo\Lrvl_curso_reporte\_doc\Clase0_IA_Primero_v3_Devs.pptx
//     - Actividad integradora
//         - se agregaron prompts de trabajo 
//           - analizarlos y ver que mas conviene adicionarles( usar xej Gemini para esto )
         - c:\eid\Xampp_82\htdocs\desarrollo\Lrvl_curso_reporte\_doc\Actividad Integrada .pdf
//         - analizar el proyecto "vacaciones en la escuela" y aprovechar para documentar todo
//           - ver como "migrarlo" a Laravel 12/13 y tener un "metodo" teniendo en cuenta los prompts previos
// 
         
// - N8N - NodeRed :
     - https://campus.udelaciudad.edu.ar/course/view.php?id=5177
// 

// 
*/

$d1_GEMINI_CHATS = null;
/*
// - CONSULTA : 
//   - nuevo chat de Gemini :
       - https://gemini.google.com/app?utm_source=app_launcher&utm_medium=owned&utm_campaign=base_all
// 
// - CONSULTA : "tengo que convertir un esquema yml de doctrine 1.2 a Eloquent en Laravel 12...como hago"
       - https://gemini.google.com/share/f90b867858c8
//   - se armo el modelo Pofp en base a este chat( Gemini )
//   - revisar las migraciones y modelos generados y completar tomando como referencia este chat en :
//     - el metodo cnv_tbSchema(h_action, h_itemSt) en :
         - .\_callback_yp\harbour\yp_ctx\hd_lrvl.prg:eval_cmd=find, value=tag#conversion-desde-schema-001
// 

// - CONSULTA : "como agrego un archivo de javascript que usa jquery y jquery-ui en un ambiente Laravel 12"
       - https://gemini.google.com/share/62029789fbb5
//   - ver como instalar jquery y jquey-ui tomando como referencia este chat
// 

// - CONSULTA : "donde guardar los archivos que se quieren cachear en Laravel 12 "
     - https://gemini.google.com/share/d691fab7446b
// 
*/

$e0_TEST_REPORTE = null;

$e1_TEST_REPORTE_ORIGINAL = null;
/*
// - EN WSAD => Listado de Planta Completa Valorizada por Área y Año( 2020 )  
//   - filtro => 
//     - Año  => 2025
//     - Area => Adultos
     - http://localhost/desarrollo/vpn_wsad/web/pof_presupuestaria.php/listado_area/mk_longproc/action?id=A
// 
// - ver los parametros en la impresion ORIGINAL :
//   - ir a los listados :
       - http://localhost/desarrollo/vpn_wsad/web/pof_presupuestaria.php/establecimiento/show?menu=listados_ejecutivos
//   - action/metodo donde buscar el src del reporte a analizar( EJECUCION DE LOS MULTI-PDFS ) :
       - ..\vpn_wsad\apps\pof_presupuestaria\modules\listado_area\actions\actions.class.php:eval_cmd=find, value=function executeMk_longproc
//   - action/metodo donde buscar el src del reporte INDIVIDUAL a analizar :
       - ..\vpn_ia_reporte\apps\frontend\modules\longproc\actions\actions.class.php:eval_cmd=find, value=#multig_min02
       
// - ver los parametros en la impresion ACTUAL :
     - ..\vpn_ia_reporte\apps\frontend\modules\listado\actions\actions.class.php:eval_cmd=find, value=private $b01_RPT_LISTADOS_EJECUTIVOS
// 
*/

$e2_TEST_REPORTE_MOCKEADO = null;
/*
// - url(prod) al reporte MOCKEADO :
       - http://localhost/desarrollo/vpn_ia_reporte/web/min_list/min_pof_02/area/A/anio/2025
       - http://localhost/desarrollo/vpn_ia_reporte/web/min_list/min_pof_02/area/D/anio/2025
//   - se hace este de Media para testear :
       - http://localhost/desarrollo/vpn_ia_reporte/web/min_list/min_pof_02/area/M/anio/2025

// - url(dev) al reporte MOCKEADO :
     - http://localhost/desarrollo/vpn_ia_reporte/web/frontend_dev.php/min_list/min_pof_02/area/A/anio/2025
     - http://localhost/desarrollo/vpn_ia_reporte/web/frontend_dev.php/min_list/min_pof_02/area/D/anio/2025
// 
*/

$e3_TEST_REPORTE_LARAVEL = null;
/*
// - url(prod) al reporte LARAVEL :
//   - Parámetros opcionales de ruta: estab (establecimiento) y anio (año).
//     - /reporte/min_02/               -> default 1400 / 2020
//     - /reporte/min_02/3510           -> estab 3510, año 2020
//     - /reporte/min_02/3510/2019      -> estab 3510, año 2019
     - http://127.0.0.1:8000/reporte/min_02/3510/2020
//      
//   - test de demo con meter para varios establecimiewntos :
       - http://127.0.0.1:8000/reporte/min_02-demo

//   - test del merge :
       - http://localhost:8000/reporte/merge-test

//   - test del ajax para procesos largos :
       - http://localhost:8000/reporte/longops-demo
//      
//   - test del ajax para procesos largos con ARMADO DE ESTABLECIMIENTOS POR AREA :
       - http://localhost:8000/reporte/longops-area-demo
//        
*/

$g1_ARMAR_APP_LARAVEL = null;
/*
// - Paso 1:
//   - armado del Proyecto Inicial
//     - se uso el siguiente CA para armar el proyecto de Laravel( 12 )
         - /pseudocode/action/cmd-laravel/mk-proj/key=andres:
//         - genero el proyecto :
//           - c:\eid\Xampp_82\htdocs\desarrollo\andres     
//     
//         - se lo renombro a "Lrvl_curso_reporte", su README.md es :
             - c:\eid\Xampp_82\htdocs\desarrollo\Lrvl_curso_reporte\README.md

// - Pasos xxx( segun commit ) :
       
// 
*/

$_____FIN_archivo = null;
