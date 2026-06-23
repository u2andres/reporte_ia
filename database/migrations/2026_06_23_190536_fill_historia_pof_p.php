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
//   - php artisan make:migration fill_historia_pof_p
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
    { // - "chunks" sobre la tabla ...
      //   - insertar los "chunks" en ...
      $tableName = '661_HISTORIA_POF_P';
      $pkName    = 'c661_id';
      
      // vacia la tabla destino para que la migracion sea idempotente
      // (evita "UNIQUE constraint failed" si una corrida previa inserto filas parcialmente)
      DB::connection('sqlite')->table($tableName)->truncate();

      $n_item = 100; // chunks de 100 items...
      DB::connection('doctrine')->table($tableName)->orderBy($pkName)->chunk($n_item, 
        function (Collection $items) {
          foreach ($items as $oItem) 
          { // ...
            DB::connection('sqlite')->table('661_HISTORIA_POF_P')->insert([
              'c661_id'     => $oItem->c661_id     ,  // id
              'c661_658_id' => $oItem->c661_658_id ,  // establecimiento_id (notnull, default 0)
              'c661_anio'   => $oItem->c661_anio   ,  // anio (notnull, default 0)
              'c661_664_id' => $oItem->c661_664_id ,  // modalidad_id
              'c661_657_id' => $oItem->c661_657_id ,  // distrito_escolar_id
              'c661_656_id' => $oItem->c661_656_id ,  // cgp_id
              ]);
          }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      $tableName = '661_HISTORIA_POF_P';
      DB::connection('sqlite')->table($tableName)->truncate();
    }
};
