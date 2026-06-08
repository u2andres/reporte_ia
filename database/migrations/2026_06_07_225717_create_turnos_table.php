<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Mapeo del esquema Doctrine 1.2 "TurnoPofP" -> tabla 686_TURNO_POF_P
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('686_TURNO_POF_P', function (Blueprint $table) {
            // PK string(1) NO autoincremental (ej: 'C', 'M', 'T', 'V')
            $table->char('c686_id', 1)->primary();        // id
            $table->string('c686_descripcion', 50);       // descripcion (notnull)
            $table->integer('c686_orden')->nullable();    // orden
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('686_TURNO_POF_P');
    }
};
