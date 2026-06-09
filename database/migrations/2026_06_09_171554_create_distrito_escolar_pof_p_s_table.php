<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Mapeo del esquema Doctrine 1.2 "DistritoEscolarPofP" -> tabla 657_DISTRITO_ESCOLAR_POF_P
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('657_DISTRITO_ESCOLAR_POF_P', function (Blueprint $table) {
            $table->integer('c657_id')->autoIncrement();      // id
            $table->string('c657_region', 4)->nullable();     // region
            $table->integer('c657_de')->default(0);           // de (número de Distrito Escolar, notnull)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('657_DISTRITO_ESCOLAR_POF_P');
    }
};
