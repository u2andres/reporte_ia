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
