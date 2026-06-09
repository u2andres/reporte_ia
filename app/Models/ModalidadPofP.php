<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mapeo del esquema Doctrine 1.2 "ModalidadPofP" (tabla 664_MODALIDAD_POF_P).
 * Catálogo de modalidades. PK int. La descripción (c664_descripcion) es el
 * nombre a mostrar (ej. "Supervisión").
 */
class ModalidadPofP extends Model
{
    protected $connection = 'doctrine';
    protected $table = '664_MODALIDAD_POF_P';
    protected $primaryKey = 'c664_id';
    public $timestamps = false;

    protected $fillable = [
        'c664_650_id',      // area_id (char 1)
        'c664_modalidad',   // abreviatura (char 2)
        'c664_descripcion', // descripcion
        'c664_659_id',      // grupo_id
        'c664_689_id',      // tipo_curso_id
        'c664_anio_alta',
        'c664_anio_baja',
        'c664_012_id',      // modalidad_id (nomenclador externo)
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(AreaPofP::class, 'c664_650_id', 'c650_id');
    }

    // Relaciones a modelos aún no creados (Grupo/TipoCurso/Plan/etc.): comentadas.

    // ----- Accesores (alias Doctrine) -----
    public function getDescripcion() { return $this->attributes['c664_descripcion']; }
    public function getAbreviatura() { return $this->attributes['c664_modalidad']; }
    public function getAreaId()      { return $this->attributes['c664_650_id']; }
}
