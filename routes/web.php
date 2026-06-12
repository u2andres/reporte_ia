<?php

use App\Http\Controllers\ReporteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Prueba de generación de PDF con tc-lib-pdf
Route::get('/reporte/test', [ReporteController::class, 'test'])->name('reporte.test');

// Reporte de cursos con WrapTcpLib (TCPDF clásico)
Route::get('/reporte/cursos', [ReporteController::class, 'cursos'])->name('reporte.cursos');

// Reporte de wsad(Planta Completa Valorizada), con WrapTcpLib (TCPDF clásico)
// Parámetros opcionales de ruta: estab (establecimiento) y anio (año).
//   /reporte/min_02/               -> default 1400 / 2020
//   /reporte/min_02/3510           -> estab 3510, año 2020
//   /reporte/min_02/3510/2019      -> estab 3510, año 2019
Route::get('/reporte/min_02/{estab?}/{anio?}', [ReporteController::class, 'rpt_min02'])->name('reporte.rpt_min02');

// Página demo (layout longproc + barra de progreso jQuery UI)
Route::get('/reporte/min_02-demo', [ReporteController::class, 'min02Demo'])->name('reporte.min02.demo');

// Prueba de PDFMerger (combina los PDFs de app/Libraries/test/)
Route::get('/reporte/merge-test', [ReporteController::class, 'mergeTest'])->name('reporte.merge.test');

// Demo del helper longOps (diálogo jQuery UI con barra de progreso) + su backend
Route::get('/reporte/longops-demo', [ReporteController::class, 'longopsDemo'])->name('reporte.longops.demo');
Route::get('/reporte/longops/backend', [ReporteController::class, 'longopsBackend'])->name('reporte.longops.backend');

// Demo del reporte por área (longOps + merge)
Route::get('/reporte/longops-area-demo', [ReporteController::class, 'longopsAreaDemo'])->name('reporte.longops.areaDemo');

// Backend longOps del reporte POR ÁREA (genera rpt_min02 por establecimiento y los mergea)
//   /reporte/longops/backendArea?area=F&anio=2020   (longOps manda los params en 'start')
//   /reporte/longops/backendArea/F/2020             (también por ruta; ?limit=N para topear)
Route::get('/reporte/longops/backendArea/{area?}/{anio?}', [ReporteController::class, 'longopsBackendArea'])->name('reporte.longops.backendArea');
Route::get('/reporte/longops/area-result/{job}', [ReporteController::class, 'longopsAreaResult'])->name('reporte.longops.areaResult');

// si se agrega esta FORMA, SOLO FUNCIONA :
//   /reporte/min_02/ue/3510/anio/2019      -> estab 3510, año 2019
// Route::get('/reporte/min_02/ue/{estab?}/anio/{anio?}', [ReporteController::class, 'rpt_min02'])->name('reporte.rpt_min02');
