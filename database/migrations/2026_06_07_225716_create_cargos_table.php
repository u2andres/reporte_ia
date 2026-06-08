<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Mapeo del esquema Doctrine 1.2 "CargoPofP" -> tabla 652_CARGO_POF_P
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('652_CARGO_POF_P', function (Blueprint $table) {
            $table->integer('c652_id')->autoIncrement();          // id

            $table->char('c652_650_id', 1)->nullable();           // area_id
            $table->char('c652_688_id', 1)->nullable();           // categoria_cargo_id
            $table->string('c652_denominacion', 255)->nullable(); // denominacion
            $table->float('c652_puntaje')->nullable();            // puntaje
            $table->integer('c652_max_cant_hs')->nullable();      // max_cant_hs
            $table->char('c652_685_id', 1)->nullable();           // tipo_cargo_id (C=Conducción, E=Ejecución, H=Horas Cátedra)
            $table->integer('c652_672_id')->nullable();           // nivel_id
            $table->float('c652_partida')->nullable();            // partida
            $table->integer('c652_anio_alta')->nullable();        // anio_alta
            $table->integer('c652_anio_baja')->nullable();        // anio_baja
            $table->boolean('c652_incrementa')->default(true);    // incrementa
            $table->boolean('c652_reduce')->default(true);        // reduce
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('652_CARGO_POF_P');
    }
};
