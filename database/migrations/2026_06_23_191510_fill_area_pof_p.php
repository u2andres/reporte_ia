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
//   - php artisan make:migration fill_area_pof_p
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
      $tableName = '650_AREA_POF_P';
      $pkName    = 'c650_id';
      
      // vacia la tabla destino para que la migracion sea idempotente
      // (evita "UNIQUE constraint failed" si una corrida previa inserto filas parcialmente)
      DB::connection('sqlite')->table($tableName)->truncate();

      $n_item = 100; // chunks de 100 items...
      DB::connection('doctrine')->table($tableName)->orderBy($pkName)->chunk($n_item, 
        function (Collection $items) {
          foreach ($items as $oItem) 
          { // ...
            DB::connection('sqlite')->table('650_AREA_POF_P')->insert([
              'c650_id'          => $oItem->c650_id         , // id (char 1, ej. 'D')
              'c650_descripcion' => $oItem->c650_descripcion, // descripcion
              'c650_orden'       => $oItem->c650_orden      , // orden
              'c650_002_id'      => $oItem->c650_002_id     , // area_id (nomenclador, notnull)
              ]);
          }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      $tableName = '650_AREA_POF_P';
      DB::connection('sqlite')->table($tableName)->truncate();
    }
};
