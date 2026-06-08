<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Mapeo del esquema Doctrine 1.2 "HistoriaPofP" -> tabla 661_HISTORIA_POF_P
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('661_HISTORIA_POF_P', function (Blueprint $table) {
            $table->integer('c661_id')->autoIncrement();      // id

            $table->integer('c661_658_id')->default(0);       // establecimiento_id (notnull, default 0)
            $table->integer('c661_anio')->default(0);         // anio (notnull, default 0)
            $table->integer('c661_664_id')->nullable();       // modalidad_id
            $table->integer('c661_657_id')->nullable();       // distrito_escolar_id
            $table->integer('c661_656_id')->nullable();       // cgp_id
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('661_HISTORIA_POF_P');
    }
};
