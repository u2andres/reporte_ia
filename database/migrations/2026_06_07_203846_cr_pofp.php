<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('680_POF_P', function (Blueprint $table) {
            // Clave Primaria Autoincremental
            $table->integer('c680_id')->autoIncrement(); 
            
            // Columnas de datos
            $table->integer('c680_anio')->nullable();
            $table->integer('c680_cantidad')->nullable();

            // Claves Foráneas (Mapeadas de los IDs físicos)
            $table->integer('c680_658_id')->nullable(); // establecimiento_id
            $table->char('c680_686_id', 1)->nullable(); // turno_id (string fixed length 1)
            $table->integer('c680_652_id')->nullable(); // cargo_id
            $table->integer('c680_661_id')->nullable(); // historia_id

            // Si necesitas desactivar los timestamps por defecto de Laravel (created_at/updated_at)
            // ya que Doctrine 1.2 no los tenía explícitos aquí:
            // $table->timestamps(); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('680_POF_P');
    }
};
