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
//   - php artisan make:migration fill_pof_p
// - para rollback de ultimo cambio :
//   - php artisan migrate:rollback --step=1
// - para "ejecuta" la migracion :
//   - php artisan migrate

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    { // - "chunks" sobre la tabla 680_POF_P...
      //   - insertar los "chunks" en ...
      // vacia la tabla destino para que la migracion sea idempotente
      // (evita "UNIQUE constraint failed" si una corrida previa inserto filas parcialmente)
      DB::connection('sqlite')->table('680_POF_P')->truncate();
      
      $n_item = 100; // chunks de 100 items...
      DB::connection('doctrine')->table('680_POF_P')->orderBy('c680_id')->chunk($n_item, 
        function (Collection $items) {
          foreach ($items as $oItem) 
          { // ...
            DB::connection('sqlite')->table('680_POF_P')->insert([
              'c680_id'       => $oItem->c680_id,
              'c680_anio'     => $oItem->c680_anio,
              'c680_cantidad' => $oItem->c680_cantidad,
              'c680_658_id'   => $oItem->c680_658_id,
              'c680_686_id'   => $oItem->c680_686_id,
              'c680_652_id'   => $oItem->c680_652_id,
              'c680_661_id'   => $oItem->c680_661_id,
              ]);
          }
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    { // vacia la tabla ws_...
      DB::connection('sqlite')->table('680_POF_P')->truncate();
    }
};
