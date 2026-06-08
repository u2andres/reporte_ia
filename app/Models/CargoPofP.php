<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mapeo del esquema Doctrine 1.2 "CargoPofP" (tabla 652_CARGO_POF_P).
 *
 * El "tipo de cargo" (C=Conducción, E=Ejecución, H=Horas Cátedra) que usa
 * el reporte rpt_min02 es la columna c652_685_id (alias tipo_cargo_id).
 */
class CargoPofP extends Model
{
    protected $connection = 'doctrine';
    protected $table = '652_CARGO_POF_P';
    protected $primaryKey = 'c652_id';
    public $timestamps = false;

    protected $fillable = [
        'c652_650_id',       // area_id
        'c652_688_id',       // categoria_cargo_id
        'c652_denominacion', // denominacion
        'c652_puntaje',      // puntaje
        'c652_max_cant_hs',  // max_cant_hs
        'c652_685_id',       // tipo_cargo_id
        'c652_672_id',       // nivel_id
        'c652_partida',      // partida
        'c652_anio_alta',
        'c652_anio_baja',
        'c652_incrementa',
        'c652_reduce',
    ];

    protected $casts = [
        'c652_puntaje'    => 'float',
        'c652_partida'    => 'float',
        'c652_incrementa' => 'boolean',
        'c652_reduce'     => 'boolean',
    ];

    // ----- Relaciones -----
    // Cargos -> muchos registros en POF_P (680)
    public function pofps(): HasMany
    {
        return $this->hasMany(PofP::class, 'c680_652_id', 'c652_id');
    }

    // Relaciones a modelos aún no creados (Area/TipoCargo/Categoria/Nivel/Modalidad):
    // public function area(): BelongsTo { return $this->belongsTo(AreaPofP::class, 'c652_650_id', 'id'); }
    // public function tipoCargo(): BelongsTo { return $this->belongsTo(TipoCargoPofP::class, 'c652_685_id', 'id'); }
    // public function categoriaCargo(): BelongsTo { return $this->belongsTo(CategoriaCargoPofP::class, 'c652_688_id', 'id'); }
    // public function nivel(): BelongsTo { return $this->belongsTo(NivelPofP::class, 'c652_672_id', 'id'); }

    // ----- Accesores (alias Doctrine) -----
    public function getAreaId()           { return $this->attributes['c652_650_id']; }
    public function getCategoriaCargoId() { return $this->attributes['c652_688_id']; }
    public function getDenominacion()     { return $this->attributes['c652_denominacion']; }
    public function getPuntaje()          { return $this->attributes['c652_puntaje']; }
    public function getMaxCantHs()        { return $this->attributes['c652_max_cant_hs']; }
    public function getTipoCargoId()      { return $this->attributes['c652_685_id']; }
    public function getNivelId()          { return $this->attributes['c652_672_id']; }
    public function getPartida()          { return $this->attributes['c652_partida']; }
}
