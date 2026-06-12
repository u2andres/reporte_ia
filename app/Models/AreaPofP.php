<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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

  /**
   * IDs de establecimientos del área (vía su modalidad) que tienen POF en
   * el año dado. El área del establecimiento se deriva de la modalidad
   * (664.c664_650_id), porque la columna 658.area suele venir vacía.
   *
   * @return array<int>  códigos de establecimiento
   */
  public static function getEstablecimientosArea($area, $anio = null): array
  {
    return DB::connection('doctrine')
      ->table('680_POF_P as p')
      ->join('658_ESTABLECIMIENTO_POF_P as e', 'e.c658_id', '=', 'p.c680_658_id')
      ->join('664_MODALIDAD_POF_P as m', 'm.c664_id', '=', 'e.c658_664_id')
      ->where('m.c664_650_id', $area)
      ->when($anio !== null, fn ($q) => $q->where('p.c680_anio', (int) $anio))
      ->distinct()
      ->orderBy('p.c680_658_id')
      ->pluck('p.c680_658_id')
      ->map(fn ($v) => (int) $v)
      ->all();
  }

}
