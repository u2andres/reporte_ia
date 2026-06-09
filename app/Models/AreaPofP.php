<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mapeo del esquema Doctrine 1.2 "AreaPofP" (tabla 650_AREA_POF_P).
 * Catálogo de áreas. PK char(1) (ej. 'D' = Gestión Privada).
 */
class AreaPofP extends Model
{
    protected $connection = 'doctrine';
    protected $table = '650_AREA_POF_P';
    protected $primaryKey = 'c650_id';
    public $incrementing = false;     // PK char(1), no autoincremental
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'c650_id',          // id (char 1)
        'c650_descripcion', // descripcion
        'c650_orden',       // orden
        'c650_002_id',      // area_id (nomenclador externo)
    ];

    public function modalidades(): HasMany
    {
        return $this->hasMany(ModalidadPofP::class, 'c664_650_id', 'c650_id');
    }

    public function establecimientos(): HasMany
    {
        return $this->hasMany(EstablecimientoPofP::class, 'area', 'c650_id');
    }

    // ----- Accesores (alias Doctrine) -----
    public function getDescripcion() { return $this->attributes['c650_descripcion']; }
    public function getOrden()       { return $this->attributes['c650_orden']; }
}
