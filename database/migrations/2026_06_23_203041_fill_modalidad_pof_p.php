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
//   - php artisan make:migration fill_modalidad_pof_p
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
      $tableName = '664_MODALIDAD_POF_P';
      $pkName    = 'c664_id';
      
      // vacia la tabla destino para que la migracion sea idempotente
      // (evita "UNIQUE constraint failed" si una corrida previa inserto filas parcialmente)
      DB::connection('sqlite')->table($tableName)->truncate();

      $n_item = 100; // chunks de 100 items...
      DB::connection('doctrine')->table($tableName)->orderBy($pkName)->chunk($n_item, 
        function (Collection $items) {
          foreach ($items as $oItem) 
          { // ...
            DB::connection('sqlite')->table('664_MODALIDAD_POF_P')->insert([
              'c664_id'          => $oItem->c664_id         , // id
              'c664_650_id'      => $oItem->c664_650_id     , // area_id
              'c664_modalidad'   => $oItem->c664_modalidad  , // abreviatura
              'c664_descripcion' => $oItem->c664_descripcion, // descripcion
              'c664_659_id'      => $oItem->c664_659_id     , // grupo_id
              'c664_689_id'      => $oItem->c664_689_id     , // tipo_curso_id
              'c664_anio_alta'   => $oItem->c664_anio_alta  , // anio_alta
              'c664_anio_baja'   => $oItem->c664_anio_baja  , // anio_baja
              'c664_012_id'      => $oItem->c664_012_id     , // modalidad_id (nomenclador, notnull)
              ]);
          }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      $tableName = '664_MODALIDAD_POF_P';
      DB::connection('sqlite')->table($tableName)->truncate();
    }
};
