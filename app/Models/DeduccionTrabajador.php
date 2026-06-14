<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeduccionTrabajador extends Model
{
    protected $table = 'deduccion_trabajador';

    protected $primaryKey = 'Id_Deduccion';

    public $timestamps = false;

    public const ESTADO_PENDIENTE = 'PENDIENTE';
    public const ESTADO_APLICADA = 'APLICADA';
    public const ESTADO_ANULADA = 'ANULADA';

    protected $fillable = [
        'Id_Trabajador',
        'Id_Detalle_Planilla',
        'Fecha_Deduccion',
        'Concepto',
        'Monto',
        'Estado',
        'Observacion',
    ];

    protected $casts = [
        'Id_Deduccion' => 'integer',
        'Id_Trabajador' => 'integer',
        'Id_Detalle_Planilla' => 'integer',
        'Fecha_Deduccion' => 'datetime',
        'Monto' => 'decimal:2',
    ];

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'Id_Trabajador', 'Id_Trabajador');
    }

    public function detallePlanilla(): BelongsTo
    {
        return $this->belongsTo(DetallePlanilla::class, 'Id_Detalle_Planilla', 'Id_Detalle_Planilla');
    }
}