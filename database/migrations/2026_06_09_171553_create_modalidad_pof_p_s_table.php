<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Mapeo del esquema Doctrine 1.2 "ModalidadPofP" -> tabla 664_MODALIDAD_POF_P
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('664_MODALIDAD_POF_P', function (Blueprint $table) {
            $table->integer('c664_id')->autoIncrement();         // id
            $table->char('c664_650_id', 1)->nullable();          // area_id
            $table->string('c664_modalidad', 2)->nullable();     // abreviatura
            $table->string('c664_descripcion', 50)->nullable();  // descripcion
            $table->integer('c664_659_id')->nullable();          // grupo_id
            $table->integer('c664_689_id')->nullable();          // tipo_curso_id
            $table->integer('c664_anio_alta')->nullable();       // anio_alta
            $table->integer('c664_anio_baja')->nullable();       // anio_baja
            $table->string('c664_012_id', 2)->nullable();        // modalidad_id (nomenclador; el origen admite NULL)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('664_MODALIDAD_POF_P');
    }
};
