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
Route::get('/reporte/min_02', [ReporteController::class, 'rpt_min02'])->name('reporte.rpt_min02');
