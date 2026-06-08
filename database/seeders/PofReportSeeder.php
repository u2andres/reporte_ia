<?php

namespace Database\Seeders;

use App\Models\CargoPofP;
use App\Models\EstablecimientoPofP;
use App\Models\HistoriaPofP;
use App\Models\PofP;
use App\Models\TurnoPofP;
use Illuminate\Database\Seeder;

/**
 * Datos de prueba para el reporte rpt_min02 ("Planta Completa Valorizada").
 *
 * Los datos reproducen los mocks de App\Libraries\Reports\ReportMin02:
 * el establecimiento 1400 (Gestión Privada), año 2020, con 10 cargos de
 * Conducción (tipo 'C') turno 'C' (Completo) cuyas cantidades suman 90
 * (el mismo total que mockea cbk_head1conduccion / cbk_total).
 *
 * Ejecutar:  php artisan db:seed --class=PofReportSeeder
 */
class PofReportSeeder extends Seeder
{
    public function run(): void
    {
        // SEGURIDAD: si los modelos apuntan a la conexión 'doctrine' (MySQL con
        // datos reales), NO sembrar (haría truncate/insert sobre datos reales).
        if ((new CargoPofP())->getConnectionName() === 'doctrine') {
            $this->command?->warn('PofReportSeeder omitido: modelos en conexión "doctrine" (MySQL real). No se toca esa base.');
            return;
        }

        // Limpieza para que el seeder sea repetible
        PofP::truncate();
        CargoPofP::truncate();
        TurnoPofP::truncate();
        HistoriaPofP::truncate();
        EstablecimientoPofP::truncate();

        // -------- Turnos --------
        TurnoPofP::insert([
            ['c686_id' => 'C', 'c686_descripcion' => 'Completo',   'c686_orden' => 1],
            ['c686_id' => 'M', 'c686_descripcion' => 'Mañana',     'c686_orden' => 2],
            ['c686_id' => 'T', 'c686_descripcion' => 'Tarde',      'c686_orden' => 3],
            ['c686_id' => 'V', 'c686_descripcion' => 'Vespertino', 'c686_orden' => 4],
        ]);

        // -------- Cargos de Conducción (tipo 'C') --------
        // [ id, denominacion, cantidad ]  -> cantidades suman 90
        $cargosConduccion = [
            [75,   'SUPERVISOR D.G.E.G.P EDUCACION INCLUSIVA',                                1],
            [607,  'SUPERVISOR  D.G.E.G.P PRIMARIO',                                          23],
            [606,  'SUPERVISOR  D.G.E.G.P. TERCIARIA',                                        10],
            [605,  'SUPERVISOR  D.G.E.G.P. MEDIA',                                            18],
            [53,   'SUPERVISOR D.G.E.G.P REGISTRO INSTITUCIONES EDUCATIVAS ASISTENCIALES',     5],
            [3743, 'SUPERVISOR D.G.E.G.P. DE EDUCACIÓN SUPERIOR SALUD',                        1],
            [2988, 'SUPERVISOR D.G.E.G.P. DE ORGANIZACIÓN ESCOLAR',                           11],
            [2987, 'SUPERVISOR D.G.E.G.P. TÉCNICO PEDAGÓGICA',                                 6],
            [2857, 'SUPERVISOR D.G.E.G.P. DE LA EDUCACIÓN ESPECIAL',                           4],
            [2856, 'SUPERVISOR D.G.E.G.P. EDUCACIÓN INICIAL',                                 11],
        ];

        $cargoRows = [];
        foreach ($cargosConduccion as [$id, $denom, $cant]) {
            $cargoRows[] = [
                'c652_id'           => $id,
                'c652_650_id'       => 'D',   // area_id (Gestión Privada)
                'c652_685_id'       => 'C',   // tipo_cargo_id = Conducción
                'c652_denominacion' => $denom,
                'c652_puntaje'      => null,
                'c652_incrementa'   => true,
                'c652_reduce'       => true,
            ];
        }
        CargoPofP::insert($cargoRows);

        // -------- Establecimiento 1400 (Gestión Privada) --------
        EstablecimientoPofP::insert([[
            'c658_id'         => 1400,
            'c658_escuela'    => 99,
            'area'            => 'D',
            'modalidad'       => '74',
            'c658_nombre'     => 'SUPERVISION GESTION PRIVADA',
            'c658_direccion'  => 'CARLOS H PERETTE 770, 4º PISO',
            'c658_cue'        => 999999,
            'c658_664_id'     => 74,
        ]]);

        // -------- Historia (snapshot estab 1400, año 2020) --------
        HistoriaPofP::insert([[
            'c661_id'     => 1,
            'c661_658_id' => 1400,
            'c661_anio'   => 2020,
        ]]);

        // -------- POF_P: una fila por cargo (estab 1400, año 2020, turno 'C') --------
        $pofpRows = [];
        foreach ($cargosConduccion as [$id, $denom, $cant]) {
            $pofpRows[] = [
                'c680_anio'     => 2020,
                'c680_658_id'   => 1400,   // establecimiento
                'c680_686_id'   => 'C',    // turno Completo
                'c680_652_id'   => $id,    // cargo
                'c680_cantidad' => $cant,
                'c680_661_id'   => 1,      // historia
            ];
        }
        PofP::insert($pofpRows);

        $this->command?->info('PofReportSeeder: ' . count($cargoRows) . ' cargos, '
            . count($pofpRows) . ' filas POF_P (total cantidad = ' . array_sum(array_column($cargosConduccion, 2)) . ').');
    }
}
