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
//   - php artisan make:migration fill_turno_pof_p
// - para rollback de ultimo cambio :
//   - php artisan migrate:rollback --step=1
// - para "ejecuta" la migracion :
//   - php artisan migrate
// - para "borrar" y "rearmar" todas las migraciones :
//   - php artisan migrate:fresh --force

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    { // - "chunks" sobre la tabla ...
      //   - insertar los "chunks" en ...
      $tableName = '686_TURNO_POF_P';
      $pkName    = 'c686_id';
      
      // vacia la tabla destino para que la migracion sea idempotente
      // (evita "UNIQUE constraint failed" si una corrida previa inserto filas parcialmente)
      DB::connection('sqlite')->table($tableName)->truncate();

      $n_item = 100; // chunks de 100 items...
      DB::connection('doctrine')->table($tableName)->orderBy($pkName)->chunk($n_item, 
        function (Collection $items) {
          foreach ($items as $oItem) 
          { // ...
            DB::connection('sqlite')->table('686_TURNO_POF_P')->insert([
              // PK string(1) NO autoincremental (ej: 'C', 'M', 'T', 'V')
              'c686_id'         => $oItem->c686_id         , // id
              'c686_descripcion'=> $oItem->c686_descripcion, // descripcion (notnull)
              'c686_orden'      => $oItem->c686_orden      ,  // orden
              ]);
          }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      $tableName = '686_TURNO_POF_P';
      DB::connection('sqlite')->table($tableName)->truncate();
    }
};
