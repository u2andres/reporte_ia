<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mapeo del esquema Doctrine 1.2 "EstablecimientoPofP" (tabla 658_ESTABLECIMIENTO_POF_P).
 *
 * Nota: el reporte rpt_min02 NO consulta esta tabla (el controlador pasa el
 * hash $h_estab armado a mano). Se mapea igual para completitud del modelo
 * relacional y para usos futuros.
 */
class EstablecimientoPofP extends Model
{
    protected $connection = 'doctrine';
    protected $table = '658_ESTABLECIMIENTO_POF_P';
    protected $primaryKey = 'c658_id';
    public $timestamps = false;

    protected $fillable = [
        'c658_657_id',        // distrito_escolar_id
        'c658_escuela',       // escuela
        'area',               // area (sin prefijo en el esquema)
        'modalidad',          // modalidad (sin prefijo)
        'c658_nombre',        // nombre
        'c658_direccion',     // direccion
        'c658_telefono',      // telefono
        'c658_fax',           // fax
        'c658_email',         // email
        'c658_codigo_postal', // codigo_postal
        'c658_681_id',        // programa_id
        'c658_cue',           // cue
        'c658_an_cue',        // an_cue
        'c658_cui',           // cui
        'c658_656_id',        // cgp_id
        'c658_651_id',        // barrio_id
        'c658_anio_alta',
        'c658_anio_baja',
        'c658_password',
        'c658_664_id',        // modalidad_id
        'c658_reparticion',
        'c658_local',
        'c658_011_id',        // establecimiento_id
    ];

    public function pofps(): HasMany
    {
        return $this->hasMany(PofP::class, 'c680_658_id', 'c658_id');
    }

    public function historias(): HasMany
    {
        return $this->hasMany(HistoriaPofP::class, 'c661_658_id', 'c658_id');
    }

    // ----- Accesores (alias Doctrine, los más usados) -----
    public function getEscuela()   { return $this->attributes['c658_escuela']; }
    public function getNombre()    { return $this->attributes['c658_nombre']; }
    public function getDireccion() { return $this->attributes['c658_direccion']; }
    public function getCue()       { return $this->attributes['c658_cue']; }
}
