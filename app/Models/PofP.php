<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PofP extends Model
{

// se convierte desde este esquema de Doctrine 1.2 :
// -------------------------------------------------
// PofP:
//   connection: doctrine
//   tableName: 680_POF_P
//   columns:
//     c680_id:
//       name: c680_id as id
//       type: integer(4)
//       fixed: false
//       unsigned: false
//       primary: true
//       autoincrement: true
//     c680_anio:
//       name: c680_anio as anio
//       type: integer(4)
//       fixed: false
//       unsigned: false
//       primary: false
//       notnull: false
//       autoincrement: false
//     c680_658_id:
//       name: c680_658_id as establecimiento_id
//       type: integer(4)
//       fixed: false
//       unsigned: false
//       primary: false
//       notnull: false
//       autoincrement: false
//     c680_686_id:
//       name: c680_686_id as turno_id
//       type: string(1)
//       fixed: true
//       unsigned: false
//       primary: false
//       notnull: false
//       autoincrement: false
//     c680_652_id:
//       name: c680_652_id as cargo_id
//       type: integer(4)
//       fixed: false
//       unsigned: false
//       primary: false
//       notnull: false
//       autoincrement: false
//     c680_cantidad:
//       name: c680_cantidad as cantidad
//       type: integer(4)
//       fixed: false
//       unsigned: false
//       primary: false
//       notnull: false
//       autoincrement: false
//     c680_661_id:
//       name: c680_661_id as historia_id
//       type: integer(4)
//       fixed: false
//       unsigned: false
//       primary: false
//       notnull: false
//       autoincrement: false
//   relations:
//     EstablecimientoPofP:
//       class: EstablecimientoPofP
//       local: establecimiento_id
//       foreign: id
//       type: one
//       foreignType: many
//       alias: EstablecimientoPofP
//       foreignAlias: PofPs
//     TurnoPofP:
//       class: TurnoPofP
//       local: turno_id
//       foreign: id
//       type: one
//       foreignType: many
//       alias: TurnoPofP
//       foreignAlias: PofPs
//     CargoPofP:
//       class: CargoPofP
//       local: cargo_id
//       foreign: id
//       type: one
//       foreignType: many
//       alias: CargoPofP
//       foreignAlias: PofPs
//     HistoriaPofP:
//       class: HistoriaPofP
//       local: historia_id
//       foreign: id
//       type: one
//       foreignType: many
//       alias: HistoriaPofP
//       foreignAlias: PofPs  
// 

  // 1. Definir la tabla física real
  protected $table = '680_POF_P';

  // 2. Definir la clave primaria personalizada (Laravel por defecto busca 'id')
  protected $primaryKey = 'c680_id';

  // 3. Si tu tabla no tiene columnas 'created_at' y 'updated_at', desactívalas:
  public $timestamps = false;

  // 4. Campos permitidos para asignación masiva (usando nombres físicos)
  protected $fillable = [
      'c680_anio',
      'c680_658_id',
      'c680_686_id',
      'c680_652_id',
      'c680_cantidad',
      'c680_661_id',
  ];

  // ==========================================
  // RELACIONES (Equivalentes a Doctrine 'type: one')
  // belongsTo(ModeloDestino, clave_local_en_este_modelo, clave_primaria_en_destino)
  // ==========================================

  public function establecimiento(): BelongsTo
  {
      return $this->belongsTo(EstablecimientoPofP::class, 'c680_658_id', 'c658_id');
  }

  public function turno(): BelongsTo
  {
      return $this->belongsTo(TurnoPofP::class, 'c680_686_id', 'c686_id');
  }

  public function cargo(): BelongsTo
  {
      return $this->belongsTo(CargoPofP::class, 'c680_652_id', 'c652_id');
  }

  public function historia(): BelongsTo
  {
      return $this->belongsTo(HistoriaPofP::class, 'c680_661_id', 'c661_id');
  }
  
  // Agrega esto dentro del modelo PofP si quieres usar nombres limpios en tu código:
  //       name: c680_anio as anio
  public function getAnio() { return $this->attributes['c680_anio']; }
  public function setAnio($value) { $this->attributes['c680_anio'] = $value; }
  
//       name: c680_658_id as establecimiento_id
  public function getEstablecimientoId() { return $this->attributes['c680_658_id']; }
  public function setEstablecimientoId($value) { $this->attributes['c680_658_id'] = $value; }

//       name: c680_686_id as turno_id
  public function getTurnoId() { return $this->attributes['c680_686_id']; }
  public function setTurnoId($value) { $this->attributes['c680_686_id'] = $value; }

//       name: c680_652_id as cargo_id
  public function getCargoId() { return $this->attributes['c680_652_id']; }
  public function setCargoId($value) { $this->attributes['c680_652_id'] = $value; }

//       name: c680_cantidad as cantidad
  public function getCantidad() { return $this->attributes['c680_cantidad']; }
  public function setCantidad($value) { $this->attributes['c680_cantidad'] = $value; }

//       name: c680_661_id as historia_id
  public function getHistoriaId() { return $this->attributes['c680_661_id']; }
  public function setHistoriaId($value) { $this->attributes['c680_661_id'] = $value; }

  // ==========================================
  // Consultas portadas desde PofPTable (Doctrine) -> Eloquent
  // Usadas por App\Libraries\Reports\ReportMin02
  // ==========================================

  /**
   * Suma de "cantidad" de cargos para un establecimiento/año.
   * Si $tipo_cargo es null, suma todos los tipos (C/E/H).
   * Reemplaza PofPTable::get_sumt1cargo().
   */
  public static function get_sumt1cargo($codigo, $anio, $tipo_cargo = null): int
  {
    return (int) static::query()
      ->where('c680_658_id', $codigo)
      ->where('c680_anio', $anio)
      ->when($tipo_cargo, fn ($q) =>
        $q->whereHas('cargo', fn ($c) => $c->where('c652_685_id', $tipo_cargo)))
      ->sum('c680_cantidad');
  }

  /**
   * Hash de cargos (mix) por establecimiento/año/tipo, con cantidad del año
   * actual y del previo, diferencia y valorización.
   * Reemplaza PofPTable::get_hmix1cargo(). $subtotal devuelve la suma de valorización.
   *
   * @return array<string,array<string,mixed>>
   */
  public static function get_hmix1cargo($codigo, $anio, $tipo_cargo, &$subtotal = 0): array
  {
    $subtotal = 0;
    $h_merge  = [];

    $grupos = static::with(['cargo', 'turno'])
      ->where('c680_658_id', $codigo)
      ->where('c680_anio', $anio)
      ->whereHas('cargo', fn ($c) => $c->where('c652_685_id', $tipo_cargo))
      ->get()
      ->groupBy(fn ($r) => $r->c680_652_id . '_' . $r->c680_686_id);

    foreach ($grupos as $grupo) {
      $first = $grupo->first();
      $cargo = $first->cargo;
      $turno = $first->turno;

      $cnt_actual = (int) $grupo->sum('c680_cantidad');
      $cnt_previa = (int) static::query()
        ->where('c680_658_id', $codigo)
        ->where('c680_anio', $anio - 1)
        ->where('c680_652_id', $first->c680_652_id)
        ->where('c680_686_id', $first->c680_686_id)
        ->sum('c680_cantidad');

      $puntaje = $cargo->c652_puntaje;
      $valoriz = (int) round(((float) $puntaje) * $cnt_actual);
      $subtotal += $valoriz;

      $key = str_pad((string) (int) $puntaje, 4, '0', STR_PAD_LEFT)
        . '_' . $first->c680_652_id . '_' . $first->c680_686_id;

      $h_merge[$key] = [
        'cargo_id'   => (string) $cargo->c652_id,
        'cargo1d'    => $cargo->c652_id . ' - ' . $cargo->c652_denominacion,
        'puntaje'    => $puntaje,
        'turno_id'   => $turno->c686_id,
        'turno1d'    => $turno->c686_descripcion,
        'cnt_previa' => $cnt_previa,
        'cnt_actual' => (string) $cnt_actual,
        'diff'       => $cnt_actual - $cnt_previa,
        'valoriz'    => $valoriz,
      ];
    }

    return $h_merge;
  }

  /**
   * Cargos agregados por área para un año, con totales de valorización
   * (PI mensual / anual). Reemplaza PofPTable::get_h1cargo1area().
   * $h_totales devuelve mensual_pj / anual_pj / anual_pesos.
   *
   * @return array<string,array<string,mixed>>
   */
  public static function get_h1cargo1area($anio, $cod_area, &$h_totales = []): array
  {
    $h_totales = ['mensual_pj' => 0, 'anual_pj' => 0, 'anual_pesos' => 0];
    $h_data    = [];

    $grupos = static::with('cargo')
      ->where('c680_anio', $anio)
      ->whereHas('cargo', fn ($c) => $c->where('c652_650_id', $cod_area))
      ->get()
      ->groupBy('c680_652_id');

    foreach ($grupos as $cargoId => $grupo) {
      $cargo   = $grupo->first()->cargo;
      $total   = (int) $grupo->sum('c680_cantidad');
      $puntaje = (float) ($cargo->c652_puntaje ?? 0);
      $pj_mes  = $puntaje * $total;

      $h_data[$puntaje . '_' . $cargoId] = [
        'cargo_id' => (string) $cargoId,
        'cargo1d'  => $cargo->c652_denominacion,
        'puntaje'  => $puntaje,
        'total'    => $total,
        'pj_mes'   => $pj_mes,
        'pj_anio'  => $pj_mes * 12,
        'pj_pesos' => 0,
      ];

      $h_totales['mensual_pj'] += $pj_mes;
      $h_totales['anual_pj']   += $pj_mes * 12;
    }

    return $h_data;
  }

}
