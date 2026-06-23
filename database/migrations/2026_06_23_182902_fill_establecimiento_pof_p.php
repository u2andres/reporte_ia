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
//   - php artisan make:migration fill_establecimiento_pof_p
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
    {
      $tableName = '658_ESTABLECIMIENTO_POF_P';
      $pkName    = 'c658_id';
      
      // vacia la tabla destino para que la migracion sea idempotente
      // (evita "UNIQUE constraint failed" si una corrida previa inserto filas parcialmente)
      DB::connection('sqlite')->table($tableName)->truncate();

      $n_item = 100; // chunks de 100 items...
      DB::connection('doctrine')->table($tableName)->orderBy($pkName)->chunk($n_item, 
        function (Collection $items) {
          foreach ($items as $oItem) 
          { // ...
            DB::connection('sqlite')->table('658_ESTABLECIMIENTO_POF_P')->insert([
              'c658_id'            => $oItem->c658_id            , // id
              'c658_657_id'        => $oItem->c658_657_id        , // distrito_escolar_id
              'c658_escuela'       => $oItem->c658_escuela       , // escuela
              // OJO!!! => para quitar error, se tuvo que usar el 1er char en "mayuscula"...
              'Area'               => $oItem->Area               , // area
              'Modalidad'          => $oItem->Modalidad          , // modalidad
              // 
              'c658_nombre'        => $oItem->c658_nombre        , // nombre
              'c658_direccion'     => $oItem->c658_direccion     , // direccion
              'c658_telefono'      => $oItem->c658_telefono      , // telefono
              'c658_fax'           => $oItem->c658_fax           , // fax
              'c658_email'         => $oItem->c658_email         , // email
              'c658_codigo_postal' => $oItem->c658_codigo_postal , // codigo_postal
              'c658_681_id'        => $oItem->c658_681_id        , // programa_id
              'c658_cue'           => $oItem->c658_cue           , // cue
              'c658_an_cue'        => $oItem->c658_an_cue        , // an_cue
              'c658_cui'           => $oItem->c658_cui           , // cui
              'c658_656_id'        => $oItem->c658_656_id        , // cgp_id
              'c658_651_id'        => $oItem->c658_651_id        , // barrio_id
              'c658_anio_alta'     => $oItem->c658_anio_alta     , // anio_alta
              'c658_anio_baja'     => $oItem->c658_anio_baja     , // anio_baja
              'c658_password'      => $oItem->c658_password      , // password
              'c658_664_id'        => $oItem->c658_664_id        , // modalidad_id
              'c658_reparticion'   => $oItem->c658_reparticion   , // reparticion
              'c658_local'         => $oItem->c658_local         , // local
              'c658_011_id'        => $oItem->c658_011_id        , // establecimiento_id
              ]);
          }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      $tableName = '658_ESTABLECIMIENTO_POF_P';
      DB::connection('sqlite')->table($tableName)->truncate();
    }
};
