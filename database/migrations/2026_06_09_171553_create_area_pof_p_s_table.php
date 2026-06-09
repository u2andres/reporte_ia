<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Mapeo del esquema Doctrine 1.2 "AreaPofP" -> tabla 650_AREA_POF_P
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('650_AREA_POF_P', function (Blueprint $table) {
            $table->char('c650_id', 1)->primary();           // id (char 1, ej. 'D')
            $table->string('c650_descripcion', 50)->nullable(); // descripcion
            $table->integer('c650_orden')->nullable();        // orden
            $table->string('c650_002_id', 2);                 // area_id (nomenclador, notnull)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('650_AREA_POF_P');
    }
};
