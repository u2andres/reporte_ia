<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mapeo del esquema Doctrine 1.2 "DistritoEscolarPofP" (tabla 657_DISTRITO_ESCOLAR_POF_P).
 * PK int. c657_de es el número real de Distrito Escolar (D.E.) a mostrar.
 */
class DistritoEscolarPofP extends Model
{
    // xdf se usa la conexion "sqlite"
    // - que se migro con los datos de esta tabla...
    // protected $connection = 'doctrine';
    protected $table = '657_DISTRITO_ESCOLAR_POF_P';
    protected $primaryKey = 'c657_id';
    public $timestamps = false;

    protected $fillable = [
        'c657_region', // region
        'c657_de',     // de (número de Distrito Escolar)
    ];

    public function establecimientos(): HasMany
    {
        return $this->hasMany(EstablecimientoPofP::class, 'c658_657_id', 'c657_id');
    }

    // ----- Accesores (alias Doctrine) -----
    public function getDe()     { return $this->attributes['c657_de']; }
    public function getRegion() { return $this->attributes['c657_region']; }
}
