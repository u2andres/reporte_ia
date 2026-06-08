<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mapeo del esquema Doctrine 1.2 "HistoriaPofP" (tabla 661_HISTORIA_POF_P).
 *
 * Representa la "historia"/snapshot de un establecimiento para un año dado;
 * los registros de 680_POF_P referencian una historia (c680_661_id).
 */
class HistoriaPofP extends Model
{
    protected $table = '661_HISTORIA_POF_P';
    protected $primaryKey = 'c661_id';
    public $timestamps = false;

    protected $fillable = [
        'c661_658_id',  // establecimiento_id
        'c661_anio',    // anio
        'c661_664_id',  // modalidad_id
        'c661_657_id',  // distrito_escolar_id
        'c661_656_id',  // cgp_id
    ];

    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoPofP::class, 'c661_658_id', 'c658_id');
    }

    public function pofps(): HasMany
    {
        return $this->hasMany(PofP::class, 'c680_661_id', 'c661_id');
    }

    // ----- Accesores (alias Doctrine) -----
    public function getEstablecimientoId() { return $this->attributes['c661_658_id']; }
    public function getAnio()              { return $this->attributes['c661_anio']; }
}
