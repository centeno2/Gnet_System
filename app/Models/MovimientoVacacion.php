<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoVacacion extends Model
{
    protected $table = 'movimiento_vacacion';

    protected $primaryKey = 'Id_Movimiento_Vacacion';

    public $timestamps = false;

    public const TIPO_ACUMULACION = 'ACUMULACION';
    public const TIPO_TOMADA = 'TOMADA';
    public const TIPO_PAGADA = 'PAGADA';
    public const TIPO_AJUSTE_POSITIVO = 'AJUSTE_POSITIVO';
    public const TIPO_AJUSTE_NEGATIVO = 'AJUSTE_NEGATIVO';

    protected $fillable = [
        'Id_Trabajador',
        'Id_Vacacion',
        'Id_Detalle_Planilla',
        'Fecha_Movimiento',
        'Tipo_Movimiento',
        'Dias',
        'Observacion',
    ];

    protected $casts = [
        'Id_Movimiento_Vacacion' => 'integer',
        'Id_Trabajador' => 'integer',
        'Id_Vacacion' => 'integer',
        'Id_Detalle_Planilla' => 'integer',
        'Fecha_Movimiento' => 'date',
        'Dias' => 'decimal:2',
    ];

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'Id_Trabajador', 'Id_Trabajador');
    }

    public function vacacion(): BelongsTo
    {
        return $this->belongsTo(Vacaciones::class, 'Id_Vacacion', 'Id_Vacacion');
    }

    public function detallePlanilla(): BelongsTo
    {
        return $this->belongsTo(DetallePlanilla::class, 'Id_Detalle_Planilla', 'Id_Detalle_Planilla');
    }
}