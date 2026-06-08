<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mapeo del esquema Doctrine 1.2 "TurnoPofP" (tabla 686_TURNO_POF_P).
 * PK string(1) NO autoincremental (ej: 'C' = Completo).
 */
class TurnoPofP extends Model
{
    protected $connection = 'doctrine';
    protected $table = '686_TURNO_POF_P';
    protected $primaryKey = 'c686_id';
    public $incrementing = false;     // PK no autoincremental
    protected $keyType = 'string';    // PK de tipo string
    public $timestamps = false;

    protected $fillable = [
        'c686_id',          // id (se asigna manualmente)
        'c686_descripcion', // descripcion
        'c686_orden',       // orden
    ];

    public function pofps(): HasMany
    {
        return $this->hasMany(PofP::class, 'c680_686_id', 'c686_id');
    }

    // ----- Accesores (alias Doctrine) -----
    public function getDescripcion() { return $this->attributes['c686_descripcion']; }
    public function getOrden()       { return $this->attributes['c686_orden']; }
}
