<?php

namespace App\Libraries\Reports;

use App\Libraries\WrapTcpLib;

/**
 * Proveedor de datos y callbacks para el reporte de cursos de prueba.
 *
 * Los métodos son estáticos porque el YAML los referencia como
 * [ 'App\Libraries\Reports\CursoReportData', 'metodo' ], que es como
 * WrapTcpLib los invoca vía call_user_func().
 *
 * En un caso real, getCursos() consultaría la base con Eloquent en lugar
 * de devolver datos fijos.
 */
class CursoReportData
{
    /**
     * Fuente de datos de la grilla (callback1hash).
     * Debe devolver un array de filas asociativas.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function getCursos(WrapTcpLib $pdf): array
    {
        return [
            ['id' => 1, 'nombre' => 'Laravel Básico',        'inscriptos' => 30, 'aprobados' => 27],
            ['id' => 2, 'nombre' => 'PHP Avanzado',           'inscriptos' => 18, 'aprobados' => 15],
            ['id' => 3, 'nombre' => 'JavaScript Moderno',     'inscriptos' => 25, 'aprobados' => 20],
            ['id' => 4, 'nombre' => 'Bases de Datos con SQL', 'inscriptos' => 22, 'aprobados' => 19],
            ['id' => 5, 'nombre' => 'Git y Control de Versiones', 'inscriptos' => 40, 'aprobados' => 38],
        ];
    }

    /**
     * Celda calculada (callback de columna): porcentaje de aprobación.
     * WrapTcpLib invoca: call_user_func($cell['callback'], $pdf, $row, true)
     *
     * @param array<string,mixed> $row
     */
    public static function calcPorcentaje(WrapTcpLib $pdf, array $row, bool $l_calc = true): string
    {
        $insc = (int) ($row['inscriptos'] ?? 0);
        $aprob = (int) ($row['aprobados'] ?? 0);
        $pct = $insc > 0 ? round(($aprob / $insc) * 100) : 0;

        return $pct . '%';
    }
}
