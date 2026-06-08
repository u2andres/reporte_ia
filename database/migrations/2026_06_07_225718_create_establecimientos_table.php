<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Mapeo del esquema Doctrine 1.2 "EstablecimientoPofP" -> tabla 658_ESTABLECIMIENTO_POF_P
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('658_ESTABLECIMIENTO_POF_P', function (Blueprint $table) {
            $table->integer('c658_id')->autoIncrement();          // id

            $table->integer('c658_657_id')->nullable();           // distrito_escolar_id
            $table->integer('c658_escuela')->nullable();          // escuela
            // OJO: estas dos columnas NO llevan prefijo c658_ en el esquema original
            $table->string('area', 1)->nullable();                // area
            $table->string('modalidad', 2)->nullable();           // modalidad
            $table->string('c658_nombre', 255)->nullable();       // nombre
            $table->string('c658_direccion', 255)->nullable();    // direccion
            $table->string('c658_telefono', 255)->nullable();     // telefono
            $table->string('c658_fax', 255)->nullable();          // fax
            $table->string('c658_email', 255)->nullable();        // email
            $table->string('c658_codigo_postal', 8)->nullable();  // codigo_postal
            $table->string('c658_681_id', 4)->nullable();         // programa_id
            $table->integer('c658_cue')->nullable();              // cue
            $table->integer('c658_an_cue')->nullable();           // an_cue
            $table->string('c658_cui', 6)->nullable();            // cui
            $table->integer('c658_656_id')->nullable();           // cgp_id
            $table->integer('c658_651_id')->nullable();           // barrio_id
            $table->integer('c658_anio_alta')->nullable();        // anio_alta
            $table->integer('c658_anio_baja')->nullable();        // anio_baja
            $table->integer('c658_password')->nullable();         // password
            $table->integer('c658_664_id')->nullable();           // modalidad_id
            $table->integer('c658_reparticion')->nullable();      // reparticion
            $table->integer('c658_local')->nullable();            // local
            $table->integer('c658_011_id')->nullable();           // establecimiento_id
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('658_ESTABLECIMIENTO_POF_P');
    }
};
