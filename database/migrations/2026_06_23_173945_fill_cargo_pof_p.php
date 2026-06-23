<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

// cmd usados :
// ------------
// - para "crear" la migracion de la tabla "configuration"
//   - php artisan make:migration create_configuration_table
// - para "crear" ESTA migracion :
//   - php artisan make:migration fill_cargo_pof_p
// - para rollback de ultimo cambio :
//   - php artisan migrate:rollback --step=1
// - para "ejecuta" la migracion :
//   - php artisan migrate

// - ERRORES :
//   - SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: 
//     - Las dos correcciones que resolvieron el problema:
//       - ?? true en los booleanos NOT NULL → era la causa del Integrity constraint violation.
//       - truncate() al inicio → dejó la migración idempotente.
// 
//     - NOTA : Un detalle para tener presente en las próximas migraciones de relleno (fill_*): 
//       - si una columna se declara con ->default(...) pero sin ->nullable(), queda NOT NULL, 
//         y al copiar datos crudos del origen doctrine hay que coalescer los posibles NULL. 
//         - Ya lo cubriste acá, pero es el mismo patrón que puede reaparecer.
// 

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    { // - "chunks" sobre la tabla 652_CARGO_POF_P...
      //   - insertar los "chunks" en ...
      // vacia la tabla destino para que la migracion sea idempotente
      // (evita "UNIQUE constraint failed" si una corrida previa inserto filas parcialmente)
      DB::connection('sqlite')->table('652_CARGO_POF_P')->truncate();

      $n_item = 100; // chunks de 100 items...
      DB::connection('doctrine')->table('652_CARGO_POF_P')->orderBy('c652_id')->chunk($n_item, 
        function (Collection $items) {
          foreach ($items as $oItem) 
          { // ...
            DB::connection('sqlite')->table('652_CARGO_POF_P')->insert([
              'c652_id'           => $oItem->c652_id,            // id
              'c652_650_id'       => $oItem->c652_650_id,        // area_id
              'c652_688_id'       => $oItem->c652_688_id,        // categoria_cargo_id
              'c652_denominacion' => $oItem->c652_denominacion,  // denominacion
              'c652_puntaje'      => $oItem->c652_puntaje,       // puntaje
              'c652_max_cant_hs'  => $oItem->c652_max_cant_hs,   // max_cant_hs
              'c652_685_id'       => $oItem->c652_685_id,        // tipo_cargo_id (C=Conducción, E=Ejecución, H=Horas Cátedra)
              'c652_672_id'       => $oItem->c652_672_id,        // nivel_id
              'c652_partida'      => $oItem->c652_partida,       // partida
              'c652_anio_alta'    => $oItem->c652_anio_alta,     // anio_alta
              'c652_anio_baja'    => $oItem->c652_anio_baja,     // anio_baja
              'c652_incrementa'   => $oItem->c652_incrementa ?? true,  // incrementa (default true si origen NULL)
              'c652_reduce'       => $oItem->c652_reduce ?? true,      // reduce (default true si origen NULL)
              ]);
          }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    { // vacia la tabla ws_...
      DB::connection('sqlite')->table('652_CARGO_POF_P')->truncate();
    }
};
